/// <summary>
/// Represents system memory (RAM) metrics collected from the local machine.
/// Tracks total, used, and available memory along with utilization percentage.
/// </summary>
namespace DeskGuardAgent.Models
{
    public class MemoryInfo
    {
        /// <summary>
        /// Gets or sets the total physical memory installed in bytes.
        /// </summary>
        public long TotalMemoryBytes { get; set; }

        /// <summary>
        /// Gets or sets the amount of memory currently in use in bytes.
        /// </summary>
        public long UsedMemoryBytes { get; set; }

        /// <summary>
        /// Gets or sets the amount of available (free + cached) memory in bytes.
        /// </summary>
        public long AvailableMemoryBytes { get; set; }

        /// <summary>
        /// Gets or sets the memory utilization percentage (0-100).
        /// Calculated as (UsedMemoryBytes / TotalMemoryBytes) * 100.
        /// </summary>
        public double UsagePercentage { get; set; }

        /// <summary>
        /// Gets or sets the total memory formatted in human-readable format (e.g., "16.0 GB").
        /// </summary>
        public string TotalMemoryFormatted { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the timestamp when the data was collected.
        /// </summary>
        public DateTime CollectedAt { get; set; } = DateTime.UtcNow;
    }
}
