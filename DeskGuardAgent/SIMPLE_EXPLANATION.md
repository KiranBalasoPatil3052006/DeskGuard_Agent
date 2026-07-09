# DeskGuard Agent — Simple Explanation for Beginners

## Part 1: Understanding the Computer Basics First

### What is an "Operating System" (OS)?
The main software on your computer that manages everything. Examples: Windows 11, Windows 10.

### What is "Windows"?
The operating system made by Microsoft. It's the screen you see when you turn on your computer — the taskbar, start menu, desktop, etc.

### What is a "Service" (in Windows)?
A program that runs in the **background** — you don't see it, but it's always working.
- **Example:** Windows Update runs as a service. You never open it, but it checks for updates automatically.
- **Our agent** runs as a Windows Service too — it starts when the computer boots and runs 24/7 without anyone logging in.

### What is ".NET"?
A toolkit from Microsoft that programmers use to build Windows applications. Think of it like:
- **A mechanic's toolbox** — contains all the tools (code libraries) needed to build programs
- Our project uses **.NET 8** (the latest version)

### What is "WMI" (Windows Management Instrumentation)?
A way for programs to **ask Windows questions** about the computer.
- **Think of it like:** You asking a doctor "What's my temperature? What's my heart rate?"
- WMI lets our agent ask Windows: "How fast is the CPU running? How much RAM is used? What's the hard drive size?"
- **In code it looks like:** `SELECT * FROM Win32_Processor` (a query in WMI's language)

### What is "Registry"?
A big **filing cabinet** where Windows stores all settings.
- **Think of it like:** Your phone's Settings app — every setting is stored somewhere
- Our agent reads the Registry to find: What programs are installed? What programs start automatically?

### What is "Event Log"?
A **diary** that Windows keeps. Every time something important happens (error, login, USB plugged in), Windows writes it down with a date and time.
- **Think of it like:** A security camera recording — you can rewind and see what happened

### What is "JSON"?
A **format for organizing data** so computers can understand each other.
- **Think of it like:** A form you fill out at a doctor's office:
```json
{
  "name": "John",
  "age": 30,
  "temperature": 98.6
}
```
Our agent collects data and converts it to JSON before sending to the server.

### What is "API" / "Endpoint"?
- **API** = A **mailbox** where programs send messages to each other
- **Endpoint** = The **specific address** for a specific type of message
- **Example:** Our agent sends health data to `https://server.com/api/v1/health`

### What is "HTTP" / "HTTPS"?
The **language** computers use to talk over the internet.
- **HTTP** = Regular mail (not secure)
- **HTTPS** = Registered mail (encrypted, secure)
- Our agent uses HTTPS to send data safely

### What is "URL" / "Base URL"?
The **web address** of the server.
- **Example:** `https://deskguard.company.com`

### What is "Bearer Token" / "API Key"?
A **secret password** that proves our agent is allowed to send data.
- **Think of it like:** An ID badge that says "I work here, let me in"

### What is "Encryption"?
**Scrambling data** so only the right person can read it.
- **Think of it like:** Writing a letter in a secret code that only your friend knows how to decode
- Our agent uses AES-256 (military-grade encryption)

### What is "Serialization"?
Converting data from the program's format to JSON (or back).
- **Think of it like:** Translating English to Spanish, then back to English

### What is "Thread"?
A **line of work** in a program. One thread does one thing at a time.
- Multiple threads = doing multiple things at once (like cooking while talking on the phone)

### What is "Async / Await"?
A way for programs to **wait without blocking**.
- **Think of it like:** Ordering food at a restaurant:
  - **Without async:** You stand at the counter and wait until food is ready (can't do anything else)
  - **With async:** You sit down, the waiter brings food when it's ready (you can read the newspaper while waiting)

### What is "Dependency Injection"?
A fancy term for: **"Give an object everything it needs to work"** instead of making it create things itself.
- **Think of it like:** A chef doesn't grow vegetables — the kitchen manager gives them pre-washed ingredients
- In our code: `MonitoringService` doesn't create an `ApiSender` — it's given one automatically

### What is "Singleton" vs "Transient"?
- **Singleton:** One copy shared by everyone (like a single coffee machine in the office)
- **Transient:** New copy every time (like a disposable cup — use once, throw away)
- In our project: Services (MonitoringService, SchedulerService) are **Singleton**. Collectors are **Transient** (new instance each collection cycle).

### What is "NuGet"?
An **app store for code libraries**.
- Programmers don't write everything from scratch — they download ready-made packages
- **Example:** We use `Serilog` for logging, `Newtonsoft.Json` for JSON

### What is "Serilog"?
A **logging library** — it's like a flight recorder for our agent. Everything the agent does is written to a file (and shown on screen) with timestamps.

---

## Part 2: How the Whole System Works (The Big Picture)

### The Goal
Imagine you are an IT manager with **1000 computers** in your company. You want to know:
- Which computers are slow (high CPU)?
- Which computers are running out of disk space?
- Which computers have old antivirus?
- Who logged in and when?
- What software is installed on each computer?

Checking 1000 computers manually is impossible. So you create a **robot (the DeskGuard Agent)** that sits on each computer and reports back automatically.

### How the Robot Works

```
            ┌─────────────────────────────────┐
            │     Each Computer (1000 PCs)     │
            │  ┌───────────────────────────┐  │
            │  │  DeskGuard Agent (robot)  │  │
            │  │  ┌─────────────────────┐  │  │
            │  │  │ 17 Sensors/Sensors  │  │  │
            │  │  │ (CPU, RAM, Disk...) │  │  │
            │  │  └─────────┬───────────┘  │  │
            │  │            │               │  │
            │  │  ┌─────────▼───────────┐  │  │
            │  │  │   MonitoringService  │  │  │
            │  │  │  (The Manager)       │  │  │
            │  │  └─────────┬───────────┘  │  │
            │  │            │               │  │
            │  │  ┌─────────▼───────────┐  │  │
            │  │  │   ApiSenderService   │  │  │
            │  │  │  (The Mail Room)     │  │  │
            │  │  └─────────┬───────────┘  │  │
            │  └────────────┼──────────────┘  │
            └───────────────┼─────────────────┘
                            │ (Internet)
                ┌───────────▼───────────┐
                │   Backend Server      │
                │  (Central Database)   │
                └───────────────────────┘
```

### Step-by-Step: What Happens Every 5 Minutes

**Step 1: The Alarm Rings (SchedulerService)**
- The scheduler has a timer set for 5 minutes
- When it rings, it wakes up the manager (MonitoringService)

**Step 2: Manager Calls All Sensors (MonitoringService runs collectors)**
- The manager tells all 17 sensors to start working AT THE SAME TIME (in parallel)
- Each sensor goes and reads data from Windows

**Step 3: Sensors Report Back (Collectors return data)**
- CPU sensor: "CPU is at 53% usage, temperature 65°C"
- RAM sensor: "15.6 GB total, 75% used"
- Disk sensor: "C: drive is 51% full, health OK"
- ...all 17 report back

**Step 4: Manager Packages Everything (Builds a HealthPayload)**
- Takes all the small reports and combines them into ONE big report

**Step 5: Mail Room Tries to Send (ApiSenderService)**
- Puts the report in an envelope (JSON format)
- Tries to mail it to the server
- If server is unreachable:
  - Wait 10 seconds → try again
  - Wait 20 seconds → try again
  - Wait 40 seconds → try again
  - If still failing → save report to offline queue (Safety Box)

**Step 6: Safety Box Check (OfflineQueueService)**
- If there are old unsent reports in the safety box, try sending them too
- If sending fails, put them BACK in the safety box (so data is never lost)

**Step 7: Go Back to Sleep**
- Wait another 5 minutes, then repeat from Step 1

---

## Part 3: The 17 Sensors (Collectors) — How They Work

### 1. CpuCollector — "CPU Thermometer"
**What it measures:** How hard your processor is working and how hot it is.

**How it gets the data:**
- **Usage:** Uses **Performance Counter** — Windows keeps a running number of "CPU busy %" that any program can read. It's like looking at a car's speedometer.
- **Temperature:** Uses **LibreHardwareMonitorLib** — a library that can read temperature sensors. Same sensors that tell your laptop to turn on the fan.
- **Name/Speed/Cores:** Uses **WMI** — asks Windows "What kind of processor do you have?"

**Example output:**
```
CPU Usage: 53%
Temperature: 65°C
Name: 13th Gen Intel i5-13420H
Cores: 12
Speed: 2100 MHz
```

**Technical note:** It runs 4 queries at the same time (parallel) for speed:
1. PerformanceCounter for usage
2. LibreHardwareMonitor for temperature
3. WMI Win32_Processor for name/cores
4. All combined into one CpuInfo object

---

### 2. MemoryCollector — "RAM Gauge"
**What it measures:** How much memory (RAM) is being used.

**How it gets the data:**
- Uses **WMI** — queries `Win32_OperatingSystem`
- Gets: TotalVisibleMemorySize, FreePhysicalMemory
- Calculates: Used = Total - Free, Percentage = Used / Total × 100

**Example output:**
```
Total: 15.6 GB
Used: 11.7 GB (75%)
Free: 3.9 GB
```

**Why it's important:** When RAM hits 100%, the computer becomes very slow.

---

### 3. DiskCollector — "Hard Drive Gauge"
**What it measures:** Each hard drive/partition: space used, space free, type (SSD/HDD), health.

**How it gets the data:**
- Uses **DriveInfo** (a built-in .NET class) to get drive letter, total size, free space, file system (NTFS)
- Uses **WMI** to determine if the drive is SSD or HDD by querying `Win32_DiskDrive`
- Uses **WMI** to check SMART health status (like a health check for hard drives)

**Example output:**
```
Drive: C:\
Label: OS
Total: 500 GB
Used: 255 GB (51%)
Free: 245 GB
Type: HDD (or SSD, NVMe)
Health: OK
```

**For each drive (C:, D:, E:, etc.)** it creates one DiskInfo object and adds it to a list.

---

### 4. BatteryCollector — "Battery Status"
**What it measures:** For laptops — battery charge, health, estimated time remaining.

**How it gets the data:**
- Uses **WMI** — queries `Win32_Battery`
- Gets: EstimatedChargeRemaining (%), BatteryStatus (charging/discharging), DesignCapacity (original), FullChargeCapacity (current), EstimatedRunTime

**Example output:**
```
Battery present: Yes
Charge: 63%
Status: Discharging
Time remaining: 115 minutes (about 2 hours)
Wear level: 15% (battery has degraded 15% since new)
```

---

### 5. NetworkCollector — "Network Info"
**What it measures:** Each network adapter (WiFi, Ethernet, Bluetooth): connection status, IP address, MAC address, data usage.

**How it gets the data:**
- Uses **NetworkInterface** (a built-in .NET class) to list all adapters
- For each adapter: gets name, status (up/down), speed, MAC address, IP addresses
- Uses **WMI** for additional details like bytes sent/received

**Example output:**
```
Adapter 1: WiFi (Connected)
  IP: 192.168.1.5
  MAC: AB:CD:EF:12:34:56
  Speed: 300 Mbps
  Data sent: 1.2 GB
  Data received: 5.8 GB

Adapter 2: Bluetooth (Disconnected)
```

---

### 6. ProcessCollector — "Task Manager Snapshot"
**What it measures:** List of running programs (like opening Task Manager).

**How it gets the data:**
- Uses **Process.GetProcesses()** (a built-in .NET method) to get all running processes
- For each process: gets name, PID (process ID), RAM usage (working set), thread count
- Uses **WMI** to get the owner (which user started the program)
- Limits to top 100 processes (by RAM usage) to avoid huge payloads

**Example output:**
```
1. chrome.exe — 500 MB — 12 threads — User: KIRAN
2. slack.exe — 300 MB — 8 threads — User: KIRAN
3. code.exe — 250 MB — 6 threads — User: KIRAN
... (up to 100 processes)
```

---

### 7. SystemInfoCollector — "Computer ID Card"
**What it measures:** Basic info about your computer and Windows.

**How it gets the data:**
- Uses **WMI** — queries `Win32_OperatingSystem` and `Win32_ComputerSystem`
- Gets: OS name, version (e.g., Windows 11 Home), architecture (64-bit), boot time, computer name, domain, logged-in users
- Calculates: uptime = current time - boot time

**Example output:**
```
OS: Windows 11 Home Single Language
Version: 10.0.26200
Architecture: 64-bit
Computer name: KIRAN-BALASO-PA
Uptime: 12 days, 3 hours, 45 minutes
Logged-in users: 2
```

---

### 8. HardwareInventoryCollector — "Computer Birth Certificate"
**What it measures:** Detailed hardware specs for asset tracking.

**How it gets the data:**
- Runs 4 WMI queries in parallel:
  1. `Win32_ComputerSystem` — manufacturer (Dell, HP, Lenovo), model, serial number
  2. `Win32_BIOS` — BIOS vendor, version, date
  3. `Win32_Processor` — processor name, cores, threads
  4. `Win32_ComputerSystem` (again) — total physical memory
- Combines all into one HardwareInventory object

**Example output:**
```
Manufacturer: Dell Inc.
Model: Inspiron 15 3520
Serial: ABC12345678
BIOS: Dell Inc. Version 1.2.3, Date: 2023-01-15
Processor: 13th Gen Intel i5-13420H, 12 cores, 16 threads
Total Memory: 16 GB
OS: Windows 11 Home 64-bit
```

**Why it's important:** IT teams use this to know exactly what hardware they own and which computers need upgrades.

---

### 9. SoftwareInventoryCollector — "Installed Apps List"
**What it measures:** Every program installed on the computer.

**How it gets the data:**
- Reads the **Windows Registry** (the filing cabinet):
  - Opens `HKEY_LOCAL_MACHINE\Software\Microsoft\Windows\CurrentVersion\Uninstall`
  - Opens `HKEY_LOCAL_MACHINE\Software\WOW6432Node\Microsoft\Windows\CurrentVersion\Uninstall` (for 32-bit apps on 64-bit Windows)
  - Also reads the current user's key from `HKEY_CURRENT_USER`
- For each entry: gets display name, version, publisher, install date, estimated size

**Example output:**
```
1. Google Chrome — 120.0.6099.130 — Google LLC — 2024-01-10 — 250 MB
2. Visual Studio Code — 1.85.0 — Microsoft — 2024-01-05 — 350 MB
3. Zoom — 5.17.0 — Zoom Video Communications — 2023-12-20 — 180 MB
... (all installed apps)
```

**Why it's important:** IT teams can see which computers have unauthorized software or outdated versions.

---

### 10. ServiceCollector — "Windows Services Status"
**What it measures:** All Windows services and whether they're running.

**How it gets the data:**
- Uses **ServiceController.GetServices()** (a built-in .NET class) to list all services
- For each service: gets name, display name, status (Running, Stopped, etc.), start type (Automatic, Manual, Disabled)
- Filters to only include automatic-start services (to reduce payload size)

**Example output:**
```
1. Windows Update (wuauserv) — Running — Automatic
2. Print Spooler (Spooler) — Running — Automatic
3. Windows Defender (WinDefend) — Running — Automatic
4. Bluetooth Support (BTAGService) — Stopped — Manual
... (all auto-start services)
```

**Why it's important:** If a critical service like Windows Defender is stopped, IT needs to know immediately.

---

### 11. SecurityCollector — "Antivirus Status"
**What it measures:** Is the antivirus running? Is it up-to-date?

**How it gets the data:**
- First tries **WMI SecurityCenter2** (`root\SecurityCenter2`) — this is the same place Windows Security reads from
- Gets: display name, product version, real-time protection status, signature status
- **If SecurityCenter2 is not available** (common on some Windows versions): falls back to checking Windows Defender status via `root\Microsoft\Windows\Defender`

**Example output:**
```
Antivirus: Windows Defender
Real-time protection: Enabled
Signatures: Up-to-date
Status: OK
```

---

### 12. UpdateCollector — "Windows Update Status"
**What it measures:** Are there pending Windows Updates? Is auto-update on?

**How it gets the data:**
- First tries **COM (Component Object Model)** — calls the Windows Update Agent API directly (the same thing that runs when you click "Check for Updates" in Settings)
- **If that fails** (common without admin rights): falls back to reading the **Registry** to find pending updates
- Also checks Registry for auto-update settings

**Example output:**
```
Pending updates: 3
Security updates: 1
Last install: 2024-01-10
Auto-update: Enabled
Status: Not up-to-date (3 updates pending)
```

---

### 13. EventLogCollector — "Windows Diary Reader"
**What it measures:** Recent important events from Windows (errors, warnings, information).

**How it gets the data:**
- Uses **EventLog** (a built-in .NET class) to open Windows log files
- Reads from 3 logs:
  1. **System** — hardware/driver issues, service crashes
  2. **Application** — program crashes/errors
  3. **Security** — login attempts (requires admin rights)
- Collects events from the last 24 hours, up to `MaxEventLogEntries` (default: 50)

**Example output:**
```
1. System — Error — Event ID 1001 — "Service XYZ crashed" — 2:30 PM
2. Application — Warning — Event ID 100 — "Disk is almost full" — 1:15 PM
3. System — Info — Event ID 7036 — "Print Spooler started" — 9:00 AM
... (up to 50 entries)
```

**Why it's important:** The Event Log is the first place IT looks when troubleshooting problems.

---

### 14. FirewallCollector — "Firewall Status"
**What it measures:** Is Windows Firewall turned on for each network type?

**How it gets the data:**
- Uses **COM (Component Object Model)** — calls the Windows Firewall API directly
- Gets: firewall enabled/disabled for each profile (Domain, Private, Public)
- Gets: which profile is currently active
- **If COM fails**: falls back to WMI

**Example output:**
```
Domain profile: Enabled
Private profile: Enabled
Public profile: Enabled
Active profile: Private
Status: OK
```

---

### 15. StartupProgramCollector — "Startup Programs"
**What it measures:** Programs that launch when Windows starts (like Task Manager > Startup tab).

**How it gets the data:**
- Reads the **Registry** for startup entries:
  - `HKLM\Software\Microsoft\Windows\CurrentVersion\Run` (all users)
  - `HKCU\Software\Microsoft\Windows\CurrentVersion\Run` (current user)
- Also checks the **Startup folder** in the file system: `%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup`

**Example output:**
```
1. OneDrive — C:\Program Files\OneDrive\OneDrive.exe — HKLM\Run
2. Discord — C:\Users\KIRAN\AppData\Local\Discord\Update.exe — HKCU\Run
3. Slack — C:\Users\KIRAN\AppData\Roaming\Slack\slack.exe — Startup Folder
```

**Why it's important:** Too many startup programs slow down boot time.

---

### 16. LoginActivityCollector — "Log In / Log Out Tracker"
**What it measures:** Who logged in successfully and who tried and failed.

**How it gets the data:**
- Reads from the **Security Event Log** (if admin rights are available)
- Looks for specific **Event IDs**:
  - **4624** = Successful login (someone logged in correctly)
  - **4625** = Failed login (someone entered wrong password — could be a hacker)
- Collects events from the last 24 hours

**Example output:**
```
1. 4624 — Success — User: KIRAN — 9:00 AM — Logon type: Interactive (at keyboard)
2. 4625 — Failure — User: ADMIN — 3:15 AM — Logon type: Network (over internet)
3. 4624 — Success — User: KIRAN — 5:00 PM
```

**Why it's important:** Multiple failed logins (4625) at odd hours means someone might be trying to hack in.

---

### 17. UsbCollector — "USB Tracker"
**What it measures:** When USB devices are plugged in or removed.

**How it gets the data:**
- Reads from the **System Event Log**
- Looks for specific **Event IDs**:
  - **2003** = A USB device was plugged in
  - **2004** = A USB device was removed
  - Also checks for other USB-related source names
- Collects events from the last 24 hours

**Example output:**
```
1. 2003 — USB drive plugged in — USB\VID_0781&PID_5583 — 2:00 PM
2. 2004 — USB drive removed — USB\VID_0781&PID_5583 — 2:30 PM
3. 2003 — USB mouse plugged in — 4:00 PM
```

**Why it's important:** IT teams can detect if someone is copying company data to a personal USB drive.

---

## Part 4: How Data Flows (The Journey)

### A Single Collection Cycle (every 5 minutes)

```
[TIME: 0 seconds]
Scheduler alarm rings
          │
          ▼
[MonitoringService wakes up]
          │
          ├──► CpuCollector ───────────► WMI + PerformanceCounter
          ├──► MemoryCollector ────────► WMI
          ├──► DiskCollector ──────────► DriveInfo + WMI
          ├──► BatteryCollector ───────► WMI
          ├──► NetworkCollector ───────► NetworkInterface + WMI
          ├──► ProcessCollector ───────► Process.GetProcesses()
          ├──► SystemInfoCollector ────► WMI
          ├──► HardwareInventory ──────► WMI (4 queries in parallel)
          ├──► SoftwareInventory ──────► Registry
          ├──► ServiceCollector ───────► ServiceController
          ├──► SecurityCollector ──────► WMI SecurityCenter2
          ├──► UpdateCollector ───────► COM Windows Update
          ├──► EventLogCollector ─────► EventLog
          ├──► FirewallCollector ─────► COM HNetCfg
          ├──► StartupProgramCollector ► Registry + File System
          ├──► LoginActivityCollector ► EventLog (Security)
          └──► UsbCollector ──────────► EventLog (System)
          │
          ▼ (All collectors finish)
[MonitoringService builds HealthPayload]
          │
          ▼
[ApiSenderService tries to send]
          │
          ├──► SUCCESS: Server receives data ✓
          │
          └──► FAILURE:
                │
                ├──► Wait 10s → Retry 2
                ├──► Wait 20s → Retry 3
                └──► GIVE UP → Save to Offline Queue
                            │
                            ▼
                      [OfflineQueueService]
                      Saves to Storage/queue.json
                      (Max 1000 payloads, 100 MB)
          
          │
          ▼
[Flush offline queue — try to send old data too]
          │
          ├──► SUCCESS: Old data sent ✓ (removed from queue)
          └──► FAILURE: Old data stays in queue (re-enqueued)

          │
          ▼
[Wait 5 minutes... Repeat]
```

---

## Part 5: What Happens When Something Goes Wrong

| Problem | What the agent does | Where in the code |
|---------|-------------------|-------------------|
| No internet | Tries 3 times, saves to queue | ApiSenderService + RetryService |
| WMI query fails | Logs warning, returns empty/default data | Every collector's try/catch |
| Event Log Security (no admin) | Logs: "Cannot read Security log" | EventLogCollector |
| Windows Update COM fails | Falls back to Registry check | UpdateCollector |
| Drive type can't be determined | Returns "Unknown" | DiskCollector |
| Server is down for 3 days | Keeps collecting, queue fills up (max 1000) | OfflineQueueService |
| Server comes back | Queue is flushed automatically | FlushOfflineQueueAsync |
| Queue file gets corrupted | Starts fresh with empty queue | OfflineQueueService.ReadQueueFromFileAsync |
| App crashes on startup | Logs fatal error, exits | Program.cs try/catch |

---

## Part 6: Configuration Explained

### appsettings.json — The Settings File

```json
{
  "AgentSettings": {
    "AgentId": "",           // Computer's unique ID (auto-generated if empty)
    "TenantId": "",          // Your company name/ID
    "ApiBaseUrl": "",        // Server address (e.g., https://deskguard.company.com)
    "ApiKey": "",            // Secret password for the server
    "Environment": "Production",  // "Production" or "Development"
    "MaxRetryAttempts": 3,   // How many times to retry (3 = try, wait, try, wait, try)
    "RetryDelaySeconds": 10, // Base wait time: 10s, then 20s, then 40s
    "RequestTimeoutSeconds": 30  // Give up on request after 30 seconds
  },

  "MonitoringSettings": {
    "CollectionIntervalSeconds": 300,  // Every 5 minutes
    "EnableCpuMonitoring": true,       // Turn collectors ON/OFF
    "EnableMemoryMonitoring": true,
    "EnableDiskMonitoring": true,
    // ... (all 17 collectors have an ON/OFF switch)
    "HardwareInventoryIntervalHours": 24,  // Once a day
    "SoftwareInventoryIntervalHours": 24,  // Once a day
    "MaxEventLogEntries": 50  // Don't send more than 50 log entries at once
  }
}
```

---

## Part 7: Folder Structure — What Goes Where

```
DeskGuardAgent/
│
├── Program.cs              🔥 START HERE — The engine that starts everything
├── Worker.cs               ⏰ The background worker (keeps running until stopped)
├── appsettings.json        ⚙️ Settings panel
│
├── Collectors/             📡 17 sensors that gather data
│   ├── CpuCollector.cs
│   ├── MemoryCollector.cs
│   └── ... (15 more)
│
├── Services/               🏭 The workers that coordinate everything
│   ├── MonitoringService.cs    👔 The manager
│   ├── ApiSenderService.cs     📮 The mail room
│   ├── OfflineQueueService.cs  📦 The safety box
│   ├── SchedulerService.cs     ⏰ The alarm clock
│   └── RetryService.cs         🔄 The "try again" machine
│
├── Models/                 📄 Form templates (what data looks like)
│   ├── CpuInfo.cs
│   ├── MemoryInfo.cs
│   └── ... (13 more)
│
├── Interfaces/             📝 Contracts (promises between parts)
│   ├── ICollector.cs
│   ├── IApiSenderService.cs
│   └── ... (3 more)
│
├── Configuration/          ⚙️ Settings classes (match appsettings.json)
│   ├── AgentSettings.cs
│   └── MonitoringSettings.cs
│
├── Constants/              📏 Fixed values (5 minutes = 300 seconds, etc.)
│   ├── AgentConstants.cs
│   └── ApiRoutes.cs
│
├── Utilities/              🛠️ Helper tools
│   ├── JsonHelper.cs           🔄 Data translator
│   ├── EncryptionHelper.cs     🔒 Data locker
│   ├── MachineIdentifier.cs    🆔 Computer fingerprint
│   ├── FileHelper.cs           📁 File reader/writer
│   └── ValidationHelper.cs     ✅ Settings checker
│
├── Logs/                   📓 Diary (log files are saved here)
│
└── Storage/                📦 Safety box (offline queue saved here)
    └── queue.json
```

---

## Glossary of Technical Terms

| Word | Simple Meaning | Example in our project |
|------|---------------|----------------------|
| **API** | A way for programs to talk to each other | The server's address where we send data |
| **Async** | Doing something without waiting | While waiting for server, agent can still work |
| **Await** | "Wait here until done, but don't freeze" | `await _apiSender.SendAsync()` |
| **Class** | A blueprint for creating objects | `CpuCollector` is a class; each run creates one |
| **Constructor** | Code that runs when a new object is created | Sets up HttpClient, logger, etc. at startup |
| **Dependency Injection** | Giving an object everything it needs | MonitoringService gets all collectors injected |
| **DTO / Model** | A simple container for data | `CpuInfo` just holds CPU data, no logic |
| **Endpoint** | A specific URL path | `/api/v1/health` |
| **Exception** | An error that can crash the program | Caught by try/catch so agent doesn't crash |
| **JSON** | A text format for data | `{"cpu": 53, "ram": 75}` |
| **Method** | A function inside a class | `CollectAsync()` = the method that collects data |
| **Namespace** | A folder for organizing classes | `DeskGuardAgent.Collectors` |
| **NuGet** | A store for code packages | We downloaded Serilog, Newtonsoft.Json, etc. |
| **Payload** | The package of data sent to server | A HealthPayload contains all 17 sensor readings |
| **Singleton** | A single shared instance | Only one SchedulerService exists |
| **Thread** | A line of work in a program | Collectors run in parallel (multiple threads) |
| **Transient** | A new instance each time | New collector created for each collection cycle |
| **WMI** | A way to ask Windows questions | "Hey Windows, how hot is the CPU?" |
