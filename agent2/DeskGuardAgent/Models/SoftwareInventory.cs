/// <summary>
/// Represents a single installed software application entry.
/// Collected from Windows Registry (Uninstall keys) for software inventory tracking.
/// </summary>
namespace DeskGuardAgent.Models
{
    public class SoftwareInventory
    {
        /// <summary>
        /// Gets or sets the display name of the installed software.
        /// Example: "Microsoft Visual Studio Code", "Google Chrome".
        /// </summary>
        public string DisplayName { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the version string of the installed software.
        /// Example: "1.85.0", "120.0.6099.109".
        /// </summary>
        public string DisplayVersion { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the software publisher/vendor name.
        /// Example: "Microsoft Corporation", "Google LLC".
        /// </summary>
        public string Publisher { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the date when the software was installed.
        /// Null if the install date is not recorded in the registry.
        /// </summary>
        public DateTime? InstallDate { get; set; }

        /// <summary>
        /// Gets or sets the size of the installation in megabytes.
        /// Null if size information is unavailable.
        /// </summary>
        public double? EstimatedSizeMB { get; set; }

        /// <summary>
        /// Gets or sets the uninstall registry key path where this entry was found.
        /// Useful for diagnostics and debugging inventory issues.
        /// </summary>
        public string RegistryKeyPath { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets whether the software is a 64-bit application.
        /// Determined by which registry hive (32-bit or 64-bit) the entry was found in.
        /// </summary>
        public bool Is64Bit { get; set; }

        /// <summary>
        /// Gets or sets the timestamp when the inventory was collected.
        /// </summary>
        public DateTime CollectedAt { get; set; } = DateTime.UtcNow;
    }
}
