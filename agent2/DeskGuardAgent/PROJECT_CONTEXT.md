# DeskGuard Agent - Project Context

## 1. Project Overview

**DeskGuard Agent** is a lightweight, secure, enterprise-grade Windows monitoring agent built with .NET 8. It is installed on company-managed desktops and laptops to continuously monitor system health, hardware inventory, software inventory, security status, and operating system metrics. The agent supports thousands of enterprise endpoints.

### Business Goals
- Provide real-time endpoint health monitoring
- Track hardware and software assets across the organization
- Monitor security posture (antivirus, firewall, login activity)
- Enable proactive IT operations and security incident response
- Support offline operation with automatic retry and queuing

### Supported Operating Systems
- Windows 10
- Windows 11
- Windows Server (2019/2022)

### Core Responsibilities
- Collect system metrics (CPU, RAM, Disk, Network, Battery)
- Collect hardware and software inventory
- Monitor security status (antivirus, firewall, login, USB)
- Monitor Windows services, updates, and event logs
- Monitor peripheral devices (USB, printer, keyboard, mouse, display, etc.)
- Detect real-time device connect/disconnect events (ManagementEventWatcher)
- Perform periodic 30-minute full peripheral scans (Win32_PnPEntity)
- Register agent via OTP-based mobile verification
- Transmit collected data to a central backend API
- Handle offline scenarios with local queuing and automatic retry

---

## 2. Architecture

```text
┌─────────────────────────────────────────────────────────────┐
│                    DeskGuard Agent                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌──────────────────────────────────────────────────────┐  │
│  │                  Collectors Layer                    │  │
│  │  ┌──────┐ ┌──────┐ ┌──────┐ ┌────────┐ ┌────────┐   │  │
│  │  │ CPU  │ │ RAM  │ │ Disk │ │Network │ │Battery │   │  │
│  │  └──────┘ └──────┘ └──────┘ └────────┘ └────────┘   │  │
│  │  ┌──────────┐ ┌────────┐ ┌───────┐ ┌──────────┐     │  │
│  │  │ Hardware │ │Software│ │Service│ │ Security │     │  │
│  │  │ Inventory│ │Inventory│ │Monitor│ │ Monitor  │     │  │
│  │  └──────────┘ └────────┘ └───────┘ └──────────┘     │  │
│  │  ┌──────────┐ ┌──────────┐ ┌────┐ ┌──────────┐     │  │
│  │  │ EventLog │ │ Update   │ │USB │ │ Firewall │     │  │
│  │  └──────────┘ └──────────┘ └────┘ └──────────┘     │  │
│  │  ┌──────────────────────┐ ┌──────────────────────┐ │  │
│  │  │ PeripheralCollector │ │ DeviceEventWatcher   │ │  │
│  │  │ (Win32_PnPEntity)   │ │ (Real-time WMI)      │ │  │
│  │  └──────────────────────┘ └──────────────────────┘ │  │
│  └──────────────────────────────────────────────────────┘  │
│                          │                                   │
│              ┌───────────┴────────────┐                     │
│              ▼                        ▼                     │
│  ┌──────────────────────┐  ┌──────────────────────┐       │
│  │ UserRegistration   │  │  MonitoringService   │       │
│  │ Service (OTP flow) │  │  Orchestrates cycles │       │
│  └──────────────────────┘  └──────────────────────┘       │
│              │                        │                     │
│              ▼                        ▼                     │
│  ┌──────────────────────────────────────────────────────┐  │
│  │              ApiSenderService                        │  │
│  │        Sends payloads with retry logic               │  │
│  └──────────────────────────────────────────────────────┘  │
│                          │                                   │
│                    ┌─────┴─────┐                            │
│                    ▼           ▼                            │
│  ┌────────────────────┐  ┌────────────────────┐           │
│  │   Backend API      │  │ OfflineQueueService│           │
│  │   (Remote Server)  │  │  (Local JSON File) │           │
│  └────────────────────┘  └────────────────────┘           │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 3. Folder Structure

```
DeskGuardAgent/
├── Program.cs                          # Application entry point and DI configuration
├── Worker.cs                           # BackgroundService lifecycle manager
├── appsettings.json                    # All configuration settings
├── PROJECT_CONTEXT.md                  # This file - project documentation
├── DeskGuardAgent.csproj               # Project file with NuGet dependencies
│
├── Configuration/
│   ├── AgentSettings.cs                # Core agent identity and communication settings
│   └── MonitoringSettings.cs           # Collector intervals and feature toggles
│
├── Models/
│   ├── CpuInfo.cs                      # CPU utilization and temperature
│   ├── MemoryInfo.cs                   # RAM usage metrics
│   ├── DiskInfo.cs                     # Disk partition usage and SMART health
│   ├── BatteryInfo.cs                  # Battery status and wear level
│   ├── NetworkInfo.cs                  # Network adapter status and traffic
│   ├── HardwareInventory.cs            # System manufacturer, model, serial number, BIOS
│   ├── SoftwareInventory.cs            # Installed applications from registry
│   ├── AntivirusInfo.cs                # Antivirus product status
│   ├── ServiceInfo.cs                  # Windows service status
│   ├── UpdateInfo.cs                   # Windows Update status
│   ├── EventLogInfo.cs                 # Windows Event Log entry
│   ├── FirewallInfo.cs                 # Firewall enablement status
│   ├── ProcessInfo.cs                  # Running process snapshot
│   ├── SystemInfo.cs                   # OS version, boot time, uptime
│   ├── HealthPayload.cs                # Complete payload sent to API
│   ├── PeripheralInfo.cs               # Connected peripheral device (NEW)
│   ├── DeviceEventInfo.cs              # Device connect/disconnect event (NEW)
│   └── OtpResponse.cs                  # OTP API response models (NEW)
│
├── Interfaces/
│   ├── ICollector.cs                   # Generic collection contract
│   ├── IApiSenderService.cs            # API transmission contract
│   ├── IMonitoringService.cs           # Monitoring orchestration contract
│   ├── ILoggerService.cs               # Logging abstraction contract
│   └── IOfflineQueueService.cs         # Offline queue management contract
│
├── Collectors/
│   ├── CpuCollector.cs                 # CPU usage + temperature (PerfCounter + LibreHardwareMonitor)
│   ├── MemoryCollector.cs              # RAM usage via WMI
│   ├── DiskCollector.cs                # Disk space + SMART health via WMI
│   ├── BatteryCollector.cs             # Battery status via WMI
│   ├── NetworkCollector.cs             # Network adapters via NetworkInterface + WMI
│   ├── ProcessCollector.cs             # Running processes via System.Diagnostics
│   ├── SystemInfoCollector.cs          # OS info, boot time, uptime via WMI
│   ├── HardwareInventoryCollector.cs   # Manufacturer, model, serial, BIOS via WMI
│   ├── SoftwareInventoryCollector.cs   # Installed software via Registry
│   ├── ServiceCollector.cs             # Windows services via ServiceController
│   ├── SecurityCollector.cs            # Antivirus status via SecurityCenter2 WMI
│   ├── UpdateCollector.cs              # Windows Update via WU API + Registry fallback
│   ├── EventLogCollector.cs            # Event log entries via System.Diagnostics.EventLog
│   ├── FirewallCollector.cs            # Firewall status via COM + WMI fallback
│   ├── StartupProgramCollector.cs      # Startup programs via Registry + Startup folders
│   ├── LoginActivityCollector.cs       # Login events (4624/4625) via Security log
│   ├── UsbCollector.cs                 # USB device events via System log
│   └── PeripheralCollector.cs          # Connected peripherals via Win32_PnPEntity (NEW)
│
├── Services/
│   ├── MonitoringService.cs            # Central orchestrator for all collectors
│   ├── ApiSenderService.cs             # HTTP payload transmission with retry
│   ├── OfflineQueueService.cs          # File-based offline queue management
│   ├── SchedulerService.cs             # Timer-based periodic task scheduling (includes device scan)
│   ├── RetryService.cs                 # Exponential backoff retry logic
│   ├── UserRegistrationService.cs      # OTP-based mobile registration flow (NEW)
│   └── DeviceEventWatcher.cs           # Real-time WMI device change monitoring (NEW)
│
├── Utilities/
│   ├── JsonHelper.cs                   # JSON serialization (Newtonsoft.Json)
│   ├── EncryptionHelper.cs             # AES-256 encryption utilities
│   ├── MachineIdentifier.cs            # Hardware-based unique machine ID generation
│   ├── FileHelper.cs                   # Atomic file I/O operations
│   └── ValidationHelper.cs             # Configuration validation utilities
│
├── Constants/
│   ├── ApiRoutes.cs                    # Backend API endpoint routes
│   └── AgentConstants.cs               # Application-wide constants
│
├── Logs/                               # Serilog log file output directory
│
├── Storage/
│   └── queue.json                      # Offline queue persistent storage file
│
├── Installer/                          # Windows service installer scripts
│
└── Tests/                              # Unit and integration test project
```

---

## 4. Technology Stack

### Language & Framework
| Component | Technology |
|-----------|-----------|
| Language | C# 12 (.NET 8) |
| Framework | .NET 8 Worker Service |
| Architecture | Clean Architecture with DI |
| Operating System | Windows 10/11, Windows Server |

### NuGet Packages
| Package | Version | Purpose |
|---------|---------|---------|
| `Newtonsoft.Json` | 13.0.4 | JSON serialization/deserialization |
| `Serilog` | 4.3.1 | Structured logging |
| `Serilog.Extensions.Hosting` | 10.0.0 | Serilog integration with .NET Host |
| `Serilog.Sinks.Console` | 6.1.1 | Console log output |
| `Serilog.Sinks.File` | 7.0.0 | File-based log output with rotation |
| `System.Management` | 10.0.9 | WMI queries for system metrics |
| `System.Diagnostics.PerformanceCounter` | 10.0.9 | CPU performance counters |
| `LibreHardwareMonitorLib` | 0.9.6 | Hardware sensor readings (CPU temp) |
| `Microsoft.Extensions.Http` | 10.0.9 | HttpClient factory and DI integration |
| `Microsoft.Extensions.Hosting.WindowsServices` | 10.0.9 | Windows Service lifecycle support |

---

## 5. Configuration

### AgentSettings (appsettings.json → AgentSettings section)

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `AgentId` | string | `""` | Unique agent identifier (auto-generated from hardware if empty) |
| `TenantId` | string | `""` | Organization/tenant identifier (required) |
| `ApiBaseUrl` | string | `""` | Backend API base URL (required) |
| `ApiKey` | string | `""` | Bearer token API key (required) |
| `Environment` | string | `"Production"` | Deployment environment name |
| `StoragePath` | string | `"Storage/queue.json"` | Offline queue file path |
| `LogPath` | string | `"Logs"` | Log file directory |
| `MaxRetryAttempts` | int | `3` | Maximum retry attempts for API calls |
| `RetryDelaySeconds` | int | `10` | Base delay for exponential backoff |
| `RequestTimeoutSeconds` | int | `30` | HTTP request timeout |
| `EmployeeMobileNumber` | string | `""` | Employee contact number, sent with every health payload |
| `MachineLabel` | string | `""` | Human-friendly machine label for dashboard display |

### MonitoringSettings (appsettings.json → MonitoringSettings section)

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `CollectionIntervalSeconds` | int | `300` | Seconds between collection cycles |
| `EnableCpuMonitoring` | bool | `true` | Enable CPU usage collection |
| `EnableCpuTemperatureMonitoring` | bool | `true` | Enable CPU temperature collection |
| `EnableMemoryMonitoring` | bool | `true` | Enable RAM usage collection |
| `EnableDiskMonitoring` | bool | `true` | Enable disk usage collection |
| `EnableNetworkMonitoring` | bool | `true` | Enable network adapter collection |
| `EnableBatteryMonitoring` | bool | `true` | Enable battery status collection |
| `EnableHardwareInventory` | bool | `true` | Enable hardware inventory collection |
| `EnableSoftwareInventory` | bool | `true` | Enable software inventory collection |
| `EnableServiceMonitoring` | bool | `true` | Enable Windows service monitoring |
| `EnableSecurityMonitoring` | bool | `true` | Enable antivirus monitoring |
| `EnableUpdateMonitoring` | bool | `true` | Enable Windows Update monitoring |
| `EnableEventLogMonitoring` | bool | `true` | Enable event log collection |
| `EnableFirewallMonitoring` | bool | `true` | Enable firewall status collection |
| `EnableStartupProgramMonitoring` | bool | `true` | Enable startup program collection |
| `EnableLoginActivityMonitoring` | bool | `true` | Enable login activity collection |
| `EnableUsbMonitoring` | bool | `true` | Enable USB activity collection |
| `EnableProcessMonitoring` | bool | `true` | Enable process collection |
| `HardwareInventoryIntervalHours` | int | `24` | Hours between hardware inventory |
| `SoftwareInventoryIntervalHours` | int | `24` | Hours between software inventory |
| `MaxEventLogEntries` | int | `50` | Max event log entries per cycle |

---

## 6. Collectors Documentation

### CpuCollector
- **Purpose**: Collects CPU utilization percentage and temperature
- **Metrics**: Usage %, Temperature °C, Processor Name, Logical Processors, Clock Speed
- **Dependencies**: `System.Diagnostics.PerformanceCounter`, `LibreHardwareMonitorLib`, `System.Management`
- **Output**: `CpuInfo`

### MemoryCollector
- **Purpose**: Collects RAM usage metrics
- **Metrics**: Total, Used, Available Memory (bytes + formatted), Usage %
- **Dependencies**: `System.Management`
- **Output**: `MemoryInfo`

### DiskCollector
- **Purpose**: Collects disk partition usage and SMART health
- **Metrics**: Per-drive: Total, Used, Free Space, Usage %, File System, Drive Type, SMART Status
- **Dependencies**: `System.IO.DriveInfo`, `System.Management`
- **Output**: `List<DiskInfo>`

### BatteryCollector
- **Purpose**: Collects battery status for portable devices
- **Metrics**: Present, Charge %, Charging Status, Runtime, Chemistry, Design/Full Capacity, Wear Level
- **Dependencies**: `System.Management`
- **Output**: `BatteryInfo`

### NetworkCollector
- **Purpose**: Collects network adapter status and traffic
- **Metrics**: Per-adapter: Connection Status, IP (v4/v6), MAC, Speed, Bytes Sent/Received
- **Dependencies**: `System.Net.NetworkInformation`, `System.Management`
- **Output**: `List<NetworkInfo>`

### ProcessCollector
- **Purpose**: Captures snapshot of running processes
- **Metrics**: Per-process: PID, Name, Path, Working Set, Thread Count, Owner
- **Dependencies**: `System.Diagnostics`, `System.Management`
- **Output**: `List<ProcessInfo>`

### SystemInfoCollector
- **Purpose**: Collects general system information
- **Metrics**: OS Name, Version, Architecture, Boot Time, Uptime, Computer Name, Domain, Logged-in Users
- **Dependencies**: `System.Management`
- **Output**: `SystemInfo`

### HardwareInventoryCollector
- **Purpose**: Collects detailed hardware asset information
- **Metrics**: Manufacturer, Model, Serial Number, BIOS Vendor/Version/Date, Processor, Memory, OS
- **Dependencies**: `System.Management`
- **Output**: `HardwareInventory`

### SoftwareInventoryCollector
- **Purpose**: Enumerates installed software from registry
- **Metrics**: Per-app: Display Name, Version, Publisher, Install Date, Size
- **Dependencies**: `Microsoft.Win32.Registry`
- **Output**: `List<SoftwareInventory>`

### ServiceCollector
- **Purpose**: Monitors Windows services status
- **Metrics**: Per-service: Name, Display Name, Status, Start Type, IsRunning
- **Dependencies**: `System.ServiceProcess.ServiceController`, `System.Management`
- **Output**: `List<ServiceInfo>`

### SecurityCollector
- **Purpose**: Monitors antivirus protection status
- **Metrics**: Product Name, Version, Real-time Protection, Signature Up-to-date, Status
- **Dependencies**: `System.Management` (root\SecurityCenter2)
- **Output**: `AntivirusInfo`

### UpdateCollector
- **Purpose**: Checks Windows Update status
- **Metrics**: Pending Count, Security Pending Count, Last Install Date, Auto-Update Enabled, IsUpToDate
- **Dependencies**: COM (Microsoft.Update.Session), Registry fallback
- **Output**: `UpdateInfo`

### EventLogCollector
- **Purpose**: Collects recent Windows Event Log entries
- **Metrics**: Per-entry: Log Name, Event ID, Level, Source, Message, Time, User
- **Dependencies**: `System.Diagnostics.EventLog`
- **Output**: `List<EventLogInfo>`

### FirewallCollector
- **Purpose**: Checks Windows Firewall status per profile
- **Metrics**: Domain/Private/Public Enabled, Active Profile, Status
- **Dependencies**: COM (HNetCfg.FwPolicy2), WMI fallback
- **Output**: `FirewallInfo`

### StartupProgramCollector
- **Purpose**: Enumerates auto-start programs
- **Metrics**: Per-program: Name, Path, Source (HKLM/HKCU/Startup Folder), User
- **Dependencies**: `Microsoft.Win32.Registry`, File system
- **Output**: `List<ProcessInfo>`

### LoginActivityCollector
- **Purpose**: Tracks login success/failure events
- **Metrics**: Event ID 4624 (success) and 4625 (failure) from Security log
- **Dependencies**: `System.Diagnostics.EventLog`
- **Output**: `List<EventLogInfo>`

### UsbCollector
- **Purpose**: Tracks USB device connections/disconnections
- **Metrics**: USB-related events from System event log
- **Dependencies**: `System.Diagnostics.EventLog`
- **Output**: `List<EventLogInfo>`

---

## 7. Services Documentation

### MonitoringService
- **Role**: Central orchestrator for all collection activities
- **Responsibilities**:
  - Coordinates all 17 collectors
  - Runs health metrics collection in parallel
  - Manages inventory collection intervals (24h default)
  - Builds HealthPayload from collected data
  - Sends payloads via ApiSenderService
  - Flushes offline queue on each cycle
- **Implements**: `IMonitoringService`

### SchedulerService
- **Role**: Manages periodic task scheduling
- **Responsibilities**:
  - Creates timers for health, hardware inventory, software inventory
  - First health collection runs immediately on start
  - Hardware/software inventory delayed by 5-10 minutes on start
  - Graceful shutdown of all timers
- **Key Settings**: `CollectionIntervalSeconds`, `HardwareInventoryIntervalHours`, `SoftwareInventoryIntervalHours`

### ApiSenderService
- **Role**: Handles all HTTP communication with backend API
- **Responsibilities**:
  - Serializes payloads to compact JSON
  - Sends POST requests with Bearer token authentication
  - Uses RetryService for automatic retry
  - Falls back to OfflineQueueService on failure
  - Sends agent identification headers
- **Endpoints**: `/api/v1/health`, `/api/v1/inventory/hardware`, `/api/v1/inventory/software`, `/api/v1/events`, `/api/v1/security`

### RetryService
- **Role**: Implements exponential backoff retry
- **Configuration**: `MaxRetryAttempts` (default 3), `RetryDelaySeconds` (default 10)
- **Behavior**: Delay doubles each attempt (10s → 20s → 40s), capped at 5 minutes
- **Generic**: Supports both `Func<Task<T>>` and `Func<Task>` overloads

### OfflineQueueService
- **Role**: Stores payloads when API is unreachable
- **Storage**: JSON file at `Storage/queue.json`
- **Capacity**: Max 1000 payloads, max 50MB file size
- **Thread Safety**: SemaphoreSlim for concurrent access
- **Atomic Writes**: Temp file + rename pattern prevents corruption
- **Retry**: All queued payloads are flushed on startup and each collection cycle

---

## 8. API Communication

### Endpoints

| Endpoint | Route | Payload Type | Frequency |
|----------|-------|-------------|-----------|
| Health | `POST /api/v1/health` | `HealthPayload` (includes `employeeMobileNumber`) | Every collection cycle (default 2 min) |
| Hardware Inventory | `POST /api/v1/inventory/hardware` | `HardwareInventory` | Every 24 hours |
| Software Inventory | `POST /api/v1/inventory/software` | `List<SoftwareInventory>` | Every 24 hours |
| Device Events | `POST /api/v1/device-events` | Device connect/disconnect event | Real-time (via WMI) |
| Device Sync | `POST /api/v1/device-sync` | `{ machine_uid, devices: [{ device_name, device_type, manufacturer, connection_type, device_status, last_seen }] }` | Every 30 minutes |

### Authentication
- **Method**: Bearer Token (JWT)
- **Header**: `Authorization: Bearer {ApiKey}`
- **Additional Headers**: `X-Agent-Id`, `X-Agent-Version`

### Payload Format
- **Serialization**: Compact JSON (camelCase, no indentation)
- **Date Format**: ISO 8601 (`yyyy-MM-ddTHH:mm:ss.fffZ`)
- **Null Handling**: Null properties are omitted

### Retry Behavior
- **Max Attempts**: 3 (configurable)
- **Backoff**: Exponential (base 10s: 10s, 20s, 40s)
- **Max Delay**: 5 minutes
- **Failure Fallback**: Payload saved to offline queue

---

## 9. Error Handling Strategy

### Core Principles
1. Never crash the application - all exceptions are caught at the collector level
2. If a collector fails, continue collecting remaining metrics
3. If API communication fails, save data locally via OfflineQueueService
4. Retry failed requests automatically with exponential backoff

### Exception Handling Rules
- Every `CollectAsync()` method wraps operations in try-catch
- MonitoringService catches all exceptions during collection cycles
- Worker.cs catches exceptions in the main execution loop
- RetryService handles transient API failures
- File operations use atomic write patterns to prevent corruption

### Fallback Mechanisms
- **API Unreachable**: Payloads queued locally → retried on next cycle
- **WMI Unavailable**: Collectors return default values, log warning
- **Temperature Sensor Unavailable**: Returns null, continues with other metrics
- **Registry Key Missing**: Skips gracefully, logs debug message
- **Security Log Inaccessible**: Logs warning (requires admin privileges)

---

## 10. Logging Strategy

### Log Provider
- **Primary**: Serilog
- **Sinks**: Console (debugging), File (production)

### Log Levels
| Level | Usage |
|-------|-------|
| `Debug` | Detailed diagnostic information for troubleshooting |
| `Information` | Normal operational events (startup, shutdown, successful operations) |
| `Warning` | Recoverable issues (API timeout, WMI unavailable, offline queue) |
| `Error` | Failures requiring investigation (collector failure, API failure) |
| `Fatal` | Application startup failure (rare) |

### Log File Configuration
- **Path**: `Logs/deskguard-.log` (date-based rolling)
- **Rolling Interval**: Daily
- **Retention**: 30 days
- **Format**: `{Timestamp} [{Level}] {Message}{NewLine}{Exception}`

### Logged Events
- Startup and shutdown
- Each collection cycle start/complete
- Collector failures (per-collector, not stopping the cycle)
- API communication success/failure
- Retry attempts
- Offline queue operations (enqueue, dequeue, flush)
- Configuration validation errors

---

## 11. Security Strategy

### API Authentication
- Bearer token sent in `Authorization` header
- API key configured in `appsettings.json`
- Additional `X-Agent-Id` and `X-Agent-Version` headers for agent identification

### Encryption
- `EncryptionHelper` provides AES-256 encryption with PBKDF2 key derivation
- Used for encrypting sensitive configuration values at rest
- 100,000 PBKDF2 iterations per OWASP recommendation
- Random salt and IV per encryption operation

### Machine Identification
- `MachineIdentifier` generates unique ID from hardware components
- Combines motherboard serial, processor ID, disk serial
- SHA-256 hashed for fixed-length, consistent identifier
- Fallback to machine name + OS version if hardware IDs unavailable

### Secure Coding Practices
- No hardcoded secrets in source code
- Configuration values read from `appsettings.json` or environment variables
- HTTP requests use configurable timeouts (default 30s)
- File operations use atomic write patterns
- Queue file size is capped (50MB max)

---

## 12. Development Roadmap

### Completed Features
- [x] Project structure and configuration
- [x] Logging infrastructure (Serilog)
- [x] CPU monitoring (usage + temperature)
- [x] Memory/RAM monitoring
- [x] Disk monitoring (usage + SMART health)
- [x] Battery monitoring
- [x] Network monitoring
- [x] Process monitoring
- [x] System information collection
- [x] Hardware inventory collection
- [x] Software inventory collection
- [x] Windows service monitoring
- [x] Security/antivirus monitoring
- [x] Windows Update monitoring
- [x] Event log monitoring
- [x] Firewall monitoring
- [x] Startup program monitoring
- [x] Login activity monitoring
- [x] USB device activity monitoring
- [x] API communication with retry
- [x] Offline queue with automatic retry
- [x] Windows Service packaging support
- [x] Exponential backoff retry mechanism
- [x] Configuration validation

### Completed Features (Change Detection)
- [x] Hardware baseline initialization and change detection
- [x] Software baseline initialization and change detection
- [x] Peripheral baseline initialization and change detection
- [x] Security baseline initialization and change detection
- [x] Network baseline initialization and change detection
- [x] Configuration baseline initialization and change detection
- [x] Severity engine (information, warning, important, critical)
- [x] Change event generation with standardized payload format
- [x] Backend change history storage with severity support
- [x] Alert generation for critical/important changes
- [x] Email notifications for critical changes
- [x] Approval workflow (approve change -> update baseline)
- [x] Card-based frontend UI with change detail modal
- [x] Dashboard recent changes widget with severity badges

### Pending Features
- [ ] Unit tests for all collectors
- [ ] Integration tests for API communication
- [ ] Windows MSI installer creation
- [ ] Group Policy configuration support
- [ ] Encrypted configuration storage
- [ ] Health check endpoint for monitoring
- [ ] Performance optimization for large-scale deployments
- [ ] Automatic update mechanism for agent software

---

## 13. Changelog

### 2026-07-10 - Change Detection System (Phases 1-14)
**Added Complete Change Detection System**:
- **BaselineManager**: Extended to support security, network, and configuration baselines plus severity classification on all change events
- **Baseline comparison**: Hardware (12 components), Software (added/removed/updated), Peripheral (connect/disconnect), Security (antivirus/firewall state), Network (MAC/IP/speed/status), Configuration (services/startup programs)
- **ChangeEvent model**: Added `Severity` property with classification: critical, important, warning, information
- **ChangeDetectionService**: Fixed empty machine_uid bug — now correctly reads from AgentSettings
- **MonitoringService**: Integrated security, network, configuration baseline init + change detection into the collection cycle
- **New agent models referenced**: BaselineManager now uses AntivirusInfo, FirewallInfo, NetworkInfo, ServiceInfo, ProcessInfo for comprehensive change detection

### Backend Changes (synchronized with agent)
- **3 new migrations**: severity column on change_history, security_baselines table, configuration_baselines table
- **2 new models**: SecurityBaseline, ConfigurationBaseline
- **BaselineService**: Added sync/get methods for security and configuration baselines
- **AgentChangeController**: Added severity storage, alert generation for critical/important changes, email notifications via NotificationService
- **ChangeController**: Added severity filtering support
- **BaselineController**: Added security/configuration baseline endpoints + approveChange workflow
- **8 new API routes**: security/configuration baselines + resync endpoints + approve change

### Frontend Changes (synchronized with backend)
- **MachineDetails.jsx**: Replaced changes tab table with card-based UI (severity-colored cards with category icons)
- **Change Detail Modal**: Click any change card to view full details (previous vs current state, recommendation)
- **Dashboard.jsx**: Recent changes widget now shows severity badges
- **ChangesList.jsx**: Grid card layout with category AND severity filters
- **machines.js service**: Added all baseline API calls + approveChange function

### 2026-07-14 - Account Management Module (Backend)
**Added Complete Account Management System in DeskGuardBackend**:
- **New Entity columns**: `employee_id` and `created_by_user_id` on `users` table
- **New role**: "Admin" (Role ID 4) added alongside existing Super Admin, Company Admin, Support Technician
- **New service**: `IAccountService`/`AccountService` — create, list, update, delete, disable, enable accounts
- **New controller**: `AccountsController` — 8 REST endpoints for account CRUD + employee ID generation
- **New DTOs**: `CreateAccountRequest`, `UpdateAccountRequest`, `AccountDto`, `AccountListResponse`
- **BCrypt password hashing**: All passwords hashed before storage
- **Soft delete**: Existing `DeletedAt` pattern used (no physical deletes)
- **Seed updated**: Default user now "Kiran Balaso Patil" with phone "6846810210" and Super Admin role

**Frontend Changes**:
- **New page**: `/accounts` route with Create/Edit form + searchable/filterable/paginated data table
- **New service**: `accounts.js` with all CRUD API calls
- **Sidebar**: "Create Account" menu item visible only to Super Admin role
- **View/Edit/Disable/Delete**: Full action buttons with confirmation dialogs

**Documentation**:
- `BACKEND_CONTEXT.md` — Backend architecture and endpoints
- `DATABASE_CONTEXT.md` — Database schema changes
- `FRONTEND_CONTEXT.md` — Frontend page structure and components

### 2026-06-21 - Initial Release v1.0.0
**Added**:
- Complete project structure with Clean Architecture
- 17 collectors for comprehensive system monitoring
- 5 services (Monitoring, API Sender, Offline Queue, Scheduler, Retry)
- 15 model classes for strongly-typed data representation
- Configuration system with validation
- Serilog logging with console and file sinks
- JSON serialization with Newtonsoft.Json
- AES-256 encryption utilities
- Hardware-based machine identification
- API communication with Bearer token authentication
- Offline queue with automatic retry and flush
- Exponential backoff retry mechanism
- Windows Service lifecycle support
- Comprehensive XML documentation on all public members
- PROJECT_CONTEXT.md documentation

### 2026-06-21 - Fix: appsettings.json & NuGet alignment
**Fixed**:
- Removed `/* */` JSONC comments from `appsettings.json` — now valid JSON, no tooling conflicts
- Removed dead `Serilog` section from `appsettings.json` — Serilog is configured in `Program.cs` using the `LogPath` from `AgentSettings`
- Restructured `Program.cs` to read `AgentSettings` before configuring Serilog, so `LogPath` setting is used instead of hardcoded `"Logs"`
- Fixed NuGet version mismatch: `Microsoft.Extensions.Hosting` upgraded from 8.0.1 → 10.0.9 to match `WindowsServices` (10.0.9)

### 2026-06-21 - Fix: Graceful empty config & test mode
**Fixed**:
- Added graceful fallback in `Program.cs` — if `ApiBaseUrl` is empty, agent starts in offline/test mode using `http://localhost/`
- Removed `_httpClient.BaseAddress` override from `ApiSenderService.cs` constructor (already set by `AddHttpClient` factory)
- Guarded `ApiKey` header in `ApiSenderService.cs` — no crash if API key is empty
- `DiskCollector.cs`: Wrapped `foreach` in inner try-catch for `ManagementException` on drives with no physical disk association (virtual/CD/network drives); returns `"Unknown"` instead of logging full stack trace
- `EventLogCollector.cs`: Suppressed verbose `SecurityException` stack trace for `Security` log (requires admin); logs short message instead

### 2026-06-21 - Fix: Log bloat & offline queue data loss
**Fixed**:
- `RetryService.cs`: Suppressed full stack trace on intermediate retry attempts — only the error message is logged on attempts 1 and 2; full stack trace is reserved for the final (3rd) failure log. Reduced log size by ~80 lines per retry cycle.
- `ApiSenderService.cs`: Removed redundant full stack trace in outer catch (RetryService already logs it on final failure).
- `MonitoringService.cs` `FlushOfflineQueueAsync`: Failed payloads are now re-enqueued to offline storage instead of being silently discarded. Previously: dequeue all → try send → fail → data lost. Now: dequeue all → try send → fail → re-enqueue → survives until backend is available.

### 2026-07-06 - Fix: Data-loss mismatches & new tabs
**Fixed**:
- `HealthPayload.cs`: Added `EmployeeMobileNumber` property — agent now sends the configured mobile number
- `MonitoringService.cs`: Sets `EmployeeMobileNumber` on every health payload from `AgentSettings`; device-sync JSON now includes `device_status` and `last_seen` fields
- `SendRawPayloadAsync`: Changed silent `catch { return false; }` to `catch (Exception ex)` that logs `$"{ex.GetType().Name} - {ex.Message}"`
- `ApiSenderService.cs`: `AddHttpClient<MonitoringService>` in `Program.cs` now correctly configures `BaseAddress` and `Timeout` — offline-queue flush no longer silently fails

**New Dashboard Tabs (React frontend)**:
- Processes, Services, Network, System Logs — 4 new data tables in MachineDetails.jsx
- 4 new backend API endpoints: `/machines/{id}/services`, `/startup-programs`, `/event-logs`, `/network`
- 4 new frontend service functions in `machines.js`

**Backend Processors Updated**:
- `HardwareInventoryProcessor` / `AgentInventoryController`: Added `biosVendor`, `biosReleaseDate`, `systemArchitecture`; fixed `processorLogicalThreads` alias
- `BatteryProcessor`: Added `isBatteryPresent`, `designCapacity`, `fullChargeCapacity`
- `DiskProcessor`: Added `volumeLabel`
- `ProcessProcessor`: Added `processId`, `executablePath`, `threadCount`, `userName`
- `MachineProcessor`: Added `domainName`, `architecture`, `uptimeSeconds`, `currentLoggedInUsers`
- `NetworkProcessor`: Added `ipAddressV6`, `adapterType`
- `ServiceProcessor`: Added `serviceType`, `logOnAs`
- `LoginActivityProcessor`: Fixed field name mismatch (`eventType`/`sessionId` instead of `loginType`/`logonId`)
- `DeviceEventController::sync()`: Accepts and stores `device_status` and `last_seen`
- **New `UsbActivityProcessor`**: Created to save USB activity data (was completely missing)
- **New migration `2026_07_06_000001_add_missing_data_capture_columns`**: 22 columns across 8 tables

### 2026-07-06 - Fix: DB columns sync & LoginActivityProcessor EventLogInfo mapping
**Fixed**:
- **New migration `2026_07_06_000002_add_registry_key_and_status_to_startup_programs`**: Added `registry_key` (varchar 500) and `status` (varchar 50) columns to `startup_programs` — the `StartupProgramProcessor` was trying to write these columns but they didn't exist, causing SQL errors on every health payload with startup data
- **LoginActivityProcessor**: Agent sends login activities as `EventLogInfo` objects (eventId, userName, timeGenerated, etc.). The processor now maps `eventId` to `eventType` (4624→Logon, 4634→Logoff, 4647→Logoff Initiative, 4778/4779→Session Reconnect/Disconnect, 4800/4801→Lock/Unlock). Removed `eventId` fallback from `sessionId` (Windows Event IDs are not session identifiers — was storing 4624/4634 as session_id)
- **UsbActivity PHPDoc**: Removed stale `vendor_id`, `product_id`, `action` ghost properties from docblock; added `event_type`
- **All 17 pipeline model `$fillable` arrays verified**: Confirmed every fillable column exists in its corresponding migration — no more phantom columns

**Known limitation**: Agent's `ServiceInfo` DTO lacks `serviceType` and `logOnAs` fields — `windows_services` table stores them but they always come through as null

### 2026-07-06 - Fix: Frontend field cross-reference audit & backend accessors
**Fixed**:
- **Machine model**: Added `$appends` with computed accessors for `employee_name` (from `assignedUser.name`), `current_user` (same), `cpu_model` (from `processor`). Previously these fields didn't exist in any DB column — frontend showed "SYSTEM"/"—" — now they return real data.
- **LoginActivity model**: Added `$appends` with `getIsSuccessAttribute()` — returns `true` for successful event types (Logon, Logoff, etc.), `false` for Failed Logon, `null` otherwise. Fixes the Activity tab where ALL logins showed "Failed" (red badge) because `is_success` was undefined.
- **LoginActivityProcessor**: Added eventId 4625 → `'Failed Logon'` mapping (Windows Security Event ID for failed logon attempts). Previously these were mapped to 'unknown'.
- **Complete frontend field audit**: Cross-referenced every field accessed in MachineDetails.jsx (10 tabs) and LiveMonitoring.jsx against backend controller responses and database schema. Documented in Backend PROJECT_CONTEXT.md Section 8.

### 2026-07-06 - Fix: Remaining frontend display gaps (ip_address, network chart, kb_article)
**Fixed**:
- **Machine model `ip_address`/`mac_address`**: Added `networkAdapters` hasMany relationship + `$appends` accessors returning first adapter's IP/MAC. MachineService now eager-loads `networkAdapters` — LiveMonitoring no longer shows "N/A" for IP/MAC.
- **New migration `2026_07_06_000003`**: Added `network_sent_bytes` + `network_received_bytes` to `health_logs` (for LiveMonitoring network chart); added `kb_article` to `windows_updates` (for KB number display).
- **NetworkProcessor**: Now accepts and updates the shared `healthLog` with aggregate `network_sent_bytes`/`network_received_bytes` — health_logs time series finally includes network traffic data.
- **MachineController::history()**: Computes `network_bytes_sent_per_sec` from consecutive health_log records — LiveMonitoring network chart shows real data instead of flat zero.
- **UpdateProcessor**: Now maps agent's `kbId`/`kb_id` field to `kb_article` column — frontend shows proper KB article numbers instead of falling through fallback chain.
