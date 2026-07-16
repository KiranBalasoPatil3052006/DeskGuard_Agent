# DeskGuard Performance Analysis

## Overview
Comprehensive performance audit of the DeskGuard monitoring system across all layers: Database, Backend (Laravel), Frontend (React/Vite), and Agent (C# .NET).

---

## 1. Database Bottlenecks

### 1.1 Missing Composite Indexes
| Table | Query Pattern | Missing Index | Impact |
|-------|---------------|---------------|--------|
| `health_logs` | Filter by `machine_id` + `created_at` range (history, charts) | `(machine_id, collected_at)` — exists but used only for machine-level queries | High |
| `health_logs` | `whereHas('machine', fn=> where company_id)` pattern | No index on `(company_id, collected_at)` — exists at company level but `WHERE` clause uses subquery | High |
| `alerts` | Filter by `machine_id` + `severity` + `status` | `(machine_id, severity, status, created_at)` | Medium |
| `machines` | Filter by `company_id` + `status` + `is_online` | `(company_id, is_online, status)` | Medium |
| `process_logs` | Filter by `machine_id` + `collected_at` | `(machine_id, collected_at)` | Medium |
| `login_activities` | Filter by `machine_id` + `event_type` + `created_at` | `(machine_id, created_at)` | Low |
| `usb_activities` | Filter by `machine_id` + `event_type` | `(machine_id, event_type, created_at)` | Low |

### 1.2 WHERE HAS Anti-Pattern
**Affected files:**
- `DashboardRepository.php:91-96` — `whereHas('machine', company_id)` on alerts
- `DashboardRepository.php:106-108` — `whereHas('machine', company_id)` on usb_activities
- `DashboardRepository.php:194-196` — `whereHas('machine', company_id)` on health_logs
- `DashboardRepository.php:222-225` — `whereHas('machine', company_id)` on health_logs
- `DashboardRepository.php:253-255` — `whereHas('machine', company_id)` on alerts

**Impact:** Each `whereHas` generates a correlated subquery, preventing index usage and causing full table scans on large tables. Estimated impact: 2-5x slower queries.

**Fix:** Replace `whereHas('machine', company_id)` with direct `join` or add `company_id` column + direct `where` clause.

### 1.3 Unpaginated Queries
**Affected files:**
- `MachineController::machineAlerts()` — `->get()` no pagination
- `MachineController::services()` — `->get()` no pagination
- `MachineController::startupPrograms()` — `->get()` no pagination
- `MachineController::processes()` — `->get()` with `take(100)` but no pagination
- `AlertService::getMachineAlerts()` — `->get()` no pagination
- `AlertService::getCriticalAlerts()` — `->get()` no pagination

**Impact:** As data grows, these endpoints will return increasingly large JSON payloads, causing memory pressure and slow responses.

### 1.4 SELECT * in BaseRepository
**Affected files:**
- `BaseRepository.php:64` — `findAll(['*'])` selects all columns
- `BaseRepository.php:156` — `paginate(15, ['*'])` selects all columns
- `BaseRepository.php:177` — `findByField()` defaults to `['*']`
- `BaseRepository.php:206` — `findWhere()` defaults to `['*']`
- Various repository methods use `$this->model->all()` without explicit column selection

**Impact:** Returns unnecessary columns (JSON, text, blob fields), increasing payload size by 30-50%.

---

## 2. Backend Processing Bottlenecks

### 2.1 Sequential Processing in PayloadProcessorService
**File:** `PayloadProcessorService.php`

The `process()` method runs 20 processors sequentially within a single database transaction. Each processor writes to the database independently.

**Impact:** As the number of machines grows, the transaction duration increases linearly. A single slow processor blocks the entire batch.

**Fix:** Process payload sections in parallel where possible, commit early for non-critical data.

### 2.2 LogApiRequestsMiddleware Overhead
**File:** `LogApiRequestsMiddleware.php`

**Problem:** Logs EVERY API request to the `audit_log` channel with full sanitized payload, including agent health pings (every 2-5 minutes per machine). For 10,000 machines, this generates 120,000+ log entries per day.

**Impact:** Wasted I/O, increased disk usage, log rotation overhead.

**Fix:** Skip logging for agent health/heartbeat endpoints, or log at a lower sample rate.

### 2.3 Duplicate Queries in Machine Details
**File:** `MachineController.php`

**Problem:** Each sub-resource endpoint (status, history, inventory, etc.) loads the machine first via `$this->machineService->getMachine($id)` which eagerly loads relationships (`company`, `assignedUser`, `currentStatus`, `networkAdapters`). These relationships are not needed for sub-resource endpoints.

**Impact:** Each API call to a machine sub-resource runs 4 extra queries unnecessarily.

### 2.4 Missing Query Execution Logging
No database query logging middleware exists. Without SQL query monitoring, it's impossible to identify slow queries in production.

### 2.5 Large Data Processing in Memory
**File:** `DashboardService::getCombinedChartData()`

**Problem:** Loads up to 50,000 health log records into memory, then iterates to build chart datasets. For 10,000 machines, this could be millions of rows.

**Impact:** Memory exhaustion, slow response times.

---

## 3. Frontend Bottlenecks

### 3.1 MachineDetails.jsx — Monolithic Component
**File:** `src/pages/machines/MachineDetails.jsx` (1,581 lines)

| Issue | Details | Impact |
|-------|---------|--------|
| 23+ useState hooks | Cascading re-renders on any state change | High |
| State defined for every tab | All 11 tab datasets loaded into memory | High |
| Functions defined in render body | `getHealthScore`, `formatBytes`, `getChangeSeverity` recreated every render | Medium |
| No React.memo | Entire component re-renders on every state change | High |
| Inline SVG component | `HealthCircle` recreated every render | Low |
| Direct service calls | No React Query caching or deduplication | High |

**Fix:** 
- Convert to React Query hooks for all data fetching
- Lazy-load tabs (only fetch data when tab is clicked)
- Split into smaller components
- Use `useCallback` / `useMemo` for expensive computations

### 3.2 No List Virtualization
All lists (machines, alerts, changes, processes, services) render all items in DOM. For large datasets (>100 items), DOM size grows unbounded.

**Files affected:** MachinesList, AlertsList, ChangesList, MachineDetails (all tabs)

**Impact:** Slow page rendering, janky scrolling, high memory usage.

### 3.3 Inconsistent Data Fetching
**Problem:** Two parallel patterns exist:
- React Query hooks (Dashboard, MachinesList, AlertsList, ChangesList) 
- Raw service calls + useState (MachineDetails, ReportsList, Settings, UserProfile)

**Impact:** The raw-service pages have no caching, no deduplication, no stale-while-revalidate. Every navigation re-fetches all data.

### 3.4 Sequential API Calls in MachineDetails
**File:** `MachineDetails.jsx:103-164`

All tab data is fetched sequentially within a `switch` statement. Tabs like "System Logs" fetch event logs AND startup programs sequentially, then "Activity" fetches timeline AND alerts.

**Fix:** Fire all independent requests in parallel.

### 3.5 Chart Data Over-fetching
**File:** `MachineDetails.jsx` Performance tab

The Performance tab fetches ALL health history for a selected date (potentially 288 rows for a day at 5-min intervals). Chart.js then renders all points. No downsampling.

### 3.6 Large Initial Bundle (316 KB)
Code splitting is in place via `React.lazy()` but the main chunk still bundles all dependencies.

---

## 4. Agent-Side Bottlenecks

### 4.1 Full Health Payload Every Cycle
**File:** `Models/HealthPayload.cs`

**Problem:** Every collection cycle (default 120s) sends the COMPLETE payload: CPU, RAM, Disk, Network (full lists), all processes, all services, all event logs, all startup programs, all USB events.

**Impact:** For a machine with 200 processes, 150 services, 5 disks, 5 network adapters — payload is ~50-100KB every 2 minutes. For 10,000 machines: 5-10 GB/minute of ingress data.

**Fix:** Send differential updates for frequently changing metrics. Only send full snapshot on initial registration and every 24h.

### 4.2 CpuCollector Thread.Sleep
**File:** `Collectors/CpuCollector.cs`

**Problem:** `Thread.Sleep(1000)` inside `Task.Run` blocks a thread-pool thread for 1 second.

**Impact:** Wastes thread-pool resources. Could use `Task.Delay` or better sampling approach.

### 4.3 No Event Log Bookmarking
**File:** `Collectors/EventLogCollector.cs`

**Problem:** Every cycle reads the last 24 hours of event logs from position 0. Same events are re-read and re-sent.

**Impact:** Duplicate data transmission, wasted CPU on repeated log parsing.

### 4.4 Peripheral Data Collection Redundancy
**Files:** `SchedulerService.cs`, `MonitoringService.cs`

**Problem:** Peripheral data is collected BOTH by the main collection cycle AND by a separate timer in SchedulerService.

**Impact:** Duplicate data, wasted system resources.

---

## 5. Network / Transfer Bottlenecks

### 5.1 No Response Compression
No Gzip or Brotli compression is configured on the Laravel backend. API responses (especially machine lists, alert lists, health history) are transmitted uncompressed.

**Impact:** 3-5x larger transfer sizes than necessary.

### 5.2 No WebSocket/SSE for Real-Time Alerts
Alerts are fetched via polling (React Query with `staleTime: 15000` — 15s polling). This means:
- 4 requests per minute per open page
- For 100 concurrent dashboard users: 400 requests/minute just for alerts

### 5.3 No Caching Headers
API responses lack `Cache-Control`, `ETag`, or `Last-Modified` headers. Browsers and CDNs cannot cache any responses.

---

## 6. Impact Summary

| Bottleneck | Affected Component | Impact Level | Users Affected |
|------------|-------------------|--------------|----------------|
| Missing indexes | Database | Critical | All |
| WHERE HAS anti-pattern | Backend | Critical | Dashboard users |
| No pagination on sub-resources | Backend | High | Machine detail users |
| Monolithic MachineDetails | Frontend | Critical | Machine detail users |
| Full payload every cycle | Agent | High | All (network/server) |
| No response compression | Backend | High | All |
| No virtualized lists | Frontend | Medium | All list pages |
| Inconsistent data fetching | Frontend | Medium | All |
| Sequential API calls | Frontend | Medium | Machine detail users |
| Log middleware overhead | Backend | Medium | Agent endpoints |
| SELECT * in repositories | Backend | Low-Medium | All |

---

## 7. Recommended Priority Order

1. **Critical (Immediate):** Missing indexes, WHERE HAS → join, paginate sub-resources
2. **Critical (Immediate):** Refactor MachineDetails.jsx to React Query + lazy tabs
3. **High (This sprint):** Response compression (Gzip/Brotli), caching headers
4. **High (This sprint):** Differential agent payloads, event log bookmarking
5. **Medium (Next sprint):** WebSocket/SSE for real-time alerts
6. **Medium (Next sprint):** List virtualization on all pages
7. **Low (Backlog):** Log middleware sampling, SELECT * cleanups
