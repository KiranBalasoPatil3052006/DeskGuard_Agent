/// <summary>
/// Collects security-related metrics including antivirus status and general security posture.
/// Queries Windows Security Center (WMI root\SecurityCenter2) for antivirus product information.
/// Also monitors Windows Defender and other registered security products.
/// </summary>
using System.Management;
using DeskGuardAgent.Interfaces;
using DeskGuardAgent.Models;

namespace DeskGuardAgent.Collectors
{
    /// <summary>
    /// Collector responsible for retrieving security status information.
    /// Checks antivirus product status, real-time protection, and signature update status.
    /// Implements ICollector&lt;AntivirusInfo&gt; for standardized security metric collection.
    /// </summary>
    public class SecurityCollector : ICollector<AntivirusInfo>
    {
        private readonly ILoggerService _logger;

        /// <summary>
        /// Initializes a new instance of the SecurityCollector class.
        /// </summary>
        /// <param name="logger">Service for logging collector operations and errors.</param>
        public SecurityCollector(ILoggerService logger)
        {
            _logger = logger;
        }

        /// <summary>
        /// Executes security status collection by querying Windows Security Center.
        /// Retrieves antivirus product details and protection status.
        /// Never throws - all exceptions are caught and logged.
        /// </summary>
        /// <returns>An AntivirusInfo object with current protection status.</returns>
        public async Task<AntivirusInfo> CollectAsync()
        {
            _logger.LogDebug("Starting security metrics collection.");

            var antivirusInfo = new AntivirusInfo
            {
                DisplayName = "Unknown",
                Status = "Unknown",
                CollectedAt = DateTime.UtcNow
            };

            try
            {
                await Task.Run(() =>
                {
                    // Query the Security Center for registered antivirus products.
                    // Note: This namespace may not be available on all Windows editions.
                    try
                    {
                        using (ManagementObjectSearcher searcher = new ManagementObjectSearcher(
                            @"root\SecurityCenter2", "SELECT * FROM AntivirusProduct"))
                        {
                            using (ManagementObjectCollection results = searcher.Get())
                            {
                                foreach (ManagementObject obj in results)
                                {
                                    // Get the display name of the antivirus product.
                                    antivirusInfo.DisplayName = obj["displayName"]?.ToString() ?? "Unknown";

                                    // Determine if real-time protection is enabled.
                                    // productState is a bitmask that varies by security product.
                                    if (obj["productState"] != null)
                                    {
                                        int productState = Convert.ToInt32(obj["productState"]);
                                        antivirusInfo.IsRealTimeProtectionEnabled = IsAntivirusActive(productState);
                                    }

                                    // Get product version if available.
                                    antivirusInfo.ProductVersion = obj["productVersion"]?.ToString()
                                        ?? obj["productUptoDate"]?.ToString() ?? "Unknown";
                                    antivirusInfo.IsSignatureUpToDate = obj["productUptoDate"]?.ToString() == "True";

                                    // Set overall status based on protection state.
                                    antivirusInfo.Status = antivirusInfo.IsRealTimeProtectionEnabled
                                        ? "OK"
                                        : "At Risk";

                                    // Only process the first (primary) antivirus product.
                                    break;
                                }
                            }
                        }
                    }
                    catch (ManagementException)
                    {
                        // Security Center2 namespace may not be available on some systems.
                        // Fall back to checking Windows Defender status via MSFT_MpComputerStatus.
                        _logger.LogDebug("Security Center2 WMI namespace unavailable, checking Windows Defender status via MSFT_MpComputerStatus.");

                        // Check if Windows Defender is active and get detailed signature info.
                        antivirusInfo.DisplayName = "Windows Defender";
                        CheckWindowsDefenderDetails(antivirusInfo);
                        antivirusInfo.Status = antivirusInfo.IsRealTimeProtectionEnabled
                            ? "OK"
                            : "At Risk";
                    }
                });

                _logger.LogDebug($"Security collection complete. Antivirus: {antivirusInfo.DisplayName}, Status: {antivirusInfo.Status}");
            }
            catch (Exception ex)
            {
                _logger.LogError("Failed to collect security metrics.", ex);
            }

            return antivirusInfo;
        }

        /// <summary>
        /// Determines if antivirus real-time protection is active based on the productState value.
        /// The productState value interpretation varies by vendor, but generally:
        /// - Lower 4 bits indicate the threat state
        /// - Next 4 bits indicate the product state
        /// - Value 0x1000 or 0x11000 typically means active protection
        /// </summary>
        /// <param name="productState">The product state integer from WMI.</param>
        /// <returns>True if real-time protection appears to be active.</returns>
        private static bool IsAntivirusActive(int productState)
        {
            // Check if the product state indicates active monitoring.
            // This is a heuristic based on common Security Center implementations.
            int stateFlag = (productState >> 12) & 0xF;
            return stateFlag == 1;
        }

        /// <summary>
        /// Checks Windows Defender status with full details including signature version
        /// and last update time using MSFT_MpComputerStatus WMI class.
        /// </summary>
        /// <param name="antivirusInfo">The AntivirusInfo object to populate.</param>
        private static void CheckWindowsDefenderDetails(AntivirusInfo antivirusInfo)
        {
            try
            {
                using (ManagementObjectSearcher searcher = new ManagementObjectSearcher(
                    @"root\Microsoft\Windows\Defender", "SELECT * FROM MSFT_MpComputerStatus"))
                {
                    using (ManagementObjectCollection results = searcher.Get())
                    {
                        foreach (ManagementObject obj in results)
                        {
                            if (obj["RealTimeProtectionEnabled"] != null)
                            {
                                antivirusInfo.IsRealTimeProtectionEnabled = Convert.ToBoolean(obj["RealTimeProtectionEnabled"]);
                            }

                            if (obj["AntivirusSignatureVersion"] != null)
                            {
                                antivirusInfo.ProductVersion = obj["AntivirusSignatureVersion"]?.ToString() ?? "";
                            }

                            if (obj["AntivirusSignatureLastUpdated"] != null)
                            {
                                try
                                {
                                    antivirusInfo.LastSignatureUpdate = ManagementDateTimeConverter.ToDateTime(obj["AntivirusSignatureLastUpdated"].ToString()!);
                                }
                                catch
                                {
                                    antivirusInfo.LastSignatureUpdate = null;
                                }
                            }

                            if (obj["AntivirusSignatureAge"] != null)
                            {
                                int ageDays = Convert.ToInt32(obj["AntivirusSignatureAge"]);
                                antivirusInfo.IsSignatureUpToDate = ageDays <= 1;
                            }

                            break;
                        }
                    }
                }
            }
            catch (ManagementException)
            {
                // MSFT_MpComputerStatus may not be available on older Windows versions.
            }
            catch
            {
                // Defender WMI namespace may not be available.
            }
        }
    }
}
