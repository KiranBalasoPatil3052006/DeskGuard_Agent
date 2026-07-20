using System;
using System.Security.Claims;
using System.Threading.Tasks;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using DeskGuardBackend.DTOs.Common;
using DeskGuardBackend.Services.Interfaces;
using DeskGuardBackend.Data;
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Logging;
using System.Linq;
using System.Text.Json;

namespace DeskGuardBackend.Controllers
{
    [Authorize]
    [ApiController]
    [Route("api/v1/machines")]
    public class MachineController : ControllerBase
    {
        private readonly IMachineService _machineService;
        private readonly DeskGuardDbContext _dbContext;
        private readonly ILogger<MachineController> _logger;

        public MachineController(
            IMachineService machineService,
            DeskGuardDbContext dbContext,
            ILogger<MachineController> _logger)
        {
            _machineService = machineService;
            _dbContext = dbContext;
            this._logger = _logger;
        }

        private long GetCompanyId()
        {
            var compIdStr = User.FindFirst("CompanyId")?.Value;
            if (string.IsNullOrEmpty(compIdStr) || !long.TryParse(compIdStr, out var companyId))
            {
                return 1; // Fallback to company ID 1 for dev
            }
            return companyId;
        }

        [HttpGet]
        public async Task<IActionResult> Index([FromQuery] int page = 1, [FromQuery] int per_page = 15, [FromQuery] string? status = null, [FromQuery] string? search = null)
        {
            try
            {
                var companyId = GetCompanyId();
                var result = await _machineService.GetCompanyMachinesAsync(companyId, page, per_page, status, search);
                return Ok(result); // Return the LengthAwarePaginator structure directly
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Failed to get company machines");
                return StatusCode(500, ApiResponse.Fail("Failed to retrieve machines."));
            }
        }

        [HttpGet("online")]
        public async Task<IActionResult> Online()
        {
            var companyId = GetCompanyId();
            var count = await _machineService.GetOnlineCountAsync(companyId);
            return Ok(ApiResponse<object>.Ok(new { count = count }));
        }

        [HttpGet("offline")]
        public async Task<IActionResult> Offline()
        {
            var companyId = GetCompanyId();
            var count = await _machineService.GetOfflineCountAsync(companyId);
            return Ok(ApiResponse<object>.Ok(new { count = count }));
        }

        [HttpGet("{id}")]
        public async Task<IActionResult> Show(long id)
        {
            try
            {
                var machine = await _dbContext.Machines
                    .Include(m => m.CurrentStatus)
                    .Include(m => m.AssignedUser)
                    .FirstOrDefaultAsync(m => m.Id == id);

                if (machine == null) return NotFound(ApiResponse.Fail("Machine not found."));
                return Ok(ApiResponse<object>.Ok(machine));
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Failed to show machine ID {Id}", id);
                return StatusCode(500, ApiResponse.Fail("Failed to retrieve machine details."));
            }
        }

        [HttpGet("{id}/status")]
        public async Task<IActionResult> Status(long id)
        {
            var status = await _dbContext.MachineCurrentStatuses
                .FirstOrDefaultAsync(s => s.MachineId == id);
            return Ok(ApiResponse<object>.Ok(status));
        }

        [HttpGet("{id}/history")]
        public async Task<IActionResult> History(long id)
        {
            var history = await _dbContext.HealthLogs
                .Where(h => h.MachineId == id)
                .OrderByDescending(h => h.CollectedAt)
                .Take(50)
                .ToListAsync();
            return Ok(ApiResponse<object>.Ok(history));
        }

        [HttpPost("{id}/assign")]
        public async Task<IActionResult> Assign(long id, [FromBody] JsonElement body)
        {
            if (body.TryGetProperty("user_id", out var userProp) && userProp.TryGetInt64(out var userId))
            {
                var machine = await _machineService.AssignMachineAsync(id, userId);
                return Ok(ApiResponse<object>.Ok(machine, "Machine assigned successfully."));
            }
            return BadRequest(ApiResponse.Fail("user_id is required."));
        }

        [HttpPost("{id}/unassign")]
        public async Task<IActionResult> Unassign(long id)
        {
            var machine = await _machineService.UnassignMachineAsync(id);
            return Ok(ApiResponse<object>.Ok(machine, "Machine unassigned successfully."));
        }

        // Sub-Resources Details for Machine Details Tabs
        [HttpGet("{id}/inventory")]
        public async Task<IActionResult> Inventory(long id)
        {
            var hw = await _dbContext.HardwareInventories.Where(x => x.MachineId == id).FirstOrDefaultAsync();
            var sw = await _dbContext.SoftwareInventories.Where(x => x.MachineId == id).ToListAsync();
            return Ok(ApiResponse<object>.Ok(new { hardware = hw, software = sw }));
        }

        [HttpGet("{id}/security")]
        public async Task<IActionResult> Security(long id)
        {
            var av = await _dbContext.AntivirusStatuses.Where(x => x.MachineId == id).FirstOrDefaultAsync();
            var fw = await _dbContext.FirewallStatuses.Where(x => x.MachineId == id).FirstOrDefaultAsync();
            var logins = await _dbContext.LoginActivities.Where(x => x.MachineId == id).OrderByDescending(x => x.EventTime).Take(20).ToListAsync();
            var updates = await _dbContext.WindowsUpdates.Where(x => x.MachineId == id).OrderByDescending(x => x.InstalledOn).Take(20).ToListAsync();
            return Ok(ApiResponse<object>.Ok(new { antivirus = av, firewall = fw, logins = logins, updates = updates }));
        }

        [HttpGet("{id}/devices")]
        public async Task<IActionResult> Devices(long id)
        {
            var connected = await _dbContext.MachineConnectedDevices.Where(x => x.MachineId == id).ToListAsync();
            var usb = await _dbContext.UsbActivities.Where(x => x.MachineId == id).OrderByDescending(x => x.EventTime).Take(20).ToListAsync();
            var deviceEvents = await _dbContext.DeviceEvents.Where(x => x.MachineId == id).OrderByDescending(x => x.CreatedAt).Take(20).ToListAsync();
            return Ok(ApiResponse<object>.Ok(new { connected = connected, usb_activity = usb, device_events = deviceEvents }));
        }

        [HttpGet("{id}/device-issues")]
        public async Task<IActionResult> DeviceIssues(long id)
        {
            var issues = await _dbContext.MachineConnectedDevices
                .Where(x => x.MachineId == id && x.HasProblem == true)
                .ToListAsync();
            return Ok(ApiResponse<object>.Ok(issues));
        }

        [HttpGet("{id}/alerts")]
        public async Task<IActionResult> MachineAlerts(long id)
        {
            var alerts = await _dbContext.Alerts
                .Where(x => x.MachineId == id)
                .OrderByDescending(x => x.CreatedAt)
                .ToListAsync();
            return Ok(ApiResponse<object>.Ok(alerts));
        }

        [HttpGet("{id}/timeline")]
        public async Task<IActionResult> Timeline(long id)
        {
            // Fetch combined activity timeline items
            var alerts = await _dbContext.Alerts.Where(x => x.MachineId == id).Select(x => new { type = "alert", title = x.Title, description = x.Description ?? string.Empty, timestamp = x.CreatedAt }).Take(20).ToListAsync();
            var usb = await _dbContext.UsbActivities.Where(x => x.MachineId == id).Select(x => new { type = "usb", title = x.DeviceName ?? string.Empty, description = x.EventType ?? string.Empty, timestamp = x.EventTime ?? DateTime.UtcNow }).Take(20).ToListAsync();
            var logins = await _dbContext.LoginActivities.Where(x => x.MachineId == id).Select(x => new { type = "login", title = x.Username ?? string.Empty, description = x.EventType ?? string.Empty, timestamp = x.EventTime ?? DateTime.UtcNow }).Take(20).ToListAsync();

            var combined = alerts.Concat(usb).Concat(logins).OrderByDescending(x => x.timestamp).Take(30).ToList();
            return Ok(ApiResponse<object>.Ok(combined));
        }

        [HttpGet("{id}/processes")]
        public async Task<IActionResult> Processes(long id)
        {
            var processes = await _dbContext.ProcessLogs.Where(x => x.MachineId == id).ToListAsync();
            return Ok(ApiResponse<object>.Ok(processes));
        }

        [HttpGet("{id}/services")]
        public async Task<IActionResult> Services(long id)
        {
            var services = await _dbContext.WindowsServices.Where(x => x.MachineId == id).ToListAsync();
            return Ok(ApiResponse<object>.Ok(services));
        }

        [HttpGet("{id}/startup-programs")]
        public async Task<IActionResult> StartupPrograms(long id)
        {
            var startup = await _dbContext.StartupPrograms.Where(x => x.MachineId == id).ToListAsync();
            return Ok(ApiResponse<object>.Ok(startup));
        }

        [HttpGet("{id}/event-logs")]
        public async Task<IActionResult> EventLogs(long id)
        {
            var logs = await _dbContext.EventLogs.Where(x => x.MachineId == id).OrderByDescending(x => x.TimeGenerated).Take(50).ToListAsync();
            return Ok(ApiResponse<object>.Ok(logs));
        }

        [HttpGet("{id}/network")]
        public async Task<IActionResult> NetworkAdapters(long id)
        {
            var adapters = await _dbContext.MachineNetworkAdapters.Where(x => x.MachineId == id).ToListAsync();
            var disks = await _dbContext.MachineDisks.Where(x => x.MachineId == id).ToListAsync();
            return Ok(ApiResponse<object>.Ok(new { adapters = adapters, disks = disks }));
        }
    }
}
