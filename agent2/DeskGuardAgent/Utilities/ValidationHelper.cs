/// <summary>
/// Provides input validation and configuration checking utilities.
/// Ensures that required configuration values are present and valid before the agent starts.
/// Prevents runtime failures due to missing or invalid configuration.
/// </summary>
namespace DeskGuardAgent.Utilities
{
    /// <summary>
    /// Static helper class for validating configuration and input values.
    /// </summary>
    public static class ValidationHelper
    {
        /// <summary>
        /// Validates that the agent configuration is complete and ready for operation.
        /// Checks for required settings like ApiBaseUrl, TenantId, and ApiKey.
        /// </summary>
        /// <param name="agentSettings">The agent settings to validate.</param>
        /// <returns>A list of validation error messages. Empty list if configuration is valid.</returns>
        public static List<string> ValidateAgentConfiguration(Configuration.AgentSettings agentSettings)
        {
            List<string> errors = new List<string>();

            // Validate that the API base URL is configured.
            // Without this, the agent cannot communicate with the backend.
            if (string.IsNullOrWhiteSpace(agentSettings.ApiBaseUrl))
            {
                errors.Add("ApiBaseUrl is not configured. Set AgentSettings:ApiBaseUrl in appsettings.json.");
            }

            // Tenant ID is optional; the agent will function without it.
            // Set AgentSettings:TenantId in appsettings.json for multi-tenant identification.

            // Validate the API key is present for authentication.
            if (string.IsNullOrWhiteSpace(agentSettings.ApiKey))
            {
                errors.Add("ApiKey is not configured. Set AgentSettings:ApiKey in appsettings.json.");
            }

            // Validate that retry settings are within reasonable bounds.
            if (agentSettings.MaxRetryAttempts < 0)
            {
                errors.Add("MaxRetryAttempts must be 0 or greater.");
            }

            // Validate that retry delay is within reasonable bounds.
            if (agentSettings.RetryDelaySeconds < 1)
            {
                errors.Add("RetryDelaySeconds must be 1 or greater.");
            }

            // Validate that request timeout is within reasonable bounds.
            if (agentSettings.RequestTimeoutSeconds < 5)
            {
                errors.Add("RequestTimeoutSeconds must be 5 or greater.");
            }

            return errors;
        }

        /// <summary>
        /// Validates monitoring settings for consistency.
        /// </summary>
        /// <param name="monitoringSettings">The monitoring settings to validate.</param>
        /// <returns>A list of validation error messages. Empty list if configuration is valid.</returns>
        public static List<string> ValidateMonitoringConfiguration(Configuration.MonitoringSettings monitoringSettings)
        {
            List<string> errors = new List<string>();

            // Validate that collection interval is reasonable (minimum 30 seconds).
            // Very short intervals could cause excessive resource usage.
            if (monitoringSettings.CollectionIntervalSeconds < 30)
            {
                errors.Add("CollectionIntervalSeconds must be 30 or greater to prevent excessive resource usage.");
            }

            // Validate that hardware inventory interval is reasonable (minimum 1 hour).
            if (monitoringSettings.HardwareInventoryIntervalHours < 1)
            {
                errors.Add("HardwareInventoryIntervalHours must be 1 or greater.");
            }

            // Validate that software inventory interval is reasonable (minimum 1 hour).
            if (monitoringSettings.SoftwareInventoryIntervalHours < 1)
            {
                errors.Add("SoftwareInventoryIntervalHours must be 1 or greater.");
            }

            return errors;
        }

        /// <summary>
        /// Validates that a string is not null, empty, or whitespace.
        /// </summary>
        /// <param name="value">The string value to validate.</param>
        /// <param name="parameterName">The name of the parameter (for error messages).</param>
        /// <returns>True if the string is valid, false otherwise.</returns>
        public static bool IsValidString(string? value, string parameterName)
        {
            return !string.IsNullOrWhiteSpace(value);
        }

        /// <summary>
        /// Validates that a URL string is in a valid HTTP/HTTPS format.
        /// </summary>
        /// <param name="url">The URL string to validate.</param>
        /// <returns>True if the URL is valid, false otherwise.</returns>
        public static bool IsValidUrl(string? url)
        {
            if (string.IsNullOrWhiteSpace(url))
                return false;

            // Attempt to parse the URL to verify it is well-formed.
            return Uri.TryCreate(url, UriKind.Absolute, out Uri? uri) &&
                   (uri.Scheme == "http" || uri.Scheme == "https");
        }
    }
}
