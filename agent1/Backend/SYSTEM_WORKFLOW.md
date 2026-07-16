# DeskGuard Backend - System Workflows

## 1. Agent OTP Registration Flow

```
Agent Start
    │
    ├── Check for existing token (agent_token.dat)
    │
    ├── [No Token] ──> Prompt mobile number
    │                       │
    │                       ▼
    │               POST /api/v1/agent/request-otp
    │                       │
    │                       ▼
    │               SMS/Console "OTP sent"
    │                       │
    │                       ▼
    │               Prompt OTP from user
    │                       │
    │                       ▼
    │               POST /api/v1/agent/verify-otp
    │               { mobile_number, otp, machine_uid }
    │                       │
    │                       ├── [Valid OTP]
    │                       │       ├── Mark OTP as used
    │                       │       ├── Find or create User (auto-create)
    │                       │       ├── Issue Sanctum bearer token
    │                       │       ├── Save token to agent_token.dat
    │                       │       └── Return { token, user }
    │                       │
    │                       └── [Invalid/Expired] -> 422 error
    │
    └── [Has Token] ──> Continue with auth header
```

**Backend logic (OtpService):**
1. `generate(mobile)` - Invalidate old OTPs, create new 6-digit OTP (expires 10 min)
2. `verify(mobile, otp)` - Check valid OTP, mark used, return OtpCode
3. `findOrCreateUser(mobile)` - Find by mobile_number or auto-create User
4. `issueToken(user)` - Create Sanctum token

## 2. Machine Linking Flow (via Activation Token)

```
Admin (Web UI)                          Agent
    │                                      │
    │  1. Create user + company            │
    │  2. Generate activation token        │
    │  3. Share token with employee        │
    │                                      │
    │                              POST /api/v1/agent/register
    │                              { machine_uid, activation_token,
    │                                hostname, operating_system }
    │                                      │
    │  4. Validate activation token        │
    │  5. Create Machine record            │
    │  6. Generate machine API token       │
    │  7. Return machine + api_token       │
    │                                      │
    │                              Store api_token for auth
```

## 3. Device Monitoring Flow

```
Real-time (ManagementEventWatcher)          Periodic (30 min)
         │                                          │
         ▼                                          ▼
  Win32_DeviceChangeEvent                  PeripheralCollector
  (EventType 1=Insert, 2=Remove)           (Win32_PnPEntity)
         │                                          │
         ▼                                          ▼
  Collect device details                    Collect all devices
  (Win32_PnPEntity query)                   with status, type
         │                                          │
         ▼                                          ▼
  POST /api/v1/agent/device-events         POST /api/v1/agent/device-sync
  {                                         {
    machine_uid, device_name,                  machine_uid,
    device_type, manufacturer,                 devices: [{ device_name,
    connection_type, event_type,                 device_type, manufacturer,
    event_time                                   connection_type }]
  }                                          }
         │                                          │
         ▼                                          ▼
  Backend: DeviceEventController           Backend: DeviceEventController
  1. Store DeviceEvent record              1. Mark stale devices "removed"
  2. Update MachineConnectedDevice          2. Upsert current devices
     (upsert if Connected,                 3. Return success
      mark disconnected if Removed)
  3. Check alert rules against
     device_type + device_name
  4. Generate Alert if matched
     (USB/Printer/External/Bluetooth)
```

## 4. Admin Search Flow

```
GET /api/v1/admin/search?query=xxx
         │
         ▼
  AdminSearchController::search
         │
         ├── Search Users: mobile_number, name, email LIKE %query%
         │       └── Load: machines + currentStatus
         │
         ├── Search Machines: machine_uid, hostname, device_name LIKE %query%
         │       └── Load: currentStatus
         │
         └── Return unified results: [{ type, id, name, ... }]

GET /api/v1/admin/users/{id}
         │
         ▼
  Load user + machines + currentStatus + deviceEvents (latest 20) + company
```

## 5. Data Model Relationships

```
Company
  ├── Users (hasMany)
  │     ├── Machines (hasMany through user_id)
  │     ├── Reports (hasMany)
  │     ├── AuditLogs (hasMany)
  │     └── OtpCodes (hasMany through mobile_number)
  │
  └── Machines (hasMany)
        ├── MachineCurrentStatus (hasOne)
        ├── HealthLogs (hasMany)
        ├── HardwareInventories (hasMany)
        ├── SoftwareInventories (hasMany)
        ├── AntivirusStatuses (hasMany)
        ├── FirewallStatuses (hasMany)
        ├── LoginActivities (hasMany)
        ├── UsbActivities (hasMany)
        ├── WindowsServices (hasMany)
        ├── WindowsUpdates (hasMany)
        ├── EventLogs (hasMany)
        ├── StartupPrograms (hasMany)
        ├── Alerts (hasMany)
        ├── AuditLogs (hasMany)
        ├── MachineTokens (hasMany)
        ├── DeviceEvents (hasMany)               ← NEW
        └── MachineConnectedDevices (hasMany)     ← NEW

AlertRule
  └── Alerts (hasMany) ──→ Machine

User
  └── OtpCodes (hasMany through mobile_number)   ← NEW
```
