/// <summary>
/// Collects CPU performance and temperature metrics from the local machine.
/// Uses PerformanceCounter for utilization and LibreHardwareMonitorLib for temperature readings.
/// Falls back gracefully if temperature sensors are unavailable.
/// </summary>
using System.Diagnostics;
using DeskGuardAgent.Configuration;
using DeskGuardAgent.Interfaces;
using DeskGuardAgent.Models;
using LibreHardwareMonitor.Hardware;

namespace DeskGuardAgent.Collectors
{
    /// <summary>
    /// Collector responsible for retrieving CPU utilization percentage,
    /// temperature, processor name, and clock speed information.
    /// Implements ICollector&lt;CpuInfo&gt; for standardized metric collection.
    /// </summary>
    public class CpuCollector : ICollector<CpuInfo>
    {
        private readonly ILoggerService _logger;
        private readonly MonitoringSettings _settings;

        /// <summary>
        /// Performance counter instance for measuring CPU utilization.
        /// Uses the "Processor Information" category for accurate multi-core readings.
        /// </summary>
        private PerformanceCounter? _cpuCounter;

        /// <summary>
        /// Initializes a new instance of the CpuCollector class.
        /// </summary>
        /// <param name="logger">Service for logging collector operations and errors.</param>
        /// <param name="settings">Monitoring settings controlling which metrics are collected.</param>
        public CpuCollector(ILoggerService logger, MonitoringSettings settings)
        {
            _logger = logger;
            _settings = settings;
        }

        /// <summary>
        /// Executes CPU metric collection asynchronously.
        /// Gathers utilization, temperature, processor details, and clock speed.
        /// Never throws - all exceptions are caught and logged.
        /// </summary>
        /// <returns>A CpuInfo object containing the collected metrics, or default values on failure.</returns>
        public async Task<CpuInfo> CollectAsync()
        {
            // Log the start of CPU collection for debugging purposes.
            _logger.LogDebug("Starting CPU metrics collection.");

            var cpuInfo = new CpuInfo();

            try
            {
                // Perform all collection tasks in parallel for efficiency.
                Task<double> usageTask = GetCpuUsageAsync();
                Task<double?> tempTask = _settings.EnableCpuTemperatureMonitoring
                    ? GetCpuTemperatureAsync()
                    : Task.FromResult<double?>(null);
                Task<string> nameTask = GetProcessorNameAsync();
                Task<(int logical, double? currentMHz, double? maxMHz)> detailsTask = GetProcessorDetailsAsync();

                // Wait for all collection tasks to complete.
                await Task.WhenAll(usageTask, tempTask, nameTask, detailsTask);

                // Assign collected values to the result object.
                cpuInfo.UsagePercentage = usageTask.Result;
                cpuInfo.TemperatureCelsius = tempTask.Result;
                cpuInfo.ProcessorName = nameTask.Result;
                cpuInfo.NumberOfLogicalProcessors = detailsTask.Result.logical;
                cpuInfo.CurrentClockSpeedMHz = detailsTask.Result.currentMHz;
                cpuInfo.MaxClockSpeedMHz = detailsTask.Result.maxMHz;
                cpuInfo.CollectedAt = DateTime.UtcNow;

                // Log successful collection with key metrics.
                _logger.LogDebug($"CPU collection complete. Usage: {cpuInfo.UsagePercentage:F1}%, Temp: {cpuInfo.TemperatureCelsius?.ToString("F1") ?? "N/A"}°C");
            }
            catch (Exception ex)
            {
                // Log the error but never crash - return whatever data was collected.
                _logger.LogError("Failed to collect CPU metrics.", ex);
            }

            return cpuInfo;
        }

        /// <summary>
        /// Retrieves the current CPU utilization percentage using PerformanceCounter.
        /// Measures the percentage of non-idle processor time across all cores.
        /// </summary>
        /// <returns>CPU usage percentage (0-100), or 0 if measurement fails.</returns>
        private async Task<double> GetCpuUsageAsync()
        {
            return await Task.Run(() =>
            {
                try
                {
                    // Initialize the performance counter if not already created.
                    if (_cpuCounter == null)
                    {
                        _cpuCounter = new PerformanceCounter(
                            "Processor Information", "% Processor Utility", "_Total");
                    }

                    // First call returns 0, second call gives real data.
                    _cpuCounter.NextValue();

                    // Wait briefly to get a meaningful sample.
                    System.Threading.Thread.Sleep(1000);

                    // Return the CPU utilization percentage.
                    return (double)_cpuCounter.NextValue();
                }
                catch (Exception ex)
                {
                    // Log warning and return 0 if counter is unavailable.
                    _logger.LogWarning("Failed to read CPU performance counter.", ex);
                    return 0.0;
                }
            });
        }

        /// <summary>
        /// Retrieves CPU temperature using LibreHardwareMonitorLib.
        /// Opens the hardware monitoring library and queries CPU thermal sensors.
        /// </summary>
        /// <returns>CPU temperature in Celsius, or null if unavailable.</returns>
        private async Task<double?> GetCpuTemperatureAsync()
        {
            return await Task.Run<double?>(() =>
            {
                Computer? computer = null;
                try
                {
                    // Create a new computer instance for hardware monitoring.
                    computer = new Computer
                    {
                        // Enable CPU sensor detection only.
                        IsCpuEnabled = true
                    };

                    // Open the computer for hardware access.
                    computer.Open();

                    // Iterate through all hardware components.
                    foreach (IHardware hardware in computer.Hardware)
                    {
                        // Only process CPU hardware.
                        if (hardware.HardwareType == HardwareType.Cpu)
                        {
                            // Update sensor readings.
                            hardware.Update();

                            // Search for temperature sensors.
                            foreach (ISensor sensor in hardware.Sensors)
                            {
                                // Return the first temperature sensor value found.
                                if (sensor.SensorType == SensorType.Temperature && sensor.Value.HasValue)
                                {
                                    // Convert float? to double? as the return type.
                                    return (double?)sensor.Value.Value;
                                }
                            }
                        }
                    }
                }
                catch (Exception ex)
                {
                    // Log warning if temperature monitoring fails.
                    _logger.LogWarning("CPU temperature monitoring unavailable.", ex);
                }
                finally
                {
                    // Ensure computer resources are released.
                    computer?.Close();
                }

                return null;
            });
        }

        /// <summary>
        /// Retrieves the CPU processor name from WMI.
        /// </summary>
        /// <returns>The processor name string, or "Unknown" if not found.</returns>
        private async Task<string> GetProcessorNameAsync()
        {
            return await Task.Run(() =>
            {
                try
                {
                    using (var searcher = new System.Management.ManagementObjectSearcher(
                        "SELECT Name FROM Win32_Processor"))
                    {
                        using (var results = searcher.Get())
                        {
                            foreach (System.Management.ManagementObject obj in results)
                            {
                                return obj["Name"]?.ToString() ?? "Unknown";
                            }
                        }
                    }
                }
                catch (Exception ex)
                {
                    _logger.LogWarning("Failed to retrieve processor name.", ex);
                }

                return "Unknown";
            });
        }

        /// <summary>
        /// Retrieves processor core count and clock speed details from WMI.
        /// </summary>
        /// <returns>
        /// A tuple containing:
        /// - Number of logical processors
        /// - Current clock speed in MHz (nullable)
        /// - Maximum clock speed in MHz (nullable)
        /// </returns>
        private async Task<(int logical, double? currentMHz, double? maxMHz)> GetProcessorDetailsAsync()
        {
            return await Task.Run(() =>
            {
                try
                {
                    using (var searcher = new System.Management.ManagementObjectSearcher(
                        "SELECT NumberOfLogicalProcessors, CurrentClockSpeed, MaxClockSpeed FROM Win32_Processor"))
                    {
                        using (var results = searcher.Get())
                        {
                            foreach (System.Management.ManagementObject obj in results)
                            {
                                int logical = Convert.ToInt32(obj["NumberOfLogicalProcessors"] ?? 0);
                                double? currentMHz = obj["CurrentClockSpeed"] != null
                                    ? Convert.ToDouble(obj["CurrentClockSpeed"])
                                    : null;
                                double? maxMHz = obj["MaxClockSpeed"] != null
                                    ? Convert.ToDouble(obj["MaxClockSpeed"])
                                    : null;

                                return (logical, currentMHz, maxMHz);
                            }
                        }
                    }
                }
                catch (Exception ex)
                {
                    _logger.LogWarning("Failed to retrieve processor details.", ex);
                }

                return (0, null, null);
            });
        }
    }
}
