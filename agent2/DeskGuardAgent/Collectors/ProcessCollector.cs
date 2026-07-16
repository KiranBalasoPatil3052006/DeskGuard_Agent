/// <summary>
/// Collects information about running processes on the local machine.
/// Enumerates all active processes and captures their resource usage metrics.
/// Provides a snapshot of CPU, memory, and thread usage for each process.
/// </summary>
using System.Diagnostics;
using DeskGuardAgent.Interfaces;
using DeskGuardAgent.Models;
using Microsoft.Win32;

namespace DeskGuardAgent.Collectors
{
    /// <summary>
    /// Collector responsible for retrieving running process information including
    /// process name, PID, memory usage, CPU usage, thread count, and owner.
    /// Implements ICollector&lt;List&lt;ProcessInfo&gt;&gt; for standardized collection.
    /// </summary>
    public class ProcessCollector : ICollector<List<ProcessInfo>>
    {
        private readonly ILoggerService _logger;
        private static readonly Dictionary<int, (DateTime time, TimeSpan cpu)> _previousCpuSamples = new();
        private static readonly object _cpuLock = new();

        /// <summary>
        /// Initializes a new instance of the ProcessCollector class.
        /// </summary>
        /// <param name="logger">Service for logging collector operations and errors.</param>
        public ProcessCollector(ILoggerService logger)
        {
            _logger = logger;
        }

        /// <summary>
        /// Executes process information collection for all running processes.
        /// Enumerates processes and captures their resource usage as a snapshot.
        /// Never throws - all exceptions are caught and logged.
        /// </summary>
        /// <returns>A list of ProcessInfo objects for all running processes.</returns>
        public async Task<List<ProcessInfo>> CollectAsync()
        {
            _logger.LogDebug("Starting process metrics collection.");

            var processList = new List<ProcessInfo>();

            try
            {
                // Use Task.Run to avoid blocking the main thread during process enumeration.
                await Task.Run(() =>
                {
                    // Get all running processes from the system.
                    Process[] processes = Process.GetProcesses();

                    foreach (Process process in processes)
                    {
                        try
                        {
                            var processInfo = new ProcessInfo
                            {
                                ProcessId = process.Id,
                                ProcessName = process.ProcessName,
                                CollectedAt = DateTime.UtcNow
                            };

                            // Attempt to get process details that may require permissions.
                            try
                            {
                                processInfo.ExecutablePath = process.MainModule?.FileName ?? string.Empty;
                            }
                            catch
                            {
                                // Access denied for system processes, leave path empty.
                            }

                            try
                            {
                                // Get private memory working set in bytes.
                                processInfo.WorkingSetBytes = process.WorkingSet64;
                            }
                            catch
                            {
                                // Memory info may be inaccessible for some processes.
                            }

                            try
                            {
                                // Get the number of threads in the process.
                                processInfo.ThreadCount = process.Threads.Count;
                            }
                            catch
                            {
                                // Thread count may be inaccessible.
                            }

                            // Get the process owner username using WMI.
                            processInfo.UserName = GetProcessOwner(process.Id);

                            // CPU usage calculated via delta between consecutive samples.
                            // This gives a proper 0-100% value unlike the cumulative approach.
                            try
                            {
                                processInfo.CpuUsagePercentage = CalculateCpuDelta(process);
                            }
                            catch
                            {
                                // CPU time may be inaccessible.
                            }

                            processList.Add(processInfo);
                        }
                        catch (Exception ex)
                        {
                            // Log and continue with the next process.
                            _logger.LogWarning($"Failed to collect info for process ID {process.Id}.", ex);
                        }
                    }
                });

                _logger.LogDebug($"Process collection complete. Found {processList.Count} processes.");
            }
            catch (Exception ex)
            {
                _logger.LogError("Failed to collect process metrics.", ex);
            }

            return processList;
        }

        /// <summary>
        /// Calculates CPU usage percentage by comparing current TotalProcessorTime
        /// against the previous sample. Returns 0 on first sample for a process.
        /// </summary>
        private static double CalculateCpuDelta(Process process)
        {
            var now = DateTime.UtcNow;
            var currentCpu = process.TotalProcessorTime;

            lock (_cpuLock)
            {
                if (_previousCpuSamples.TryGetValue(process.Id, out var previous))
                {
                    var elapsedSec = (now - previous.time).TotalSeconds;
                    var cpuDeltaSec = (currentCpu - previous.cpu).TotalSeconds;
                    _previousCpuSamples[process.Id] = (now, currentCpu);

                    if (elapsedSec <= 0) return 0;
                    return Math.Round(cpuDeltaSec / elapsedSec * 100 / Environment.ProcessorCount, 2);
                }
                else
                {
                    _previousCpuSamples[process.Id] = (now, currentCpu);
                    return 0;
                }
            }
        }

        /// <summary>
        /// Retrieves the username of the account that owns the specified process.
        /// Uses WMI Win32_Process to get the owner information.
        /// </summary>
        /// <param name="processId">The process ID to look up.</param>
        /// <returns>The owner username, or "Unknown" if it cannot be determined.</returns>
        private static string GetProcessOwner(int processId)
        {
            try
            {
                using (var searcher = new System.Management.ManagementObjectSearcher(
                    $"SELECT * FROM Win32_Process WHERE ProcessId = {processId}"))
                {
                    using (var results = searcher.Get())
                    {
                        foreach (System.Management.ManagementObject obj in results)
                        {
                            // Invoke the GetOwner method on the WMI object.
                            object[] ownerArgs = new object[2];
                            obj.InvokeMethod("GetOwner", ownerArgs);

                            if (ownerArgs[0] != null)
                            {
                                string? user = ownerArgs[0]?.ToString();
                                string? domain = ownerArgs[1]?.ToString();
                                return $"{domain}\\{user}";
                            }
                        }
                    }
                }
            }
            catch
            {
                // Silently fail - owner info requires admin privileges for some processes.
            }

            return "Unknown";
        }
    }
}
