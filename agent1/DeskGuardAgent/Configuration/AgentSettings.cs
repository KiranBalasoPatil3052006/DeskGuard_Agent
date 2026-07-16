/// <summary>
/// Represents the core configuration settings for the DeskGuard Agent.
/// This class maps to the "AgentSettings" section in appsettings.json.
/// It defines identity, communication, and operational parameters for the agent.
/// </summary>
namespace DeskGuardAgent.Configuration
{
    public class AgentSettings
    {
        /// <summary>
        /// Gets or sets the unique identifier for this agent instance.
        /// Used by the backend API to identify which endpoint is reporting data.
        /// This value is auto-generated on first run using MachineIdentifier.
        /// </summary>
        public string AgentId { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the tenant or organization identifier.
        /// Used for multi-tenant deployments where agents belong to different organizations.
        /// Must match the tenant ID configured in the backend API.
        /// </summary>
        public string TenantId { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the base URL of the backend API endpoint.
        /// All collected payloads will be sent to this URL.
        /// Example: https://api.deskguard.company.com
        /// </summary>
        public string ApiBaseUrl { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the API key used for authenticating with the backend API.
        /// This key is sent as a Bearer token in the Authorization header.
        /// Must be kept secure and never hardcoded in source.
        /// </summary>
        public string ApiKey { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the environment name (e.g., "Production", "Staging", "Development").
        /// Controls certain behaviors like log verbosity and API endpoints.
        /// </summary>
        public string Environment { get; set; } = "Production";

        /// <summary>
        /// Gets or sets the local storage path for offline queue data.
        /// When the agent cannot reach the API, payloads are saved here.
        /// Defaults to "Storage/queue.json" relative to the application base directory.
        /// </summary>
        public string StoragePath { get; set; } = "Storage/queue.json";

        /// <summary>
        /// Gets or sets the log directory path.
        /// Serilog will write log files to this location.
        /// Defaults to "Logs" relative to the application base directory.
        /// </summary>
        public string LogPath { get; set; } = "Logs";

        /// <summary>
        /// Gets or sets the maximum number of retry attempts for failed API calls.
        /// Prevents infinite retry loops and excessive resource consumption.
        /// Default value is 3 retries.
        /// </summary>
        public int MaxRetryAttempts { get; set; } = 3;

        /// <summary>
        /// Gets or sets the delay in seconds between retry attempts.
        /// Uses exponential backoff internally, this is the base delay.
        /// Default value is 10 seconds.
        /// </summary>
        public int RetryDelaySeconds { get; set; } = 10;

        /// <summary>
        /// Gets or sets the HTTP request timeout in seconds.
        /// Prevents the agent from hanging indefinitely on unresponsive APIs.
        /// Default value is 30 seconds.
        /// </summary>
        public int RequestTimeoutSeconds { get; set; } = 30;

        /// <summary>
        /// <summary>
        /// Gets or sets the employee mobile number associated with this machine.
        /// Sent to the backend and displayed on dashboards and in alert notifications.
        /// Configured per-machine by the deploying administrator.
        /// </summary>
        public string EmployeeMobileNumber { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets a human-friendly label for this machine.
        /// Used by administrators to identify machines in the dashboard.
        /// Falls back to the hostname if not set.
        /// </summary>
        public string MachineLabel { get; set; } = string.Empty;

        /// <summary>
        /// Indicates whether the agent is running in offline/test mode.
        /// True when ApiBaseUrl was never explicitly configured (empty string).
        /// In offline mode, data is collected and stored locally but never sent to an API.
        /// </summary>
        public bool IsOfflineMode => string.IsNullOrWhiteSpace(ApiBaseUrl);
    }
}
