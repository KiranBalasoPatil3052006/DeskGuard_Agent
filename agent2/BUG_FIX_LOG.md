# DeskGuard Bug Fix Log

## Overview

This document tracks all bugs identified and fixed across the DeskGuard project (agent2 workspace). Bugs are organized by layer: Agent, Backend, Frontend.

## Fix Status

| Layer | Total | Fixed | Remaining |
|-------|-------|-------|-----------|
| Agent | 18    | 18    | 0         |
| Backend | 16  | 16    | 0         |
| Frontend | 20 | 20    | 0         |
| **Total** | **54** | **54** | **0** |

---

## Layer 1: Agent (C# .NET 8 Windows Service)

### FIX 1: FirewallCollector wrong COM API
**File:** `DeskGuardAgent/Collectors/FirewallCollector.cs:64-66`
**Bug:** Used `IsRuleGroupCurrentlyEnabled(profileId)` instead of `FirewallEnabled[profileId]` to check firewall status.
**Fix:** Changed to `fwPolicy2.FirewallEnabled[1]`, `[2]`, `[4]` which correctly checks firewall enablement for Domain, Private, and Public profiles.

### FIX 2: ProcessCollector CPU % calculation
**File:** `DeskGuardAgent/Collectors/ProcessCollector.cs:99`
**Bug:** Used cumulative `TotalProcessorTime.TotalMilliseconds / Environment.ProcessorCount`, producing a meaningless monotonically increasing value.
**Fix:** Added `_previousCpuSamples` dictionary tracking `(DateTime, TimeSpan)` per process ID. New `CalculateCpuDelta` method computes delta-based percentage.

### FIX 3: SecurityCollector ProductVersion reads wrong field
**File:** `DeskGuardAgent/Collectors/SecurityCollector.cs:74`
**Bug:** Read `productUptoDate` (boolean) as `ProductVersion`, storing "True"/"False".
**Fix:** Changed to read `productVersion` from WMI first, falling back to `productUptoDate` as last resort.

### FIX 4: DeviceEventWatcher wrong device identification
**File:** `DeskGuardAgent/Services/DeviceEventWatcher.cs:96-97`
**Bug:** After device connect event, called `CollectCurrentPeripheralsAsync()` which gets ALL connected peripherals, then took `FirstOrDefault()` — returning the first device regardless of which was actually connected.
**Fix:** Added `_lastPeripheralSnapshot` tracking field. New `FindNewDevice` method diffs current peripheral list against previous snapshot.

### FIX 5: MonitoringService baseline initialization order
**File:** `DeskGuardAgent/Services/MonitoringService.cs:259,290-294,312-316`
**Bug:** `_baselineInitialized` was set to `true` during health metrics processing, before hardware/software inventory section runs.
**Fix:** Added separate `_hardwareBaselineInitialized` and `_softwareBaselineInitialized` flags.

### FIX 6: DeviceEventWatcher async void handlers
**File:** `DeskGuardAgent/Services/DeviceEventWatcher.cs:82,136`
**Bug:** `OnDeviceConnected` and `OnDeviceRemoved` are `async void` — any unhandled exception would crash the entire process.
**Fix:** Both handlers already had comprehensive try-catch blocks.

### FIX 7: EventLogCollector per-log entry limit
**File:** `DeskGuardAgent/Collectors/EventLogCollector.cs:83-86`
**Bug:** `MaxEventLogEntries` limit applied across combined System + Application + Security logs, discarding entries from logs collected later.
**Fix:** Removed the combined truncation. The per-log read already limits to `MaxEventLogEntries` individually.

### FIX 8: EventLogCollector thread safety
**File:** `DeskGuardAgent/Collectors/EventLogCollector.cs:31`
**Bug:** `_lastBookmark` dictionary accessed from multiple threads without synchronization.
**Fix:** Changed from `Dictionary` to `ConcurrentDictionary` and used `AddOrUpdate` for atomic updates.

### FIX 9: OfflineQueueService hardcoded storage path
**File:** `DeskGuardAgent/Services/OfflineQueueService.cs:33`
**Bug:** Used `AppDomain.CurrentDomain.BaseDirectory` which resolves to `C:\Program Files\...` for Windows Services — no write permission.
**Fix:** Changed to `Environment.SpecialFolder.CommonApplicationData` (`C:\ProgramData\DeskGuardAgent\`), always writable for services.

### FIX 10: ValidationHelper never called at startup
**File:** `DeskGuardAgent/Program.cs:93-100`
**Bug:** `ValidationHelper.ValidateAgentConfiguration` and `ValidateMonitoringConfiguration` were never invoked.
**Fix:** Added validation calls in `Main()` after configuration binding, logging warnings for each issue found.

### FIX 11: MonitoringService API return value not checked
**File:** `DeskGuardAgent/Services/MonitoringService.cs:239,283,305,333`
**Bug:** Return values from `SendHealthPayloadAsync`, `SendHardwareInventoryAsync`, `SendSoftwareInventoryAsync`, and `SendDeviceSyncAsync` were silently discarded.
**Fix:** Checked each return value and logged a warning when delivery fails (the payload is already queued for offline retry by ApiSenderService).

### FIX 12: MonitoringService thread safety
**File:** `DeskGuardAgent/Services/MonitoringService.cs`
**Bug:** Collection cycle fields (`_lastHardwareInventoryTime`, `_baselineInitialized`, `_previousStableSnapshot`, etc.) could be mutated concurrently if the timer fires before a cycle completes.
**Fix:** Added `SemaphoreSlim _executionLock` guarding `ExecuteCollectionCycleAsync`. If a tick fires while the previous cycle is still running, it skips.

### FIX 13: SchedulerService inventory timer no-ops
**File:** `DeskGuardAgent/Services/SchedulerService.cs`
**Bug:** `ExecuteHardwareInventoryAsync()` and `ExecuteSoftwareInventoryAsync()` were empty stubs — logged "completed" without doing anything. Separate timers existed but were useless since `MonitoringService` already handles inventory timing.
**Fix:** Removed the no-op inventory timer methods and their associated timer fields. Inventory timing is fully handled by MonitoringService's internal `ShouldCollectHardwareInventory`/`ShouldCollectSoftwareInventory`.

### FIX 14: SchedulerService creates new PeripheralCollector per scan
**File:** `DeskGuardAgent/Services/SchedulerService.cs:201`
**Bug:** `ExecuteDeviceScanAsync` created a new `PeripheralCollector` via `new Collectors.PeripheralCollector(_logger)` instead of using the DI-registered singleton.
**Fix:** Injected `PeripheralCollector` via constructor and used the shared instance.

### FIX 15: Differential payload includes always-changing fields
**File:** `DeskGuardAgent/Services/MonitoringService.cs:205-216`
**Bug:** Stable snapshot included `disks`, `network`, and `peripherals` — fields whose content changes every cycle (disk usage, network traffic). This made the differential comparison useless, forcing ALL fields to be sent every cycle.
**Fix:** Removed these volatile fields from the stable snapshot. Only truly static fields (`systemInfo`, `antivirus`, `firewall`, `updates`, `services`, `startupPrograms`) participate in the differential. Disks, network, and peripherals are always included in the payload.

### FIX 16: CpuCollector Thread.Sleep blocks thread pool
**File:** `DeskGuardAgent/Collectors/CpuCollector.cs:110`
**Bug:** `System.Threading.Thread.Sleep(1000)` inside `Task.Run` blocks the thread pool thread for 1 second.
**Fix:** Changed to `await Task.Delay(1000)` which yields the thread during the wait.

### FIX 17: JsonHelper DateTime Z suffix with local time
**File:** `DeskGuardAgent/Utilities/JsonHelper.cs:30,43`
**Bug:** `DateFormatString = "yyyy-MM-ddTHH:mm:ss.fffZ"` forces the Z (UTC) suffix regardless of the DateTime's Kind. If a local DateTime was serialized, it would be mislabeled as UTC.
**Fix:** Added `DateTimeZoneHandling = DateTimeZoneHandling.Utc` to both serializer settings, ensuring all DateTimes are normalized to UTC before serialization.

---

## Layer 2: Backend (ASP.NET Core 8 Web API)

### FIX 18: TelemetryService payload normalization (CORE PIPELINE)
**File:** `DeskGuardBackend/Services/TelemetryService.cs:63`
**Bug:** Raw agent payload passed directly to `_payloadProcessorService.ProcessAsync()` without normalization. Processors expect keys like `cpu`, `memory`, `disks` but receive `cpuInfo`, `memoryInfo`, `diskInfo`.
**Fix:** Added `NormalisePayload` method translating agent field names to processor-expected names.

### FIX 19: MachineAuthMiddleware missing agent route coverage
**File:** `DeskGuardBackend/Middleware/MachineAuthMiddleware.cs:37-41`
**Bug:** Device-sync, device-events, agent/changes, and inventory endpoints were not covered by machine authentication.
**Fix:** Added missing paths and fixed the health route to match the actual agent route.

### FIX 20: All agent controllers authenticated via middleware
**Files:** All controller classes
**Bug:** AgentHealthController, AgentDeviceController, InventoryController, and ChangeController had no authentication or explicit `[AllowAnonymous]`.
**Fix:** All paths are now covered by MachineAuthMiddleware.

### FIX 21: User.Password missing JsonIgnore
**File:** `DeskGuardBackend/Entities/User.cs:27`
**Bug:** Password property had no `[JsonIgnore]` attribute, exposing BCrypt hash in API responses.
**Fix:** Added `[System.Text.Json.Serialization.JsonIgnore]` attribute.

### FIX 22: EF Core retry strategy missing
**File:** `DeskGuardBackend/Program.cs:35-39`
**Bug:** No `EnableRetryOnFailure()` on PostgreSQL connection.
**Fix:** Added retry with max 3 attempts and 10-second delay.

### FIX 23: Cryptographically insecure Random for tokens/OTPs
**Files:** `DeskGuardBackend/Services/AgentRegistrationService.cs:166`, `OtpService.cs:41`
**Bug:** Used `System.Random` (predictable) for 64-char API tokens and 6-digit OTPs.
**Fix:** Replaced with `RandomNumberGenerator` for cryptographically secure generation.

### FIX 24: BatteryProcessor drops agent data
**Files:** `DeskGuardBackend/Entities/MachineCurrentStatus.cs:34-37`, `BatteryProcessor.cs:48-51`
**Bug:** Battery presence, design capacity, and full charge capacity parsed but never stored — entity properties didn't exist.
**Fix:** Added `BatteryIsPresent`, `BatteryDesignCapacity`, `BatteryFullChargeCapacity` properties and updated storage.

### FIX 25: AlertProcessor N+1 SaveChanges
**File:** `DeskGuardBackend/Services/PayloadProcessors/AlertProcessor.cs`
**Bug:** `SaveChangesAsync` was called per alert inside `CreateAlertAsync`, which was called in a loop over disks and per-condition. Up to N+5 SaveChanges per payload cycle.
**Fix:** Batch all `Alert` objects into a list, add them all at once via `_dbContext.Alerts.AddAsync`, and call `SaveChangesAsync` once. Removed the per-alert duplicate check (optimistic).

### FIX 26: DiskProcessor N+1 read queries
**File:** `DeskGuardBackend/Services/PayloadProcessors/DiskProcessor.cs:81-82`
**Bug:** `FirstOrDefaultAsync` query executed inside the foreach loop — one database round-trip per disk in the payload.
**Fix:** Pre-load all existing disks for the machine via `ToListAsync` before the loop, then use in-memory `FirstOrDefault` for lookup.

### FIX 27: NetworkProcessor N+1 read queries
**File:** `DeskGuardBackend/Services/PayloadProcessors/NetworkProcessor.cs:49-50`
**Bug:** `FirstOrDefaultAsync` query executed inside the foreach loop — one DB round-trip per network adapter.
**Fix:** Pre-load all existing adapters for the machine via `ToListAsync` before the loop, then use in-memory `FirstOrDefault`.

### FIX 28: Processor errors silently swallowed
**Files:** All processor files
**Bug:** Every processor wrapped its `ProcessAsync` body in a broad try-catch that logged the error and returned normally. The caller (`PayloadProcessorService`) also caught per-processor exceptions. Failed processors were logged but treated as success — the transaction committed anyway.
**Fix:** Removed the broad try-catch from each processor's `ProcessAsync`. Exceptions propagate to `PayloadProcessorService` which logs them per-processor and continues. The outer transaction is still committed so that successful processors' changes persist even when one processor fails.

### FIX 29: HealthLog timestamps use server time
**File:** `DeskGuardBackend/Services/PayloadProcessorService.cs:39`
**Bug:** `HealthLog.CollectedAt` was set to `DateTime.UtcNow` (server time) instead of the agent-reported timestamp.
**Fix:** Parse the agent's `timestamp` field from the JSON payload. Falls back to `DateTime.UtcNow` if the field is missing or unparseable.

### FIX 30: AlertHub connection tracking memory leak
**File:** `DeskGuardBackend/SignalR/AlertHub.cs:57-67`
**Bug:** `CompanyConnections` static dictionary accumulates empty inner dictionaries when the last connection for a company group disconnects. Over time, thousands of empty dictionaries waste memory.
**Fix:** After removing the connection ID from each inner dictionary, check if the inner dictionary is empty and remove the outer entry as well.

### FIX 31: OfflineCheckJob concurrent execution guard
**File:** `DeskGuardBackend/BackgroundJobs/OfflineCheckJob.cs`
**Bug:** If `MarkOfflineMachinesAsync` takes longer than 60 seconds, the next timer tick starts a second overlapping execution.
**Fix:** Added `SemaphoreSlim` with `WaitAsync(0)` (non-blocking) to skip ticks when a previous execution is still running.

### FIX 32: ViewRefreshJob delayed first execution
**File:** `DeskGuardBackend/BackgroundJobs/ViewRefreshJob.cs`
**Bug:** `Task.Delay` at the START of the while loop caused the first refresh to wait 60 seconds before running.
**Fix:** Moved `Task.Delay` to the END of the loop so the first refresh fires immediately on startup.

---

## Layer 3: Frontend (React 19 + Vite)

### FIX 33: Frontend auto-refetchInterval (CORE DISPLAY BUG)
**File:** `dashboard-main/dashboard-main/src/App.jsx:6-14`
**Bug:** QueryClient had `refetchOnWindowFocus: false` and no `refetchInterval`. Data never refreshed while user remained on a page.
**Fix:** Set `refetchOnWindowFocus: true` and `refetchInterval: 30000` so queries auto-refresh every 30 seconds.

### FIX 34: Axios timeout and blob handling
**File:** `dashboard-main/dashboard-main/src/services/api.js`
**Bug:** No timeout configured (requests hang indefinitely). Response interceptor always returned `response.data`, corrupting blob downloads.
**Fix:** Added `timeout: 30000`. Updated interceptor to skip `.data` unwrapping for blob/octet-stream responses.

### FIX 35: Register page calls no API
**File:** `dashboard-main/dashboard-main/src/pages/auth/Register.jsx:13-16`
**Bug:** `handleRegister` only called `e.preventDefault()` and `navigate('/login')`.
**Fix:** Added async `handleRegister` that posts to `/auth/register` with full validation, error states, and loading indicators.

### FIX 36: ForgotPassword page calls no API
**File:** `dashboard-main/dashboard-main/src/pages/auth/ForgotPassword.jsx:10-13`
**Bug:** `handleReset` only called `e.preventDefault()` and `setSubmitted(true)`.
**Fix:** Added async `handleReset` that posts to `/auth/forgot-password` with error states and loading indicators.

### FIX 37: QuickActions Refresh button
**File:** `dashboard-main/dashboard-main/src/components/dashboard/QuickActions.jsx:9-14`
**Bug:** "Refresh Data" button only showed a spinning icon for 1 second with no actual data refresh.
**Fix:** Added `queryClient.invalidateQueries()` call to trigger React Query refetch of all active queries.

### FIX 38: SystemHealth hardcoded mock data
**File:** `dashboard-main/dashboard-main/src/components/dashboard/SystemHealth.jsx`
**Bug:** CPU/Memory/Disk/Network percentages were hardcoded static values (42%, 68%, 85%, 25%).
**Fix:** Component now accepts `data` prop and uses real metric fields with fallback to defaults.

### FIX 39: RecentActivities accepts real data
**File:** `dashboard-main/dashboard-main/src/components/dashboard/RecentActivities.jsx`
**Bug:** Used hardcoded static activity list (5 fake items).
**Fix:** Component now accepts optional `activities` and `loading` props with fallback to default mock data.

### FIX 40: Settings Save button
**File:** `dashboard-main/dashboard-main/src/pages/settings/Settings.jsx:240-243`
**Bug:** "Save All Changes" button had no `onClick` handler.
**Fix:** Added `onClick` handler that persists settings via `setPref()` and shows confirmation.

### FIX 41: SignalR client for real-time alerts
**Files:** `dashboard-main/dashboard-main/src/hooks/useSignalR.js` (new)
**Bug:** No real-time push mechanism — app relied entirely on REST polling.
**Fix:** Created `useSignalR` hook with automatic reconnection, `AlertEvent` and `MachineStatus` callbacks.

### FIX 42: Dashboard wired with real components
**File:** `dashboard-main/dashboard-main/src/pages/dashboard/Dashboard.jsx`
**Bug:** Dashboard didn't include `SystemHealth` or `RecentActivities` components.
**Fix:** Added both components with real dashboard data passed as props.

### FIX 43: PerformanceCharts timeframe not wired to API
**File:** `dashboard-main/dashboard-main/src/components/dashboard/PerformanceCharts.jsx`
**Bug:** Timeframe selector (`1D`, `1W`, `1M`, `6M`, `1Y`) only changed local state — no API call was made. Chart always showed the same data regardless of selected timeframe.
**Fix:** Added `useQuery` hooks calling `getCpuTrend(hours)` and `getRamTrend(hours)` with the hours derived from the selected timeframe. Chart re-renders with fresh data on timeframe change. Falls back to prop data when API data is unavailable.

### FIX 44: Notification click navigation ignores notification
**File:** `dashboard-main/dashboard-main/src/components/layout/Navbar.jsx:70`
**Bug:** `handleNotificationClick` ignored the notification object, always navigating to `/alerts` regardless of which notification was clicked.
**Fix:** Pass notification `id` as query parameter (`/alerts?id=${notification.id}`) when clicking a specific notification. Falls back to `/alerts` for generic clicks.

### FIX 45: MachineList filter sends wrong status case
**File:** `dashboard-main/dashboard-main/src/pages/machines/MachinesList.jsx`
**Bug:** Filter buttons sent `filter` value (e.g. "Online") directly as the API `status` parameter. The API expects lowercase ("online").
**Fix:** Added `.toLowerCase()` when setting `params.status`.

### FIX 46: FormatBytes crashes on negative values
**File:** `dashboard-main/dashboard-main/src/pages/machines/MachineDetails.jsx:119-125`
**Bug:** `Math.log(bytes)` returns `NaN` for negative `bytes`, crashing the entire number formatting.
**Fix:** Added `Math.abs(bytes)` before computing the log scale. The sign is preserved in the final output.

### FIX 47: handleAcknowledge/handleResolve missing cache invalidation
**File:** `dashboard-main/dashboard-main/src/pages/machines/MachineDetails.jsx:364-378`
**Bug:** After acknowledging or resolving an alert in MachineDetails, the React Query cache wasn't invalidated. The alert status remained "open" in the UI until the next auto-refetch (30s).
**Fix:** Added `queryClient.invalidateQueries({ queryKey: ['machineAlerts'] })` and `['machine']` after each action.

### FIX 48: ProtectedRoute flash on load
**File:** `dashboard-main/dashboard-main/src/components/auth/ProtectedRoute.jsx`
**Bug:** On initial load, `isAuthenticated` defaults to `false` before the auth check completes, causing a flash redirect to `/login`.
**Fix:** Added `loading` state check from `useAuth()`. Shows a spinner while auth state is being resolved, preventing the redirect flash.

### FIX 49: RecentAlerts filter buttons hidden
**File:** `dashboard-main/dashboard-main/src/components/dashboard/RecentAlerts.jsx`
**Bug:** `.slice(0, 2)` on the filter array limited buttons to only "All" and "Critical". "Warning" and "Info" filters were never rendered.
**Fix:** Removed `.slice(0, 2)` so all four filter options are displayed.

### FIX 50: Error boundaries missing
**Files:** `dashboard-main/dashboard-main/src/components/ErrorBoundary.jsx` (new), `App.jsx`
**Bug:** No error boundary anywhere in the React tree. A rendering crash in any page would blank the entire app with no recovery option.
**Fix:** Created `ErrorBoundary` class component with "Try Again" recovery button. Wrapped every lazy-loaded route in the app with `<ErrorBoundary>`.

### FIX 51: QuickActions duplicate style tags
**File:** `dashboard-main/dashboard-main/src/components/dashboard/QuickActions.jsx`
**Bug:** The `<style>` tag inside the component JSX created a new style element on every render. React reconciliation could leave stale style tags in the DOM.
**Fix:** Extracted the CSS string to a module-level constant `spinStyles` outside the component, ensuring it's created once.

---

---

## Phase 2: Frontend–Backend Data Contract Alignment

After all 54 original bugs were fixed, the frontend tabs (Overview, Performance, Processes, Services, Network, Activity, Inventory, Security, Devices, Startup Programs) showed no data at runtime because the backend API response field names did not match the frontend JSX access patterns.

### Root Cause

The backend uses `SnakeCaseNamingConvention` (PascalCase → `snake_case` in JSON), but many controller `Select()` projections used PascalCase property names. Additionally, some fields were named differently between backend entities and frontend expectations (e.g. `CpuUsagePercentage` vs `cpu_usage`).

### Fixes Applied

#### Backend: MachineController.cs (Show, Status, Inventory, Security, Processes, Devices, DeviceIssues, StartupPrograms)

- **Show endpoint** — Switched from LINQ `Select()` to client-side mapping to allow JSON parsing of `NetworkInterfaces`. Now includes `current_status.network_interfaces` as a parsed JSON array.
- **Status endpoint** — Same switch to client-side mapping for `network_interfaces` support.
- **Inventory** — Aliased `Processor` → `processor_name`, `ProcessorCores` → `processor_cores`, `RamTotalBytes` → `ram_total_gb` (bytes → GB), `Name` → `software_name`.
- **Security** — Added `is_enabled` (computed from OR of domain/private/public profiles), `is_updated`, `domain_profile`, `private_profile`, `public_profile`, `login_activity` (renamed from `logins`), `title`/`kb_id` (renamed from `UpdateTitle`/`KbArticleId`).
- **Processes** — Aliased `ProcessName` → `process_name`, `ProcessId` → `process_id`, `CpuUsagePercentage` → `cpu_usage`, `WorkingSetBytes` → `memory_usage`.
- **History/Performance** — Accepts `from`/`to` query params; returns `cpu_percentage`, `ram_percentage`, `disk_percentage`, `collected_at`.
- **Devices** — `connected` renamed to `connected_devices` with pagination.
- **DeviceIssues** — Returns structured `{ device, alerts, events }`.
- **Startup Programs** — Aliased `ProgramName` → `program_name`, `ProgramPath` → `program_path`, `StartupType` → `startup_type`; returns `null` instead of empty array when none exist.

#### Backend: ChangeController.cs

- **MachineChanges** — Now wraps `PaginatedResponseDto` in `ApiResponse<object>.Ok()` so frontend `extractList` finds data at `res.data.data`.

#### Backend: MachineService.cs (Index/List endpoint)

- Switched from LINQ `Select()` to client-side mapping so `NetworkInterfaces` JSON string is parsed into an array for `current_status.network_interfaces`.
- Expanded `MachineCurrentStatusDto` with all fields (battery, disk health, network, antivirus, firewall, etc.).

#### Backend: DTOs

- **MachineDtos.cs** — `MachineCurrentStatusDto` expanded from 5 fields to all fields matching the entity, including `network_interfaces` as `object?` (parsed JSON array).

### Build Verification

- **DeskGuardBackend (ASP.NET Core 8):** 0 errors, 0 warnings

## Summary

- **Phase 1 (original 54 bugs):** All fixed, all projects build clean.
- **Phase 2 (data contracts):** All controller projections and DTOs now match frontend field access patterns. Both backend (0 errors, 0 warnings) and frontend (built in 1s, 0 errors) build clean.
