using System;
using System.Threading.Tasks;
using Microsoft.AspNetCore.SignalR;
using Microsoft.Extensions.Logging;

namespace DeskGuardBackend.SignalR
{
    /// <summary>
    /// SignalR Hub for real-time alert and notification streaming.
    /// Clients (e.g. the React dashboard) connect to this hub and join a group based on their company_id.
    /// This replaces the Server-Sent Events (SSE) loop in the PHP backend.
    /// </summary>
    public class AlertHub : Hub
    {
        private readonly ILogger<AlertHub> _logger;

        public AlertHub(ILogger<AlertHub> logger)
        {
            _logger = logger;
        }

        public async Task JoinCompanyGroup(string companyId)
        {
            if (string.IsNullOrEmpty(companyId)) return;

            await Groups.AddToGroupAsync(Context.ConnectionId, $"Company_{companyId}");
            _logger.LogInformation("Connection {ConnectionId} joined real-time group Company_{CompanyId}", Context.ConnectionId, companyId);
        }

        public async Task LeaveCompanyGroup(string companyId)
        {
            if (string.IsNullOrEmpty(companyId)) return;

            await Groups.RemoveFromGroupAsync(Context.ConnectionId, $"Company_{companyId}");
            _logger.LogInformation("Connection {ConnectionId} left real-time group Company_{CompanyId}", Context.ConnectionId, companyId);
        }

        public override async Task OnConnectedAsync()
        {
            _logger.LogInformation("Real-time client connected: {ConnectionId}", Context.ConnectionId);
            await base.OnConnectedAsync();
        }

        public override async Task OnDisconnectedAsync(Exception? exception)
        {
            _logger.LogInformation("Real-time client disconnected: {ConnectionId}", Context.ConnectionId);
            await base.OnDisconnectedAsync(exception);
        }
    }
}
