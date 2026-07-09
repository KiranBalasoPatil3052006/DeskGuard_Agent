/// <summary>
/// Collects battery status and health metrics from the local machine.
/// Uses WMI Win32_Battery class to retrieve battery presence, charge level,
/// charging status, and battery wear information.
/// Primarily relevant for laptop and portable devices.
/// </summary>
using System.Management;
using DeskGuardAgent.Interfaces;
using DeskGuardAgent.Models;

namespace DeskGuardAgent.Collectors
{
    /// <summary>
    /// Collector responsible for retrieving battery metrics including charge percentage,
    /// charging status, estimated runtime, and battery wear level.
    /// Implements ICollector&lt;BatteryInfo&gt; for standardized metric collection.
    /// Returns default values if no battery is present.
    /// </summary>
    public class BatteryCollector : ICollector<BatteryInfo>
    {
        private readonly ILoggerService _logger;

        /// <summary>
        /// Initializes a new instance of the BatteryCollector class.
        /// </summary>
        /// <param name="logger">Service for logging collector operations and errors.</param>
        public BatteryCollector(ILoggerService logger)
        {
            _logger = logger;
        }

        /// <summary>
        /// Executes battery metric collection asynchronously.
        /// Queries WMI for battery status, charge levels, and design specifications.
        /// Never throws - all exceptions are caught and logged.
        /// </summary>
        /// <returns>A BatteryInfo object with collected metrics.</returns>
        public async Task<BatteryInfo> CollectAsync()
        {
            _logger.LogDebug("Starting battery metrics collection.");

            var batteryInfo = new BatteryInfo
            {
                IsBatteryPresent = false,
                CollectedAt = DateTime.UtcNow
            };

            try
            {
                // Query WMI for battery information.
                await Task.Run(() =>
                {
                    using (ManagementObjectSearcher searcher = new ManagementObjectSearcher(
                        "SELECT * FROM Win32_Battery"))
                    {
                        using (ManagementObjectCollection results = searcher.Get())
                        {
                            foreach (ManagementObject obj in results)
                            {
                                // A battery was found, mark as present.
                                batteryInfo.IsBatteryPresent = true;

                                // Get the current battery charge percentage (0-100).
                                if (obj["EstimatedChargeRemaining"] != null)
                                {
                                    batteryInfo.BatteryPercentage = Convert.ToInt32(obj["EstimatedChargeRemaining"]);
                                }

                                // Determine if the battery is currently charging.
                                // BatteryStatus values: 1=Discharging, 2=AC Power, 3=Fully Charged, 4=Low, 5=Critical, 6=Charging, 7=Charging High, 8=Charging Low, 9=Undefined, 10=Undefined
                                if (obj["BatteryStatus"] != null)
                                {
                                    int status = Convert.ToInt32(obj["BatteryStatus"]);
                                    batteryInfo.IsCharging = status is 2 or 6 or 7 or 8;
                                }

                                // Get estimated runtime in seconds.
                                if (obj["EstimatedRunTime"] != null)
                                {
                                    int runTime = Convert.ToInt32(obj["EstimatedRunTime"]);
                                    // -1 means the estimate is unavailable.
                                    batteryInfo.EstimatedRunTimeSeconds = runTime > 0 ? runTime : null;
                                }

                                // Get battery chemistry type.
                                if (obj["Chemistry"] != null)
                                {
                                    int chemistry = Convert.ToInt32(obj["Chemistry"]);
                                    batteryInfo.Chemistry = ChemistryToString(chemistry);
                                }

                                // Get design capacity (milliwatt-hours).
                                if (obj["DesignCapacity"] != null)
                                {
                                    batteryInfo.DesignCapacity = Convert.ToInt32(obj["DesignCapacity"]);
                                }

                                // Get full charge capacity (milliwatt-hours).
                                if (obj["FullChargeCapacity"] != null)
                                {
                                    batteryInfo.FullChargeCapacity = Convert.ToInt32(obj["FullChargeCapacity"]);
                                }

                                // Calculate battery wear level based on capacity degradation.
                                if (batteryInfo.DesignCapacity.HasValue && batteryInfo.DesignCapacity > 0 &&
                                    batteryInfo.FullChargeCapacity.HasValue)
                                {
                                    batteryInfo.WearLevelPercentage = Math.Round(
                                        (1.0 - (double)batteryInfo.FullChargeCapacity.Value / batteryInfo.DesignCapacity.Value) * 100, 2);
                                }

                                // Only one battery is expected, break after first.
                                break;
                            }
                        }
                    }
                });

                _logger.LogDebug($"Battery collection complete. Present: {batteryInfo.IsBatteryPresent}, Charge: {batteryInfo.BatteryPercentage}%");
            }
            catch (Exception ex)
            {
                _logger.LogError("Failed to collect battery metrics.", ex);
            }

            return batteryInfo;
        }

        /// <summary>
        /// Converts WMI battery chemistry code to a human-readable string.
        /// </summary>
        /// <param name="chemistryCode">The WMI chemistry code (1-6).</param>
        /// <returns>The battery chemistry type name.</returns>
        private static string ChemistryToString(int chemistryCode)
        {
            return chemistryCode switch
            {
                1 => "Other",
                2 => "Unknown",
                3 => "Lead Acid",
                4 => "Nickel Cadmium (NiCd)",
                5 => "Nickel Metal Hydride (NiMH)",
                6 => "Lithium-ion (Li-Ion)",
                _ => "Unknown"
            };
        }
    }
}
