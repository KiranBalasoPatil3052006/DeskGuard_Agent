using System;
using System.Collections.Generic;
using System.Text.Json;
using System.Threading.Tasks;
using Microsoft.Extensions.Logging;
using DeskGuardBackend.Data;
using DeskGuardBackend.Entities;
using DeskGuardBackend.Services.Interfaces;
using DeskGuardBackend.Services.PayloadProcessors;

namespace DeskGuardBackend.Services
{
    public class PayloadProcessorService : IPayloadProcessorService
    {
        private readonly DeskGuardDbContext _dbContext;
        private readonly IEnumerable<IPayloadProcessor> _processors;
        private readonly ILogger<PayloadProcessorService> _logger;

        public PayloadProcessorService(
            DeskGuardDbContext dbContext,
            IEnumerable<IPayloadProcessor> processors,
            ILogger<PayloadProcessorService> logger)
        {
            _dbContext = dbContext;
            _processors = processors;
            _logger = logger;
        }

        public async Task ProcessAsync(Machine machine, JsonElement payload)
        {
            using var transaction = await _dbContext.Database.BeginTransactionAsync();
            try
            {
                // Create ONE shared HealthLog row for this payload cycle
                var healthLog = new HealthLog
                {
                    CompanyId = machine.CompanyId,
                    MachineId = machine.Id,
                    CollectedAt = DateTime.UtcNow
                };

                await _dbContext.HealthLogs.AddAsync(healthLog);
                await _dbContext.SaveChangesAsync();

                foreach (var processor in _processors)
                {
                    try
                    {
                        await processor.ProcessAsync(machine, payload, healthLog);
                    }
                    catch (Exception ex)
                    {
                        _logger.LogError(ex, "PayloadProcessorService: Processor {ProcessorName} failed for machine {MachineId}", 
                            processor.GetType().Name, machine.Id);
                    }
                }

                await transaction.CommitAsync();
                _logger.LogInformation("PayloadProcessorService: Successfully processed telemetry payload for machine {MachineId}", machine.Id);
            }
            catch (Exception ex)
            {
                await transaction.RollbackAsync();
                _logger.LogError(ex, "PayloadProcessorService: Transaction rolled back due to error processing payload for machine {MachineId}", machine.Id);
                throw;
            }
        }
    }
}
