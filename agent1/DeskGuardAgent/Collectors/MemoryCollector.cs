/// <summary>
/// Collects system memory (RAM) utilization metrics from the local machine.
/// Uses WMI queries to retrieve total, used, and available memory information.
/// Provides both raw byte values and formatted human-readable strings.
/// </summary>
using System.Management;
using DeskGuardAgent.Interfaces;
using DeskGuardAgent.Models;

namespace DeskGuardAgent.Collectors
{
    /// <summary>
    /// Collector responsible for retrieving RAM usage metrics including total memory,
    /// used memory, available memory, and utilization percentage.
    /// Implements ICollector&lt;MemoryInfo&gt; for standardized metric collection.
    /// </summary>
    public class MemoryCollector : ICollector<MemoryInfo>
    {
        private readonly ILoggerService _logger;

        /// <summary>
        /// Initializes a new instance of the MemoryCollector class.
        /// </summary>
        /// <param name="logger">Service for logging collector operations and errors.</param>
        public MemoryCollector(ILoggerService logger)
        {
            _logger = logger;
        }

        /// <summary>
        /// Executes memory metric collection asynchronously.
        /// Queries WMI for total visible memory size and free physical memory.
        /// Calculates used memory and utilization percentage.
        /// Never throws - all exceptions are caught and logged.
        /// </summary>
        /// <returns>A MemoryInfo object containing collected memory metrics.</returns>
        public async Task<MemoryInfo> CollectAsync()
        {
            _logger.LogDebug("Starting memory metrics collection.");

            var memoryInfo = new MemoryInfo();

            try
            {
                // Run WMI queries in parallel for efficiency.
                var memoryTask = Task.Run(() => GetMemoryFromWmi());

                // Wait for WMI queries to complete.
                var (totalBytes, freeBytes) = await memoryTask;

                // Populate the memory info object with collected data.
                memoryInfo.TotalMemoryBytes = totalBytes;
                memoryInfo.AvailableMemoryBytes = freeBytes;
                memoryInfo.UsedMemoryBytes = totalBytes - freeBytes;
                memoryInfo.UsagePercentage = totalBytes > 0
                    ? Math.Round((double)memoryInfo.UsedMemoryBytes / totalBytes * 100, 2)
                    : 0;
                memoryInfo.TotalMemoryFormatted = FormatBytes(totalBytes);
                memoryInfo.CollectedAt = DateTime.UtcNow;

                _logger.LogDebug($"Memory collection complete. Usage: {memoryInfo.UsagePercentage}% ({FormatBytes(memoryInfo.UsedMemoryBytes)} / {memoryInfo.TotalMemoryFormatted})");
            }
            catch (Exception ex)
            {
                _logger.LogError("Failed to collect memory metrics.", ex);
            }

            return memoryInfo;
        }

        /// <summary>
        /// Queries Windows WMI for total and free physical memory values.
        /// Uses Win32_OperatingSystem for accurate memory reporting.
        /// </summary>
        /// <returns>A tuple containing total physical memory bytes and free physical memory bytes.</returns>
        private (long totalBytes, long freeBytes) GetMemoryFromWmi()
        {
            try
            {
                using (ManagementObjectSearcher searcher = new ManagementObjectSearcher(
                    "SELECT TotalVisibleMemorySize, FreePhysicalMemory FROM Win32_OperatingSystem"))
                {
                    using (ManagementObjectCollection results = searcher.Get())
                    {
                        foreach (ManagementObject obj in results)
                        {
                            // WMI returns memory values in kilobytes, convert to bytes.
                            long totalKb = Convert.ToInt64(obj["TotalVisibleMemorySize"] ?? 0);
                            long freeKb = Convert.ToInt64(obj["FreePhysicalMemory"] ?? 0);

                            // Convert kilobytes to bytes (multiply by 1024).
                            return (totalKb * 1024, freeKb * 1024);
                        }
                    }
                }
            }
            catch (Exception ex)
            {
                _logger.LogWarning("Failed to read memory from WMI.", ex);
            }

            return (0, 0);
        }

        /// <summary>
        /// Formats a byte value into a human-readable string with appropriate units.
        /// Example: 17179869184 bytes becomes "16.0 GB".
        /// </summary>
        /// <param name="bytes">The byte value to format.</param>
        /// <returns>A formatted string with size and unit.</returns>
        private static string FormatBytes(long bytes)
        {
            // Define size units in ascending order.
            string[] suffixes = { "B", "KB", "MB", "GB", "TB" };
            int unitIndex = 0;
            double value = bytes;

            // Divide by 1024 until the value is between 1 and 1024.
            while (value >= 1024 && unitIndex < suffixes.Length - 1)
            {
                value /= 1024;
                unitIndex++;
            }

            // Format with one decimal place for GB and TB, whole numbers for smaller units.
            return unitIndex >= 3
                ? $"{value:F1} {suffixes[unitIndex]}"
                : $"{value:F0} {suffixes[unitIndex]}";
        }
    }
}
