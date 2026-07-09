# DeskGuard Backend - Database Architecture

## Overview
The DeskGuard database uses a hybrid design combining current-state snapshots (one row per machine) with append-only time-series tables. All agent telemetry is processed through 19 section processors inside a single database transaction.

## Table Summary (32 tables)

### Core Entities
| Table | Type | Purpose |
|-------|------|---------|
| `companies` | Master | Tenant companies |
| `users` | Master | Admin users and machine-assigned employees |
| `machines` | Master | Registered machines (one per agent) |
| `machine_tokens` | Auth | SHA-256 hashed API tokens for machine auth |
| `machine_current_status` | Snapshot | Real-time health snapshot (CPU, RAM, disk, battery, AV, FW, updates) |
| `otp_codes` | Auth | One-time passwords for mobile verification |

### Time-Series (Append-Only)
| Table | Key Columns | Retention |
|-------|-------------|-----------|
| `health_logs` | cpu_percentage, ram_percentage, disk_percentage, battery_percentage, collected_at | Full history |
| `event_logs` | event_id, log_name, source, level, message, event_time | Filtered (Error/Warning/Critical only) |
| `login_activities` | username, session_type, logon_id, logon_time, logoff_time | Full history |
| `process_logs` | process_name, process_id, cpu_usage, memory_usage_mb, collected_at | Top 20 by CPU/memory |
| `device_events` | device_name, device_type, manufacturer, connection_type, event_type, event_time | Full history |

### Snapshot (One Row Per Machine)
| Table | Strategy | Key Columns |
|-------|----------|-------------|
| `machine_current_status` | updateOrCreate | cpu_percentage, ram_percentage, disk_percentage, battery_percentage, antivirus_status, firewall_status, pending_updates |
| `health_logs` (latest) | updateOrCreate via CpuProcessor, MemoryProcessor, etc. | Same as time-series but per-processor updateOrCreate |

### Inventory (Truncate-and-Reload)
| Table | Strategy | Notes |
|-------|----------|-------|
| `hardware_inventory` | Delete all + batch insert | Full hardware scan each cycle |
| `software_inventory` | Delete all + batch insert | Full software scan each cycle |
| `startup_programs` | Delete all + batch insert | Full startup list each cycle |
| `windows_services` | Delete all + batch insert | Full service list each cycle |
| `windows_updates` | Delete all + batch insert | Full update list each cycle |
| `machine_connected_devices` | Delete all + batch insert | Full device snapshot each cycle |

### Status Tables (updateOrCreate / FirstOrCreate)
| Table | Column | Strategy |
|-------|--------|----------|
| `antivirus_status` | product_name, is_enabled, is_updated, real_time_protection, definition_version, engine_version | updateOrCreate per machine |
| `firewall_status` | display_name, is_enabled, profile | updateOrCreate per machine |
| `machine_disks` | drive_letter, total_bytes, used_bytes, free_bytes, usage_percentage | updateOrCreate (one per drive letter) |
| `machine_network_adapters` | adapter_name, mac_address, ip_address | updateOrCreate (one per MAC) |

### Derived Data
| Table | Source | Purpose |
|-------|--------|---------|
| `alerts` | AlertProcessor (CPU > 90%, RAM > 90%, disk > 95%, AV disabled, FW disabled) | Deduplicated by title + machine_id where status = open |
| `alert_rules` | Admin-defined rules | Threshold configuration |
| `reports` | Generated PDF/Excel reports | Stored file metadata |
| `notifications` | System notifications | Admin notification inbox |
| `audit_logs` | User actions | Activity trail |

### Raw Payload
| Table | Purpose | Cleanup |
|-------|---------|---------|
| `raw_payload_logs` | Stores complete JSON payload for debugging | LongText column, index on received_at |

### Auth & Permissions (Spatie)
| Table | Purpose |
|-------|---------|
| `permissions` | Named permissions (e.g. "view-machines") |
| `roles` | Named roles (e.g. "super-admin", "company-head") |
| `model_has_roles` | Polymorphic pivot (user ↔ role) |
| `model_has_permissions` | Polymorphic pivot (user ↔ permission) |
| `role_has_permissions` | Pivot (role ↔ permission) |

## Key Indexes
- `machines.machine_uid` (unique) — agent lookup
- `health_logs(machine_id, collected_at)` — time-series queries
- `event_logs(machine_id, level)` — filtered event retrieval
- `alerts(machine_id, status)` — active alert queries
- `raw_payload_logs(received_at)` — payload cleanup queries

## Data Flow
```
Agent POST /api/v1/agent/telemetry
  → TelemetryController::__invoke
    → TelemetryRequest validation
    → TelemetryPayloadDTO hydration
    → TelemetryService::processTelemetry
      → Store RawPayloadLog
      → PayloadProcessorService::process
        → DB::transaction
          → 19 processors loop (each try/catch isolated)
          → MachineProcessor (updateOrCreate machine row)
          → CpuProcessor (updateOrCreate status + create health_log)
          → MemoryProcessor (updateOrCreate status + create health_log)
          → DiskProcessor (updateOrCreate each disk)
          → BatteryProcessor (updateOrCreate status)
          → NetworkProcessor (updateOrCreate each adapter)
          → HardwareInventoryProcessor (truncate + batch insert)
          → SoftwareInventoryProcessor (truncate + batch insert)
          → ProcessProcessor (truncate + insert top 20)
          → ServiceProcessor (truncate + batch insert)
          → AntivirusProcessor (firstOrCreate)
          → FirewallProcessor (firstOrCreate)
          → UpdateProcessor (truncate + batch insert)
          → EventLogProcessor (create filtered logs)
          → LoginActivityProcessor (create)
          → StartupProgramProcessor (truncate + batch insert)
          → DeviceProcessor (truncate + batch insert)
          → DeviceEventProcessor (create events)
          → AlertProcessor (evaluate thresholds, deduplicate create)
```

## Migration Order
```
01. companies
02. users
03. machines
04. machine_tokens
05. machine_current_status
06. health_logs
07. hardware_inventory
08. software_inventory
09. antivirus_status
10. firewall_status
11. login_activities
12. usb_activities
13. windows_services
14. windows_updates
15. event_logs
16. startup_programs
17. alert_rules
18. alerts
19. reports
20. notifications
21. audit_logs
22. permission_tables (Spatie)
23. otp_codes
24. device_events (new)
25. machine_connected_devices (new)
26. raw_payload_logs (new)
27. machine_disks (new)
28. machine_network_adapters (new)
29. process_logs (new)
30. add_security_fields_to_machine_current_status (new)
```
