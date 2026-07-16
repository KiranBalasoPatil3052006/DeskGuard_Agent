/// <summary>
/// Contains application-wide constants used throughout the DeskGuard Agent.
/// These values control agent identity, operational limits, and default behaviors.
/// Centralizing constants prevents magic values and improves maintainability.
/// </summary>
namespace DeskGuardAgent.Constants
{
    /// <summary>
    /// Defines constant values for agent identification, operational limits, and defaults.
    /// </summary>
    public static class AgentConstants
    {
        /// <summary>
        /// The name of the agent application.
        /// Used for logging, service registration, and self-identification.
        /// </summary>
        public const string AgentName = "DeskGuardAgent";

        /// <summary>
        /// The current version of the agent following semantic versioning.
        /// Sent with every payload to help the backend track agent versions across endpoints.
        /// </summary>
        public const string AgentVersion = "1.0.0";

        /// <summary>
        /// The name of the Windows service when installed.
        /// Used for ServiceController interactions and Windows Service Manager display.
        /// </summary>
        public const string ServiceName = "DeskGuardAgent";

        /// <summary>
        /// The display name shown in Windows Service Manager.
        /// More human-readable than the service name.
        /// </summary>
        public const string ServiceDisplayName = "DeskGuard Monitoring Agent";

        /// <summary>
        /// The description shown in Windows Service Manager.
        /// Explains the purpose of the service to administrators.
        /// </summary>
        public const string ServiceDescription = "Collects system health, inventory, and security metrics from endpoints for centralized monitoring.";

        /// <summary>
        /// Maximum size of the offline queue file in bytes before oldest entries are trimmed.
        /// Prevents the queue file from consuming excessive disk space.
        /// Default: 100 MB (104857600 bytes).
        /// </summary>
        public const long MaxQueueFileSizeBytes = 100 * 1024 * 1024;

        /// <summary>
        /// Maximum number of queued payloads in the offline queue.
        /// When exceeded, oldest payloads are discarded to make room for new ones.
        /// </summary>
        public const int MaxQueuedPayloads = 1000;

        /// <summary>
        /// The date/time format used throughout the agent for consistency.
        /// Uses ISO 8601 format with timezone offset for unambiguous timestamps.
        /// </summary>
        public const string DateTimeFormat = "yyyy-MM-ddTHH:mm:ss.fffzzz";

        /// <summary>
        /// The Serilog log template for consistent log message formatting.
        /// Includes timestamp, log level, source context, and message.
        /// </summary>
        public const string LogTemplate = "{Timestamp:yyyy-MM-dd HH:mm:ss.fff zzz} [{Level:u3}] {SourceContext} {Message:lj}{NewLine}{Exception}";

        /// <summary>
        /// WMI query namespace for standard Windows management instrumentation.
        /// Used by most WMI-based collectors.
        /// </summary>
        public const string WmiNamespace = @"root\cimv2";

        /// <summary>
        /// WMI query namespace for security-related instrumentation.
        /// Used by SecurityCollector and FirewallCollector.
        /// </summary>
        public const string WmiSecurityNamespace = @"root\SecurityCenter2";

        /// <summary>
        /// Default event log name to monitor for system-level events.
        /// </summary>
        public const string SystemEventLogName = "System";

        /// <summary>
        /// Default event log name to monitor for application-level events.
        /// </summary>
        public const string ApplicationEventLogName = "Application";

        /// <summary>
        /// Default event log name to monitor for security-related events.
        /// </summary>
        public const string SecurityEventLogName = "Security";

        /// <summary>
        /// Event ID for login success events in the Security event log.
        /// </summary>
        public const int LoginSuccessEventId = 4624;

        /// <summary>
        /// Event ID for login failure events in the Security event log.
        /// </summary>
        public const int LoginFailureEventId = 4625;

        /// <summary>
        /// Event ID for USB device connection events.
        /// </summary>
        public const int UsbDeviceConnectEventId = 2003;

        /// <summary>
        /// Event ID for USB device disconnection events.
        /// </summary>
        public const int UsbDeviceDisconnectEventId = 2004;

        /// <summary>
        /// Interval in minutes for periodic full peripheral device scans.
        /// Default: 30 minutes.
        /// </summary>
        public const int DeviceScanIntervalMinutes = 30;
    }
}
