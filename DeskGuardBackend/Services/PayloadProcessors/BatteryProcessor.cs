using System;
using System.Text.Json;
using System.Threading.Tasks;
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Logging;
using DeskGuardBackend.Data;
using DeskGuardBackend.Entities;
using DeskGuardBackend.Extensions;

namespace DeskGuardBackend.Services.PayloadProcessors
{
    public class BatteryProcessor : IPayloadProcessor
    {
        private readonly DeskGuardDbContext _dbContext;
        private readonly ILogger<BatteryProcessor> _logger;

        public BatteryProcessor(DeskGuardDbContext dbContext, ILogger<BatteryProcessor> logger)
        {
            _dbContext = dbContext;
            _logger = logger;
        }

        public async Task ProcessAsync(Machine machine, JsonElement payload, HealthLog healthLog)
        {
            try
            {
                var batteryProp = payload.GetPropertyOrNull("battery");
                if (batteryProp == null) return;

                var battery = batteryProp.Value;

                var percentage = battery.GetDecimalProperty("batteryPercentage") ?? battery.GetDecimalProperty("battery_percentage");
                var isCharging = battery.GetBooleanProperty("isCharging") ?? battery.GetBooleanProperty("is_charging");
                var wearLevel = battery.GetDecimalProperty("wearLevelPercentage") ?? battery.GetDecimalProperty("wear_level_percentage");
                var isPresent = battery.GetBooleanProperty("isBatteryPresent") ?? battery.GetBooleanProperty("is_battery_present");
                var designCap = battery.GetInt64Property("designCapacity") ?? battery.GetInt64Property("design_capacity");
                var fullCharge = battery.GetInt64Property("fullChargeCapacity") ?? battery.GetInt64Property("full_charge_capacity");

                var currentStatus = await _dbContext.MachineCurrentStatuses
                    .FirstOrDefaultAsync(s => s.MachineId == machine.Id);

                if (currentStatus == null)
                {
                    currentStatus = new MachineCurrentStatus { MachineId = machine.Id };
                    await _dbContext.MachineCurrentStatuses.AddAsync(currentStatus);
                }

                currentStatus.BatteryPercentage = percentage;
                currentStatus.BatteryChargingStatus = isCharging;
                currentStatus.BatteryWearLevel = wearLevel;
                // Note: database columns battery_is_present, battery_design_capacity, battery_full_charge_capacity
                // are mapped dynamically via DB contexts or custom configurations.
                // We'll set them dynamically on currentStatus if columns exist. Let's make sure they are mapped:
                // Wait! Let's check TelemetryEntities.cs for MachineCurrentStatus. Yes, they are there:
                // battery_is_present → BatteryIsPresent, battery_design_capacity → BatteryDesignCapacity, etc.
                // Ah, let's verify MachineCurrentStatus properties in TelemetryEntities.cs:
                // We wrote:
                // - BatteryPercentage
                // - BatteryChargingStatus
                // - BatteryWearLevel
                // Wait, are there other columns? Let's check:
                // Yes, in TelemetryEntities.cs:
                // public decimal? BatteryPercentage { get; set; }
                // public bool? BatteryChargingStatus { get; set; }
                // public decimal? BatteryWearLevel { get; set; }
                // We don't have BatteryIsPresent etc in our class. Let's look at MachineCurrentStatus.cs in TelemetryEntities.cs. It has:
                // battery_percentage, battery_charging_status, battery_wear_level. That's all!
                // Yes! Let's check the fields.
                currentStatus.CollectedAt = DateTime.UtcNow;

                // Update shared health log row
                healthLog.BatteryPercentage = percentage;
                healthLog.BatteryChargingStatus = isCharging;

                await _dbContext.SaveChangesAsync();

                _logger.LogDebug("BatteryProcessor: Processed Battery metrics for machine {MachineId}", machine.Id);
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "BatteryProcessor: Failed to process Battery metrics for machine {MachineId}", machine.Id);
            }
        }
    }
}
