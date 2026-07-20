using System;
using System.Collections.Generic;
using System.Linq;
using System.Text.Json;
using System.Threading.Tasks;
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Logging;
using DeskGuardBackend.Data;
using DeskGuardBackend.Entities;
using DeskGuardBackend.Exceptions;
using DeskGuardBackend.Services.Interfaces;
using DeskGuardBackend.Enums;

namespace DeskGuardBackend.Services
{
    public class AlertService : IAlertService
    {
        private readonly DeskGuardDbContext _dbContext;
        private readonly INotificationService _notificationService;
        private readonly IAuditLogService _auditLogService;
        private readonly ILogger<AlertService> _logger;

        public AlertService(
            DeskGuardDbContext dbContext,
            INotificationService notificationService,
            IAuditLogService auditLogService,
            ILogger<AlertService> logger)
        {
            _dbContext = dbContext;
            _notificationService = notificationService;
            _auditLogService = auditLogService;
            _logger = logger;
        }

        public async Task EvaluateMachineAlertsAsync(Machine machine, MachineCurrentStatus status)
        {
            try
            {
                var rules = await _dbContext.AlertRules
                    .Where(r => r.CompanyId == machine.CompanyId && r.IsEnabled)
                    .ToListAsync();

                foreach (var rule in rules)
                {
                    try
                    {
                        if (EvaluateRule(rule, status))
                        {
                            await CreateAlertFromRuleAsync(rule, machine, status);
                        }
                    }
                    catch (Exception ex)
                    {
                        _logger.LogWarning(ex, "AlertService::EvaluateMachineAlertsAsync - Rule evaluation failed for rule ID {RuleId}, machine {MachineId}", rule.Id, machine.Id);
                    }
                }
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "AlertService::EvaluateMachineAlertsAsync - Failed to evaluate alerts for machine: {MachineId}", machine.Id);
                throw new AlertGenerationException("Failed to evaluate alert rules for machine.", 500);
            }
        }

        public async Task<Alert> AcknowledgeAlertAsync(long alertId, long userId)
        {
            try
            {
                var alert = await _dbContext.Alerts
                    .Include(a => a.Machine)
                    .FirstOrDefaultAsync(a => a.Id == alertId);

                if (alert == null)
                {
                    throw new KeyNotFoundException($"Alert not found: {alertId}");
                }

                alert.Status = AlertStatus.Acknowledged.ToString().ToLowerInvariant();
                alert.AcknowledgedBy = userId;
                alert.AcknowledgedAt = DateTime.UtcNow;
                await _dbContext.SaveChangesAsync();

                await _auditLogService.LogAsync(
                    EventType.Acknowledge.ToString(),
                    $"Alert acknowledged: {alert.Title}",
                    user: await _dbContext.Users.FindAsync(userId),
                    machine: alert.Machine
                );

                _logger.LogInformation("Alert {AlertId} acknowledged by user {UserId}", alertId, userId);
                return alert;
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "AlertService::AcknowledgeAlertAsync failed for alert ID: {AlertId}", alertId);
                throw;
            }
        }

        public async Task<Alert> ResolveAlertAsync(long alertId, long userId, string? resolution)
        {
            try
            {
                var alert = await _dbContext.Alerts
                    .Include(a => a.Machine)
                    .FirstOrDefaultAsync(a => a.Id == alertId);

                if (alert == null)
                {
                    throw new KeyNotFoundException($"Alert not found: {alertId}");
                }

                var resolutionMetadata = new Dictionary<string, string>();
                if (alert.Metadata != null)
                {
                    try
                    {
                        resolutionMetadata = JsonSerializer.Deserialize<Dictionary<string, string>>(alert.Metadata) ?? new Dictionary<string, string>();
                    }
                    catch { }
                }

                if (resolution != null)
                {
                    resolutionMetadata["resolution"] = resolution;
                }

                alert.Status = AlertStatus.Resolved.ToString().ToLowerInvariant();
                alert.ResolvedBy = userId;
                alert.ResolvedAt = DateTime.UtcNow;
                alert.Metadata = JsonSerializer.Serialize(resolutionMetadata);
                await _dbContext.SaveChangesAsync();

                await _auditLogService.LogAsync(
                    EventType.Resolve.ToString(),
                    $"Alert resolved: {alert.Title} {(resolution != null ? " - " + resolution : "")}",
                    user: await _dbContext.Users.FindAsync(userId),
                    machine: alert.Machine
                );

                _logger.LogInformation("Alert {AlertId} resolved by user {UserId}", alertId, userId);
                return alert;
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "AlertService::ResolveAlertAsync failed for alert ID: {AlertId}", alertId);
                throw;
            }
        }

        public async Task<PaginatedResponseDto<Alert>> GetCompanyAlertsAsync(
            long companyId, 
            string? severity, 
            string? status, 
            int page, 
            int perPage)
        {
            try
            {
                var query = _dbContext.Alerts
                    .Include(a => a.Machine)
                    .Include(a => a.Acknowledger)
                    .Include(a => a.Resolver)
                    .Where(a => a.CompanyId == companyId);

                if (!string.IsNullOrEmpty(severity))
                {
                    query = query.Where(a => a.Severity == severity.ToLowerInvariant());
                }

                if (!string.IsNullOrEmpty(status))
                {
                    query = query.Where(a => a.Status == status.ToLowerInvariant());
                }

                var total = await query.CountAsync();
                perPage = Math.Min(Math.Max(1, perPage), 100);
                var lastPage = (int)Math.Ceiling((double)total / perPage);
                page = Math.Min(Math.Max(1, page), Math.Max(1, lastPage));

                var items = await query
                    .OrderByDescending(a => a.CreatedAt)
                    .Skip((page - 1) * perPage)
                    .Take(perPage)
                    .ToListAsync();

                return new PaginatedResponseDto<Alert>
                {
                    Data = items,
                    CurrentPage = page,
                    PerPage = perPage,
                    Total = total,
                    LastPage = lastPage
                };
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "AlertService::GetCompanyAlertsAsync failed for company: {CompanyId}", companyId);
                throw;
            }
        }

        public async Task<IEnumerable<Alert>> GetMachineAlertsAsync(long machineId)
        {
            return await _dbContext.Alerts
                .Include(a => a.Acknowledger)
                .Include(a => a.Resolver)
                .Where(a => a.MachineId == machineId)
                .OrderByDescending(a => a.CreatedAt)
                .ToListAsync();
        }

        public async Task<IEnumerable<Alert>> GetCriticalAlertsAsync(long companyId)
        {
            return await _dbContext.Alerts
                .Include(a => a.Machine)
                .Include(a => a.Acknowledger)
                .Include(a => a.Resolver)
                .Where(a => a.CompanyId == companyId && a.Severity == "critical" && (a.Status == "open" || a.Status == "acknowledged"))
                .OrderByDescending(a => a.CreatedAt)
                .ToListAsync();
        }

        public async Task<IEnumerable<AlertRule>> GetAlertRulesAsync(long companyId)
        {
            return await _dbContext.AlertRules
                .Where(r => r.CompanyId == companyId)
                .OrderBy(r => r.Name)
                .ToListAsync();
        }

        public async Task<AlertRule> UpdateAlertRuleAsync(long ruleId, IDictionary<string, object> data)
        {
            try
            {
                var rule = await _dbContext.AlertRules.FindAsync(ruleId);
                if (rule == null)
                {
                    throw new KeyNotFoundException($"Alert rule not found: {ruleId}");
                }

                if (data.TryGetValue("name", out var nameVal)) rule.Name = nameVal.ToString() ?? rule.Name;
                if (data.TryGetValue("is_enabled", out var enabledVal) && enabledVal is bool isEnabled) rule.IsEnabled = isEnabled;
                if (data.TryGetValue("severity", out var sevVal)) rule.Severity = sevVal.ToString() ?? rule.Severity;
                if (data.TryGetValue("threshold_value", out var threshVal) && decimal.TryParse(threshVal.ToString(), out var decVal)) rule.ThresholdValue = decVal;

                await _dbContext.SaveChangesAsync();

                await _auditLogService.LogAsync(
                    EventType.Update.ToString(),
                    $"Alert rule updated: {rule.Name}"
                );

                _logger.LogInformation("Alert rule {RuleId} updated successfully", ruleId);
                return rule;
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "AlertService::UpdateAlertRuleAsync failed for rule ID: {RuleId}", ruleId);
                throw;
            }
        }

        private static bool EvaluateRule(AlertRule rule, MachineCurrentStatus status)
        {
            var metricValue = GetMetricValue(rule.MetricType, status);
            if (metricValue == null) return false;

            var threshold = rule.ThresholdValue;

            return rule.Condition switch
            {
                ">" => metricValue > threshold,
                ">=" => metricValue >= threshold,
                "<" => metricValue < threshold,
                "<=" => metricValue <= threshold,
                "==" => metricValue == threshold,
                "!=" => metricValue != threshold,
                _ => false
            };
        }

        private static decimal? GetMetricValue(string metricType, MachineCurrentStatus status)
        {
            return metricType.ToLowerInvariant() switch
            {
                "cpu_percentage" => status.CpuPercentage,
                "cpu_temperature" => status.CpuTemperature,
                "ram_percentage" => status.RamPercentage,
                "disk_percentage" => status.DiskPercentage,
                "battery_percentage" => status.BatteryPercentage,
                _ => null
            };
        }

        private async Task CreateAlertFromRuleAsync(AlertRule rule, Machine machine, MachineCurrentStatus status)
        {
            try
            {
                var metricValue = GetMetricValue(rule.MetricType, status);
                var alertDescription = $"{rule.MetricType.Replace('_', ' ')} is {rule.Condition} {rule.ThresholdValue} (current: {metricValue})";

                var alertMetadata = new Dictionary<string, object>
                {
                    { "metric", rule.MetricType },
                    { "operator", rule.Condition },
                    { "threshold", rule.ThresholdValue },
                    { "current_value", metricValue ?? 0 }
                };

                var alert = new Alert
                {
                    CompanyId = machine.CompanyId ?? 0,
                    MachineId = machine.Id,
                    AlertRuleId = rule.Id,
                    Severity = rule.Severity,
                    Title = rule.Name,
                    Description = alertDescription,
                    Metadata = JsonSerializer.Serialize(alertMetadata),
                    Status = AlertStatus.Open.ToString().ToLowerInvariant()
                };

                await _dbContext.Alerts.AddAsync(alert);
                await _dbContext.SaveChangesAsync();

                await _auditLogService.LogAsync(
                    EventType.Create.ToString(),
                    $"Alert generated: {rule.Name} for machine: {machine.MachineUid}",
                    machine: machine,
                    newValues: alert
                );

                await _notificationService.SendAlertNotificationAsync(alert);

                if (rule.Severity == "critical" || rule.Severity == "warning")
                {
                    await _notificationService.SendEmailNotificationAsync(alert);
                }

                _logger.LogInformation("Alert created from rule: {AlertId} for machine {MachineId}", alert.Id, machine.Id);
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "AlertService::CreateAlertFromRuleAsync failed for rule: {RuleId}", rule.Id);
            }
        }
    }
}
