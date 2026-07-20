using System;
using System.Collections.Generic;
using System.Linq;
using System.Security.Claims;
using System.Text.Json;
using System.Threading.Tasks;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using DeskGuardBackend.DTOs.Common;
using DeskGuardBackend.Entities;
using DeskGuardBackend.Data;
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Logging;
using DeskGuardBackend.Services.Interfaces;

namespace DeskGuardBackend.Controllers
{
    [Authorize]
    [ApiController]
    [Route("api/v1")]
    public class ChangeController : ControllerBase
    {
        private readonly DeskGuardDbContext _dbContext;
        private readonly ILogger<ChangeController> _logger;

        public ChangeController(DeskGuardDbContext dbContext, ILogger<ChangeController> logger)
        {
            _dbContext = dbContext;
            _logger = logger;
        }

        private long GetCompanyId()
        {
            var compIdStr = User.FindFirst("CompanyId")?.Value;
            if (string.IsNullOrEmpty(compIdStr) || !long.TryParse(compIdStr, out var companyId))
            {
                return 1;
            }
            return companyId;
        }

        private long GetUserId()
        {
            var userIdStr = User.FindFirst(ClaimTypes.NameIdentifier)?.Value;
            return long.TryParse(userIdStr, out var userId) ? userId : 0;
        }

        [HttpGet("changes")]
        public async Task<IActionResult> Index(
            [FromQuery] string? category = null,
            [FromQuery] string? severity = null,
            [FromQuery] string? status = null,
            [FromQuery] long? machine_id = null,
            [FromQuery] int? days = null,
            [FromQuery] DateTime? date_from = null,
            [FromQuery] DateTime? date_to = null,
            [FromQuery] int page = 1,
            [FromQuery] int per_page = 50)
        {
            try
            {
                var companyId = GetCompanyId();
                var query = _dbContext.ChangeHistories
                    .Include(c => c.Machine)
                    .Where(c => c.CompanyId == companyId);

                if (!string.IsNullOrEmpty(category))
                    query = query.Where(c => c.Category == category);

                if (!string.IsNullOrEmpty(severity))
                    query = query.Where(c => c.Severity == severity);

                if (!string.IsNullOrEmpty(status))
                    query = query.Where(c => c.Status == status);

                if (machine_id.HasValue)
                    query = query.Where(c => c.MachineId == machine_id.Value);

                if (days.HasValue)
                {
                    var since = DateTime.UtcNow.AddDays(-days.Value);
                    query = query.Where(c => c.DetectedAt >= since);
                }

                if (date_from.HasValue)
                    query = query.Where(c => c.DetectedAt >= date_from.Value);

                if (date_to.HasValue)
                {
                    var toLimit = date_to.Value.Date.AddDays(1).AddTicks(-1);
                    query = query.Where(c => c.DetectedAt <= toLimit);
                }

                var total = await query.CountAsync();
                var items = await query
                    .OrderByDescending(c => c.DetectedAt)
                    .Skip((page - 1) * per_page)
                    .Take(per_page)
                    .ToListAsync();

                // Add helper properties or recommendation if needed matching append('recommendation')
                var result = new PaginatedResponseDto<ChangeHistory>
                {
                    Data = items,
                    Total = total,
                    CurrentPage = page,
                    PerPage = per_page,
                    LastPage = (int)Math.Ceiling((double)total / per_page)
                };
                return Ok(result);
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Failed to get changes history");
                return StatusCode(500, ApiResponse.Fail("Failed to retrieve change history."));
            }
        }

        [HttpGet("machines/{id}/changes")]
        public async Task<IActionResult> MachineChanges(
            long id,
            [FromQuery] string? category = null,
            [FromQuery] string? severity = null,
            [FromQuery] string? status = null,
            [FromQuery] int? days = null,
            [FromQuery] DateTime? date_from = null,
            [FromQuery] DateTime? date_to = null,
            [FromQuery] int page = 1,
            [FromQuery] int per_page = 50)
        {
            try
            {
                var companyId = GetCompanyId();
                var machine = await _dbContext.Machines.FirstOrDefaultAsync(m => m.Id == id && m.CompanyId == companyId);
                if (machine == null) return NotFound(ApiResponse.Fail("Machine not found."));

                var query = _dbContext.ChangeHistories.Where(c => c.MachineId == machine.Id);

                if (!string.IsNullOrEmpty(category))
                    query = query.Where(c => c.Category == category);

                if (!string.IsNullOrEmpty(severity))
                    query = query.Where(c => c.Severity == severity);

                if (!string.IsNullOrEmpty(status))
                    query = query.Where(c => c.Status == status);

                if (days.HasValue)
                {
                    var since = DateTime.UtcNow.AddDays(-days.Value);
                    query = query.Where(c => c.DetectedAt >= since);
                }

                if (date_from.HasValue)
                    query = query.Where(c => c.DetectedAt >= date_from.Value);

                if (date_to.HasValue)
                {
                    var toLimit = date_to.Value.Date.AddDays(1).AddTicks(-1);
                    query = query.Where(c => c.DetectedAt <= toLimit);
                }

                var total = await query.CountAsync();
                var items = await query
                    .OrderByDescending(c => c.DetectedAt)
                    .Skip((page - 1) * per_page)
                    .Take(per_page)
                    .ToListAsync();

                var result = new PaginatedResponseDto<ChangeHistory>
                {
                    Data = items,
                    Total = total,
                    CurrentPage = page,
                    PerPage = per_page,
                    LastPage = (int)Math.Ceiling((double)total / per_page)
                };
                return Ok(result);
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Failed to get machine changes");
                return StatusCode(500, ApiResponse.Fail("Failed to retrieve machine changes."));
            }
        }

        [HttpGet("changes/recent")]
        public async Task<IActionResult> RecentChanges([FromQuery] int days = 7, [FromQuery] int limit = 10)
        {
            try
            {
                var companyId = GetCompanyId();
                var since = DateTime.UtcNow.AddDays(-days);

                var changes = await _dbContext.ChangeHistories
                    .Include(c => c.Machine)
                    .Where(c => c.CompanyId == companyId && c.DetectedAt >= since)
                    .OrderByDescending(c => c.DetectedAt)
                    .Take(limit)
                    .ToListAsync();

                return Ok(ApiResponse<IEnumerable<ChangeHistory>>.Ok(changes, "Recent changes retrieved successfully."));
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Failed to get recent changes");
                return StatusCode(500, ApiResponse.Fail("Failed to retrieve recent changes."));
            }
        }

        [HttpGet("changes/summary")]
        public async Task<IActionResult> Summary([FromQuery] int days = 7)
        {
            try
            {
                var companyId = GetCompanyId();
                var since = DateTime.UtcNow.AddDays(-days);

                var categoryCounts = await _dbContext.ChangeHistories
                    .Where(c => c.CompanyId == companyId && c.DetectedAt >= since)
                    .GroupBy(c => new { c.Category, c.ChangeType })
                    .Select(g => new
                    {
                        Category = g.Key.Category ?? "Unknown",
                        ChangeType = g.Key.ChangeType ?? "Unknown",
                        Count = g.Count()
                    })
                    .ToListAsync();

                var totalChanges = categoryCounts.Sum(x => x.Count);
                
                var byCategory = categoryCounts
                    .GroupBy(x => x.Category)
                    .ToDictionary(
                        cg => cg.Key,
                        cg => new
                        {
                            total = cg.Sum(x => x.Count),
                            by_type = cg.ToDictionary(x => x.ChangeType, x => x.Count)
                        }
                    );

                return Ok(ApiResponse<object>.Ok(new
                {
                    total_changes = totalChanges,
                    by_category = byCategory,
                    detail = categoryCounts
                }, "Change summary retrieved successfully."));
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Failed to get change summary");
                return StatusCode(500, ApiResponse.Fail("Failed to retrieve change summary."));
            }
        }

        [HttpPut("changes/{id}/status")]
        public async Task<IActionResult> UpdateStatus(long id, [FromBody] JsonElement body)
        {
            try
            {
                if (!body.TryGetProperty("status", out var statusProp) || string.IsNullOrEmpty(statusProp.GetString()))
                {
                    return BadRequest(ApiResponse.Fail("status is required."));
                }

                var status = statusProp.GetString()!;
                var note = body.TryGetProperty("note", out var noteProp) ? noteProp.GetString() : null;

                var companyId = GetCompanyId();
                var change = await _dbContext.ChangeHistories
                    .FirstOrDefaultAsync(c => c.Id == id && c.CompanyId == companyId);

                if (change == null) return NotFound(ApiResponse.Fail("Change record not found."));

                // Deserialise existing metadata or init
                var metadata = string.IsNullOrEmpty(change.Metadata) 
                    ? new Dictionary<string, object>()
                    : JsonSerializer.Deserialize<Dictionary<string, object>>(change.Metadata) ?? new Dictionary<string, object>();

                var userId = GetUserId();
                metadata["status_updated_by"] = userId;
                metadata["status_updated_at"] = DateTime.UtcNow.ToString("o");
                if (!string.IsNullOrEmpty(note))
                {
                    metadata["status_note"] = note;
                }

                change.Status = status;
                change.Metadata = JsonSerializer.Serialize(metadata);

                await _dbContext.SaveChangesAsync();

                return Ok(ApiResponse<ChangeHistory>.Ok(change, "Change status updated successfully."));
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Failed to update change status: {ChangeId}", id);
                return StatusCode(500, ApiResponse.Fail("Failed to update change status."));
            }
        }
    }
}
