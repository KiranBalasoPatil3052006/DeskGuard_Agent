/// <summary>
/// Collects software inventory by enumerating installed applications from the Windows Registry.
/// Reads from both 32-bit and 64-bit Uninstall registry keys to capture all installed software.
/// Provides a comprehensive list of applications for asset management tracking.
/// </summary>
using DeskGuardAgent.Interfaces;
using DeskGuardAgent.Models;
using Microsoft.Win32;

namespace DeskGuardAgent.Collectors
{
    /// <summary>
    /// Collector responsible for retrieving the list of installed software applications
    /// by reading the Windows Installer registry keys.
    /// Implements ICollector&lt;List&lt;SoftwareInventory&gt;&gt; for standardized collection.
    /// </summary>
    public class SoftwareInventoryCollector : ICollector<List<SoftwareInventory>>
    {
        private readonly ILoggerService _logger;

        /// <summary>
        /// Initializes a new instance of the SoftwareInventoryCollector class.
        /// </summary>
        /// <param name="logger">Service for logging collector operations and errors.</param>
        public SoftwareInventoryCollector(ILoggerService logger)
        {
            _logger = logger;
        }

        /// <summary>
        /// Executes software inventory collection by scanning registry uninstall keys.
        /// Reads both 64-bit and 32-bit application registry hives.
        /// Never throws - all exceptions are caught and logged.
        /// </summary>
        /// <returns>A list of SoftwareInventory objects for all installed applications.</returns>
        public async Task<List<SoftwareInventory>> CollectAsync()
        {
            _logger.LogDebug("Starting software inventory collection.");

            var softwareList = new List<SoftwareInventory>();

            try
            {
                await Task.Run(() =>
                {
                    // Create a set to track unique display names and avoid duplicates.
                    var seenNames = new HashSet<string>(StringComparer.OrdinalIgnoreCase);

                    // Scan 64-bit applications registry key.
                    // Located at: HKEY_LOCAL_MACHINE\SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall
                    ScanRegistryUninstallKey(
                        Registry.LocalMachine.OpenSubKey(@"SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall"),
                        softwareList, seenNames, is64Bit: true);

                    // Scan 32-bit applications on 64-bit systems.
                    // Located at: HKEY_LOCAL_MACHINE\SOFTWARE\WOW6432Node\Microsoft\Windows\CurrentVersion\Uninstall
                    ScanRegistryUninstallKey(
                        Registry.LocalMachine.OpenSubKey(@"SOFTWARE\WOW6432Node\Microsoft\Windows\CurrentVersion\Uninstall"),
                        softwareList, seenNames, is64Bit: false);

                    // Scan per-user installed applications.
                    // Located at: HKEY_CURRENT_USER\SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall
                    ScanRegistryUninstallKey(
                        Registry.CurrentUser.OpenSubKey(@"SOFTWARE\Microsoft\Windows\CurrentVersion\Uninstall"),
                        softwareList, seenNames, is64Bit: false);
                });

                _logger.LogDebug($"Software inventory complete. Found {softwareList.Count} applications.");
            }
            catch (Exception ex)
            {
                _logger.LogError("Failed to collect software inventory.", ex);
            }

            return softwareList;
        }

        /// <summary>
        /// Scans a registry uninstall key for installed software entries.
        /// Each subkey represents an installed application.
        /// </summary>
        /// <param name="uninstallKey">The registry key to scan.</param>
        /// <param name="softwareList">The list to add discovered software to.</param>
        /// <param name="seenNames">Set of seen display names to avoid duplicates.</param>
        /// <param name="is64Bit">Whether this is the 64-bit registry hive.</param>
        private void ScanRegistryUninstallKey(
            RegistryKey? uninstallKey,
            List<SoftwareInventory> softwareList,
            HashSet<string> seenNames,
            bool is64Bit)
        {
            // Skip if the registry key does not exist (e.g., no WOW6432Node on 32-bit systems).
            if (uninstallKey == null)
                return;

            try
            {
                // Get all subkey names (each is an installed application).
                string[] subKeyNames = uninstallKey.GetSubKeyNames();

                foreach (string subKeyName in subKeyNames)
                {
                    try
                    {
                        using (RegistryKey? subKey = uninstallKey.OpenSubKey(subKeyName))
                        {
                            if (subKey == null)
                                continue;

                            // Get the display name - skip entries without a name.
                            string? displayName = subKey.GetValue("DisplayName")?.ToString();
                            if (string.IsNullOrWhiteSpace(displayName))
                                continue;

                            // Skip duplicate entries that may appear in multiple registry locations.
                            if (!seenNames.Add(displayName))
                                continue;

                            // Get the version string.
                            string? version = subKey.GetValue("DisplayVersion")?.ToString();

                            // Get the publisher name.
                            string? publisher = subKey.GetValue("Publisher")?.ToString();

                            // Parse the install date from registry format.
                            DateTime? installDate = null;
                            string? installDateStr = subKey.GetValue("InstallDate")?.ToString();
                            if (!string.IsNullOrEmpty(installDateStr) && installDateStr.Length == 8)
                            {
                                // Registry format: YYYYMMDD
                                if (DateTime.TryParseExact(installDateStr, "yyyyMMdd",
                                    null, System.Globalization.DateTimeStyles.None, out DateTime parsedDate))
                                {
                                    installDate = parsedDate;
                                }
                            }

                            // Get the estimated size in megabytes.
                            double? estimatedSize = null;
                            object? sizeValue = subKey.GetValue("EstimatedSize");
                            if (sizeValue != null)
                            {
                                // Registry stores size in kilobytes, convert to megabytes.
                                estimatedSize = Math.Round(Convert.ToDouble(sizeValue) / 1024.0, 2);
                            }

                            // Build the registry key path for reference.
                            string keyPath = subKey.Name;

                            var software = new SoftwareInventory
                            {
                                DisplayName = displayName,
                                DisplayVersion = version ?? string.Empty,
                                Publisher = publisher ?? string.Empty,
                                InstallDate = installDate,
                                EstimatedSizeMB = estimatedSize,
                                RegistryKeyPath = keyPath,
                                Is64Bit = is64Bit,
                                CollectedAt = DateTime.UtcNow
                            };

                            softwareList.Add(software);
                        }
                    }
                    catch (Exception ex)
                    {
                        // Log and continue with the next subkey.
                        _logger.LogWarning($"Failed to read registry subkey {subKeyName}.", ex);
                    }
                }
            }
            catch (Exception ex)
            {
                _logger.LogWarning($"Failed to scan registry key {uninstallKey.Name}.", ex);
            }
            finally
            {
                // Ensure the registry key is closed.
                uninstallKey.Close();
            }
        }
    }
}
