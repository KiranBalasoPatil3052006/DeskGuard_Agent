/// <summary>
/// Implements API communication for sending collected data to the backend server.
/// Handles HTTP request creation, authentication, serialization, and error handling.
/// Integrates with RetryService for automatic retry of failed requests.
/// Integrates with OfflineQueueService for storing payloads when the API is unreachable.
/// </summary>
using System.Net.Http.Headers;
using System.Text;
using DeskGuardAgent.Configuration;
using DeskGuardAgent.Constants;
using DeskGuardAgent.Interfaces;
using DeskGuardAgent.Utilities;

namespace DeskGuardAgent.Services
{
    /// <summary>
    /// Service responsible for sending all collected data to the DeskGuard backend API.
    /// Implements IApiSenderService for standardized payload transmission.
    /// Uses HttpClient for HTTP communication with Bearer token authentication.
    /// </summary>
    public class ApiSenderService : IApiSenderService
    {
        private readonly HttpClient _httpClient;
        private readonly AgentSettings _settings;
        private readonly ILoggerService _logger;
        private readonly RetryService _retryService;
        private readonly IOfflineQueueService _offlineQueue;

        /// <summary>
        /// Initializes a new instance of the ApiSenderService class.
        /// </summary>
        /// <param name="httpClient">The HttpClient instance for HTTP communication.</param>
        /// <param name="settings">Agent settings containing API configuration.</param>
        /// <param name="logger">Service for logging API operations and errors.</param>
        /// <param name="retryService">Service for retrying failed API calls.</param>
        /// <param name="offlineQueue">Service for storing payloads when API is unreachable.</param>
        public ApiSenderService(
            HttpClient httpClient,
            AgentSettings settings,
            ILoggerService logger,
            RetryService retryService,
            IOfflineQueueService offlineQueue)
        {
            _httpClient = httpClient;
            _settings = settings;
            _logger = logger;
            _retryService = retryService;
            _offlineQueue = offlineQueue;

            // Set the default request timeout.
            _httpClient.Timeout = TimeSpan.FromSeconds(_settings.RequestTimeoutSeconds);

            // Set the default authorization header with the API key.
            if (!string.IsNullOrWhiteSpace(_settings.ApiKey))
            {
                _httpClient.DefaultRequestHeaders.Authorization =
                    new AuthenticationHeaderValue("Bearer", _settings.ApiKey);
            }

            // Set the default Accept header for JSON responses.
            _httpClient.DefaultRequestHeaders.Accept.Add(
                new MediaTypeWithQualityHeaderValue("application/json"));

            // Add the agent identifier header for server-side tracking.
            _httpClient.DefaultRequestHeaders.Add("X-Agent-Id", _settings.AgentId);
            _httpClient.DefaultRequestHeaders.Add("X-Agent-Version", AgentConstants.AgentVersion);
        }

        /// <summary>
        /// Sends a health payload to the backend health endpoint.
        /// Uses retry logic and falls back to offline queue on failure.
        /// </summary>
        /// <param name="payload">The health payload to send.</param>
        /// <returns>True if the payload was sent successfully, false otherwise.</returns>
        public async Task<bool> SendHealthPayloadAsync(object payload)
        {
            return await SendPayloadAsync(payload, ApiRoutes.HealthEndpoint, "HealthPayload");
        }

        /// <summary>
        /// Sends hardware inventory data to the hardware inventory endpoint.
        /// </summary>
        /// <param name="payload">The hardware inventory data to send.</param>
        /// <returns>True if the payload was sent successfully, false otherwise.</returns>
        public async Task<bool> SendHardwareInventoryAsync(object payload)
        {
            return await SendPayloadAsync(payload, ApiRoutes.HardwareInventoryEndpoint, "HardwareInventory");
        }

        /// <summary>
        /// Sends software inventory data to the software inventory endpoint.
        /// </summary>
        /// <param name="payload">The software inventory data to send.</param>
        /// <returns>True if the payload was sent successfully, false otherwise.</returns>
        public async Task<bool> SendSoftwareInventoryAsync(object payload)
        {
            return await SendPayloadAsync(payload, ApiRoutes.SoftwareInventoryEndpoint, "SoftwareInventory");
        }

        /// <summary>
        /// Sends event log data to the events endpoint.
        /// </summary>
        /// <param name="payload">The event log data to send.</param>
        /// <returns>True if the payload was sent successfully, false otherwise.</returns>
        public async Task<bool> SendEventLogAsync(object payload)
        {
            return await SendPayloadAsync(payload, ApiRoutes.EventLogEndpoint, "EventLog");
        }

        /// <summary>
        /// Sends security metrics data to the security endpoint.
        /// </summary>
        /// <param name="payload">The security data to send.</param>
        /// <returns>True if the payload was sent successfully, false otherwise.</returns>
        public async Task<bool> SendSecurityDataAsync(object payload)
        {
            return await SendPayloadAsync(payload, ApiRoutes.SecurityEndpoint, "SecurityData");
        }

        /// <summary>
        /// Sends a device event to the device events endpoint.
        /// </summary>
        /// <param name="payload">The device event data.</param>
        /// <returns>True if sent successfully, false otherwise.</returns>
        public async Task<bool> SendDeviceEventAsync(object payload)
        {
            return await SendPayloadAsync(payload, ApiRoutes.DeviceEventEndpoint, "DeviceEvent");
        }

        /// <summary>
        /// Sends a full device sync snapshot to the device sync endpoint.
        /// </summary>
        /// <param name="payload">The device sync payload.</param>
        /// <returns>True if sent successfully, false otherwise.</returns>
        public async Task<bool> SendDeviceSyncAsync(object payload)
        {
            return await SendPayloadAsync(payload, ApiRoutes.DeviceSyncEndpoint, "DeviceSync");
        }

        /// <summary>
        /// Sends change detection events to the changes endpoint.
        /// </summary>
        /// <param name="payload">The change detection payload.</param>
        /// <returns>True if sent successfully, false otherwise.</returns>
        public async Task<bool> SendChangeDetectionAsync(object payload)
        {
            return await SendPayloadAsync(payload, ApiRoutes.ChangeDetectionEndpoint, "ChangeDetection");
        }

        /// <summary>
        /// Generic method for sending a payload to a specified API endpoint.
        /// Serializes the payload to JSON, sends it via POST with retry logic,
        /// and falls back to offline queue if the API is unreachable.
        /// In offline mode, skips HTTP entirely and stores directly to queue.
        /// </summary>
        /// <param name="payload">The payload object to send.</param>
        /// <param name="endpoint">The API endpoint route.</param>
        /// <param name="payloadType">A descriptive name for the payload type (for logging).</param>
        /// <returns>True if the payload was sent successfully, false otherwise.</returns>
        private async Task<bool> SendPayloadAsync(object payload, string endpoint, string payloadType)
        {
            try
            {
                // Serialize the payload to compact JSON for network transmission.
                string jsonPayload = JsonHelper.SerializeCompact(payload);

                // In offline mode, skip HTTP entirely and store directly to queue.
                if (_settings.IsOfflineMode)
                {
                    _logger.LogDebug($"Offline mode: queuing {payloadType} for local storage.");
                    await _offlineQueue.EnqueueAsync(endpoint, jsonPayload);
                    return false;
                }

                // Use the retry service to send the request with exponential backoff.
                // StringContent is created inside the retry lambda because each retry
                // needs a fresh content instance (the stream is consumed after first send).
                bool success = await _retryService.ExecuteWithRetryAsync(async () =>
                {
                    var content = new StringContent(jsonPayload, Encoding.UTF8, "application/json");
                    HttpResponseMessage response = await _httpClient.PostAsync(endpoint, content);

                    // Check if the response indicates success.
                    if (response.IsSuccessStatusCode)
                    {
                        _logger.LogDebug($"Successfully sent {payloadType} to {endpoint}.");
                        return true;
                    }

                    // If the server returns an error, throw to trigger retry.
                    string responseBody = await response.Content.ReadAsStringAsync();
                    throw new HttpRequestException(
                        $"API returned {(int)response.StatusCode} {response.ReasonPhrase}. " +
                        $"Response: {responseBody}");
                }, $"Send{payloadType}");

                // If the retry succeeded, return true.
                if (success)
                {
                    return true;
                }

                // All retry attempts failed - save to offline queue.
                _logger.LogWarning($"Failed to send {payloadType} after retries. Queuing for offline storage.");
                await _offlineQueue.EnqueueAsync(endpoint, jsonPayload);

                return false;
            }
            catch
            {
                // Log the error (message only — RetryService already logged full stack trace).
                _logger.LogError($"Failed to send {payloadType} payload after all retries.");

                try
                {
                    // Attempt to save the serialized payload to the offline queue.
                    string jsonPayload = JsonHelper.SerializeCompact(payload);
                    await _offlineQueue.EnqueueAsync(endpoint, jsonPayload);
                }
                catch (Exception queueEx)
                {
                    _logger.LogError("Failed to queue payload for offline storage.", queueEx);
                }

                return false;
            }
        }
    }
}
