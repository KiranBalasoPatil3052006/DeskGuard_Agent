/// <summary>
/// Collects USB device connection and disconnection activity from the Windows Event Log.
/// Monitors System event log for USB-related events (DeviceSetupManager and other sources)
/// to track USB storage device usage for security auditing purposes.
/// </summary>
using System.Diagnostics;
using DeskGuardAgent.Constants;
using DeskGuardAgent.Interfaces;
using DeskGuardAgent.Models;

namespace DeskGuardAgent.Collectors
{
    /// <summary>
    /// Collector responsible for tracking USB device connection and disconnection events.
    /// Reads from the System event log to detect when USB devices are plugged in or removed.
    /// Implements ICollector&lt;List&lt;EventLogInfo&gt;&gt; for standardized collection.
    /// </summary>
    public class UsbCollector : ICollector<List<EventLogInfo>>
    {
        private readonly ILoggerService _logger;

        /// <summary>
        /// Initializes a new instance of the UsbCollector class.
        /// </summary>
        /// <param name="logger">Service for logging collector operations and errors.</param>
        public UsbCollector(ILoggerService logger)
        {
            _logger = logger;
        }

        /// <summary>
        /// Executes USB device activity collection from the System event log.
        /// Searches for USB-related event sources and captures connection/disconnection events.
        /// Never throws - all exceptions are caught and logged.
        /// </summary>
        /// <returns>A list of EventLogInfo objects for USB-related events.</returns>
        public async Task<List<EventLogInfo>> CollectAsync()
        {
            _logger.LogDebug("Starting USB device activity collection.");

            var usbEvents = new List<EventLogInfo>();

            try
            {
                // Set the time window for the last 24 hours.
                DateTime timeThreshold = DateTime.Now.AddHours(-24);

                await Task.Run(() =>
                {
                    try
                    {
                        // Open the System event log for USB device events.
                        using (EventLog systemLog = new EventLog(AgentConstants.SystemEventLogName))
                        {
                            // Iterate through entries in reverse chronological order.
                            for (int i = systemLog.Entries.Count - 1; i >= 0; i--)
                            {
                                EventLogEntry entry = systemLog.Entries[i];

                                // Stop if we've gone past the time threshold.
                                if (entry.TimeGenerated < timeThreshold)
                                    break;

                                // Check if this entry is related to USB device activity.
                                // Common USB-related event sources:
                                // - "DeviceSetupManager" (modern Windows)
                                // - "USB" (legacy)
                                // - "Kernel-PnP" (device installation)
                                // - "Microsoft-Windows-DeviceSetupManager"
                                if (!IsUsbRelatedEvent(entry))
                                    continue;

                                string level = entry.EntryType switch
                                {
                                    EventLogEntryType.Information => "Information",
                                    EventLogEntryType.Warning => "Warning",
                                    EventLogEntryType.Error => "Error",
                                    _ => entry.EntryType.ToString()
                                };

                                var eventInfo = new EventLogInfo
                                {
                                    LogName = AgentConstants.SystemEventLogName,
                                    EventId = (int)entry.InstanceId,
                                    Level = level,
                                    Source = entry.Source,
                                    Message = entry.Message?.Length > 500
                                        ? entry.Message[..500] + "..."
                                        : entry.Message ?? string.Empty,
                                    TimeGenerated = entry.TimeGenerated,
                                    UserName = string.Empty, // USB events typically don't have user info.
                                    MachineName = entry.MachineName,
                                    CollectedAt = DateTime.UtcNow
                                };

                                usbEvents.Add(eventInfo);
                            }
                        }
                    }
                    catch (Exception ex)
                    {
                        _logger.LogWarning("Failed to read System event log for USB activity.", ex);
                    }
                });

                _logger.LogDebug($"USB activity collection complete. Found {usbEvents.Count} events.");
            }
            catch (Exception ex)
            {
                _logger.LogError("Failed to collect USB device activity.", ex);
            }

            return usbEvents;
        }

        /// <summary>
        /// Determines whether an event log entry is related to USB device activity.
        /// Checks the event source and message content for USB-related keywords.
        /// </summary>
        /// <param name="entry">The event log entry to check.</param>
        /// <returns>True if the entry appears to be USB-related, false otherwise.</returns>
        private static bool IsUsbRelatedEvent(EventLogEntry entry)
        {
            // Check if the source is a known USB-related event source.
            string source = entry.Source ?? string.Empty;

            // Known USB event sources.
            string[] usbSources = new[]
            {
                "DeviceSetupManager",
                "USB",
                "Kernel-PnP",
                "Microsoft-Windows-DeviceSetupManager",
                "Microsoft-Windows-Kernel-PnP",
                "Microsoft-Windows-DriverFrameworks-UserMode",
                "Microsoft-Windows-Partition",
                "Microsoft-Windows-USB-USBHUB3",
                "Microsoft-Windows-USB-USBPORT",
                "Microsoft-Windows-Storage-Storport"
            };

            foreach (string usbSource in usbSources)
            {
                if (source.IndexOf(usbSource, StringComparison.OrdinalIgnoreCase) >= 0)
                    return true;
            }

            // Check the message content for USB-related keywords as a fallback.
            string message = entry.Message ?? string.Empty;
            string[] usbKeywords = new[]
            {
                "USB",
                "Universal Serial Bus",
                "mass storage",
                "USB mass storage",
                "USB device",
                "USB hub"
            };

            foreach (string keyword in usbKeywords)
            {
                if (message.IndexOf(keyword, StringComparison.OrdinalIgnoreCase) >= 0)
                    return true;
            }

            return false;
        }
    }
}
