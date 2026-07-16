/// <summary>
/// Collects Windows Update status and pending update information.
/// Uses Microsoft.Update.Searcher COM component via the Windows Update Agent API
/// to query available updates, pending installations, and update history.
/// </summary>
using DeskGuardAgent.Interfaces;
using DeskGuardAgent.Models;

namespace DeskGuardAgent.Collectors
{
    /// <summary>
    /// Collector responsible for retrieving Windows Update status including
    /// pending update counts, last installation dates, and automatic update configuration.
    /// Implements ICollector&lt;UpdateInfo&gt; for standardized collection.
    /// </summary>
    public class UpdateCollector : ICollector<UpdateInfo>
    {
        private readonly ILoggerService _logger;

        /// <summary>
        /// Initializes a new instance of the UpdateCollector class.
        /// </summary>
        /// <param name="logger">Service for logging collector operations and errors.</param>
        public UpdateCollector(ILoggerService logger)
        {
            _logger = logger;
        }

        /// <summary>
        /// Executes Windows Update status collection.
        /// Queries the Windows Update Agent API for pending updates and installation history.
        /// Falls back to registry-based detection if the WU API is unavailable.
        /// Never throws - all exceptions are caught and logged.
        /// </summary>
        /// <returns>An UpdateInfo object with current update status.</returns>
        public async Task<UpdateInfo> CollectAsync()
        {
            _logger.LogDebug("Starting Windows Update collection.");

            var updateInfo = new UpdateInfo
            {
                IsUpToDate = true,
                IsAutomaticUpdatesEnabled = true,
                CollectedAt = DateTime.UtcNow
            };

            try
            {
                await Task.Run(() =>
                {
                    // Attempt to use Windows Update Agent API for accurate update information.
                    // This requires the Microsoft.Update.Searcher COM type.
                    try
                    {
                        // Create the UpdateSession and UpdateSearcher objects.
                        Type? sessionType = Type.GetTypeFromProgID("Microsoft.Update.Session");
                        if (sessionType != null)
                        {
                            dynamic updateSession = Activator.CreateInstance(sessionType)!;
                            dynamic updateSearcher = updateSession.CreateUpdateSearcher();

                            // Search for pending updates (not installed, not hidden).
                            dynamic searchResult = updateSearcher.Search("IsInstalled=0 AND IsHidden=0");

                            // Count total pending updates.
                            int totalCount = searchResult.Updates.Count;
                            updateInfo.PendingUpdateCount = totalCount;

                            // Count pending security updates and collect individual update details.
                            int securityCount = 0;
                            var pendingList = new List<DeskGuardAgent.Models.PendingUpdateDetail>();
                            for (int i = 0; i < totalCount; i++)
                            {
                                dynamic update = searchResult.Updates[i];
                                if (update.IsSecurity)
                                {
                                    securityCount++;
                                }

                                pendingList.Add(new DeskGuardAgent.Models.PendingUpdateDetail
                                {
                                    Title = update.Title ?? "Unknown",
                                    Description = update.Description ?? null,
                                    Category = update.Category?.Name ?? null,
                                    Severity = update.IsSecurity ? "Critical" : "Warning",
                                    KbId = update.KBArticleIDs?.Count > 0 ? update.KBArticleIDs[0]?.ToString() : null,
                                    IsSecurity = update.IsSecurity
                                });
                            }
                            updateInfo.PendingSecurityUpdateCount = securityCount;
                            updateInfo.PendingUpdates = pendingList;

                            // Determine if the device is up to date.
                            updateInfo.IsUpToDate = totalCount == 0;

                            // Get the last install date from update history.
                            try
                            {
                                dynamic historyResult = updateSearcher.QueryHistory(0, 1);
                                if (historyResult.Count > 0)
                                {
                                    dynamic lastEntry = historyResult[0];
                                    updateInfo.LastInstallationDate = lastEntry.Date;
                                }
                            }
                            catch
                            {
                                // History may be unavailable, that's acceptable.
                            }

                            // Check if automatic updates are enabled via registry.
                            updateInfo.IsAutomaticUpdatesEnabled = IsAutomaticUpdatesEnabled();

                            return;
                        }
                    }
                    catch
                    {
                        // Windows Update Agent API may not be available.
                        // Fall through to fallback detection method.
                        _logger.LogWarning("Windows Update Agent API unavailable, using fallback detection.");
                    }

                    // Fallback: Check registry for pending updates.
                    updateInfo.PendingUpdateCount = GetPendingUpdateCountFromRegistry();
                    updateInfo.IsUpToDate = updateInfo.PendingUpdateCount == 0;
                    updateInfo.IsAutomaticUpdatesEnabled = IsAutomaticUpdatesEnabled();
                });

                _logger.LogDebug($"Update collection complete. Pending: {updateInfo.PendingUpdateCount} updates.");
            }
            catch (Exception ex)
            {
                _logger.LogError("Failed to collect Windows Update information.", ex);
            }

            return updateInfo;
        }

        /// <summary>
        /// Checks the Windows Update registry key to determine if automatic updates are enabled.
        /// </summary>
        /// <returns>True if automatic updates are enabled, false otherwise.</returns>
        private static bool IsAutomaticUpdatesEnabled()
        {
            try
            {
                using (var key = Microsoft.Win32.Registry.LocalMachine.OpenSubKey(
                    @"SOFTWARE\Microsoft\Windows\CurrentVersion\WindowsUpdate\Auto Update"))
                {
                    if (key != null)
                    {
                        // AUOptions values:
                        // 1 = Keep my computer up to date (disabled)
                        // 2 = Notify before downloading
                        // 3 = Automatically download and notify
                        // 4 = Automatically download and schedule
                        int? auOptions = key.GetValue("AUOptions") as int?;
                        return auOptions.HasValue && auOptions.Value >= 2;
                    }
                }
            }
            catch
            {
                // Registry key may not exist on all systems.
            }

            return true; // Default to enabled if we can't determine.
        }

        /// <summary>
        /// Counts pending Windows updates by checking registry keys.
        /// This is a fallback method when the WU API is unavailable.
        /// </summary>
        /// <returns>The count of pending updates found in the registry.</returns>
        private static int GetPendingUpdateCountFromRegistry()
        {
            int count = 0;

            try
            {
                // Check for pending updates in various registry locations.
                string[] registryPaths = new[]
                {
                    @"SOFTWARE\Microsoft\Windows\CurrentVersion\WindowsUpdate\Pending",
                    @"SOFTWARE\Microsoft\Windows\CurrentVersion\WindowsUpdate\Auto Update\Pending"
                };

                foreach (string path in registryPaths)
                {
                    using (var key = Microsoft.Win32.Registry.LocalMachine.OpenSubKey(path))
                    {
                        if (key != null)
                        {
                            // Each subkey represents a pending update.
                            count += key.GetSubKeyNames().Length;
                        }
                    }
                }
            }
            catch
            {
                // Registry paths may not exist.
            }

            return count;
        }
    }
}
