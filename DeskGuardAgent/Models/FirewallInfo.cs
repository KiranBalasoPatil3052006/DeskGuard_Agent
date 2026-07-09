/// <summary>
/// Represents the Windows Firewall status and configuration.
/// Used to verify that the firewall is active and properly configured.
/// </summary>
namespace DeskGuardAgent.Models
{
    public class FirewallInfo
    {
        /// <summary>
        /// Gets or sets whether the Windows Firewall is enabled for domain profiles.
        /// </summary>
        public bool IsDomainFirewallEnabled { get; set; }

        /// <summary>
        /// Gets or sets whether the Windows Firewall is enabled for private network profiles.
        /// </summary>
        public bool IsPrivateFirewallEnabled { get; set; }

        /// <summary>
        /// Gets or sets whether the Windows Firewall is enabled for public network profiles.
        /// </summary>
        public bool IsPublicFirewallEnabled { get; set; }

        /// <summary>
        /// Gets or sets the current active firewall profile.
        /// Example: "Domain", "Private", "Public".
        /// </summary>
        public string ActiveProfile { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the overall firewall status.
        /// "OK" if firewall is enabled, "At Risk" if not.
        /// </summary>
        public string Status { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the timestamp when the data was collected.
        /// </summary>
        public DateTime CollectedAt { get; set; } = DateTime.UtcNow;
    }
}
