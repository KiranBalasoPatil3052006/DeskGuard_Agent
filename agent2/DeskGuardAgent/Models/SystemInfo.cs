/// <summary>
/// Represents general system information and status.
/// Includes boot time, uptime, operating system version, and system architecture details.
/// </summary>
namespace DeskGuardAgent.Models
{
    public class SystemInfo
    {
        /// <summary>
        /// Gets or sets the operating system name and edition.
        /// Example: "Microsoft Windows 11 Pro".
        /// </summary>
        public string OperatingSystem { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the operating system version number.
        /// Example: "10.0.22621".
        /// </summary>
        public string OsVersion { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the system architecture.
        /// Example: "x64-based PC".
        /// </summary>
        public string Architecture { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the system boot time (when the system was last started).
        /// </summary>
        public DateTime BootTime { get; set; }

        /// <summary>
        /// Gets or sets the system uptime as a formatted string.
        /// Example: "5 days, 3 hours, 12 minutes".
        /// </summary>
        public string UptimeFormatted { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the total uptime in seconds.
        /// </summary>
        public double UptimeSeconds { get; set; }

        /// <summary>
        /// Gets or sets the computer name on the network.
        /// </summary>
        public string ComputerName { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the domain or workgroup name the computer belongs to.
        /// </summary>
        public string DomainName { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the number of users currently logged on.
        /// </summary>
        public int CurrentLoggedInUsers { get; set; }

        /// <summary>
        /// Gets or sets the timestamp when the data was collected.
        /// </summary>
        public DateTime CollectedAt { get; set; } = DateTime.UtcNow;
    }
}
