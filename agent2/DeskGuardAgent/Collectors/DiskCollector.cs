/// <summary>
/// Collects disk drive metrics including partition usage, free space, and SMART health status.
/// Uses WMI for partition information and performance counters for disk activity.
/// Supports multiple drives and provides per-partition usage statistics.
/// </summary>
using System.Management;
using DeskGuardAgent.Interfaces;
using DeskGuardAgent.Models;

namespace DeskGuardAgent.Collectors
{
    /// <summary>
    /// Collector responsible for retrieving disk usage metrics and health status
    /// for all fixed drives on the local machine.
    /// Implements ICollector&lt;List&lt;DiskInfo&gt;&gt; to support multiple drives.
    /// </summary>
    public class DiskCollector : ICollector<List<DiskInfo>>
    {
        private readonly ILoggerService _logger;

        /// <summary>
        /// Initializes a new instance of the DiskCollector class.
        /// </summary>
        /// <param name="logger">Service for logging collector operations and errors.</param>
        public DiskCollector(ILoggerService logger)
        {
            _logger = logger;
        }

        /// <summary>
        /// Executes disk metric collection for all fixed drives.
        /// Enumerates logical disks, retrieves usage statistics, and checks SMART health.
        /// Never throws - all exceptions are caught and logged.
        /// </summary>
        /// <returns>A list of DiskInfo objects, one per fixed drive.</returns>
        public Task<List<DiskInfo>> CollectAsync()
        {
            _logger.LogDebug("Starting disk metrics collection.");

            var diskList = new List<DiskInfo>();

            try
            {
                // Enumerate all logical drives on the system.
                foreach (DriveInfo drive in DriveInfo.GetDrives())
                {
                    // Only process fixed drives (SSD, HDD, NVMe).
                    // Skip CD-ROM, removable, and network drives.
                    if (drive.DriveType != DriveType.Fixed)
                        continue;

                    var diskInfo = new DiskInfo
                    {
                        DriveName = drive.Name,
                        VolumeLabel = drive.VolumeLabel ?? string.Empty,
                        FileSystem = drive.DriveFormat,
                        DriveType = DetermineDriveType(drive.Name),
                        CollectedAt = DateTime.UtcNow
                    };

                    try
                    {
                        // Populate size information if the drive is ready.
                        if (drive.IsReady)
                        {
                            diskInfo.TotalSizeBytes = drive.TotalSize;
                            diskInfo.FreeSpaceBytes = drive.AvailableFreeSpace;
                            diskInfo.UsedSpaceBytes = drive.TotalSize - drive.AvailableFreeSpace;
                            diskInfo.UsagePercentage = drive.TotalSize > 0
                                ? Math.Round((double)diskInfo.UsedSpaceBytes / drive.TotalSize * 100, 2)
                                : 0;
                        }
                    }
                    catch (Exception ex)
                    {
                        // Log warning if drive info is inaccessible (encrypted drives, etc.).
                        _logger.LogWarning($"Failed to read info for drive {drive.Name}.", ex);
                    }

                    diskList.Add(diskInfo);
                }

                // Check SMART health status synchronously so data is available immediately.
                CheckSmartHealth(diskList);

                _logger.LogDebug($"Disk collection complete. Found {diskList.Count} drives.");
            }
            catch (Exception ex)
            {
                _logger.LogError("Failed to collect disk metrics.", ex);
            }

            return Task.FromResult(diskList);
        }

        /// <summary>
        /// Estimates the drive type (SSD, HDD, NVMe) based on the drive name.
        /// Uses WMI Win32_DiskDrive to get the media type and interface type.
        /// </summary>
        /// <param name="driveName">The logical drive name (e.g., "C:\").</param>
        /// <returns>A string indicating the drive type: "SSD", "HDD", "NVMe", or "Unknown".</returns>
        private string DetermineDriveType(string driveName)
        {
            try
            {
                // Query WMI for disk drive details matching the logical drive.
                using (ManagementObjectSearcher searcher = new ManagementObjectSearcher(
                    $"ASSOCIATORS OF {{Win32_LogicalDisk.DeviceID='{driveName.Replace("\\", "\\\\")}'}} " +
                    $"WHERE ResultClass=Win32_DiskDrive"))
                {
                    using (ManagementObjectCollection results = searcher.Get())
                    {
                        try
                        {
                            foreach (ManagementObject obj in results)
                            {
                                // Check the interface type for NVMe detection.
                                string interfaceType = obj["InterfaceType"]?.ToString() ?? "";
                                if (interfaceType.Contains("NVMe", StringComparison.OrdinalIgnoreCase))
                                    return "NVMe";

                                // Check media type or model name for SSD detection.
                                string model = obj["Model"]?.ToString() ?? "";
                                string mediaType = obj["MediaType"]?.ToString() ?? "";

                                if (mediaType.Contains("SSD", StringComparison.OrdinalIgnoreCase) ||
                                    mediaType.Contains("Solid State", StringComparison.OrdinalIgnoreCase) ||
                                    model.Contains("SSD", StringComparison.OrdinalIgnoreCase))
                                    return "SSD";
                            }
                        }
                        catch (ManagementException)
                        {
                            // Drive type association not available (virtual/CD/network drives).
                            return "Unknown";
                        }
                    }
                }
            }
            catch (Exception ex)
            {
                _logger.LogWarning("Failed to determine drive type.", ex);
                return "Unknown";
            }

            // Default to HDD if no specific type was determined.
            return "HDD";
        }

        /// <summary>
        /// Checks SMART health status for all physical disks using WMI.
        /// Win32_DiskDrive reports SMART status via the Status property.
        /// Updates each DiskInfo object in the provided list.
        /// </summary>
        /// <param name="diskList">The list of DiskInfo objects to update with SMART status.</param>
        private void CheckSmartHealth(List<DiskInfo> diskList)
        {
            try
            {
                // Query all physical disk drives for their status.
                using (ManagementObjectSearcher searcher = new ManagementObjectSearcher(
                    "SELECT Index, Status, Model FROM Win32_DiskDrive"))
                {
                    using (ManagementObjectCollection results = searcher.Get())
                    {
                        int driveIndex = 0;

                        foreach (ManagementObject obj in results)
                        {
                            // Match physical drives to logical drives by index.
                            if (driveIndex < diskList.Count)
                            {
                                string status = obj["Status"]?.ToString() ?? "";
                                // SMART is OK if status is "OK" or "Good".
                                diskList[driveIndex].IsSmartHealthOk =
                                    string.Equals(status, "OK", StringComparison.OrdinalIgnoreCase) ||
                                    string.Equals(status, "Good", StringComparison.OrdinalIgnoreCase);
                            }

                            driveIndex++;
                        }
                    }
                }
            }
            catch (Exception ex)
            {
                _logger.LogWarning("Failed to check SMART health status.", ex);
            }
        }
    }
}
