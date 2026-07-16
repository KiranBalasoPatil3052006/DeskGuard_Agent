/// <summary>
/// Represents the status of a single Windows service.
/// Used for monitoring critical services that should be running on the endpoint.
/// </summary>
namespace DeskGuardAgent.Models
{
    public class ServiceInfo
    {
        /// <summary>
        /// Gets or sets the internal Windows service name.
        /// Example: "wuauserv", "Spooler", "MpsSvc".
        /// </summary>
        public string ServiceName { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the display name of the service.
        /// Example: "Windows Update", "Print Spooler", "Windows Firewall".
        /// </summary>
        public string DisplayName { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the current status of the service.
        /// Example: "Running", "Stopped", "Paused".
        /// </summary>
        public string Status { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the startup type of the service.
        /// Example: "Automatic", "Manual", "Disabled".
        /// </summary>
        public string StartType { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets whether the service is in the expected running state.
        /// True if a service with Automatic start type is currently Running.
        /// </summary>
        public bool IsRunning { get; set; }

        /// <summary>
        /// Gets or sets the timestamp when the data was collected.
        /// </summary>
        public DateTime CollectedAt { get; set; } = DateTime.UtcNow;
    }
}
