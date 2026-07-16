/// <summary>
/// Represents a single entry from the Windows Event Log.
/// Used for monitoring system, application, and security events.
/// </summary>
namespace DeskGuardAgent.Models
{
    public class EventLogInfo
    {
        /// <summary>
        /// Gets or sets the event log name where this entry originated.
        /// Example: "System", "Application", "Security".
        /// </summary>
        public string LogName { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the event ID that identifies the type of event.
        /// Example: 4624 (login success), 4625 (login failure).
        /// </summary>
        public int EventId { get; set; }

        /// <summary>
        /// Gets or sets the event level/severity.
        /// Example: "Error", "Warning", "Information", "Critical".
        /// </summary>
        public string Level { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the source of the event (application or component that logged it).
        /// Example: "Service Control Manager", "Microsoft-Windows-Security-Auditing".
        /// </summary>
        public string Source { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the event message text.
        /// Contains the detailed description of what occurred.
        /// </summary>
        public string Message { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the timestamp when the event was logged.
        /// </summary>
        public DateTime TimeGenerated { get; set; }

        /// <summary>
        /// Gets or sets the user or account associated with the event, if any.
        /// </summary>
        public string UserName { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the machine name where the event occurred.
        /// </summary>
        public string MachineName { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the timestamp when the data was collected.
        /// </summary>
        public DateTime CollectedAt { get; set; } = DateTime.UtcNow;
    }
}
