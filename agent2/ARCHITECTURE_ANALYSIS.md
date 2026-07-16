# DeskGuard Architecture Analysis

## Stack Overview

| Layer | Technology |
|---|---|
| Frontend | React 18 + Vite + TanStack React Query |
| Backend | ASP.NET Core 8 Web API + EF Core 8 |
| Database | PostgreSQL 17 |
| Realtime | SignalR |
| Background Jobs | Hangfire (via IHostedService) |
| Caching | IMemoryCache (in-process only) |
| Logging | Serilog |

---

## Current Architecture Flow

```
Agent (C# Worker)
  ↓ POST /api/v1/agent/health (HealthPayload - full snapshot every 120s)
  ↓ POST /api/v1/agent/heartbeat (every 30s)
Backend (ASP.NET Core 8)
  → Middleware Pipeline: GlobalException → RequestLogging → CORS → Auth → CompanyScope → MachineAuth
  → Controller → Service → EF Core → PostgreSQL
  → SignalR Hub (AlertHub) for push notifications
Frontend (React + React Query)
  → AuthContext (JWT token management)
  → useQueries.js (16 hooks + 3 mutations)
  → TanStack Query cache (15s-120s staleTime)
  → Components render with skeleton loaders
```

---

## Database Schema (28 tables)

### Core Tables
- `companies`, `users`, `machines`, `machine_tokens`
- `machine_current_status` (1:1 — single row per machine, optimized for dashboard)

### Metric Tables (high volume)
- `health_logs` — **~720 rows/machine/day** at 120s interval
- `process_logs` — snapshot of running processes each cycle
- `windows_services` — service status snapshot each cycle

### Event Tables (append-only, medium volume)
- `event_logs`, `login_activities`, `usb_activities`, `device_events`

### Inventory Tables (low volume)
- `hardware_inventory`, `software_inventory` (collected every 24h)
- `machine_connected_devices`, `machine_network_adapters`, `machine_disks`

### Security Tables
- `antivirus_status`, `firewall_status`, `windows_updates`, `startup_programs`

### Alert/Change Tables
- `alerts`, `alert_rules`, `change_history`
- `hardware_baselines`, `software_baselines`, `security_baselines`, `configuration_baselines`

### System Tables
- `notifications`, `audit_logs`, `reports`, `otp_codes`, `email_recipients`

---

## Critical Bottlenecks Found

### 1. NO PAGINATION on MachineDetail sub-resources
Files: `MachineController.cs` lines 194-211
- `GET /machines/{id}/processes` — returns ALL processes (unbounded)
- `GET /machines/{id}/services` — returns ALL services
- `GET /machines/{id}/startup-programs` — returns ALL startup programs
- `GET /machines/{id}/alerts` — returns ALL alerts

### 2. NO AsNoTracking() on any query
File: `MachineController.cs` — every EF query tracks entities for change detection unnecessarily

### 3. SELECT * everywhere
All endpoints call `.ToListAsync()` without `.Select()` projections, returning all columns

### 4. Dashboard chart aggregation over full health_logs
File: `DashboardService.cs` lines 183-203
- Groups ALL health_logs for a company by MachineId + Date + Hour
- At 10K machines × 720 rows/day = 7.2M rows/day, this grows exponentially
- The `Take(500)` at the end doesn't prevent the full aggregation

### 5. Missing composite indexes for common query patterns

### 6. No frontend code splitting
`App.jsx` imports all pages eagerly — no `React.lazy()` used

### 7. No virtualized lists
Process table (200+ rows), Device table all render as regular HTML tables

### 8. OfflineCheckJob loads all machines into memory
File: `BackgroundJobs/OfflineCheckJob.cs` — `.ToListAsync()` on ALL online machines

### 9. MachineController bypasses MachineService
Uses `_dbContext` directly instead of the service layer

### 10. No Redis cache
Only in-process MemoryCache (not shared across instances)
