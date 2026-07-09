# DeskGuard Backend - Project Context

## 1. Overview
REST API backend for the DeskGuard Agent monitoring system. Receives agent health payloads every ~2 minutes, stores time-series metrics, and serves the React dashboard with machine details, status, history, inventory, security, and alerts.

**Stack:** Laravel 13 + PHP 8.3 + MySQL 8.0 + Sanctum token auth

## 2. Tech Stack

| Component | Technology |
|-----------|-----------|
| Framework | Laravel 13.8.0 (v13.16.1) |
| Language | PHP 8.3.31 |
| Database | MySQL 8.0.44 (127.0.0.1:3306, db: `deskguard`) |
| Auth | Laravel Sanctum v4.3.2 |
| Authorization | Spatie Laravel Permission v8.0.0 (RBAC) |
| API Format | REST JSON (`{success, message, data}`) |

### Installed Packages
| Package | Version | Purpose |
|---------|---------|---------|
| laravel/sanctum | v4.3.2 | API token auth |
| spatie/laravel-permission | v8.0.0 | Roles & permissions |
| intervention/image | v4.1.4 | Image manipulation |
| maatwebsite/excel | v3.1.69 | Excel import/export |
| dompdf/dompdf | v3.1.5 | PDF generation |
| barryvdh/laravel-debugbar | v4.3.0 | Debug toolbar (dev) |

## 3. Data Flow: Agent Health Payload → Database

```
POST /api/v1/health
  │
  ▼
AgentHealthController::__invoke()
  │ 1. Resolve machineUid (machineId / machineUid / machine_uid / X-Agent-Id)
  │ 2. Find-or-create Machine record
  │ 3. Store RawPayloadLog (full JSON for debugging)
  │ 4. Call normalisePayload() → maps agent camelCase to internal format
  │ 5. Call PayloadProcessorService::process()
  │
  ▼
PayloadProcessorService::process()
  │ Creates ONE HealthLog row per cycle
  │ Calls 18 processors in order:
  │
  ├── MachineProcessor         → Updates `machines` (hostname, os, domain, uptime, users)
  ├── CpuProcessor             → Updates `machine_current_status` (cpu_percentage, temp, clock)
  ├── MemoryProcessor          → Updates `machine_current_status` (ram_* bytes & percentage)
  ├── DiskProcessor            → Updates `machine_disks` per-drive + `machine_current_status` aggregate
  ├── BatteryProcessor         → Updates `machine_current_status` (battery_* fields)
  ├── NetworkProcessor         → Updates `machine_network_adapters` per-adapter + aggregate status
  ├── HardwareInventoryProcessor → Updates `hardware_inventory` (system specs)
  ├── SoftwareInventoryProcessor → Updates `software_inventory` (installed apps) – NOP in health flow
  ├── ProcessProcessor         → Creates `process_logs` (top 20 by CPU)
  ├── ServiceProcessor         → Updates `windows_services` (only "important" service names)
  ├── AntivirusProcessor       → Updates `antivirus_status`
  ├── FirewallProcessor        → Updates `firewall_status`
  ├── UpdateProcessor          → Creates/updates `windows_updates`
  ├── EventLogProcessor        → Creates `event_logs` (latest 50)
  ├── LoginActivityProcessor   → Creates `login_activities` (maps eventId 4624→Logon, 4634→Logoff; handles EventLogInfo format from agent)
  ├── StartupProgramProcessor  → Batch-replaces `startup_programs`
  ├── UsbActivityProcessor     → Creates `usb_activities` (USB plug/unplug events)
  ├── DeviceProcessor          → Processes connected device snapshots
  ├── DeviceEventProcessor     → Processes device connect/disconnect events
  └── AlertProcessor           → Evaluates alert rules, creates `alerts`
```

## 4. Folder Structure

```
Backend/
├── app/
│   ├── Http/Controllers/Api/V1/
│   │   ├── AgentHealthController.php     # Receives health payloads from agent
│   │   ├── AgentInventoryController.php  # Receives hardware/software inventory
│   │   ├── DeviceEventController.php     # Device connect/disconnect events + sync
│   │   ├── MachineController.php         # CRUD + status, history, processes, services, etc.
│   │   ├── AuthController.php            # Login/logout/refresh
│   │   ├── AgentController.php           # Agent registration, OTP flow
│   │   └── AdminController.php           # Admin search, user management
│   │
│   ├── Models/                   # 24 Eloquent models
│   ├── Services/
│   │   ├── MachineService.php    # Machine CRUD, assignment, heartbeat
│   │   ├── PayloadProcessorService.php  # Orchestrates 18 processors
│   │   ├── InventoryService.php  # Hardware/software inventory queries
│   │   ├── HealthLogRepository.php  # Health log history queries
│   │   ├── AuditLogService.php   # Event auditing
│   │   └── OtpService.php        # OTP generation & verification
│   │
│   ├── Services/PayloadProcessors/  # 18 individual metric processors
│   │   ├── MachineProcessor.php        # machines table
│   │   ├── CpuProcessor.php            # cpu_percentage, temp, clock
│   │   ├── MemoryProcessor.php         # ram_* fields
│   │   ├── DiskProcessor.php           # per-drive + aggregate
│   │   ├── BatteryProcessor.php        # battery percentage, wear, present, capacities
│   │   ├── NetworkProcessor.php        # per-adapter + aggregate traffic
│   │   ├── HardwareInventoryProcessor.php  # system specs
│   │   ├── SoftwareInventoryProcessor.php  # installed software
│   │   ├── ProcessProcessor.php        # process_logs
│   │   ├── ServiceProcessor.php        # windows_services (important only)
│   │   ├── AntivirusProcessor.php      # antivirus_status
│   │   ├── FirewallProcessor.php       # firewall_status
│   │   ├── UpdateProcessor.php         # windows_updates
│   │   ├── EventLogProcessor.php       # event_logs
│   │   ├── LoginActivityProcessor.php  # login_activities
│   │   ├── StartupProgramProcessor.php # startup_programs
│   │   ├── UsbActivityProcessor.php    # usb_activities (NEW)
│   │   ├── DeviceProcessor.php         # machine_connected_devices
│   │   ├── DeviceEventProcessor.php    # device_events
│   │   └── AlertProcessor.php          # alerts
│   │
│   ├── Repositories/
│   │   └── HealthLogRepository.php  # Time-series query logic
│   │
│   └── Traits/
│       └── ApiResponseTrait.php     # successResponse / errorResponse helpers
│
├── database/
│   └── migrations/              # 40 migration files
│
├── routes/
│   └── api.php                  # All API route definitions
│
└── .env                         # DB credentials, app config
```

## 5. Database Schema (39 migrations)

### Core Tables
| Table | Key Columns | Purpose |
|-------|------------|---------|
| `companies` | id, name, is_active | Organization tenant |
| `users` | id, email, mobile_number, is_verified | Admin users |
| `machines` | id, company_id, machine_uid, hostname, device_name, domain_name, architecture, operating_system, os_version, uptime_seconds, current_logged_in_users, manufacturer, model, serial_number, bios_version, processor, ram_gb, employee_mobile_number, is_online, last_heartbeat_at, status | Device endpoints |

### Status & Metrics
| Table | Key Columns | Purpose |
|-------|------------|---------|
| `machine_current_status` | id, machine_id, cpu_percentage, cpu_temperature, cpu_clock_speed, cpu_core_count, ram_*, disk_*, battery_*, battery_is_present, battery_design_capacity, battery_full_charge_capacity, network_sent_bytes, network_received_bytes, online_status, collected_at | Latest live snapshot (1 row per machine) |
| `health_logs` | id, machine_id, cpu_percentage, ram_percentage, disk_percentage, battery_percentage, collected_at | Time-series history for charts |

### Per-Metric Tables
| Table | Key Columns | Purpose |
|-------|------------|---------|
| `machine_disks` | id, machine_id, drive_letter, volume_label, total_gb, used_gb, free_gb, file_system, drive_type, health_status | Disk partitions |
| `machine_network_adapters` | id, machine_id, adapter_name, ip_address, ip_address_v6, mac_address, adapter_type, speed, bytes_sent, bytes_received, status | Network adapters |
| `process_logs` | id, machine_id, process_name, process_id, executable_path, thread_count, user_name, cpu_usage, memory_usage, collected_at | Running process snapshots |
| `windows_services` | id, company_id, machine_id, service_name, display_name, status, start_type, service_type, log_on_as | Windows services |
| `event_logs` | id, machine_id, event_id, level, source, message, time_generated | Event log entries |
| `windows_updates` | id, machine_id, update_name, description, is_installed, installed_at | Windows updates |

### Inventory
| Table | Key Columns | Purpose |
|-------|------------|---------|
| `hardware_inventory` | id, company_id, machine_id, manufacturer, model, serial_number, bios_version, bios_vendor, bios_release_date, processor_name, processor_cores, processor_threads, processor_clock_speed, system_architecture, ram_total_gb, ram_type, disk_model, disk_type, disk_size_gb, gpu_name | Hardware asset inventory |
| `software_inventory` | id, company_id, machine_id, software_name, version, publisher, install_date, architecture, registry_key_path, estimated_size_mb | Installed software |

### Security & Activity
| Table | Key Columns | Purpose |
|-------|------------|---------|
| `antivirus_status` | id, company_id, machine_id, display_name, is_real_time_protection, is_signature_up_to_date, product_version, collected_at | Antivirus status |
| `firewall_status` | id, company_id, machine_id, is_domain_enabled, is_private_enabled, is_public_enabled, active_profile, collected_at | Firewall status |
| `login_activities` | id, company_id, machine_id, event_type, username, session_id, logon_time, collected_at | Login events |
| `usb_activities` | id, company_id, machine_id, device_name, device_serial, drive_letter, event_type, collected_at | USB plug/unplug events |
| `startup_programs` | id, company_id, machine_id, program_name, program_path, registry_key, startup_type, status, collected_at | Auto-start programs (registry_key & status added by 2026_07_06_000002) |

### Devices & Alerts
| Table | Key Columns | Purpose |
|-------|------------|---------|
| `device_events` | id, machine_id, device_name, device_type, manufacturer, connection_type, event_type, event_time | Device connect/disconnect log |
| `machine_connected_devices` | id, machine_id, device_name, device_type, manufacturer, connection_type, status, last_seen | Current device snapshot |
| `alerts` | id, machine_id, alert_rule_id, alert_type, severity, title, description, triggered_at, is_acknowledged | Security/event alerts |
| `alert_rules` | id, rule_type, condition, severity, is_active | Alert rule definitions |

### Other
| Table | Purpose |
|-------|---------|
| `otp_codes` | OTP verification codes |
| `machine_tokens` | Agent API tokens |
| `raw_payload_logs` | Full raw JSON of every received agent payload |
| `audit_logs` | Admin action audit trail |
| `notifications` | System notifications |
| `reports` | Generated reports |
| `email_recipients` | Email notification recipients |
| `personal_access_tokens` | Sanctum tokens |
| `permissions` / `roles` / `role_has_permissions` / `model_has_roles` / `model_has_permissions` | Spatie RBAC tables |

## 6. API Routes

### Public (no auth)
| Method | Route | Description |
|--------|-------|-------------|
| POST | `/api/v1/auth/login` | Admin login (email + password) |
| POST | `/api/v1/agent/register` | Activation-token based machine registration |
| POST | `/api/v1/agent/request-otp` | Request OTP for mobile verification |
| POST | `/api/v1/agent/verify-otp` | Verify OTP → get Sanctum bearer token |

### Auth: sanctum (admin users)
| Method | Route | Description |
|--------|-------|-------------|
| POST | `/api/v1/auth/logout` | Logout (revoke current token) |
| GET | `/api/v1/machines` | List machines (paginated, searchable) |
| GET | `/api/v1/machines/online` | Count online machines |
| GET | `/api/v1/machines/offline` | Count offline machines |
| GET | `/api/v1/machines/{id}` | Machine detail (with company, user, current_status) |
| POST | `/api/v1/machines/{id}/assign` | Assign user to machine |
| POST | `/api/v1/machines/{id}/unassign` | Unassign user from machine |
| GET | `/api/v1/machines/{id}/status` | Current status snapshot |
| GET | `/api/v1/machines/{id}/history` | Health log history (from/to params) |
| GET | `/api/v1/machines/{id}/inventory` | Hardware + software inventory |
| GET | `/api/v1/machines/{id}/security` | Antivirus + firewall + logins + updates |
| GET | `/api/v1/machines/{id}/devices` | Connected devices + device events |
| GET | `/api/v1/machines/{id}/processes` | Recent process logs |
| GET | `/api/v1/machines/{id}/services` | Windows services |
| GET | `/api/v1/machines/{id}/startup-programs` | Startup programs |
| GET | `/api/v1/machines/{id}/event-logs` | Event logs |
| GET | `/api/v1/machines/{id}/network` | Network adapters + disks |
| GET | `/api/v1/machines/{id}/alerts` | Machine alerts |
| GET | `/api/v1/machines/{id}/timeline` | Combined activity timeline |
| GET | `/api/v1/alerts` | All alerts (paginated, filterable) |
| GET | `/api/v1/device-events` | All device events (paginated) |
| GET | `/api/v1/admin/search` | Unified search (users + machines) |
| GET | `/api/v1/admin/users/{id}` | User detail with machines, events |

### Auth: sanctum (agent endpoints via machine middleware)
| Method | Route | Description |
|--------|-------|-------------|
| POST | `/api/v1/health` | Agent health payload (main data ingress) |
| POST | `/api/v1/inventory/hardware` | Hardware inventory (24h interval) |
| POST | `/api/v1/inventory/software` | Software inventory (24h interval) |
| POST | `/api/v1/device-events` | Device connect/disconnect event |
| POST | `/api/v1/device-sync` | Full device snapshot sync |

## 7. Agent Payload Processing

### Health Payload Format (POST /api/v1/health)

The C# agent sends a flattened camelCase JSON payload. The `AgentHealthController::normalisePayload()` method maps agent keys to internal keys:

| Agent Key | Internal Key | Processed By |
|-----------|-------------|-------------|
| `machineId` | `machineId` | MachineProcessor |
| `systemInfo` | `systemInfo` | MachineProcessor |
| `cpuInfo` | `cpu` | CpuProcessor |
| `memoryInfo` | `memory` | MemoryProcessor |
| `diskInfo` | `disks` | DiskProcessor |
| `batteryInfo` | `battery` | BatteryProcessor |
| `networkInfo` | `networkAdapters` (mapped) | NetworkProcessor |
| `processInfo` | `processes` (mapped) | ProcessProcessor |
| `serviceInfo` | `services` | ServiceProcessor |
| `antivirusInfo` | `antivirus` (mapped) | AntivirusProcessor |
| `firewallInfo` | `firewall` (mapped) | FirewallProcessor |
| `updateInfo` | `windowsUpdates` (mapped) | UpdateProcessor |
| `eventLogInfo` | `eventLogs` | EventLogProcessor |
| `loginActivityInfo` | `loginActivities` (mapped) | LoginActivityProcessor |
| `usbActivityInfo` | `usbActivities` (mapped) | UsbActivityProcessor |
| `startupProgramInfo` | `startupPrograms` | StartupProgramProcessor |
| `peripheralInfo` | `connectedDevices` | DeviceProcessor |
| `employeeMobileNumber` | (stored on Machine) | AgentHealthController |

### Field Name Conventions
- **Agent → Backend**: C# PascalCase → JSON camelCase via Newtonsoft `CamelCasePropertyNamesContractResolver`
- **Backend storage**: snake_case (Laravel convention)
- **Backend processors** read both camelCase and snake_case keys as fallback (e.g., `cpuUsage` vs `cpu_usage`)

### Device Sync Payload (POST /api/v1/device-sync)
- Agent sends `{ machine_uid, devices: [{ device_name, device_type, manufacturer, connection_type, device_status, last_seen }] }`
- Backend marks existing `connected` devices as `removed`, then upserts each device
- Uses composite UNIQUE index on `(machine_id, device_name)` for O(n) performance

## 8. Current Status & Known Issues

### Working
- All 40 migrations applied successfully (last: `2026_07_06_000002` adds `registry_key` + `status` to `startup_programs`)
- 10 dashboard tabs: Overview → Performance → Processes → Services → Network → Activity → Inventory → Security → Devices → System Logs
- Live Monitoring page fetches real API data every 30 seconds
- Device sync with camelCase alias handling
- All backend processors read multiple field name variants
- `employeeMobileNumber` now sent by agent from appsettings
- LoginActivityProcessor maps eventId→eventType (4624→Logon, 4625→Failed Logon, 4634→Logoff, etc.) for EventLogInfo format from agent
- All 17 pipeline model `$fillable` arrays verified to match their migrations
- Machine model now has computed accessors for `employee_name`, `current_user`, `cpu_model` (via `$appends` — no DB changes needed)
- LoginActivity model has computed `is_success` accessor (true for Logon/Logoff types, false for Failed Logon)

### Frontend ⇔ Backend Field Audit (2026-07-06)
Complete cross-reference of every field used in `MachineDetails.jsx` (10 tabs) and `LiveMonitoring.jsx` against actual backend controller responses and DB columns:

| Tab | Field | Backend Source | Status |
|-----|-------|---------------|--------|
| Overview | `cs.cpu_usage` | Fallback for `cpu_percentage` | ✅ Fallback |
| Overview | `cs.memory_usage` | Fallback for `ram_percentage` | ✅ Fallback |
| Overview | `cs.battery_level` | Fallback for `battery_percentage` | ✅ Fallback |
| Overview | `csrf.current_user` | Machine accessor (from `assignedUser.name`) | ✅ Fixed |
| Overview | `cs.cpu_model` | Machine accessor (from `machine.processor`) | ✅ Fixed |
| Performance | `h.cpu_temperature` | health_logs column | ✅ |
| Performance | `h.cpu_percentage/ram_percentage/disk_percentage` | health_logs columns | ✅ |
| Activity | `alert.id/severity/title/status/created_at` | alerts table | ✅ |
| Activity | `event.type/severity/title/description/timestamp` | Timeline controller | ✅ |
| Inventory | `hardware.*` | HardwareInventory model | ✅ Full match |
| Inventory | `cs.cpu_model` | Machine accessor fallback | ✅ Fixed |
| Security | `antivirus.is_enabled/is_updated/definition_status` | AntivirusStatus model | ✅ |
| Security | `firewall.is_enabled/profile_name` | FirewallStatus model | ✅ |
| Security | `login.is_success` | LoginActivity accessor | ✅ Fixed |
| Security | `login.logon_type` | Fallback for `event_type` | ✅ Fallback |
| Security | `upd.kb_article` | Fallback-chain, not in DB | ⚠️ Fallback |
| Devices | `connected_devices.*` | MachineConnectedDevice model | ✅ Full match |
| Devices | `usb_activity.*` | UsbActivity model | ✅ |
| Devices | `device_events.*` | DeviceEvent model | ✅ |
| Processes | `p.process_name/cpu_usage/memory_usage` | ProcessLog model | ✅ |
| Services | `s.service_name/display_name/status/start_type` | WindowsService model | ✅ |
| Network | `adapters.*` | MachineNetworkAdapter model | ✅ |
| Network | `disks.*` | MachineDisk model | ✅ |
| System Logs | `sp.program_name/program_path/registry_key/startup_type/status` | StartupProgram model | ✅ (now has columns) |
| System Logs | `el.level/source/message/event_time` | EventLog model | ✅ |
| LiveMonitoring | `machineInfo.employee_name` | Machine accessor | ✅ Fixed |
| LiveMonitoring | `machineInfo.ip_address/mac_address` | Machine accessor (from 1st networkAdapter) | ✅ Fixed |
| LiveMonitoring | `h.network_bytes_sent_per_sec` | HealthLog accessor (computed from consecutive records) | ✅ Fixed |

### Recently Fixed (2026-07-06)
- **`ip_address`/`mac_address` on Machine model**: Added `networkAdapters` relation + accessors (returns from first adapter). `MachineService::getMachine()` now eager-loads `networkAdapters`. LiveMonitoring page now shows real IP/MAC instead of "N/A".
- **`network_bytes_sent_per_sec`**: Added `network_sent_bytes` and `network_received_bytes` columns to `health_logs` (migration `2026_07_06_000003`). `NetworkProcessor` now writes aggregate network data to healthLog. `MachineController::history()` computes per-second rate from consecutive records — LiveMonitoring network chart now shows real data instead of flat zero.
- **`kb_article` on WindowsUpdate**: Added column (migration `2026_07_06_000003`). `UpdateProcessor` now maps `kbId`/`kb_id` from agent payload to `kb_article` column. Frontend now shows proper KB article numbers instead of falling through fallback chain.
- **`employee_name`/`current_user` on Machine**: Added `$appends` accessors returning `assignedUser.name`. Overview tab and LiveMonitoring now show the actual assigned user name instead of "—"/"SYSTEM".
- **`cpu_model` on Machine**: Added `$appends` accessor returning `machine.processor`. Inventory tab fallback now has real CPU model data.
- **`is_success` on LoginActivity**: Added `$appends` accessor returning `true` for successful event types (Logon, Logoff, etc.), `false` for Failed Logon. All logins no longer incorrectly show red "Failed" badge.
- **4625 → 'Failed Logon' mapping**: Added to `LoginActivityProcessor` — failed Windows logon events (eventId 4625) are now properly categorized.

### Known Issues
- **Agent ServiceInfo doesn't send `serviceType` or `logOnAs`**: `WindowsService` model has these columns but agent's `ServiceInfo` DTO only has `serviceName`, `displayName`, `status`, `startType`, `isRunning`. Columns will always be null.
- **USB activity data limited**: Agent sends USB events as `EventLogInfo` (log entry format), but `UsbActivityProcessor` expects structured fields (`deviceName`, `deviceSerial`). Device names default to `'Unknown USB Device'`. The agent's `peripheralInfo` provides accurate USB device names.
- **`estimatedRunTimeSeconds` and `chemistry` (battery) read but not stored**: BatteryProcessor reads these from payload but no DB column exists for them.
- **`lastSignatureUpdate` (antivirus) read but not stored**: AntivirusProcessor reads it but doesn't persist it.

### Agent Binary
- Rebuilt agent binary blocked by Windows Application Control (WDAC)
- Requires admin to unblock/sign the DLL or whitelist via Group Policy
- Without running agent, tables have no data for testing
