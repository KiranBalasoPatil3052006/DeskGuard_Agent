# DeskGuard Agent — Folder & File Explanation

This document explains every folder and file in the project in simple English.

---

## Root Files (top-level files)

### Program.cs
**Purpose:** This is the "Start" button of the whole application.

**What it does:**
- Reads the settings from `appsettings.json`
- Sets up logging (prints to console + saves to log files)
- Connects all the pieces together (collectors, services, etc.)
- Starts the agent
- If no backend URL is set, it starts in "test mode" (collects data but doesn't crash)

**Example:**
```
When you run "dotnet run", Program.cs is the first thing that executes.
It's like opening your phone — everything else starts from here.
```

---

### Worker.cs
**Purpose:** The "brain" that keeps the agent alive.

**What it does:**
- Tells the scheduler to start collecting data
- Keeps the program running until you press Ctrl+C
- Gracefully stops everything when you want to shut down

**Example:**
```
Worker.cs is like a security guard who sits at the desk 24/7.
When his shift starts, he turns on the monitoring cameras (scheduler).
When his shift ends, he turns everything off properly.
```

---

### appsettings.json
**Purpose:** The "settings panel" — where you configure how the agent behaves.

**What it contains:**
- `AgentSettings`: Your backend URL, API key, tenant ID, retry limits, timeout
- `MonitoringSettings`: How often to collect data (every 5 minutes), which collectors to enable/disable

**Example:**
```
Think of this like the settings page on your phone.
You can turn WiFi on/off (enable/disable CPU monitoring),
change how often the screen locks (collection interval), etc.
```

---

### DeskGuardAgent.csproj
**Purpose:** The "recipe card" that tells .NET what ingredients (packages) are needed.

**What it contains:**
- List of all NuGet packages (like Serilog for logging, System.Management for WMI)
- Target framework (.NET 8)
- Project type (Worker Service)

---

## Collectors/ Folder

**Purpose:** The "sensors" that gather information from your computer.

Each collector is like a thermometer or pressure gauge — it reads one specific thing.

### CpuCollector.cs
**What it does:** Checks how much your CPU is working and its temperature.

**Example:**
```
Like checking if your laptop fan is running loud.
It tells you: "CPU is at 53% usage, temperature 65°C"
```

### MemoryCollector.cs
**What it does:** Checks how much RAM is used and how much is free.

**Example:**
```
Like checking how many apps you have open on your phone.
It tells you: "15.6 GB total, 75% used, 4 GB free"
```

### DiskCollector.cs
**What it does:** Checks your hard drive space and health.

**Example:**
```
Like checking how full your hard drive is in File Explorer.
It tells you: "C: drive 500 GB total, 51% used, 250 GB free"
```

### BatteryCollector.cs
**What it does:** Checks laptop battery level and health.

**Example:**
```
Like the battery icon in your taskbar.
It tells you: "63% charged, not charging, 2 hours remaining"
```

### NetworkCollector.cs
**What it does:** Checks your internet/WiFi adapters and data usage.

**Example:**
```
Like checking your WiFi settings.
It tells you: "Connected to WiFi, IP: 192.168.1.5, MAC: AB:CD:EF:12:34:56"
```

### ProcessCollector.cs
**What it does:** Lists all running programs (like Task Manager).

**Example:**
```
Like opening Task Manager.
It tells you: "Chrome.exe running, using 500 MB RAM, 12 threads"
```

### SystemInfoCollector.cs
**What it does:** Gets basic info about your Windows and computer.

**Example:**
```
Like checking "About Your PC" in Settings.
It tells you: "Windows 11 Home, 64-bit, uptime 12 days, computer name: KIRAN-PC"
```

### HardwareInventoryCollector.cs
**What it does:** Gets detailed hardware specs (like for inventory tracking).

**Example:**
```
Like looking at your computer's "birth certificate".
It tells you: "Manufacturer: Dell, Model: Inspiron 15, Serial: ABC123"
```

### SoftwareInventoryCollector.cs
**What it does:** Lists all installed software on your computer.

**Example:**
```
Like going to "Add or Remove Programs" in Settings.
It tells you: "Chrome 120.0, VS Code 1.85, Zoom 5.17, etc."
```

### ServiceCollector.cs
**What it does:** Checks Windows services (background programs).

**Example:**
```
Like checking services.msc.
It tells you: "Windows Update is running, Print Spooler is running, etc."
```

### SecurityCollector.cs
**What it does:** Checks if your antivirus is active and up-to-date.

**Example:**
```
Like checking Windows Security Center.
It tells you: "Windows Defender is active, signatures are up-to-date"
```

### UpdateCollector.cs
**What it does:** Checks for pending Windows Updates.

**Example:**
```
Like checking Windows Update in Settings.
It tells you: "2 updates pending, 1 security update, auto-update is on"
```

### EventLogCollector.cs
**What it does:** Reads Windows event logs (System, Application, Security).

**Example:**
```
Like checking Event Viewer.
It tells you: "Error logged at 2:30 PM — Service X stopped unexpectedly"
```

### FirewallCollector.cs
**What it does:** Checks if Windows Firewall is turned on.

**Example:**
```
Like checking Windows Defender Firewall.
It tells you: "Firewall is ON for all networks (Domain, Private, Public)"
```

### StartupProgramCollector.cs
**What it does:** Lists programs that start automatically when Windows boots.

**Example:**
```
Like checking Task Manager > Startup tab.
It tells you: "OneDrive, Discord, Spotify start when Windows starts"
```

### LoginActivityCollector.cs
**What it does:** Tracks who logged in and if anyone failed to log in.

**Example:**
```
It tells you: "User logged in at 9:00 AM. Someone tried wrong password at 3:00 AM (possible hacking attempt)"
```

### UsbCollector.cs
**What it does:** Tracks when USB devices are plugged in or removed.

**Example:**
```
It tells you: "USB drive plugged in at 2:00 PM. USB mouse removed at 4:00 PM"
```

---

## Configuration/ Folder

**Purpose:** Stores the settings classes that match `appsettings.json`.

### AgentSettings.cs
**What it does:** A C# class that holds all agent settings (URL, API key, timeout, etc.).

**Example:**
```
When the program reads "ApiBaseUrl" from appsettings.json, it stores it here
so other parts of the program can easily access it.
```

### MonitoringSettings.cs
**What it does:** A C# class that holds all monitoring settings (interval, which collectors to run, etc.).

**Example:**
```
If you set "EnableCpuMonitoring: false" in the JSON file,
this class stores that value so the CPU collector knows not to run.
```

---

## Constants/ Folder

**Purpose:** Stores fixed values that don't change (like recipe measurements).

### AgentConstants.cs
**What it contains:**
- Agent name and version ("DeskGuard Agent v1.0.0")
- Service name for Windows Service registration
- Maximum queue size (1000 payloads, 50 MB file size)
- Standard event IDs (4624 = login success, 4625 = login failure)

**Example:**
```
Like knowing that 1 cup = 240 ml in a recipe.
Instead of writing "1000" everywhere, the code uses "AgentConstants.MaxQueuedPayloads"
```

### ApiRoutes.cs
**What it contains:**
- Backend API endpoint paths: `/api/v1/health`, `/api/v1/inventory/hardware`, etc.

**Example:**
```
Like a postal address for each package type.
Health data goes to /api/v1/health, hardware data to /api/v1/inventory/hardware
```

---

## Interfaces/ Folder

**Purpose:** Defines "contracts" — promises about what each piece of code will do.

### ICollector.cs
**What it says:** "Every collector must have a `CollectAsync()` method that returns data."

**Example:**
```
Like a job description that says "must know how to drive".
Whether it's a car or a truck (CPU collector or Disk collector),
they all know how to "drive" (collect data).
```

### IApiSenderService.cs
**What it says:** "The API sender must be able to send health, hardware, software, events, and security data."

### IMonitoringService.cs
**What it says:** "The monitoring service must be able to start, stop, and run a collection cycle."

### ILoggerService.cs
**What it says:** "The logger must support Info, Warning, Error, and Debug messages."

### IOfflineQueueService.cs
**What it says:** "The offline queue must support adding items, removing all items, checking count, and clearing."

**Example:**
```
Interfaces are like USB standards.
Any USB device (mouse, keyboard, flash drive) follows the same plug shape.
Any collector follows the same ICollector<T> interface.
```

---

## Models/ Folder

**Purpose:** Defines the "shapes" of data that gets collected and sent.

**What each file does:**
Each `.cs` file here is like a form template with blank fields.

| File | What data it holds |
|------|-------------------|
| CpuInfo.cs | CPU usage %, temperature, name, cores |
| MemoryInfo.cs | Total RAM, used RAM, free RAM |
| DiskInfo.cs | Drive letter, space used, space free |
| BatteryInfo.cs | Charge %, charging status, wear level |
| NetworkInfo.cs | IP address, MAC, connection status |
| ProcessInfo.cs | Program name, RAM used, PID |
| SystemInfo.cs | Windows version, uptime, computer name |
| HardwareInventory.cs | Manufacturer, model, serial number |
| SoftwareInventory.cs | Installed app name, version, publisher |
| ServiceInfo.cs | Service name, status, start type |
| AntivirusInfo.cs | Antivirus name, status, up-to-date |
| UpdateInfo.cs | Pending updates, last update date |
| EventLogInfo.cs | Event ID, level, message, time |
| FirewallInfo.cs | Firewall status per network profile |
| HealthPayload.cs | The "big package" containing all of the above |

**Example:**
```
CpuInfo.cs is like a temperature report form:
Name: __________   (e.g., "Intel i5-13420H")
Usage: _____%     (e.g., "53%")
Temp: _____°C     (e.g., "65°C")
```

---

## Services/ Folder

**Purpose:** The "workers" that do the actual work using the collectors.

### MonitoringService.cs
**What it does:** The boss/coordinator. It tells all collectors when to run, collects their data, packages it, and sends it.

**Example:**
```
Like a factory supervisor:
1. "CPU collector, go check the CPU!" (parallel)
2. "Memory collector, go check the RAM!"
3. "Disk collector, go check the hard drive!"
4. Collects all reports
5. Packages them into one big report
6. "Mail room, send this to headquarters!"
```

### ApiSenderService.cs
**What it does:** The mail room. Takes the packaged data and sends it to the backend server via the internet.

**How it works:**
- Tries to send the data
- If it fails, waits and tries again (up to 3 times, waiting 10s, then 20s, then 40s)
- If still failing, stores the data in the offline queue

**Example:**
```
Like sending a letter:
1. Put it in the mailbox (send HTTP POST)
2. If mailbox is full, try again after 10 seconds
3. If still full, try again after 20 seconds
4. If still failing, keep the letter in your pocket (offline queue)
```

### OfflineQueueService.cs
**What it does:** A "safety box" that stores data when the internet is down.

**How it works:**
- Saves data to a file (`Storage/queue.json`)
- Each piece of data has an ID and the time it was saved
- Maximum 1000 items (oldest removed if full)
- Maximum file size 50 MB
- When internet comes back, all saved data gets sent

**Example:**
```
Like writing notes on paper when your phone battery dies.
When your phone charges again, you type those notes into your phone.
```

### SchedulerService.cs
**What it does:** A timer/alarm clock that tells collectors when to work.

**Schedule:**
- Health data (CPU, RAM, disk, etc.): Every 5 minutes
- Hardware inventory: Every 24 hours (starts after 5 minutes)
- Software inventory: Every 24 hours (starts after 10 minutes)

**Example:**
```
Like setting alarms on your phone:
- Alarm at 9:00 AM every day (health check every 5 min)
- Alarm at 9:05 AM on Monday (hardware inventory every 24 hours)
- Alarm at 9:10 AM on Monday (software inventory every 24 hours)
```

### RetryService.cs
**What it does:** Implements the "try, wait, try again" pattern for failed operations.

**Pattern:**
- Attempt 1: Try immediately
- Attempt 2: Wait 10 seconds, try again
- Attempt 3: Wait 20 seconds, try again
- After 3 failures: Give up (max wait capped at 5 minutes)

**Example:**
```
Like calling someone who's not picking up:
- Call now → No answer
- Wait 10 seconds → Call again → No answer
- Wait 20 seconds → Call again → No answer
- Send a text message instead (store in offline queue)
```

---

## Utilities/ Folder

**Purpose:** Helper tools used by other parts of the program.

### JsonHelper.cs
**What it does:** Converts data to/from JSON format (the language computers speak on the internet).

**Example:**
```
Like a translator:
Your data → JSON → Send to server
JSON from server → Your data
```

### EncryptionHelper.cs
**What it does:** Locks (encrypts) and unlocks (decrypts) sensitive data using AES-256 (military-grade encryption).

**Example:**
```
Like a lockbox with a combination lock:
- Put your key in the box → Lock it → Safe!
- Know the password → Unlock → Get your key back
```

### MachineIdentifier.cs
**What it does:** Creates a unique ID for each computer based on its hardware (motherboard, processor, hard drive).

**Example:**
```
Like a fingerprint for your computer.
"755b2d9bd5e8a539917f2739f61f5f3c..."
This ID stays the same even if you reinstall Windows.
```

### FileHelper.cs
**What it does:** Reads and writes files safely, making sure data doesn't get corrupted.

**Example:**
```
Like saving a document:
1. Save to a temporary file first
2. Only replace the original when the temp file is 100% done
3. If power goes out during save, the original file is safe
```

### ValidationHelper.cs
**What it does:** Checks that settings are correct before the agent starts.

**Checks:**
- Is the API URL a valid web address?
- Is the retry count between 1 and 10?
- Is the collection interval at least 60 seconds?

**Example:**
```
Like checking your luggage before a trip:
- Do you have your passport? (Is API key set?)
- Is your ticket valid? (Is the URL correct?)
- Is your luggage under 50 kg? (Are settings within limits?)
```

---

## Properties/ Folder

**Purpose:** Standard folder that Visual Studio/.NET uses for project settings.

### launchSettings.json
**What it does:** Tells Visual Studio how to run the program when debugging.

**Example:**
```
Like the startup instructions for a car:
- Set environment to "Development" (for testing)
- Don't start in production mode yet
```

---

## Logs/ Folder

**Purpose:** Where log files are saved when the agent runs.

**What gets created:**
- `deskguard-20260621.log` (one file per day)
- Automatically deletes logs older than 30 days

**Example:**
```
Like a diary that the agent writes in every day.
If something goes wrong, you can check the diary to find out what happened.
```

---

## Storage/ Folder

**Purpose:** Where offline data is stored when the internet is down.

**What gets created:**
- `queue.json` — Contains all unsent data with endpoint, payload, and timestamp

**Example:**
```
Like a mailbox at your house.
When the post office is closed (server is down),
your letters (data) wait here until the post office opens again.
```

---

## Installer/ Folder

**Purpose:** Placeholder for future Windows installer files.

**What will eventually be here:**
- Scripts to install DeskGuard Agent as a Windows Service
- MSI installer package for IT administrators

---

## Tests/ Folder

**Purpose:** Placeholder for future test files.

**What will eventually be here:**
- Unit tests (test each piece individually, like testing a single collector)
- Integration tests (test multiple pieces together, like the full collection cycle)

---

## bin/ Folder

**Purpose:** Contains the compiled program (the actual executable).

**What's inside:**
- `DeskGuardAgent.exe` — The actual program you can run
- `DeskGuardAgent.dll` — The compiled code library
- All the package DLLs (Serilog, Newtonsoft.Json, etc.)
- Copy of `appsettings.json` (needed at runtime)

**Note:** You never need to open this folder. It's automatically created by `dotnet build` or `dotnet run`.

---

## obj/ Folder

**Purpose:** Temporary files used during compilation.

**What's inside:**
- Intermediate build files
- NuGet cache files
- Auto-generated code

**Note:** You never need to touch this folder. It's like the "scaffolding" that gets removed after building.

---

## Quick Summary Table

| Folder | Purpose | Analogy |
|--------|---------|---------|
| `Collectors/` | Gather computer data | Sensors / Thermometers |
| `Configuration/` | Read settings from JSON | Settings panel |
| `Constants/` | Fixed values that never change | Recipe measurements |
| `Interfaces/` | Contracts/code promises | USB standard |
| `Models/` | Data shapes / forms | Blank forms to fill |
| `Services/` | Main work logic | Workers / Employees |
| `Utilities/` | Helper tools | Swiss army knife |
| `Logs/` | Saved log files | Diary / Journal |
| `Storage/` | Offline data queue | Mailbox |
| `Installer/` | Future installer files | Installation CD |
| `Tests/` | Future test files | Quality check |
| `Properties/` | VS/.NET settings | Car startup instructions |
| `bin/` | Compiled program | The actual car |
| `obj/` | Build leftovers | Construction scaffolding |
