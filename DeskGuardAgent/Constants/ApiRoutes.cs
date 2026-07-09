/// <summary>
/// Contains constant API route definitions used by the DeskGuard Agent.
/// Centralizes all endpoint paths to avoid magic strings throughout the codebase.
/// All routes are relative to the ApiBaseUrl configured in AgentSettings.
/// </summary>
namespace DeskGuardAgent.Constants
{
    /// <summary>
    /// Defines the API endpoint routes for the DeskGuard backend.
    /// Each route corresponds to a specific data submission endpoint.
    /// </summary>
    public static class ApiRoutes
    {
        /// <summary>
        /// Endpoint for submitting the complete health payload.
        /// Receives all collected metrics in a single POST request.
        /// Route: /api/v1/health
        /// </summary>
        public const string HealthEndpoint = "/api/v1/health";

        /// <summary>
        /// Endpoint for submitting hardware inventory data.
        /// Separate endpoint because hardware inventory is collected less frequently.
        /// Route: /api/v1/inventory/hardware
        /// </summary>
        public const string HardwareInventoryEndpoint = "/api/v1/inventory/hardware";

        /// <summary>
        /// Endpoint for submitting software inventory data.
        /// Separate endpoint because software inventory is collected less frequently.
        /// Route: /api/v1/inventory/software
        /// </summary>
        public const string SoftwareInventoryEndpoint = "/api/v1/inventory/software";

        /// <summary>
        /// Endpoint for submitting event log data.
        /// Separated from health payload due to potentially large data size.
        /// Route: /api/v1/events
        /// </summary>
        public const string EventLogEndpoint = "/api/v1/events";

        /// <summary>
        /// Endpoint for submitting security-related metrics.
        /// Includes antivirus, firewall, and login activity data.
        /// Route: /api/v1/security
        /// </summary>
        public const string SecurityEndpoint = "/api/v1/security";

        /// <summary>
        /// Endpoint for submitting device events (connect/disconnect).
        /// Route: /api/v1/agent/device-events
        /// </summary>
        public const string DeviceEventEndpoint = "/api/v1/agent/device-events";

        /// <summary>
        /// Endpoint for submitting full device sync (current peripherals snapshot).
        /// Route: /api/v1/agent/device-sync
        /// </summary>
        public const string DeviceSyncEndpoint = "/api/v1/agent/device-sync";

    }
}
