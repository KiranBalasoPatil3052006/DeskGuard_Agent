/// <summary>
/// Defines the contract for sending collected data to the backend API.
/// Implementations handle HTTP communication, authentication, serialization,
/// and error handling when transmitting payloads to the central monitoring server.
/// </summary>
namespace DeskGuardAgent.Interfaces
{
    /// <summary>
    /// Service interface for sending data payloads to the DeskGuard backend API.
    /// </summary>
    public interface IApiSenderService
    {
        /// <summary>
        /// Sends a health payload containing all collected metrics to the backend API.
        /// </summary>
        Task<bool> SendHealthPayloadAsync(object payload);

        /// <summary>
        /// Sends hardware inventory data to the dedicated hardware inventory endpoint.
        /// </summary>
        Task<bool> SendHardwareInventoryAsync(object payload);

        /// <summary>
        /// Sends software inventory data to the dedicated software inventory endpoint.
        /// </summary>
        Task<bool> SendSoftwareInventoryAsync(object payload);

        /// <summary>
        /// Sends a batch of event log entries to the events endpoint.
        /// </summary>
        Task<bool> SendEventLogAsync(object payload);

        /// <summary>
        /// Sends security-related metrics to the security endpoint.
        /// </summary>
        Task<bool> SendSecurityDataAsync(object payload);

        /// <summary>
        /// Sends a device event (connect/disconnect) to the device events endpoint.
        /// </summary>
        Task<bool> SendDeviceEventAsync(object payload);

        /// <summary>
        /// Sends a full device sync (current peripherals snapshot) to the device sync endpoint.
        /// </summary>
        Task<bool> SendDeviceSyncAsync(object payload);
    }
}
