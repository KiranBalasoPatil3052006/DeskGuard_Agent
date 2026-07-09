/// <summary>
/// Collects recent entries from Windows Event Logs for monitoring and analysis.
/// Reads from System, Application, and Security event logs to capture errors,
/// warnings, and critical events that may indicate system health issues.
/// </summary>
using System.Diagnostics;
using DeskGuardAgent.Configuration;
using DeskGuardAgent.Constants;
using DeskGuardAgent.Interfaces;
using DeskGuardAgent.Models;

namespace DeskGuardAgent.Collectors
{
    /// <summary>
    /// Collector responsible for retrieving recent Windows Event Log entries.
    /// Reads from System, Application, and Security logs with configurable entry limits.
    /// Implements ICollector&lt;List&lt;EventLogInfo&gt;&gt; for standardized collection.
    /// </summary>
    public class EventLogCollector : ICollector<List<EventLogInfo>>
    {
        private readonly ILoggerService _logger;
        private readonly MonitoringSettings _settings;

        /// <summary>
        /// Initializes a new instance of the EventLogCollector class.
        /// </summary>
        /// <param name="logger">Service for logging collector operations and errors.</param>
        /// <param name="settings">Monitoring settings controlling event log collection parameters.</param>
        public EventLogCollector(ILoggerService logger, MonitoringSettings settings)
        {
            _logger = logger;
            _settings = settings;
        }

        /// <summary>
        /// Executes event log entry collection.
        /// Reads recent entries from System, Application, and Security logs.
        /// Never throws - all exceptions are caught and logged.
        /// </summary>
        /// <returns>A list of EventLogInfo objects with recent event log entries.</returns>
        public async Task<List<EventLogInfo>> CollectAsync()
        {
            _logger.LogDebug("Starting event log collection.");

            var eventList = new List<EventLogInfo>();

            try
            {
                // Calculate the time threshold - only collect events from the last 24 hours.
                DateTime timeThreshold = DateTime.Now.AddHours(-24);

                await Task.Run(() =>
                {
                    // Collect from System event log.
                    CollectFromEventLog(AgentConstants.SystemEventLogName, timeThreshold, eventList);

                    // Collect from Application event log.
                    CollectFromEventLog(AgentConstants.ApplicationEventLogName, timeThreshold, eventList);

                    // Collect from Security event log.
                    CollectFromEventLog(AgentConstants.SecurityEventLogName, timeThreshold, eventList);
                });

                // Sort by time generated, newest first.
                eventList = eventList.OrderByDescending(e => e.TimeGenerated).ToList();

                // Limit the total number of entries to prevent excessive payload size.
                if (eventList.Count > _settings.MaxEventLogEntries)
                {
                    eventList = eventList.Take(_settings.MaxEventLogEntries).ToList();
                }

                _logger.LogDebug($"Event log collection complete. Collected {eventList.Count} entries.");
            }
            catch (Exception ex)
            {
                _logger.LogError("Failed to collect event log entries.", ex);
            }

            return eventList;
        }

        /// <summary>
        /// Collects event log entries from a specific event log name.
        /// Filters for events within the specified time threshold.
        /// </summary>
        /// <param name="logName">The event log name to read from (e.g., "System", "Application").</param>
        /// <param name="timeThreshold">Only collect events newer than this time.</param>
        /// <param name="eventList">The list to add collected entries to.</param>
        private void CollectFromEventLog(string logName, DateTime timeThreshold, List<EventLogInfo> eventList)
        {
            try
            {
                // Open the specified event log.
                using (EventLog eventLog = new EventLog(logName))
                {
                    // Read entries in reverse chronological order (newest first).
                    int entriesToRead = Math.Min(eventLog.Entries.Count, _settings.MaxEventLogEntries);

                    for (int i = eventLog.Entries.Count - 1; i >= 0 && entriesToRead > 0; i--)
                    {
                        EventLogEntry entry = eventLog.Entries[i];

                        // Skip entries older than the time threshold.
                        if (entry.TimeGenerated < timeThreshold)
                            break;

                        // Map the entry type to a readable level string.
                        string level = entry.EntryType switch
                        {
                            EventLogEntryType.Error => "Error",
                            EventLogEntryType.Warning => "Warning",
                            EventLogEntryType.Information => "Information",
                            EventLogEntryType.SuccessAudit => "Success Audit",
                            EventLogEntryType.FailureAudit => "Failure Audit",
                            _ => entry.EntryType.ToString()
                        };

                        var eventInfo = new EventLogInfo
                        {
                            LogName = logName,
                            EventId = (int)entry.InstanceId,
                            Level = level,
                            Source = entry.Source,
                            Message = entry.Message?.Length > 500
                                ? entry.Message[..500] + "..."
                                : entry.Message ?? string.Empty,
                            TimeGenerated = entry.TimeGenerated,
                            UserName = entry.UserName ?? string.Empty,
                            MachineName = entry.MachineName,
                            CollectedAt = DateTime.UtcNow
                        };

                        eventList.Add(eventInfo);
                        entriesToRead--;
                    }
                }
            }
            catch (Exception ex)
            {
                // Log warning if event log is inaccessible (permissions, log not found, etc.).
                // Security log is expected to fail without admin privileges — log briefly.
                if (logName.Equals("Security", StringComparison.OrdinalIgnoreCase) &&
                    ex is System.Security.SecurityException)
                {
                    _logger.LogWarning($"Cannot read '{logName}' log — requires administrator privileges.");
                }
                else
                {
                    _logger.LogWarning($"Failed to read event log '{logName}'.", ex);
                }
            }
        }
    }
}
