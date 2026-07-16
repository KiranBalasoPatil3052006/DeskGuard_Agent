/// <summary>
/// Represents the complete health payload sent to the backend API.
/// Aggregates all collected metrics into a single structure for transmission.
/// This is the primary data contract between the agent and the monitoring server.
/// </summary>
namespace DeskGuardAgent.Models
{
    public class HealthPayload
    {
        /// <summary>
        /// Gets or sets the unique identifier of the agent sending the payload.
        /// </summary>
        public string AgentId { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the tenant/organization identifier.
        /// </summary>
        public string TenantId { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the agent software version.
        /// </summary>
        public string AgentVersion { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the machine identifier (unique per device).
        /// </summary>
        public string MachineId { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the employee mobile number (from appsettings config).
        /// Used by backend dashboards for contact and alert notifications.
        /// </summary>
        public string EmployeeMobileNumber { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the timestamp when this payload was created.
        /// </summary>
        public DateTime Timestamp { get; set; } = DateTime.UtcNow;

        /// <summary>
        /// Gets or sets general system information.
        /// </summary>
        public SystemInfo? SystemInfo { get; set; }

        /// <summary>
        /// Gets or sets CPU metrics data.
        /// </summary>
        public CpuInfo? CpuInfo { get; set; }

        /// <summary>
        /// Gets or sets memory/RAM metrics data.
        /// </summary>
        public MemoryInfo? MemoryInfo { get; set; }

        /// <summary>
        /// Gets or sets the list of disk drive metrics.
        /// </summary>
        public List<DiskInfo>? DiskInfo { get; set; }

        /// <summary>
        /// Gets or sets battery metrics data (laptops only).
        /// </summary>
        public BatteryInfo? BatteryInfo { get; set; }

        /// <summary>
        /// Gets or sets the list of network adapter metrics.
        /// </summary>
        public List<NetworkInfo>? NetworkInfo { get; set; }

        /// <summary>
        /// Gets or sets the list of running processes.
        /// </summary>
        public List<ProcessInfo>? ProcessInfo { get; set; }

        /// <summary>
        /// Gets or sets the list of Windows service statuses.
        /// </summary>
        public List<ServiceInfo>? ServiceInfo { get; set; }

        /// <summary>
        /// Gets or sets the list of programs configured to start automatically.
        /// </summary>
        public List<ProcessInfo>? StartupProgramInfo { get; set; }

        /// <summary>
        /// Gets or sets antivirus protection status.
        /// </summary>
        public AntivirusInfo? AntivirusInfo { get; set; }

        /// <summary>
        /// Gets or sets Windows Firewall status.
        /// </summary>
        public FirewallInfo? FirewallInfo { get; set; }

        /// <summary>
        /// Gets or sets Windows Update status.
        /// </summary>
        public UpdateInfo? UpdateInfo { get; set; }

        /// <summary>
        /// Gets or sets the list of recent event log entries.
        /// </summary>
        public List<EventLogInfo>? EventLogInfo { get; set; }

        /// <summary>
        /// Gets or sets recent login activity collected from the Windows Security log.
        /// </summary>
        public List<EventLogInfo>? LoginActivityInfo { get; set; }

        /// <summary>
        /// Gets or sets recent USB activity collected from the Windows System log.
        /// </summary>
        public List<EventLogInfo>? UsbActivityInfo { get; set; }

        /// <summary>
        /// Gets or sets the current connected peripheral device snapshot.
        /// </summary>
        public List<PeripheralInfo>? PeripheralInfo { get; set; }
    }
}
