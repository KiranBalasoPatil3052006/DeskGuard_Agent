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
    public class AlertProcessor : IPayloadProcessor
    {
        private readonly DeskGuardDbContext _dbContext;
        private readonly ILogger<AlertProcessor> _logger;

        public AlertProcessor(DeskGuardDbContext dbContext, ILogger<AlertProcessor> logger)
        {
            _dbContext = dbContext;
            _logger = logger;
        }

        public async Task ProcessAsync(Machine machine, JsonElement payload, HealthLog healthLog)
        {
            try
            {
                var cpuProp = payload.GetPropertyOrNull("cpu");
                var cpu = cpuProp?.ValueKind == JsonValueKind.Object ? cpuProp.Value : default;

                var memoryProp = payload.GetPropertyOrNull("memory");
                var memory = memoryProp?.ValueKind == JsonValueKind.Object ? memoryProp.Value : default;

                var disksProp = payload.GetPropertyOrNull("disks");
                var antivirusProp = payload.GetPropertyOrNull("antivirus");
                var antivirus = antivirusProp?.ValueKind == JsonValueKind.Object ? antivirusProp.Value : default;

                var firewallProp = payload.GetPropertyOrNull("firewall");
                var firewall = firewallProp?.ValueKind == JsonValueKind.Object ? firewallProp.Value : default;

                var cpuUsage = cpu.ValueKind == JsonValueKind.Object ? (cpu.GetDecimalProperty("usagePercentage") ?? cpu.GetDecimalProperty("usage_percentage")) : null;
                var memUsage = memory.ValueKind == JsonValueKind.Object ? (memory.GetDecimalProperty("usagePercentage") ?? memory.GetDecimalProperty("usage_percentage")) : null;
                var avEnabled = antivirus.ValueKind == JsonValueKind.Object ? (antivirus.GetBooleanProperty("isRealTimeProtectionEnabled") ?? antivirus.GetBooleanProperty("is_real_time_protection_enabled")) : null;
                var fwEnabled = firewall.ValueKind == JsonValueKind.Object ? (firewall.GetBooleanProperty("isEnabled") ?? firewall.GetBooleanProperty("is_enabled")) : null;

                if (cpuUsage.HasValue && cpuUsage.Value > 90)
                {
                    await CreateAlertAsync(machine, "critical", "High CPU Usage", $"CPU usage is {cpuUsage.Value}% on {machine.Hostname}.");
                }

                if (memUsage.HasValue && memUsage.Value > 90)
                {
                    await CreateAlertAsync(machine, "critical", "High Memory Usage", $"Memory usage is {memUsage.Value}% on {machine.Hostname}.");
                }

                if (disksProp.HasValue && disksProp.Value.ValueKind == JsonValueKind.Array)
                {
                    foreach (var disk in disksProp.Value.EnumerateArray())
                    {
                        var usagePercent = disk.GetDecimalProperty("usagePercentage") ?? disk.GetDecimalProperty("usage_percentage");
                        var driveName = disk.GetStringProperty("driveName") ?? disk.GetStringProperty("drive_letter") ?? "Unknown";

                        if (usagePercent.HasValue && usagePercent.Value > 95)
                        {
                            await CreateAlertAsync(machine, "warning", "Disk Almost Full", $"Drive {driveName} is {usagePercent.Value}% full on {machine.Hostname}.");
                        }
                    }
                }

                if (avEnabled.HasValue && !avEnabled.Value)
                {
                    await CreateAlertAsync(machine, "critical", "Antivirus Disabled", $"Antivirus real-time protection is disabled on {machine.Hostname}.");
                }

                if (fwEnabled.HasValue && !fwEnabled.Value)
                {
                    await CreateAlertAsync(machine, "warning", "Firewall Disabled", $"Windows Firewall is disabled on {machine.Hostname}.");
                }

                _logger.LogDebug("AlertProcessor: Alerts evaluated for machine {MachineId}", machine.Id);
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "AlertProcessor::ProcessAsync failed for machine {MachineId}", machine.Id);
            }
        }

        private async Task CreateAlertAsync(Machine machine, string severity, string title, string description)
        {
            try
            {
                var existing = await _dbContext.Alerts
                    .FirstOrDefaultAsync(a => a.MachineId == machine.Id && a.Title == title && a.Status == "open");

                if (existing != null) return;

                var alert = new Alert
                {
                    MachineId = machine.Id,
                    CompanyId = machine.CompanyId ?? 0,
                    Severity = severity,
                    Title = title,
                    Description = description,
                    Status = "open"
                };

                await _dbContext.Alerts.AddAsync(alert);
                await _dbContext.SaveChangesAsync();

                _logger.LogInformation("AlertProcessor: Open alert created - {Title} for machine {MachineId}", title, machine.Id);
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "AlertProcessor::CreateAlertAsync failed for machine {MachineId}, Alert: {Title}", machine.Id, title);
            }
        }
    }
}
