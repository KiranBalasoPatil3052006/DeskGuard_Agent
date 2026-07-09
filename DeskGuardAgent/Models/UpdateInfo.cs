/// <summary>
/// Represents Windows Update status and pending update information.
/// Used to track whether the endpoint has the latest security and quality updates applied.
/// </summary>
namespace DeskGuardAgent.Models
{
    public class UpdateInfo
    {
        /// <summary>
        /// Gets or sets the count of pending updates that have not been installed.
        /// </summary>
        public int PendingUpdateCount { get; set; }

        /// <summary>
        /// Gets or sets the count of pending security updates specifically.
        /// Security updates are critical and tracked separately.
        /// </summary>
        public int PendingSecurityUpdateCount { get; set; }

        /// <summary>
        /// Gets or sets the date of the last successful Windows Update installation.
        /// Null if no updates have been installed recently.
        /// </summary>
        public DateTime? LastInstallationDate { get; set; }

        /// <summary>
        /// Gets or sets whether the device is configured to receive updates automatically.
        /// </summary>
        public bool IsAutomaticUpdatesEnabled { get; set; }

        /// <summary>
        /// Gets or sets whether the device is up-to-date with all required updates.
        /// </summary>
        public bool IsUpToDate { get; set; }

        /// <summary>
        /// Gets or sets the timestamp when the data was collected.
        /// </summary>
        public DateTime CollectedAt { get; set; } = DateTime.UtcNow;

        /// <summary>
        /// Gets or sets the list of individual pending updates with their details.
        /// Populated when the Windows Update Agent API is available.
        /// Each entry contains title, description, category, severity, and KB ID.
        /// </summary>
        public List<PendingUpdateDetail>? PendingUpdates { get; set; }
    }

    /// <summary>
    /// Represents a single pending Windows Update with detailed information.
    /// </summary>
    public class PendingUpdateDetail
    {
        public string Title { get; set; } = string.Empty;
        public string? Description { get; set; }
        public string? Category { get; set; }
        public string? Severity { get; set; }
        public string? KbId { get; set; }
        public bool IsSecurity { get; set; }
    }
}
