/// <summary>
/// Represents disk drive metrics including partition usage and SMART health status.
/// Supports multiple drives and partitions on the local machine.
/// </summary>
namespace DeskGuardAgent.Models
{
    public class DiskInfo
    {
        /// <summary>
        /// Gets or sets the disk drive letter or mount point (e.g., "C:", "D:").
        /// </summary>
        public string DriveName { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the volume label of the disk (e.g., "System", "Data").
        /// </summary>
        public string VolumeLabel { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the total size of the disk partition in bytes.
        /// </summary>
        public long TotalSizeBytes { get; set; }

        /// <summary>
        /// Gets or sets the amount of free space available on the partition in bytes.
        /// </summary>
        public long FreeSpaceBytes { get; set; }

        /// <summary>
        /// Gets or sets the amount of used space on the partition in bytes.
        /// </summary>
        public long UsedSpaceBytes { get; set; }

        /// <summary>
        /// Gets or sets the disk usage percentage (0-100).
        /// Calculated as (UsedSpaceBytes / TotalSizeBytes) * 100.
        /// </summary>
        public double UsagePercentage { get; set; }

        /// <summary>
        /// Gets or sets the file system type (e.g., "NTFS", "FAT32").
        /// </summary>
        public string FileSystem { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the disk drive type (e.g., "SSD", "HDD", "NVMe").
        /// </summary>
        public string DriveType { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets whether the disk SMART health status is OK.
        /// True if the disk reports healthy status, false if issues detected.
        /// </summary>
        public bool? IsSmartHealthOk { get; set; }

        /// <summary>
        /// Gets or sets the timestamp when the data was collected.
        /// </summary>
        public DateTime CollectedAt { get; set; } = DateTime.UtcNow;
    }
}
