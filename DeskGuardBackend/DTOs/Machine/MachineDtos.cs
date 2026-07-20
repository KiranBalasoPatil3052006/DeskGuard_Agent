using System.Text.Json.Serialization;

namespace DeskGuardBackend.DTOs.Machine
{
    public class MachineRegistrationDto
    {
        [JsonPropertyName("machine_uid")]
        public string MachineUid { get; set; } = string.Empty;

        [JsonPropertyName("activation_token")]
        public string ActivationToken { get; set; } = string.Empty;

        [JsonPropertyName("hostname")]
        public string? Hostname { get; set; }

        [JsonPropertyName("operating_system")]
        public string? OperatingSystem { get; set; }
    }

    public class MachineResponseDto
    {
        [JsonPropertyName("id")]
        public long Id { get; set; }

        [JsonPropertyName("company_id")]
        public long? CompanyId { get; set; }

        [JsonPropertyName("user_id")]
        public long? UserId { get; set; }

        [JsonPropertyName("machine_uid")]
        public string MachineUid { get; set; } = string.Empty;

        [JsonPropertyName("hostname")]
        public string? Hostname { get; set; }

        [JsonPropertyName("device_name")]
        public string? DeviceName { get; set; }

        [JsonPropertyName("operating_system")]
        public string? OperatingSystem { get; set; }

        [JsonPropertyName("os_version")]
        public string? OsVersion { get; set; }

        [JsonPropertyName("manufacturer")]
        public string? Manufacturer { get; set; }

        [JsonPropertyName("model")]
        public string? Model { get; set; }

        [JsonPropertyName("serial_number")]
        public string? SerialNumber { get; set; }

        [JsonPropertyName("bios_version")]
        public string? BiosVersion { get; set; }

        [JsonPropertyName("processor")]
        public string? Processor { get; set; }

        [JsonPropertyName("ram_gb")]
        public int? RamGb { get; set; }

        [JsonPropertyName("is_online")]
        public bool IsOnline { get; set; }

        [JsonPropertyName("last_heartbeat_at")]
        public DateTime? LastHeartbeatAt { get; set; }

        [JsonPropertyName("is_active")]
        public bool IsActive { get; set; }

        [JsonPropertyName("employee_mobile_number")]
        public string? EmployeeMobileNumber { get; set; }

        [JsonPropertyName("current_status")]
        public MachineCurrentStatusDto? CurrentStatus { get; set; }
    }

    public class MachineCurrentStatusDto
    {
        [JsonPropertyName("cpu_percentage")]
        public decimal? CpuPercentage { get; set; }

        [JsonPropertyName("cpu_temperature")]
        public decimal? CpuTemperature { get; set; }

        [JsonPropertyName("ram_percentage")]
        public decimal? RamPercentage { get; set; }

        [JsonPropertyName("disk_percentage")]
        public decimal? DiskPercentage { get; set; }

        [JsonPropertyName("online_status")]
        public bool OnlineStatus { get; set; }
    }
}
