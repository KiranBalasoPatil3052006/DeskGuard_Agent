/// <summary>
/// Represents a snapshot of a single running process on the system.
/// Used for monitoring running applications and their resource consumption.
/// </summary>
namespace DeskGuardAgent.Models
{
    public class ProcessInfo
    {
        /// <summary>
        /// Gets or sets the process ID (PID) assigned by the operating system.
        /// </summary>
        public int ProcessId { get; set; }

        /// <summary>
        /// Gets or sets the process name without the file extension.
        /// Example: "chrome", "explorer", "cmd".
        /// </summary>
        public string ProcessName { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the full file path to the process executable.
        /// Example: "C:\Program Files\Google\Chrome\Application\chrome.exe".
        /// </summary>
        public string ExecutablePath { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the amount of private memory used by the process in bytes.
        /// </summary>
        public long WorkingSetBytes { get; set; }

        /// <summary>
        /// Gets or sets the CPU usage percentage for this process (0-100).
        /// This is a snapshot value and may not reflect total CPU usage accurately.
        /// </summary>
        public double CpuUsagePercentage { get; set; }

        /// <summary>
        /// Gets or sets the number of threads currently running in the process.
        /// </summary>
        public int ThreadCount { get; set; }

        /// <summary>
        /// Gets or sets the username of the account running the process.
        /// </summary>
        public string UserName { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the timestamp when the data was collected.
        /// </summary>
        public DateTime CollectedAt { get; set; } = DateTime.UtcNow;
    }
}
