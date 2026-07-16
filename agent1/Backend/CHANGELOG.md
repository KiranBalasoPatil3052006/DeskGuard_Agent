# Changelog

## v2.1.0 - 2026-06-29

### Added
- 9 fields to `machine_current_status` fillable/casts in `MachineCurrentStatus` model: cpu_clock_speed, cpu_core_count, disk_used_bytes, disk_health_status, battery_charging_status, battery_wear_level, network_received_bytes, network_sent_bytes, last_collected_at
- Fallback field name support for process payload (`cpuUsage`, `memoryUsageMb`) in `mapProcesses()` and `ProcessProcessor`
- Frontend API service layer: `src/services/api.js` (axios instance with Bearer token injection, 401 auto-redirect), `auth.js`, `machines.js`, `alerts.js`, `dashboard.js`
- Auth context (`AuthContext.jsx`) with login/logout/token persistence via localStorage
- `ProtectedRoute` component wrapping authenticated routes
- Frontend Login page: integrated with real `POST /api/v1/auth/login` API, pre-filled admin email
- Navbar: displays real authenticated user name/role, uses auth context logout

### Fixed
- `machine_current_status` NULL columns: all 9 previously-missing fields now properly written (was silent mass-assignment discard)
- `windows_updates` severity/category/description/is_installed: `mapUpdates()` was converting summary to array format stripping `pendingSecurityUpdateCount`, `lastInstallationDate`, `isUpToDate` — now passes raw `updateInfo` through to UpdateProcessor summary handler
- `network_adapters.speed`: `mapNetworkAdapters()` was dropping `connectionSpeedMbps` from mapped output
- `process_logs` cpu_usage/memory_usage: added fallback to `cpuUsage` and `memoryUsageMb` field names for test payload compatibility
- Frontend mock data replaced with real API calls across Dashboard (SummaryCards, SystemHealthOverview, PerformanceCharts, MachineTable, RecentAlerts), MachinesList, MachineDetails, AlertsList

### Audit
- Comprehensive NULL-value audit across all 12 tables (machines, machine_current_status, health_logs, machine_disks, machine_network_adapters, antivirus_status, firewall_status, windows_updates, windows_services, event_logs, process_logs, raw_payload_logs)
- Confirmed only intentional NULLs remain: `machines.manufacturer/model/serial_number/bios_version` (separate hardware endpoint), `machines.activation_token/api_token/activated_at` (device activation), and `health_logs` per-metric time-series design (1 metric per row)

### Changed
- Frontend Architecture:
  - `App.jsx`: wrapped with `AuthProvider`, added `ProtectedRoute` gate for all authenticated routes
  - `Dashboard.jsx`: fetches from `GET /api/v1/dashboard/company`, `GET /api/v1/machines`, `GET /api/v1/alerts` via Promise.all
  - `MachinesList.jsx`: fetches from `GET /api/v1/machines` with pagination, status filter, search
  - `MachineDetails.jsx`: fetches from `GET /api/v1/machines/{id}`, `GET /api/v1/machines/{id}/status`, `GET /api/v1/machines/{id}/history`
  - `AlertsList.jsx`: fetches from `GET /api/v1/alerts` with severity/status filters, pagination, detail modal
  - `Settings.jsx`: added Alert Thresholds tab with inline editing, enabled toggle, save via `PUT /api/v1/alert-rules/{id}`
  - All dashboard sub-components (`SummaryCards`, `SystemHealthOverview`, `PerformanceCharts`, `MachineTable`, `RecentAlerts`): accept real data props

## v2.0.0 - 2026-06-23

### Added
- Mobile number as primary identity (users auto-created after OTP verification)
- OTP-based agent authentication flow (request-otp → verify-otp → bearer token)
- OTP generation/verification service with 10-minute expiry
- Device event monitoring API (connect/disconnect events)
- Device sync API (full peripheral snapshot)
- Admin search API (unified search across users and machines)
- Admin user detail API (user + machines + events)
- Alert rule checking for USB/Printer/External/Bluetooth device connections
- 3 new migrations: otp_codes, device_events, machine_connected_devices
- 2 migration updates: add mobile_number/is_verified to users, add device_name/status/api_token to machines
- 3 new models: OtpCode, DeviceEvent, MachineConnectedDevice
- 3 new controllers: AgentAuthController, DeviceEventController, AdminSearchController
- New service: OtpService
- Sanctum routes for agent device endpoints (auth:sanctum)
- SYSTEM_WORKFLOW.md documentation

### Changed
- User model: nullable name/email/password, added mobile_number + is_verified
- Machine model: added device_name, status, api_token, deviceEvents, connectedDevices relationships
- Routes: added OTP, device event, and admin search endpoints

## v1.0.0 - 2026-06-22

### Added
- Laravel 13.8.0 project initialization
- MySQL database setup (deskguard)
- Laravel Sanctum for API token authentication
- Spatie Laravel Permission for RBAC
- Intervention Image, Maatwebsite Excel, DomPDF packages
- Default migrations (users, cache, jobs, personal_access_tokens)
- Project documentation (PROJECT_CONTEXT.md, README.md, CHANGELOG.md)
