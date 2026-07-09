# NULL Value Justification Document

After comprehensive audit and cleanup, the following NULL values remain in the database.
Each entry explains why the NULL is acceptable by design and cannot be eliminated.

---

## alerts

| Column | Why NULL is acceptable |
|--------|----------------------|
| `alert_rule_id` | An alert may be triggered by hard-coded thresholds in AlertProcessor (CPU > 90%, memory > 90%, disk > 95%, AV disabled, FW disabled) rather than by a configurable alert_rule. Only alerts created via alert_rules will have this populated. |
| `acknowledged_by` | NULL until an admin/user acknowledges the alert. This is a workflow state — the alert is "unacknowledged". |
| `acknowledged_at` | NULL until the alert is acknowledged; timestamp of acknowledgment. |
| `resolved_by` | NULL until the alert is resolved; user ID of the resolver. |
| `resolved_at` | NULL until the alert is resolved; timestamp of resolution. |

## audit_logs

| Column | Why NULL is acceptable |
|--------|----------------------|
| `machine_id` | Many audit log entries are not machine-specific (e.g., user login/logout, role changes, company config changes). Only actions that relate to a specific machine populate this. |
| `old_values` | Create actions have no "old" values — only update/delete actions populate this. The table stores all CRUD audit trails generically. |
| `new_values` | Delete actions have no "new" values. Only create/update actions populate this. |

## health_logs

| Column | Why NULL is acceptable |
|--------|----------------------|
| `cpu_percentage` | health_logs is a time-series table where each row represents a single collection cycle. Not every cycle captures every metric. If the CPU collector hasn't run yet for a given cycle, this will be NULL until the next cycle fills it. Once a machine's agent sends a full payload, all metrics are populated. |
| `cpu_temperature` | Same as above — per-cycle time-series metric. Not all machines/hardware report temperature. |
| `ram_percentage` | Per-cycle metric; populated only when the MemoryProcessor runs for that row's cycle. |
| `ram_used_bytes` | Per-cycle metric. |
| `ram_available_bytes` | Per-cycle metric. |
| `ram_total_bytes` | Per-cycle metric (populated once and repeats). |
| `disk_percentage` | Per-cycle metric; populated only when the DiskProcessor runs. |
| `disk_free_bytes` | Per-cycle metric. |
| `disk_total_bytes` | Per-cycle metric. |
| `battery_percentage` | Per-cycle metric; desktops without a battery will never populate this (which is correct — no battery = no percentage). |

**Design Intent:** Each HealthLog row is created empty, and individual metric processors (CPU, Memory, Disk, Battery) update the same shared row with their data. A row will have NULLs for metrics not yet processed or not applicable.

## machines

| Column | Why NULL is acceptable |
|--------|----------------------|
| `user_id` | Machine-user assignment is optional. A machine can exist in the system without being assigned to a specific user (e.g., shared workstations, servers). |
| `activation_token` | NULL until the machine activation flow is implemented. Token is generated when a machine is registered via the agent activation process. |
| `api_token` | NULL until the machine activation flow is implemented. API token is assigned after successful activation. |
| `activated_at` | NULL until the machine is activated via the activation flow. |

## users

| Column | Why NULL is acceptable |
|--------|----------------------|
| `mobile_number` | Optional profile field — not all users provide a mobile number. |
| `phone` | Optional profile field — not all users provide a phone number. |
| `avatar` | Optional profile field — not all users upload an avatar. |
| `last_login_at` | NULL until the user logs in for the first time. |
| `deleted_at` | Soft-delete timestamp — NULL means the record is active (not deleted). This is standard Laravel soft-delete behavior. |

---

## Summary

| Category | Count | Tables |
|----------|-------|--------|
| Workflow state NULLs | 5 columns | alerts (acknowledged/resolved) |
| Generic audit trail | 3 columns | audit_logs |
| Time-series per-cycle metrics | 10 columns | health_logs |
| Optional/inactive features | 4 columns | machines (activation/user) |
| Optional profile fields | 5 columns | users (phone, avatar, etc.) |
| **Total unavoidable NULL columns** | **27** | **5 tables** |

All other NULLs across all 35 tables have been eliminated through processor fixes and data cleanup.
