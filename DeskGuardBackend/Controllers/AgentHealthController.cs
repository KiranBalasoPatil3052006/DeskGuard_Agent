using System;
using System.Collections.Generic;
using System.Text.Json;
using System.Threading.Tasks;
using Microsoft.AspNetCore.Mvc;
using DeskGuardBackend.DTOs.Common;
using DeskGuardBackend.Entities;
using DeskGuardBackend.Data;
using DeskGuardBackend.Extensions;
using DeskGuardBackend.Services.Interfaces;
using Microsoft.Extensions.Logging;
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Configuration;

namespace DeskGuardBackend.Controllers
{
    [ApiController]
    [Route("api/v1/health")]
    public class AgentHealthController : ControllerBase
    {
        private readonly DeskGuardDbContext _dbContext;
        private readonly IPayloadProcessorService _payloadProcessorService;
        private readonly IConfiguration _configuration;
        private readonly ILogger<AgentHealthController> _logger;

        public AgentHealthController(
            DeskGuardDbContext dbContext,
            IPayloadProcessorService payloadProcessorService,
            IConfiguration configuration,
            ILogger<AgentHealthController> logger)
        {
            _dbContext = dbContext;
            _payloadProcessorService = payloadProcessorService;
            _configuration = configuration;
            _logger = logger;
        }

        [HttpPost]
        public async Task<IActionResult> HandleHealthPayload([FromBody] JsonElement rawPayload)
        {
            try
            {
                var machineUid = ResolveMachineUid(rawPayload);
                if (string.IsNullOrEmpty(machineUid))
                {
                    return UnprocessableEntity(ApiResponse.Fail("Machine identifier is required."));
                }

                var companyId = await GetOrCreateCompanyIdAsync();

                var systemInfoProp = rawPayload.GetPropertyOrNull("systemInfo");
                var systemInfo = systemInfoProp?.ValueKind == JsonValueKind.Object ? systemInfoProp.Value : default;

                var computerName = rawPayload.GetStringProperty("computerName") ?? systemInfo.GetStringProperty("computerName");

                var machine = await _dbContext.Machines
                    .FirstOrDefaultAsync(m => m.MachineUid == machineUid);

                if (machine == null)
                {
                    machine = new Machine
                    {
                        MachineUid = machineUid,
                        CompanyId = companyId,
                        Hostname = computerName,
                        DeviceName = computerName,
                        OperatingSystem = systemInfo.ValueKind == JsonValueKind.Object ? systemInfo.GetStringProperty("operatingSystem") : null,
                        IsOnline = true,
                        IsActive = true,
                        LastHeartbeatAt = DateTime.UtcNow
                    };
                    await _dbContext.Machines.AddAsync(machine);
                    await _dbContext.SaveChangesAsync();
                }

                // Log raw payload
                var rawLog = new RawPayloadLog
                {
                    MachineId = machine.Id,
                    MachineUid = machineUid,
                    Payload = JsonSerializer.Serialize(rawPayload),
                    SourceIp = HttpContext.Connection.RemoteIpAddress?.ToString(),
                    ReceivedAt = DateTime.UtcNow
                };
                await _dbContext.RawPayloadLogs.AddAsync(rawLog);
                await _dbContext.SaveChangesAsync();

                // Normalise the payload format to the structure expected by section processors
                var normalizedPayload = NormalisePayload(rawPayload);

                using var doc = JsonDocument.Parse(JsonSerializer.Serialize(normalizedPayload));
                await _payloadProcessorService.ProcessAsync(machine, doc.RootElement);

                _logger.LogInformation("AgentHealthController: Health payload processed for machine {MachineId}", machine.Id);
                return Ok(ApiResponse.Ok("Health data processed successfully."));
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "AgentHealthController: Failed to process health payload");
                return StatusCode(500, ApiResponse.Fail("Failed to process health data."));
            }
        }

        private string ResolveMachineUid(JsonElement payload)
        {
            var candidates = new[]
            {
                payload.GetStringProperty("machineId"),
                payload.GetStringProperty("machine_uid"),
                payload.GetStringProperty("machineUid"),
                payload.GetStringProperty("agentId"),
                Request.Headers["X-Agent-Id"].ToString()
            };

            foreach (var candidate in candidates)
            {
                if (!string.IsNullOrEmpty(candidate)) return candidate;
            }

            return string.Empty;
        }

        private async Task<long> GetOrCreateCompanyIdAsync()
        {
            var preferredId = _configuration.GetValue<long>("AgentSettings:DefaultCompanyId");
            if (preferredId > 0)
            {
                var company = await _dbContext.Companies.FindAsync(preferredId);
                if (company != null) return company.Id;
            }

            var firstCompany = await _dbContext.Companies.FirstOrDefaultAsync();
            if (firstCompany != null) return firstCompany.Id;

            var newCompany = new Company
            {
                Name = "Local Test Company",
                IsActive = true
            };
            await _dbContext.Companies.AddAsync(newCompany);
            await _dbContext.SaveChangesAsync();

            return newCompany.Id;
        }

        private static Dictionary<string, object?> NormalisePayload(JsonElement payload)
        {
            var systemInfoProp = payload.GetPropertyOrNull("systemInfo");
            var systemInfo = systemInfoProp?.ValueKind == JsonValueKind.Object ? systemInfoProp.Value : default;

            var cpuProp = payload.GetPropertyOrNull("cpuInfo");
            var memoryProp = payload.GetPropertyOrNull("memoryInfo");
            var diskProp = payload.GetPropertyOrNull("diskInfo");
            var batteryProp = payload.GetPropertyOrNull("batteryInfo");
            var networkProp = payload.GetPropertyOrNull("networkInfo");

            return new Dictionary<string, object?>
            {
                ["machineId"] = payload.GetStringProperty("machineId"),
                ["collectedAt"] = payload.GetStringProperty("timestamp"),
                ["systemInfo"] = systemInfoProp?.ValueKind == JsonValueKind.Object ? (object)systemInfo : null,
                ["cpu"] = cpuProp?.ValueKind == JsonValueKind.Object ? (object)cpuProp.Value : null,
                ["memory"] = memoryProp?.ValueKind == JsonValueKind.Object ? (object)memoryProp.Value : null,
                ["disks"] = diskProp?.ValueKind == JsonValueKind.Array ? (object)diskProp.Value : null,
                ["battery"] = batteryProp?.ValueKind == JsonValueKind.Object ? (object)batteryProp.Value : null,
                ["networkAdapters"] = networkProp?.ValueKind == JsonValueKind.Array ? (object)networkProp.Value : null
            };
        }
    }
}
