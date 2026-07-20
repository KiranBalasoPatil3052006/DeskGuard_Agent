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
    public class NetworkProcessor : IPayloadProcessor
    {
        private readonly DeskGuardDbContext _dbContext;
        private readonly ILogger<NetworkProcessor> _logger;

        public NetworkProcessor(DeskGuardDbContext dbContext, ILogger<NetworkProcessor> logger)
        {
            _dbContext = dbContext;
            _logger = logger;
        }

        public async Task ProcessAsync(Machine machine, JsonElement payload, HealthLog healthLog)
        {
            try
            {
                var adaptersProp = payload.GetPropertyOrNull("networkAdapters") ?? payload.GetPropertyOrNull("network") ?? payload.GetPropertyOrNull("network_adapters");
                if (adaptersProp == null || adaptersProp.Value.ValueKind != JsonValueKind.Array) return;

                var adapters = adaptersProp.Value;

                long totalSent = 0;
                long totalRecv = 0;

                foreach (var adapter in adapters.EnumerateArray())
                {
                    var adapterName = adapter.GetStringProperty("adapterName") ?? adapter.GetStringProperty("adapter_name") ?? "Unknown";
                    var isConnected = adapter.GetBooleanProperty("isConnected") ?? adapter.GetBooleanProperty("is_connected") ?? false;
                    var ipV4 = adapter.GetStringProperty("ipAddressV4") ?? adapter.GetStringProperty("ip_address_v4") ?? adapter.GetStringProperty("ipAddress");
                    var mac = adapter.GetStringProperty("macAddress") ?? adapter.GetStringProperty("mac_address");
                    var speed = adapter.GetInt64Property("connectionSpeedMbps") ?? adapter.GetInt64Property("connection_speed_mbps");
                    var bytesSent = adapter.GetInt64Property("bytesSent") ?? adapter.GetInt64Property("bytes_sent") ?? 0;
                    var bytesRecv = adapter.GetInt64Property("bytesReceived") ?? adapter.GetInt64Property("bytes_received") ?? 0;
                    var adapterType = adapter.GetStringProperty("adapterType") ?? adapter.GetStringProperty("adapter_type");

                    totalSent += bytesSent;
                    totalRecv += bytesRecv;

                    var dbAdapter = await _dbContext.MachineNetworkAdapters
                        .FirstOrDefaultAsync(n => n.MachineId == machine.Id && n.AdapterName == adapterName);

                    if (dbAdapter == null)
                    {
                        dbAdapter = new MachineNetworkAdapter { MachineId = machine.Id, AdapterName = adapterName };
                        await _dbContext.MachineNetworkAdapters.AddAsync(dbAdapter);
                    }

                    dbAdapter.IpAddress = ipV4;
                    dbAdapter.MacAddress = mac;
                    dbAdapter.AdapterType = adapterType;
                    dbAdapter.Speed = speed;
                    dbAdapter.Status = isConnected ? "connected" : "disconnected";
                    dbAdapter.UpdatedAt = DateTime.UtcNow;
                }

                // Update MachineCurrentStatus
                var currentStatus = await _dbContext.MachineCurrentStatuses
                    .FirstOrDefaultAsync(s => s.MachineId == machine.Id);

                if (currentStatus == null)
                {
                    currentStatus = new MachineCurrentStatus { MachineId = machine.Id };
                    await _dbContext.MachineCurrentStatuses.AddAsync(currentStatus);
                }

                currentStatus.NetworkSentBytes = totalSent;
                currentStatus.NetworkReceivedBytes = totalRecv;
                currentStatus.CollectedAt = DateTime.UtcNow;

                // Update shared health log row
                healthLog.NetworkReceivedBytes = totalRecv;
                healthLog.NetworkSentBytes = totalSent;

                await _dbContext.SaveChangesAsync();

                _logger.LogDebug("NetworkProcessor: Processed network telemetry for machine {MachineId}", machine.Id);
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "NetworkProcessor: Failed to process network metrics for machine {MachineId}", machine.Id);
            }
        }
    }
}
