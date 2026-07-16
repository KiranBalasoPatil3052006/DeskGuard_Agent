namespace DeskGuardAgent.Models
{
    public class PeripheralInfo
    {
        public string DeviceName { get; set; } = string.Empty;
        public string DeviceType { get; set; } = string.Empty;
        public string Manufacturer { get; set; } = string.Empty;
        public string ConnectionType { get; set; } = string.Empty;
        public string Status { get; set; } = "connected";
        public DateTime LastSeen { get; set; } = DateTime.UtcNow;
        public string DeviceId { get; set; } = string.Empty;
        public bool HasProblem { get; set; }
        public string? ProblemDescription { get; set; }
        public string? DriverVersion { get; set; }
    }
}
