/// <summary>
/// Collects Windows Firewall status for all network profiles.
/// Uses WMI and NetFw policies to determine if the firewall is enabled
/// for Domain, Private, and Public network profiles.
/// </summary>
using System.Management;
using DeskGuardAgent.Interfaces;
using DeskGuardAgent.Models;

namespace DeskGuardAgent.Collectors
{
    /// <summary>
    /// Collector responsible for retrieving Windows Firewall status information.
    /// Checks firewall enablement state for all network profile types.
    /// Implements ICollector&lt;FirewallInfo&gt; for standardized collection.
    /// </summary>
    public class FirewallCollector : ICollector<FirewallInfo>
    {
        private readonly ILoggerService _logger;

        /// <summary>
        /// Initializes a new instance of the FirewallCollector class.
        /// </summary>
        /// <param name="logger">Service for logging collector operations and errors.</param>
        public FirewallCollector(ILoggerService logger)
        {
            _logger = logger;
        }

        /// <summary>
        /// Executes Windows Firewall status collection.
        /// Checks firewall state for Domain, Private, and Public profiles.
        /// Also determines the currently active firewall profile.
        /// Never throws - all exceptions are caught and logged.
        /// </summary>
        /// <returns>A FirewallInfo object with current firewall status.</returns>
        public async Task<FirewallInfo> CollectAsync()
        {
            _logger.LogDebug("Starting firewall status collection.");

            var firewallInfo = new FirewallInfo
            {
                Status = "Unknown",
                CollectedAt = DateTime.UtcNow
            };

            try
            {
                await Task.Run(() =>
                {
                    // Use the Windows Firewall with Advanced Security COM interface.
                    // This is the most reliable way to check firewall status on modern Windows.
                    try
                    {
                        Type? netFwPolicyType = Type.GetTypeFromProgID("HNetCfg.FwPolicy2");
                        if (netFwPolicyType != null)
                        {
                            dynamic fwPolicy2 = Activator.CreateInstance(netFwPolicyType)!;

                            // Check firewall state for each profile type.
                            // NET_FW_PROFILE2_DOMAIN = 1
                            // NET_FW_PROFILE2_PRIVATE = 2
                            // NET_FW_PROFILE2_PUBLIC = 4
                            firewallInfo.IsDomainFirewallEnabled = fwPolicy2.FirewallEnabled[1];
                            firewallInfo.IsPrivateFirewallEnabled = fwPolicy2.FirewallEnabled[2];
                            firewallInfo.IsPublicFirewallEnabled = fwPolicy2.FirewallEnabled[4];

                            // Determine the current active profile.
                            int currentProfile = (int)fwPolicy2.CurrentProfileTypes;
                            firewallInfo.ActiveProfile = currentProfile switch
                            {
                                1 => "Domain",
                                2 => "Private",
                                4 => "Public",
                                _ => "Unknown"
                            };

                            // Set the overall status based on whether at least one profile has firewall enabled.
                            firewallInfo.Status = firewallInfo.IsDomainFirewallEnabled ||
                                                  firewallInfo.IsPrivateFirewallEnabled ||
                                                  firewallInfo.IsPublicFirewallEnabled
                                ? "OK"
                                : "At Risk";
                        }
                    }
                    catch
                    {
                        // Fall back to WMI firewall check if COM interface is unavailable.
                        _logger.LogWarning("Firewall COM interface unavailable, using WMI fallback.");
                        CheckFirewallViaWmi(firewallInfo);
                    }
                });

                _logger.LogDebug($"Firewall collection complete. Status: {firewallInfo.Status}");
            }
            catch (Exception ex)
            {
                _logger.LogError("Failed to collect firewall status.", ex);
            }

            return firewallInfo;
        }

        /// <summary>
        /// Fallback method to check firewall status using WMI.
        /// Uses the FirewallRule class from the standard SecurityCenter2 namespace.
        /// </summary>
        /// <param name="firewallInfo">The FirewallInfo object to populate.</param>
        private static void CheckFirewallViaWmi(FirewallInfo firewallInfo)
        {
            try
            {
                // Query for firewall rules to determine if the firewall is active.
                using (ManagementObjectSearcher searcher = new ManagementObjectSearcher(
                    @"root\SecurityCenter2", "SELECT * FROM FirewallProduct"))
                {
                    using (ManagementObjectCollection results = searcher.Get())
                    {
                        foreach (ManagementObject obj in results)
                        {
                            // Check if the firewall product is enabled.
                            if (obj["enabled"] != null)
                            {
                                bool isEnabled = Convert.ToBoolean(obj["enabled"]);
                                firewallInfo.IsDomainFirewallEnabled = isEnabled;
                                firewallInfo.IsPrivateFirewallEnabled = isEnabled;
                                firewallInfo.IsPublicFirewallEnabled = isEnabled;

                                firewallInfo.Status = isEnabled ? "OK" : "At Risk";
                                firewallInfo.ActiveProfile = "Unknown";

                                break;
                            }
                        }
                    }
                }
            }
            catch (ManagementException)
            {
                // SecurityCenter2 may not be available - mark as unknown.
                firewallInfo.Status = "Unknown";
            }
        }
    }
}
