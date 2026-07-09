/// <summary>
/// Collects recent login activity information from the Windows Security Event Log.
/// Monitors successful logins (Event ID 4624) and failed login attempts (Event ID 4625)
/// for security auditing and threat detection purposes.
/// </summary>
using System.Diagnostics;
using DeskGuardAgent.Constants;
using DeskGuardAgent.Interfaces;
using DeskGuardAgent.Models;

namespace DeskGuardAgent.Collectors
{
    /// <summary>
    /// Collector responsible for retrieving login activity events from the Security log.
    /// Tracks both successful and failed login attempts with user and source information.
    /// Implements ICollector&lt;List&lt;EventLogInfo&gt;&gt; for standardized collection.
    /// </summary>
    public class LoginActivityCollector : ICollector<List<EventLogInfo>>
    {
        private readonly ILoggerService _logger;

        /// <summary>
        /// Initializes a new instance of the LoginActivityCollector class.
        /// </summary>
        /// <param name="logger">Service for logging collector operations and errors.</param>
        public LoginActivityCollector(ILoggerService logger)
        {
            _logger = logger;
        }

        /// <summary>
        /// Executes login activity collection from the Security event log.
        /// Focuses on login-related event IDs (4624 and 4625) within the last 24 hours.
        /// Never throws - all exceptions are caught and logged.
        /// </summary>
        /// <returns>A list of EventLogInfo objects for recent login events.</returns>
        public async Task<List<EventLogInfo>> CollectAsync()
        {
            _logger.LogDebug("Starting login activity collection.");

            var loginEvents = new List<EventLogInfo>();

            try
            {
                // Set the time window for the last 24 hours.
                DateTime timeThreshold = DateTime.Now.AddHours(-24);

                await Task.Run(() =>
                {
                    try
                    {
                        // Open the Security event log.
                        // Note: Reading the Security log requires elevated privileges.
                        using (EventLog securityLog = new EventLog(AgentConstants.SecurityEventLogName))
                        {
                            // Iterate through entries in reverse chronological order.
                            for (int i = securityLog.Entries.Count - 1; i >= 0; i--)
                            {
                                EventLogEntry entry = securityLog.Entries[i];

                                // Stop if we've gone past the time threshold.
                                if (entry.TimeGenerated < timeThreshold)
                                    break;

                                // Only process login-related event IDs.
                                if (entry.InstanceId != AgentConstants.LoginSuccessEventId &&
                                    entry.InstanceId != AgentConstants.LoginFailureEventId)
                                    continue;

                                string level = entry.EntryType switch
                                {
                                    EventLogEntryType.SuccessAudit => "Information",
                                    EventLogEntryType.FailureAudit => "Warning",
                                    _ => entry.EntryType.ToString()
                                };

                                var eventInfo = new EventLogInfo
                                {
                                    LogName = AgentConstants.SecurityEventLogName,
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

                                loginEvents.Add(eventInfo);
                            }
                        }
                    }
                    catch (Exception ex)
                    {
                        // Security log access requires admin rights on many systems.
                        _logger.LogWarning("Failed to read Security event log for login activity. " +
                            "Elevated privileges may be required.", ex);
                    }
                });

                _logger.LogDebug($"Login activity collection complete. Found {loginEvents.Count} events.");
            }
            catch (Exception ex)
            {
                _logger.LogError("Failed to collect login activity.", ex);
            }

            return loginEvents;
        }
    }
}
