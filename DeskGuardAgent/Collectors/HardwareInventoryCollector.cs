/// <summary>
/// Collects comprehensive hardware inventory information from the local machine.
/// Retrieves system manufacturer, model, serial number, BIOS details, processor specs,
/// total memory, and operating system information for asset management purposes.
/// </summary>
using System.Management;
using DeskGuardAgent.Interfaces;
using DeskGuardAgent.Models;

namespace DeskGuardAgent.Collectors
{
    /// <summary>
    /// Collector responsible for retrieving detailed hardware inventory data.
    /// This data changes infrequently and is typically collected every 24 hours.
    /// Implements ICollector&lt;HardwareInventory&gt; for standardized collection.
    /// </summary>
    public class HardwareInventoryCollector : ICollector<HardwareInventory>
    {
        private readonly ILoggerService _logger;

        /// <summary>
        /// Initializes a new instance of the HardwareInventoryCollector class.
        /// </summary>
        /// <param name="logger">Service for logging collector operations and errors.</param>
        public HardwareInventoryCollector(ILoggerService logger)
        {
            _logger = logger;
        }

        /// <summary>
        /// Executes hardware inventory collection by querying multiple WMI classes.
        /// Collects system enclosure, BIOS, processor, memory, and OS information.
        /// Never throws - all exceptions are caught and logged.
        /// </summary>
        /// <returns>A HardwareInventory object with complete inventory data.</returns>
        public async Task<HardwareInventory> CollectAsync()
        {
            _logger.LogDebug("Starting hardware inventory collection.");

            var inventory = new HardwareInventory();

            try
            {
                // Run all WMI queries in parallel for efficiency.
                await Task.Run(() =>
                {
                    // Collect system information from Win32_ComputerSystem.
                    using (ManagementObjectSearcher searcher = new ManagementObjectSearcher(
                        "SELECT Manufacturer, Model, SystemFamily, TotalPhysicalMemory FROM Win32_ComputerSystem"))
                    {
                        using (ManagementObjectCollection results = searcher.Get())
                        {
                            foreach (ManagementObject obj in results)
                            {
                                inventory.Manufacturer = obj["Manufacturer"]?.ToString()?.Trim() ?? "Unknown";
                                inventory.Model = obj["Model"]?.ToString()?.Trim() ?? "Unknown";
                                inventory.TotalMemoryBytes = Convert.ToInt64(obj["TotalPhysicalMemory"] ?? 0);
                                break;
                            }
                        }
                    }

                    // Collect BIOS information from Win32_BIOS.
                    using (ManagementObjectSearcher searcher = new ManagementObjectSearcher(
                        "SELECT SerialNumber, SMBIOSBIOSVersion, Manufacturer, ReleaseDate FROM Win32_BIOS"))
                    {
                        using (ManagementObjectCollection results = searcher.Get())
                        {
                            foreach (ManagementObject obj in results)
                            {
                                inventory.SerialNumber = obj["SerialNumber"]?.ToString()?.Trim() ?? "Unknown";
                                inventory.BiosVersion = obj["SMBIOSBIOSVersion"]?.ToString()?.Trim() ?? "Unknown";
                                inventory.BiosVendor = obj["Manufacturer"]?.ToString()?.Trim() ?? "Unknown";

                                // Parse BIOS release date from WMI date format.
                                if (obj["ReleaseDate"] != null)
                                {
                                    string dateStr = obj["ReleaseDate"].ToString()!;
                                    try
                                    {
                                        inventory.BiosReleaseDate = ManagementDateTimeConverter.ToDateTime(dateStr);
                                    }
                                    catch
                                    {
                                        inventory.BiosReleaseDate = null;
                                    }
                                }

                                break;
                            }
                        }
                    }

                    // Collect processor information from Win32_Processor.
                    using (ManagementObjectSearcher searcher = new ManagementObjectSearcher(
                        "SELECT Name, NumberOfCores, NumberOfLogicalProcessors FROM Win32_Processor"))
                    {
                        using (ManagementObjectCollection results = searcher.Get())
                        {
                            foreach (ManagementObject obj in results)
                            {
                                inventory.ProcessorName = obj["Name"]?.ToString()?.Trim() ?? "Unknown";
                                inventory.ProcessorCores = Convert.ToInt32(obj["NumberOfCores"] ?? 0);
                                inventory.ProcessorLogicalThreads = Convert.ToInt32(obj["NumberOfLogicalProcessors"] ?? 0);
                                break;
                            }
                        }
                    }

                    // Collect operating system information.
                    using (ManagementObjectSearcher searcher = new ManagementObjectSearcher(
                        "SELECT Caption, Version, OSArchitecture FROM Win32_OperatingSystem"))
                    {
                        using (ManagementObjectCollection results = searcher.Get())
                        {
                            foreach (ManagementObject obj in results)
                            {
                                inventory.OperatingSystem = obj["Caption"]?.ToString()?.Trim() ?? "Unknown";
                                inventory.OsVersion = obj["Version"]?.ToString()?.Trim() ?? "Unknown";
                                inventory.SystemArchitecture = obj["OSArchitecture"]?.ToString()?.Trim() ?? "Unknown";
                                break;
                            }
                        }
                    }
                });

                inventory.CollectedAt = DateTime.UtcNow;

                _logger.LogDebug($"Hardware inventory complete. Manufacturer: {inventory.Manufacturer}, Model: {inventory.Model}");
            }
            catch (Exception ex)
            {
                _logger.LogError("Failed to collect hardware inventory.", ex);
            }

            return inventory;
        }
    }
}
