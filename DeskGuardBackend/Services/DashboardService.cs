using System;
using System.Collections.Generic;
using System.Linq;
using System.Threading.Tasks;
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Caching.Memory;
using Microsoft.Extensions.Logging;
using DeskGuardBackend.Data;
using DeskGuardBackend.Entities;
using DeskGuardBackend.Services.Interfaces;

namespace DeskGuardBackend.Services
{
    public class DashboardService : IDashboardService
    {
        private readonly DeskGuardDbContext _dbContext;
        private readonly IMemoryCache _cache;
        private readonly ILogger<DashboardService> _logger;

        public DashboardService(
            DeskGuardDbContext dbContext,
            IMemoryCache cache,
            ILogger<DashboardService> logger)
        {
            _dbContext = dbContext;
            _cache = cache;
            _logger = logger;
        }

        public async Task<object> GetCompanyDashboardAsync(long companyId)
        {
            try
            {
                // Machine counts
                var totalMachines = await _dbContext.Machines.CountAsync(m => m.CompanyId == companyId);
                var onlineMachines = await _dbContext.Machines.CountAsync(m => m.CompanyId == companyId && m.IsOnline);

                // Alert counts (total & critical open/acknowledged)
                var totalAlerts = await _dbContext.Alerts.CountAsync(a => a.CompanyId == companyId);
                var criticalAlerts = await _dbContext.Alerts
                    .CountAsync(a => a.CompanyId == companyId && a.Severity == "critical" && (a.Status == "open" || a.Status == "acknowledged"));

                var cards = new
                {
                    total_machines = totalMachines,
                    online_count = onlineMachines,
                    offline_count = totalMachines - onlineMachines,
                    total_alerts = totalAlerts,
                    critical_alerts = criticalAlerts
                };

                var chartData = await GetCombinedChartDataInternalAsync(companyId, 24);
                var alertChart = await GetAlertChartDataAsync(companyId, 7);

                return new
                {
                    cards = cards,
                    cpu_chart = chartData.Cpu,
                    ram_chart = chartData.Ram,
                    alert_chart = alertChart
                };
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "DashboardService::GetCompanyDashboardAsync failed for company: {CompanyId}", companyId);
                throw;
            }
        }

        public async Task<object> GetEmployeeDashboardAsync(long userId)
        {
            try
            {
                var machine = await _dbContext.Machines
                    .Include(m => m.CurrentStatus)
                    .FirstOrDefaultAsync(m => m.UserId == userId);

                var recentAlerts = new List<Alert>();
                if (machine != null)
                {
                    recentAlerts = await _dbContext.Alerts
                        .Where(a => a.MachineId == machine.Id)
                        .OrderByDescending(a => a.CreatedAt)
                        .Take(10)
                        .ToListAsync();
                }

                return new
                {
                    machine = machine,
                    current_status = machine?.CurrentStatus,
                    recent_alerts = recentAlerts
                };
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "DashboardService::GetEmployeeDashboardAsync failed for user: {UserId}", userId);
                throw;
            }
        }

        public async Task<object> GetCpuChartDataAsync(long companyId, int hours = 24)
        {
            var data = await GetCombinedChartDataInternalAsync(companyId, hours);
            return data.Cpu;
        }

        public async Task<object> GetRamChartDataAsync(long companyId, int hours = 24)
        {
            var data = await GetCombinedChartDataInternalAsync(companyId, hours);
            return data.Ram;
        }

        public async Task<object> GetAlertChartDataAsync(long companyId, int days = 7)
        {
            try
            {
                var since = DateTime.UtcNow.AddDays(-days).Date;

                var alertCounts = await _dbContext.Alerts
                    .Where(a => a.CompanyId == companyId && a.CreatedAt >= since)
                    .GroupBy(a => new { Date = a.CreatedAt.Date, Severity = a.Severity })
                    .Select(g => new
                    {
                        g.Key.Date,
                        g.Key.Severity,
                        Count = g.Count()
                    })
                    .ToListAsync();

                var labels = new List<string>();
                var critical = new List<int>();
                var warning = new List<int>();
                var info = new List<int>();

                for (int i = 0; i <= days; i++)
                {
                    var date = DateTime.UtcNow.AddDays(-days + i).Date;
                    labels.Add(date.ToString("ddd MMM dd"));

                    critical.Add(alertCounts.FirstOrDefault(c => c.Date == date && c.Severity == "critical")?.Count ?? 0);
                    warning.Add(alertCounts.FirstOrDefault(c => c.Date == date && c.Severity == "warning")?.Count ?? 0);
                    info.Add(alertCounts.FirstOrDefault(c => c.Date == date && c.Severity == "info")?.Count ?? 0);
                }

                return new
                {
                    labels = labels,
                    datasets = new[]
                    {
                        new { label = "Critical", data = critical },
                        new { label = "Warning", data = warning },
                        new { label = "Info", data = info }
                    }
                };
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "DashboardService::GetAlertChartDataAsync failed for company: {CompanyId}", companyId);
                throw;
            }
        }

        private class CombinedChartData
        {
            public object Cpu { get; set; } = null!;
            public object Ram { get; set; } = null!;
        }

        private async Task<CombinedChartData> GetCombinedChartDataInternalAsync(long companyId, int hours)
        {
            var cacheKey = $"dashboard_chart_{companyId}_{hours}";

            if (_cache.TryGetValue<CombinedChartData>(cacheKey, out var cachedData) && cachedData != null)
            {
                return cachedData;
            }

            try
            {
                var since = DateTime.UtcNow.AddHours(-hours);

                // Group by Machine, Date and Hour to aggregate hourly
                var aggregatedData = await _dbContext.HealthLogs
                    .Where(h => h.CompanyId == companyId && h.CollectedAt >= since && (h.CpuPercentage != null || h.RamPercentage != null))
                    .GroupBy(h => new
                    {
                        h.MachineId,
                        Date = h.CollectedAt!.Value.Date,
                        Hour = h.CollectedAt!.Value.Hour
                    })
                    .Select(g => new
                    {
                        g.Key.MachineId,
                        g.Key.Date,
                        g.Key.Hour,
                        AvgCpu = g.Average(x => x.CpuPercentage),
                        AvgRam = g.Average(x => x.RamPercentage)
                    })
                    .OrderBy(x => x.Date)
                    .ThenBy(x => x.Hour)
                    .Take(500)
                    .ToListAsync();

                // Format hour buckets in memory
                var aggregated = aggregatedData.Select(x => new
                {
                    x.MachineId,
                    HourBucket = new DateTime(x.Date.Year, x.Date.Month, x.Date.Day, x.Hour, 0, 0, DateTimeKind.Utc),
                    x.AvgCpu,
                    x.AvgRam
                }).ToList();

                var machineIds = aggregated.Select(x => x.MachineId).Distinct().ToList();
                var machines = await _dbContext.Machines
                    .Where(m => machineIds.Contains(m.Id))
                    .ToDictionaryAsync(m => m.Id, m => m.Hostname ?? m.DeviceName ?? $"Machine {m.Id}");

                var cpuLabelsSet = new SortedSet<string>();
                var ramLabelsSet = new SortedSet<string>();

                var cpuMachineData = new Dictionary<string, Dictionary<string, float>>();
                var ramMachineData = new Dictionary<string, Dictionary<string, float>>();

                foreach (var row in aggregated)
                {
                    var timeLabel = row.HourBucket.ToString("HH:00");
                    machines.TryGetValue(row.MachineId, out var machineName);
                    machineName ??= "Unknown";

                    if (row.AvgCpu.HasValue)
                    {
                        cpuLabelsSet.Add(timeLabel);
                        if (!cpuMachineData.ContainsKey(machineName)) cpuMachineData[machineName] = new Dictionary<string, float>();
                        cpuMachineData[machineName][timeLabel] = (float)Math.Round(row.AvgCpu.Value, 1);
                    }

                    if (row.AvgRam.HasValue)
                    {
                        ramLabelsSet.Add(timeLabel);
                        if (!ramMachineData.ContainsKey(machineName)) ramMachineData[machineName] = new Dictionary<string, float>();
                        ramMachineData[machineName][timeLabel] = (float)Math.Round(row.AvgRam.Value, 1);
                    }
                }

                var cpuLabels = cpuLabelsSet.ToList();
                var ramLabels = ramLabelsSet.ToList();

                var buildDatasets = new Func<Dictionary<string, Dictionary<string, float>>, List<string>, string, object[]>((machineData, labels, suffix) =>
                {
                    return machineData.Select(kvp => new
                    {
                        label = $"{kvp.Key} {suffix}",
                        data = labels.Select(l => kvp.Value.TryGetValue(l, out var val) ? (float?)val : null).ToArray()
                    }).ToArray();
                });

                var result = new CombinedChartData
                {
                    Cpu = new
                    {
                        labels = cpuLabels,
                        datasets = cpuMachineData.Count > 0 ? buildDatasets(cpuMachineData, cpuLabels, "CPU %") : new object[] { new { label = "No Data", data = Array.Empty<float?>() } }
                    },
                    Ram = new
                    {
                        labels = ramLabels,
                        datasets = ramMachineData.Count > 0 ? buildDatasets(ramMachineData, ramLabels, "RAM %") : new object[] { new { label = "No Data", data = Array.Empty<float?>() } }
                    }
                };

                // Cache for 5 minutes
                _cache.Set(cacheKey, result, TimeSpan.FromMinutes(5));

                return result;
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "DashboardService::GetCombinedChartDataInternalAsync failed for company: {CompanyId}", companyId);
                throw;
            }
        }
    }
}
