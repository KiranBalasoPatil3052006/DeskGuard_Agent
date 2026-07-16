namespace DeskGuardAgent.Models
{
    public class DeviceEventInfo
    {
        public string DeviceName { get; set; } = string.Empty;
        public string DeviceType { get; set; } = string.Empty;
        public string Manufacturer { get; set; } = string.Empty;
        public string ConnectionType { get; set; } = string.Empty;
        public string EventType { get; set; } = string.Empty;
        public DateTime EventTime { get; set; } = DateTime.UtcNow;
        public string DeviceId { get; set; } = string.Empty;
    }
}
