using System;
using System.Text.Json;
using System.Threading.Tasks;
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Logging;
using DeskGuardBackend.Data;
using DeskGuardBackend.Entities;
using DeskGuardBackend.Exceptions;
using DeskGuardBackend.Extensions;
using DeskGuardBackend.Services.Interfaces;

namespace DeskGuardBackend.Services
{
    public class TelemetryService : ITelemetryService
    {
        private readonly DeskGuardDbContext _dbContext;
        private readonly IPayloadProcessorService _payloadProcessorService;
        private readonly ILogger<TelemetryService> _logger;

        public TelemetryService(
            DeskGuardDbContext dbContext,
            IPayloadProcessorService payloadProcessorService,
            ILogger<TelemetryService> logger)
        {
            _dbContext = dbContext;
            _payloadProcessorService = payloadProcessorService;
            _logger = logger;
        }

        public async Task ProcessTelemetryAsync(JsonElement payload, string sourceIp)
        {
            try
            {
                var machineUid = payload.GetStringProperty("machineId") ?? payload.GetStringProperty("machine_uid") ?? string.Empty;
                if (string.IsNullOrEmpty(machineUid))
                {
                    _logger.LogWarning("TelemetryService: Received payload without machineId");
                    return;
                }

                var machine = await _dbContext.Machines
                    .FirstOrDefaultAsync(m => m.MachineUid == machineUid);

                if (machine == null)
                {
                    _logger.LogWarning("TelemetryService: Machine not found with UID {MachineUid}", machineUid);
                    return;
                }

                var rawPayloadLog = new RawPayloadLog
                {
                    MachineId = machine.Id,
                    MachineUid = machineUid,
                    Payload = JsonSerializer.Serialize(payload),
                    SourceIp = sourceIp,
                    ReceivedAt = DateTime.UtcNow
                };

                await _dbContext.RawPayloadLogs.AddAsync(rawPayloadLog);
                await _dbContext.SaveChangesAsync();

                // Process the normalized telemetry payload
                await _payloadProcessorService.ProcessAsync(machine, payload);

                _logger.LogInformation("TelemetryService: Telemetry processed successfully for machine {MachineUid}", machineUid);
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "TelemetryService::ProcessTelemetryAsync failed");
                throw;
            }
        }
    }
}
