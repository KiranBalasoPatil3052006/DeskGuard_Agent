/// <summary>
/// Represents CPU performance and health metrics collected from the local machine.
/// Includes processor utilization, temperature, and related diagnostic data.
/// </summary>
namespace DeskGuardAgent.Models
{
    public class CpuInfo
    {
        /// <summary>
        /// Gets or sets the current CPU utilization percentage (0-100).
        /// Represents the percentage of non-idle processor time.
        /// </summary>
        public double UsagePercentage { get; set; }

        /// <summary>
        /// Gets or sets the CPU temperature in degrees Celsius.
        /// Requires LibreHardwareMonitorLib or WMI access to thermal sensors.
        /// Null if temperature data is unavailable.
        /// </summary>
        public double? TemperatureCelsius { get; set; }

        /// <summary>
        /// Gets or sets the name of the CPU processor.
        /// Example: "Intel(R) Core(TM) i7-10700K CPU @ 3.80GHz"
        /// </summary>
        public string ProcessorName { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the number of logical processors (cores + hyperthreading).
        /// </summary>
        public int NumberOfLogicalProcessors { get; set; }

        /// <summary>
        /// Gets or sets the current clock speed in MHz.
        /// </summary>
        public double? CurrentClockSpeedMHz { get; set; }

        /// <summary>
        /// Gets or sets the maximum clock speed in MHz as reported by the hardware.
        /// </summary>
        public double? MaxClockSpeedMHz { get; set; }

        /// <summary>
        /// Gets or sets the timestamp when the data was collected.
        /// </summary>
        public DateTime CollectedAt { get; set; } = DateTime.UtcNow;
    }
}
