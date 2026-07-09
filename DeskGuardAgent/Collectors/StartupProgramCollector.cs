/// <summary>
/// Collects information about programs configured to run at system startup.
/// Reads from multiple registry locations where startup programs are registered,
/// including HKLM and HKCU Run keys, and the common Startup folders.
/// </summary>
using DeskGuardAgent.Interfaces;
using DeskGuardAgent.Models;
using Microsoft.Win32;

namespace DeskGuardAgent.Collectors
{
    /// <summary>
    /// Collector responsible for enumerating programs configured to start automatically
    /// when the system boots or when a user logs in.
    /// Returns process-like information for each startup entry.
    /// Implements ICollector&lt;List&lt;ProcessInfo&gt;&gt; for standardized collection.
    /// </summary>
    public class StartupProgramCollector : ICollector<List<ProcessInfo>>
    {
        private readonly ILoggerService _logger;

        /// <summary>
        /// Initializes a new instance of the StartupProgramCollector class.
        /// </summary>
        /// <param name="logger">Service for logging collector operations and errors.</param>
        public StartupProgramCollector(ILoggerService logger)
        {
            _logger = logger;
        }

        /// <summary>
        /// Executes startup program enumeration.
        /// Reads from all common startup registry locations and the startup folders.
        /// Never throws - all exceptions are caught and logged.
        /// </summary>
        /// <returns>A list of ProcessInfo objects for startup programs.</returns>
        public async Task<List<ProcessInfo>> CollectAsync()
        {
            _logger.LogDebug("Starting startup program collection.");

            var startupList = new List<ProcessInfo>();

            try
            {
                await Task.Run(() =>
                {
                    // Create a set to track unique executable paths and avoid duplicates.
                    var seenPaths = new HashSet<string>(StringComparer.OrdinalIgnoreCase);

                    // Read from HKEY_LOCAL_MACHINE\SOFTWARE\Microsoft\Windows\CurrentVersion\Run
                    // This contains startup programs for all users (machine-wide).
                    ReadStartupRegKey(
                        Registry.LocalMachine.OpenSubKey(
                            @"SOFTWARE\Microsoft\Windows\CurrentVersion\Run"),
                        startupList, seenPaths, "HKLM_Run");

                    // Read from HKEY_LOCAL_MACHINE\SOFTWARE\WOW6432Node\Microsoft\Windows\CurrentVersion\Run
                    // This contains 32-bit startup programs on 64-bit systems.
                    ReadStartupRegKey(
                        Registry.LocalMachine.OpenSubKey(
                            @"SOFTWARE\WOW6432Node\Microsoft\Windows\CurrentVersion\Run"),
                        startupList, seenPaths, "HKLM_Run_WOW");

                    // Read from HKEY_CURRENT_USER\SOFTWARE\Microsoft\Windows\CurrentVersion\Run
                    // This contains startup programs for the current user only.
                    ReadStartupRegKey(
                        Registry.CurrentUser.OpenSubKey(
                            @"SOFTWARE\Microsoft\Windows\CurrentVersion\Run"),
                        startupList, seenPaths, "HKCU_Run");

                    // Read from the common Startup folder for all users.
                    string commonStartupFolder = Environment.GetFolderPath(
                        Environment.SpecialFolder.CommonStartup);
                    ReadStartupFolder(commonStartupFolder, startupList, seenPaths);

                    // Read from the current user's Startup folder.
                    string userStartupFolder = Environment.GetFolderPath(
                        Environment.SpecialFolder.Startup);
                    ReadStartupFolder(userStartupFolder, startupList, seenPaths);
                });

                _logger.LogDebug($"Startup program collection complete. Found {startupList.Count} programs.");
            }
            catch (Exception ex)
            {
                _logger.LogError("Failed to collect startup programs.", ex);
            }

            return startupList;
        }

        /// <summary>
        /// Reads startup programs from a registry key and adds them to the list.
        /// </summary>
        /// <param name="key">The registry key to read from.</param>
        /// <param name="startupList">The list to add entries to.</param>
        /// <param name="seenPaths">Set of seen paths to avoid duplicates.</param>
        /// <param name="source">The source description for logging purposes.</param>
        private void ReadStartupRegKey(
            RegistryKey? key,
            List<ProcessInfo> startupList,
            HashSet<string> seenPaths,
            string source)
        {
            if (key == null)
                return;

            try
            {
                // Read all value names from the Run key.
                foreach (string valueName in key.GetValueNames())
                {
                    try
                    {
                        string? executablePath = key.GetValue(valueName)?.ToString();
                        if (string.IsNullOrWhiteSpace(executablePath))
                            continue;

                        // Skip duplicates that may appear in multiple registry locations.
                        if (!seenPaths.Add(executablePath))
                            continue;

                        var processInfo = new ProcessInfo
                        {
                            ProcessId = 0, // Startup programs are not running yet.
                            ProcessName = Path.GetFileNameWithoutExtension(executablePath) ?? valueName,
                            ExecutablePath = executablePath,
                            WorkingSetBytes = 0,
                            CpuUsagePercentage = 0,
                            ThreadCount = 0,
                            UserName = source.StartsWith("HKCU") ? Environment.UserName : "All Users",
                            CollectedAt = DateTime.UtcNow
                        };

                        startupList.Add(processInfo);
                    }
                    catch (Exception ex)
                    {
                        _logger.LogWarning($"Failed to read startup registry value '{valueName}'.", ex);
                    }
                }
            }
            catch (Exception ex)
            {
                _logger.LogWarning($"Failed to read startup registry key '{key.Name}'.", ex);
            }
            finally
            {
                key.Close();
            }
        }

        /// <summary>
        /// Reads shortcut files from a Startup folder and adds them to the list.
        /// </summary>
        /// <param name="folderPath">The startup folder path to scan.</param>
        /// <param name="startupList">The list to add entries to.</param>
        /// <param name="seenPaths">Set of seen paths to avoid duplicates.</param>
        private void ReadStartupFolder(
            string folderPath,
            List<ProcessInfo> startupList,
            HashSet<string> seenPaths)
        {
            if (!Directory.Exists(folderPath))
                return;

            try
            {
                // Scan all shortcut files (*.lnk) in the startup folder.
                foreach (string filePath in Directory.GetFiles(folderPath, "*.lnk"))
                {
                    try
                    {
                        // Shortcuts cannot be easily resolved in pure .NET without COM.
                        // Use the file name as the process name and path as reference.
                        string fileName = Path.GetFileNameWithoutExtension(filePath);

                        if (!seenPaths.Add(filePath))
                            continue;

                        var processInfo = new ProcessInfo
                        {
                            ProcessId = 0,
                            ProcessName = fileName,
                            ExecutablePath = filePath,
                            WorkingSetBytes = 0,
                            CpuUsagePercentage = 0,
                            ThreadCount = 0,
                            UserName = folderPath.Contains(Environment.GetFolderPath(
                                Environment.SpecialFolder.CommonStartup), StringComparison.OrdinalIgnoreCase)
                                ? "All Users"
                                : Environment.UserName,
                            CollectedAt = DateTime.UtcNow
                        };

                        startupList.Add(processInfo);
                    }
                    catch (Exception ex)
                    {
                        _logger.LogWarning($"Failed to read startup shortcut '{filePath}'.", ex);
                    }
                }
            }
            catch (Exception ex)
            {
                _logger.LogWarning($"Failed to read startup folder '{folderPath}'.", ex);
            }
        }
    }
}
