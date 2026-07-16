/// <summary>
/// Represents the comprehensive hardware inventory of the local machine.
/// Includes system manufacturer, model, serial number, BIOS, and processor details.
/// This data changes infrequently and is collected on a longer interval.
/// </summary>
namespace DeskGuardAgent.Models
{
    public class HardwareInventory
    {
        /// <summary>
        /// Gets or sets the system manufacturer name.
        /// Example: "Dell Inc.", "HP", "Lenovo".
        /// </summary>
        public string Manufacturer { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the system model name/number.
        /// Example: "Latitude 5520", "ThinkPad X1 Carbon".
        /// </summary>
        public string Model { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the system serial number (unique per device).
        /// Used for asset tracking and identification.
        /// </summary>
        public string SerialNumber { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the BIOS vendor name.
        /// Example: "Dell Inc.", "American Megatrends Inc.".
        /// </summary>
        public string BiosVendor { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the BIOS version string.
        /// Example: "1.14.0", "2.3.1".
        /// </summary>
        public string BiosVersion { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the BIOS release date.
        /// </summary>
        public DateTime? BiosReleaseDate { get; set; }

        /// <summary>
        /// Gets or sets the processor name.
        /// Example: "Intel(R) Core(TM) i7-10700K CPU @ 3.80GHz".
        /// </summary>
        public string ProcessorName { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the number of processor cores.
        /// </summary>
        public int ProcessorCores { get; set; }

        /// <summary>
        /// Gets or sets the number of logical processors (cores with hyperthreading).
        /// </summary>
        public int ProcessorLogicalThreads { get; set; }

        /// <summary>
        /// Gets or sets the total physical memory installed in bytes.
        /// </summary>
        public long TotalMemoryBytes { get; set; }

        /// <summary>
        /// Gets or sets the operating system name and version.
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
        public string SystemArchitecture { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the timestamp when the inventory was collected.
        /// </summary>
        public DateTime CollectedAt { get; set; } = DateTime.UtcNow;
    }
}
