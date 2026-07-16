/// <summary>
/// Represents the antivirus protection status of the local machine.
/// Collected from Windows Security Center (WMI) to determine if antivirus is active and up-to-date.
/// </summary>
namespace DeskGuardAgent.Models
{
    public class AntivirusInfo
    {
        /// <summary>
        /// Gets or sets the display name of the antivirus product.
        /// Example: "Windows Defender", "McAfee Endpoint Security".
        /// </summary>
        public string DisplayName { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the antivirus product version.
        /// </summary>
        public string ProductVersion { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets whether the antivirus real-time protection is enabled.
        /// </summary>
        public bool IsRealTimeProtectionEnabled { get; set; }

        /// <summary>
        /// Gets or sets whether the antivirus signatures are up-to-date.
        /// </summary>
        public bool IsSignatureUpToDate { get; set; }

        /// <summary>
        /// Gets or sets the date of the last signature update.
        /// </summary>
        public DateTime? LastSignatureUpdate { get; set; }

        /// <summary>
        /// Gets or sets the overall antivirus status.
        /// "OK" if protection is active, "At Risk" if not.
        /// </summary>
        public string Status { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the timestamp when the data was collected.
        /// </summary>
        public DateTime CollectedAt { get; set; } = DateTime.UtcNow;
    }
}
