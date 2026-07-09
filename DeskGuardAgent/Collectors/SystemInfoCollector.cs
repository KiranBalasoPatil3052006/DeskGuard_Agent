/// <summary>
/// Collects general system information including operating system details,
/// boot time, uptime, computer name, domain, and logged-in user count.
/// Provides foundational context for all other monitoring data.
/// </summary>
using System.Management;
using DeskGuardAgent.Interfaces;
using DeskGuardAgent.Models;

namespace DeskGuardAgent.Collectors
{
    /// <summary>
    /// Collector responsible for retrieving general system information
    /// such as OS version, architecture, boot time, and uptime.
    /// Implements ICollector&lt;SystemInfo&gt; for standardized collection.
    /// </summary>
    public class SystemInfoCollector : ICollector<SystemInfo>
    {
        private readonly ILoggerService _logger;

        /// <summary>
        /// Initializes a new instance of the SystemInfoCollector class.
        /// </summary>
        /// <param name="logger">Service for logging collector operations and errors.</param>
        public SystemInfoCollector(ILoggerService logger)
        {
            _logger = logger;
        }

        /// <summary>
        /// Executes system information collection.
        /// Gathers OS details, boot time, uptime, and network identification.
        /// Never throws - all exceptions are caught and logged.
        /// </summary>
        /// <returns>A SystemInfo object with the collected system data.</returns>
        public async Task<SystemInfo> CollectAsync()
        {
            _logger.LogDebug("Starting system information collection.");

            var systemInfo = new SystemInfo();

            try
            {
                // Run multiple collection operations in parallel.
                await Task.Run(() =>
                {
                    // Get operating system information from WMI.
                    using (ManagementObjectSearcher searcher = new ManagementObjectSearcher(
                        "SELECT Caption, Version, OSArchitecture, LastBootUpTime FROM Win32_OperatingSystem"))
                    {
                        using (ManagementObjectCollection results = searcher.Get())
                        {
                            foreach (ManagementObject obj in results)
                            {
                                // Get the OS display name (e.g., "Microsoft Windows 11 Pro").
                                systemInfo.OperatingSystem = obj["Caption"]?.ToString()?.Trim() ?? "Unknown";

                                // Get the OS version number (e.g., "10.0.22621").
                                systemInfo.OsVersion = obj["Version"]?.ToString()?.Trim() ?? "Unknown";

                                // Get the system architecture (e.g., "64-bit").
                                systemInfo.Architecture = obj["OSArchitecture"]?.ToString()?.Trim() ?? "Unknown";

                                // Parse the last boot time from WMI date format.
                                if (obj["LastBootUpTime"] != null)
                                {
                                    string bootTimeStr = obj["LastBootUpTime"].ToString()!;
                                    if (ManagementDateTimeConverter.ToDateTime(bootTimeStr) is DateTime bootTime)
                                    {
                                        systemInfo.BootTime = bootTime;
                                    }
                                }

                                break;
                            }
                        }
                    }

                    // Calculate system uptime based on boot time.
                    if (systemInfo.BootTime != default)
                    {
                        TimeSpan uptime = DateTime.Now - systemInfo.BootTime;

                        // Store uptime in seconds for API consumption.
                        systemInfo.UptimeSeconds = uptime.TotalSeconds;

                        // Format uptime as a human-readable string.
                        systemInfo.UptimeFormatted = FormatUptime(uptime);
                    }

                    // Get computer and domain information.
                    systemInfo.ComputerName = Environment.MachineName;
                    systemInfo.DomainName = Environment.UserDomainName;

                    // Count currently logged-in users via WMI.
                    systemInfo.CurrentLoggedInUsers = GetLoggedInUserCount();
                });

                systemInfo.CollectedAt = DateTime.UtcNow;

                _logger.LogDebug($"System info collection complete. OS: {systemInfo.OperatingSystem}, Uptime: {systemInfo.UptimeFormatted}");
            }
            catch (Exception ex)
            {
                _logger.LogError("Failed to collect system information.", ex);
            }

            return systemInfo;
        }

        /// <summary>
        /// Formats a TimeSpan into a human-readable uptime string.
        /// Example: "5 days, 3 hours, 12 minutes, 45 seconds".
        /// </summary>
        /// <param name="uptime">The total system uptime.</param>
        /// <returns>A formatted string representation of the uptime.</returns>
        private static string FormatUptime(TimeSpan uptime)
        {
            // Build the uptime string from relevant time components.
            var parts = new List<string>();

            if (uptime.Days > 0)
                parts.Add($"{uptime.Days} day{(uptime.Days != 1 ? "s" : "")}");
            if (uptime.Hours > 0)
                parts.Add($"{uptime.Hours} hour{(uptime.Hours != 1 ? "s" : "")}");
            if (uptime.Minutes > 0)
                parts.Add($"{uptime.Minutes} minute{(uptime.Minutes != 1 ? "s" : "")}");
            if (uptime.Seconds > 0 || parts.Count == 0)
                parts.Add($"{uptime.Seconds} second{(uptime.Seconds != 1 ? "s" : "")}");

            return string.Join(", ", parts);
        }

        /// <summary>
        /// Counts the number of currently logged-in user sessions using WMI.
        /// Filters for interactive and remote desktop sessions only.
        /// </summary>
        /// <returns>The count of active user sessions.</returns>
        private static int GetLoggedInUserCount()
        {
            try
            {
                int count = 0;

                // Query for all active user sessions.
                using (ManagementObjectSearcher searcher = new ManagementObjectSearcher(
                    "SELECT * FROM Win32_LogonSession WHERE LogonType = 2 OR LogonType = 10"))
                {
                    // LogonType 2 = Interactive (local console), 10 = RemoteInteractive (RDP).
                    using (ManagementObjectCollection results = searcher.Get())
                    {
                        foreach (ManagementObject obj in results)
                        {
                            count++;
                        }
                    }
                }

                return count;
            }
            catch (Exception)
            {
                // Return 0 if unable to determine user count.
                return 0;
            }
        }
    }
}
