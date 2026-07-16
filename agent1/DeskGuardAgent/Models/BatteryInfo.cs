/// <summary>
/// Represents battery status and health metrics for laptop/portable devices.
/// Includes charge level, charging status, and battery health estimates.
/// </summary>
namespace DeskGuardAgent.Models
{
    public class BatteryInfo
    {
        /// <summary>
        /// Gets or sets whether a battery is present on the system.
        /// Desktop systems without UPS may return false.
        /// </summary>
        public bool IsBatteryPresent { get; set; }

        /// <summary>
        /// Gets or sets the current battery charge percentage (0-100).
        /// </summary>
        public int BatteryPercentage { get; set; }

        /// <summary>
        /// Gets or sets whether the battery is currently charging.
        /// </summary>
        public bool IsCharging { get; set; }

        /// <summary>
        /// Gets or sets the battery estimated remaining life in seconds.
        /// Null if the estimate is unavailable.
        /// </summary>
        public int? EstimatedRunTimeSeconds { get; set; }

        /// <summary>
        /// Gets or sets the battery chemistry type (e.g., "Li-Ion", "NiMH").
        /// </summary>
        public string Chemistry { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the battery design capacity in milliwatt-hours (mWh).
        /// </summary>
        public int? DesignCapacity { get; set; }

        /// <summary>
        /// Gets or sets the battery full charge capacity in milliwatt-hours (mWh).
        /// May differ from design capacity due to battery wear.
        /// </summary>
        public int? FullChargeCapacity { get; set; }

        /// <summary>
        /// Gets or sets the battery wear level percentage (0-100).
        /// Calculated as (1 - FullChargeCapacity / DesignCapacity) * 100.
        /// Higher values indicate more battery degradation.
        /// </summary>
        public double? WearLevelPercentage { get; set; }

        /// <summary>
        /// Gets or sets the timestamp when the data was collected.
        /// </summary>
        public DateTime CollectedAt { get; set; } = DateTime.UtcNow;
    }
}
