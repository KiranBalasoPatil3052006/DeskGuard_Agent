/// <summary>
/// Manages the approved baseline state for all monitored categories.
/// Baselines represent the "known good state" of the machine.
/// The comparison engine (ChangeDetectionService) uses these baselines
/// to detect changes by comparing current collector output against stored baselines.
///
/// Supported categories:
///   - Hardware (CPU, RAM, disks, motherboard, etc.)
///   - Software (installed applications)
///   - Security (antivirus, firewall status)
///   - Peripheral (connected devices)
///   - Network (adapter configurations)
///   - Configuration (services, startup programs, OS settings)
/// </summary>
using DeskGuardAgent.Interfaces;
using DeskGuardAgent.Models;
using System.Collections.Concurrent;

namespace DeskGuardAgent.Services
{
    public class BaselineManager
    {
        private readonly ILoggerService _logger;

        // In-memory baseline dictionaries for each monitored category.
        private readonly ConcurrentDictionary<string, string> _hardwareBaseline = new();
        private readonly ConcurrentDictionary<string, string> _softwareBaseline = new();
        private readonly ConcurrentDictionary<string, string> _peripheralBaseline = new();
        private readonly ConcurrentDictionary<string, string> _securityBaseline = new();
        private readonly ConcurrentDictionary<string, string> _networkBaseline = new();
        private readonly ConcurrentDictionary<string, string> _configurationBaseline = new();

        public BaselineManager(ILoggerService logger)
        {
            _logger = logger;
        }

        // ──────────────────────────────────────────────
        //  Baseline Initialization Methods
        // ──────────────────────────────────────────────

        /// <summary>
        /// Initializes the hardware baseline from a HardwareInventory snapshot.
        /// Captures key identifying components: manufacturer, model, serial, BIOS,
        /// processor details, memory, and OS information.
        /// </summary>
        public void InitializeHardwareBaseline(HardwareInventory inventory)
        {
            _hardwareBaseline.Clear();
            AddHardwareComponent("Manufacturer", inventory.Manufacturer);
            AddHardwareComponent("Model", inventory.Model);
            AddHardwareComponent("SerialNumber", inventory.SerialNumber);
            AddHardwareComponent("BiosVendor", inventory.BiosVendor);
            AddHardwareComponent("BiosVersion", inventory.BiosVersion);
            AddHardwareComponent("ProcessorName", inventory.ProcessorName);
            AddHardwareComponent("ProcessorCores", inventory.ProcessorCores.ToString());
            AddHardwareComponent("ProcessorThreads", inventory.ProcessorLogicalThreads.ToString());
            AddHardwareComponent("TotalMemoryBytes", inventory.TotalMemoryBytes.ToString());
            AddHardwareComponent("OperatingSystem", inventory.OperatingSystem);
            AddHardwareComponent("OsVersion", inventory.OsVersion);
            AddHardwareComponent("SystemArchitecture", inventory.SystemArchitecture);

            _logger.LogInformation($"Hardware baseline initialized with {_hardwareBaseline.Count} entries");
        }

        /// <summary>
        /// Initializes the software baseline from a list of installed software.
        /// Each entry maps DisplayName to a combined version|publisher string.
        /// </summary>
        public void InitializeSoftwareBaseline(List<SoftwareInventory> softwareList)
        {
            _softwareBaseline.Clear();
            foreach (var sw in softwareList)
            {
                _softwareBaseline[sw.DisplayName] = $"{sw.DisplayVersion}|{sw.Publisher}";
            }
            _logger.LogInformation($"Software baseline initialized with {_softwareBaseline.Count} entries");
        }

        /// <summary>
        /// Initializes the peripheral baseline from a list of connected peripherals.
        /// Each entry maps DeviceName|DeviceType|Manufacturer to the device Status.
        /// </summary>
        public void InitializePeripheralBaseline(List<PeripheralInfo> peripherals)
        {
            _peripheralBaseline.Clear();
            foreach (var p in peripherals)
            {
                string key = $"{p.DeviceName}|{p.DeviceType}|{p.Manufacturer}";
                _peripheralBaseline[key] = p.Status;
            }
            _logger.LogInformation($"Peripheral baseline initialized with {_peripheralBaseline.Count} entries");
        }

        /// <summary>
        /// Initializes the security baseline from current security posture data.
        /// Captures antivirus display name, real-time protection status, signature status,
        /// and firewall enablement state for all profiles.
        /// </summary>
        public void InitializeSecurityBaseline(AntivirusInfo? antivirus, FirewallInfo? firewall)
        {
            _securityBaseline.Clear();

            if (antivirus != null)
            {
                AddSecurityComponent("antivirus_display_name", antivirus.DisplayName);
                AddSecurityComponent("antivirus_product_version", antivirus.ProductVersion);
                AddSecurityComponent("real_time_protection_enabled", antivirus.IsRealTimeProtectionEnabled.ToString());
                AddSecurityComponent("signature_up_to_date", antivirus.IsSignatureUpToDate.ToString());
                AddSecurityComponent("antivirus_status", antivirus.Status ?? "unknown");
            }

            if (firewall != null)
            {
                AddSecurityComponent("firewall_domain_enabled", firewall.IsDomainFirewallEnabled.ToString());
                AddSecurityComponent("firewall_private_enabled", firewall.IsPrivateFirewallEnabled.ToString());
                AddSecurityComponent("firewall_public_enabled", firewall.IsPublicFirewallEnabled.ToString());
                AddSecurityComponent("firewall_active_profile", firewall.ActiveProfile ?? "unknown");
            }

            _logger.LogInformation($"Security baseline initialized with {_securityBaseline.Count} entries");
        }

        /// <summary>
        /// Initializes the network baseline from a list of network adapters.
        /// Captures adapter name, IP, MAC, speed, and connection status for each adapter.
        /// </summary>
        public void InitializeNetworkBaseline(List<NetworkInfo> networkAdapters)
        {
            _networkBaseline.Clear();
            foreach (var adapter in networkAdapters)
            {
                AddNetworkComponent($"{adapter.AdapterName}|mac", adapter.MacAddress);
                AddNetworkComponent($"{adapter.AdapterName}|ip", adapter.IpAddressV4);
                string? speedStr = adapter.ConnectionSpeedMbps.HasValue ? adapter.ConnectionSpeedMbps.Value.ToString() : null;
                AddNetworkComponent($"{adapter.AdapterName}|speed", speedStr);
                AddNetworkComponent($"{adapter.AdapterName}|status", adapter.IsConnected.ToString());
            }
            _logger.LogInformation($"Network baseline initialized with {_networkBaseline.Count} entries");
        }

        /// <summary>
        /// Initializes the configuration baseline from current service and startup program states.
        /// Captures service status, start type, and startup program paths.
        /// </summary>
        public void InitializeConfigurationBaseline(List<ServiceInfo>? services, List<ProcessInfo>? startupPrograms)
        {
            _configurationBaseline.Clear();

            if (services != null)
            {
                foreach (var svc in services)
                {
                    AddConfigComponent($"service:{svc.ServiceName}|status", svc.Status ?? "unknown");
                    AddConfigComponent($"service:{svc.ServiceName}|start_type", svc.StartType ?? "unknown");
                }
            }

            if (startupPrograms != null)
            {
                foreach (var sp in startupPrograms)
                {
                    AddConfigComponent($"startup:{sp.ProcessName}", sp.ExecutablePath ?? sp.ProcessName);
                }
            }

            _logger.LogInformation($"Configuration baseline initialized with {_configurationBaseline.Count} entries");
        }

        // ──────────────────────────────────────────────
        //  Change Detection Methods
        // ──────────────────────────────────────────────

        /// <summary>
        /// Detects hardware changes by comparing current inventory against the hardware baseline.
        /// Returns a list of ChangeEvent objects for each detected difference.
        /// </summary>
        public List<ChangeEvent> DetectHardwareChanges(HardwareInventory inventory)
        {
            var changes = new List<ChangeEvent>();
            var current = new Dictionary<string, string>
            {
                ["Manufacturer"] = inventory.Manufacturer,
                ["Model"] = inventory.Model,
                ["SerialNumber"] = inventory.SerialNumber,
                ["BiosVendor"] = inventory.BiosVendor,
                ["BiosVersion"] = inventory.BiosVersion,
                ["ProcessorName"] = inventory.ProcessorName,
                ["ProcessorCores"] = inventory.ProcessorCores.ToString(),
                ["ProcessorThreads"] = inventory.ProcessorLogicalThreads.ToString(),
                ["TotalMemoryBytes"] = inventory.TotalMemoryBytes.ToString(),
                ["OperatingSystem"] = inventory.OperatingSystem,
                ["OsVersion"] = inventory.OsVersion,
                ["SystemArchitecture"] = inventory.SystemArchitecture,
            };

            foreach (var kvp in current)
            {
                _hardwareBaseline.TryGetValue(kvp.Key, out var previous);
                if (previous != kvp.Value && !string.IsNullOrEmpty(kvp.Value))
                {
                    changes.Add(new ChangeEvent
                    {
                        Category = "hardware",
                        ChangeType = "modified",
                        Severity = DetermineHardwareSeverity(kvp.Key),
                        ItemIdentifier = kvp.Key,
                        ItemLabel = kvp.Key,
                        PreviousValue = previous ?? "(none)",
                        NewValue = kvp.Value,
                        Description = $"Hardware changed: {kvp.Key} = {previous ?? "(not set)"} -> {kvp.Value}",
                        DetectedAt = DateTime.UtcNow
                    });
                }
            }

            return changes;
        }

        /// <summary>
        /// Detects software changes (additions, removals, updates) by comparing
        /// current software list against the software baseline.
        /// </summary>
        public List<ChangeEvent> DetectSoftwareChanges(List<SoftwareInventory> softwareList)
        {
            var changes = new List<ChangeEvent>();
            var currentNames = new HashSet<string>();

            foreach (var sw in softwareList)
            {
                currentNames.Add(sw.DisplayName);
                string currentValue = $"{sw.DisplayVersion}|{sw.Publisher}";

                if (_softwareBaseline.TryGetValue(sw.DisplayName, out var previousValue))
                {
                    if (previousValue != currentValue)
                    {
                        changes.Add(new ChangeEvent
                        {
                            Category = "software",
                            ChangeType = "updated",
                            Severity = "important",
                            ItemIdentifier = sw.DisplayName,
                            ItemLabel = sw.DisplayName,
                            PreviousValue = $"version: {previousValue.Split('|')[0]}",
                            NewValue = $"version: {sw.DisplayVersion}",
                            Description = $"Software updated: {sw.DisplayName} ({previousValue.Split('|')[0]} -> {sw.DisplayVersion})",
                            DetectedAt = DateTime.UtcNow
                        });
                    }
                }
                else
                {
                    changes.Add(new ChangeEvent
                    {
                        Category = "software",
                        ChangeType = "added",
                        Severity = "information",
                        ItemIdentifier = sw.DisplayName,
                        ItemLabel = sw.DisplayName,
                        PreviousValue = null,
                        NewValue = sw.DisplayVersion,
                        Description = $"New software installed: {sw.DisplayName} v{sw.DisplayVersion}",
                        DetectedAt = DateTime.UtcNow
                    });
                }
            }

            foreach (var kvp in _softwareBaseline)
            {
                if (!currentNames.Contains(kvp.Key))
                {
                    changes.Add(new ChangeEvent
                    {
                        Category = "software",
                        ChangeType = "removed",
                        Severity = "important",
                        ItemIdentifier = kvp.Key,
                        ItemLabel = kvp.Key,
                        PreviousValue = kvp.Value.Split('|')[0],
                        NewValue = null,
                        Description = $"Software uninstalled: {kvp.Key}",
                        DetectedAt = DateTime.UtcNow
                    });
                }
            }

            return changes;
        }

        /// <summary>
        /// Detects peripheral changes (devices connected or disconnected)
        /// by comparing current peripherals against the peripheral baseline.
        /// </summary>
        public List<ChangeEvent> DetectPeripheralChanges(List<PeripheralInfo> peripherals)
        {
            var changes = new List<ChangeEvent>();
            var currentKeys = new HashSet<string>();

            foreach (var p in peripherals)
            {
                string key = $"{p.DeviceName}|{p.DeviceType}|{p.Manufacturer}";
                currentKeys.Add(key);

                if (!_peripheralBaseline.ContainsKey(key))
                {
                    changes.Add(new ChangeEvent
                    {
                        Category = "peripheral",
                        ChangeType = "connected",
                        Severity = "information",
                        ItemIdentifier = key,
                        ItemLabel = p.DeviceName,
                        NewValue = p.Status,
                        Description = $"New device connected: {p.DeviceName} ({p.DeviceType})",
                        DetectedAt = DateTime.UtcNow
                    });
                }
            }

            foreach (var kvp in _peripheralBaseline)
            {
                if (!currentKeys.Contains(kvp.Key))
                {
                    changes.Add(new ChangeEvent
                    {
                        Category = "peripheral",
                        ChangeType = "disconnected",
                        Severity = "warning",
                        ItemIdentifier = kvp.Key,
                        ItemLabel = kvp.Key.Split('|')[0],
                        PreviousValue = kvp.Value,
                        Description = $"Device disconnected: {kvp.Key.Split('|')[0]}",
                        DetectedAt = DateTime.UtcNow
                    });
                }
            }

            return changes;
        }

        /// <summary>
        /// Detects security changes (antivirus, firewall status changes)
        /// by comparing current security posture against the security baseline.
        /// </summary>
        public List<ChangeEvent> DetectSecurityChanges(AntivirusInfo? antivirus, FirewallInfo? firewall)
        {
            var changes = new List<ChangeEvent>();
            var current = new Dictionary<string, string>();

            if (antivirus != null)
            {
                current["real_time_protection_enabled"] = antivirus.IsRealTimeProtectionEnabled.ToString();
                current["signature_up_to_date"] = antivirus.IsSignatureUpToDate.ToString();
                current["antivirus_display_name"] = antivirus.DisplayName ?? "unknown";
            }

            if (firewall != null)
            {
                current["firewall_domain_enabled"] = firewall.IsDomainFirewallEnabled.ToString();
                current["firewall_private_enabled"] = firewall.IsPrivateFirewallEnabled.ToString();
                current["firewall_public_enabled"] = firewall.IsPublicFirewallEnabled.ToString();
            }

            foreach (var kvp in current)
            {
                _securityBaseline.TryGetValue(kvp.Key, out var previous);
                if (previous != kvp.Value && !string.IsNullOrEmpty(kvp.Value) && kvp.Value != "unknown")
                {
                    string severity = DetermineSecuritySeverity(kvp.Key, kvp.Value);
                    changes.Add(new ChangeEvent
                    {
                        Category = "security",
                        ChangeType = kvp.Value == "False" || kvp.Value == "false" || kvp.Value == "disabled" ? "disabled" : "modified",
                        Severity = severity,
                        ItemIdentifier = kvp.Key,
                        ItemLabel = FormatSecurityLabel(kvp.Key),
                        PreviousValue = previous,
                        NewValue = kvp.Value,
                        Description = $"Security change: {FormatSecurityLabel(kvp.Key)} changed from {previous} to {kvp.Value}",
                        DetectedAt = DateTime.UtcNow
                    });
                }
            }

            return changes;
        }

        /// <summary>
        /// Detects network changes (adapter configuration changes, status changes)
        /// by comparing current network state against the network baseline.
        /// </summary>
        public List<ChangeEvent> DetectNetworkChanges(List<NetworkInfo> networkAdapters)
        {
            var changes = new List<ChangeEvent>();
            var currentKeys = new HashSet<string>();

            if (networkAdapters == null) return changes;

            foreach (var adapter in networkAdapters)
            {
                string macKey = $"{adapter.AdapterName}|mac";
                string ipKey = $"{adapter.AdapterName}|ip";
                string speedKey = $"{adapter.AdapterName}|speed";
                string statusKey = $"{adapter.AdapterName}|status";

            currentKeys.Add(macKey);
            currentKeys.Add(ipKey);
            currentKeys.Add(speedKey);
            currentKeys.Add(statusKey);

            // Check MAC address change (critical - could indicate hardware replacement)
            CompareAndAddChange(networkChanges: changes, baseline: _networkBaseline,
                key: macKey, newValue: adapter.MacAddress,
                category: "network", changeType: "modified",
                severity: "critical",
                itemLabel: $"{adapter.AdapterName} MAC",
                description: $"Network adapter {adapter.AdapterName} MAC address changed");

            // Check IP address change (information)
            CompareAndAddChange(networkChanges: changes, baseline: _networkBaseline,
                key: ipKey, newValue: adapter.IpAddressV4,
                category: "network", changeType: "modified",
                severity: "information",
                itemLabel: $"{adapter.AdapterName} IP",
                description: $"Network adapter {adapter.AdapterName} IP address changed");

            // Check link speed change (warning)
            string? speed = adapter.ConnectionSpeedMbps.HasValue ? adapter.ConnectionSpeedMbps.Value.ToString() : null;
            CompareAndAddChange(networkChanges: changes, baseline: _networkBaseline,
                key: speedKey, newValue: speed,
                category: "network", changeType: "modified",
                severity: "warning",
                itemLabel: $"{adapter.AdapterName} Speed",
                description: $"Network adapter {adapter.AdapterName} speed changed");

            // Check connection status change (warning)
            string newStatus = adapter.IsConnected ? "connected" : "disconnected";
                if (_networkBaseline.TryGetValue(statusKey, out var prevStatus))
                {
                    if (prevStatus != newStatus)
                    {
                        changes.Add(new ChangeEvent
                        {
                            Category = "network",
                            ChangeType = newStatus == "disconnected" ? "disconnected" : "connected",
                            Severity = "warning",
                            ItemIdentifier = statusKey,
                            ItemLabel = $"{adapter.AdapterName} Status",
                            PreviousValue = prevStatus,
                            NewValue = newStatus,
                            Description = $"Network adapter {adapter.AdapterName} is now {newStatus}",
                            DetectedAt = DateTime.UtcNow
                        });
                    }
                }
            }

            return changes;
        }

        /// <summary>
        /// Detects configuration changes (service status, startup program changes)
        /// by comparing current state against the configuration baseline.
        /// </summary>
        public List<ChangeEvent> DetectConfigurationChanges(List<ServiceInfo>? services, List<ProcessInfo>? startupPrograms)
        {
            var changes = new List<ChangeEvent>();

            if (services != null)
            {
                foreach (var svc in services)
                {
                    string statusKey = $"service:{svc.ServiceName}|status";
                    string startTypeKey = $"service:{svc.ServiceName}|start_type";

                    CompareAndAddChange(networkChanges: changes, baseline: _configurationBaseline,
                        key: statusKey, newValue: svc.Status,
                        category: "configuration", changeType: "modified",
                        severity: svc.Status == "Stopped" ? "important" : "warning",
                        itemLabel: $"Service {svc.ServiceName} Status",
                        description: $"Service '{svc.DisplayName ?? svc.ServiceName}' status changed");

                    CompareAndAddChange(networkChanges: changes, baseline: _configurationBaseline,
                        key: startTypeKey, newValue: svc.StartType,
                        category: "configuration", changeType: "modified",
                        severity: "important",
                        itemLabel: $"Service {svc.ServiceName} Start Type",
                        description: $"Service '{svc.DisplayName ?? svc.ServiceName}' start type changed");
                }
            }

            if (startupPrograms != null)
            {
                var currentStartupNames = new HashSet<string>();
                foreach (var sp in startupPrograms)
                {
                    string key = $"startup:{sp.ProcessName}";
                    currentStartupNames.Add(key);

                    if (!_configurationBaseline.ContainsKey(key))
                    {
                        changes.Add(new ChangeEvent
                        {
                            Category = "configuration",
                            ChangeType = "added",
                            Severity = "information",
                            ItemIdentifier = key,
                            ItemLabel = $"Startup: {sp.ProcessName}",
                            NewValue = sp.ExecutablePath ?? sp.ProcessName,
                            Description = $"New startup program added: {sp.ProcessName}",
                            DetectedAt = DateTime.UtcNow
                        });
                    }
                }

                foreach (var kvp in _configurationBaseline)
                {
                    if (kvp.Key.StartsWith("startup:") && !currentStartupNames.Contains(kvp.Key))
                    {
                        changes.Add(new ChangeEvent
                        {
                            Category = "configuration",
                            ChangeType = "removed",
                            Severity = "information",
                            ItemIdentifier = kvp.Key,
                            ItemLabel = $"Startup: {kvp.Key.Replace("startup:", "")}",
                            PreviousValue = kvp.Value,
                            Description = $"Startup program removed: {kvp.Key.Replace("startup:", "")}",
                            DetectedAt = DateTime.UtcNow
                        });
                    }
                }
            }

            return changes;
        }

        // ──────────────────────────────────────────────
        //  Baseline Update Methods (after approval)
        // ──────────────────────────────────────────────

        /// <summary>
        /// Updates the software baseline after changes are approved or detected.
        /// Handles added, removed, and updated software entries.
        /// </summary>
        public void ApplySoftwareChanges(List<ChangeEvent> changes)
        {
            foreach (var change in changes)
            {
                string? name = change.ItemIdentifier;
                if (name == null) continue;
                switch (change.ChangeType)
                {
                    case "added":
                        _softwareBaseline[name] = change.NewValue ?? "";
                        break;
                    case "removed":
                        _softwareBaseline.TryRemove(name, out _);
                        break;
                    case "updated":
                        _softwareBaseline[name] = change.NewValue ?? "";
                        break;
                }
            }
        }

        /// <summary>
        /// Updates the peripheral baseline after changes are detected or approved.
        /// </summary>
        public void ApplyPeripheralChanges(List<ChangeEvent> changes)
        {
            foreach (var change in changes)
            {
                string? key = change.ItemIdentifier;
                if (key == null) continue;
                switch (change.ChangeType)
                {
                    case "connected":
                        _peripheralBaseline[key] = "connected";
                        break;
                    case "disconnected":
                        _peripheralBaseline.TryRemove(key, out _);
                        break;
                }
            }
        }

        /// <summary>
        /// Updates the security baseline after changes are approved.
        /// </summary>
        public void ApplySecurityChanges(List<ChangeEvent> changes)
        {
            foreach (var change in changes)
            {
                string? key = change.ItemIdentifier;
                if (key == null) continue;
                _securityBaseline[key] = change.NewValue ?? "";
            }
        }

        /// <summary>
        /// Updates the network baseline after changes are approved.
        /// </summary>
        public void ApplyNetworkChanges(List<ChangeEvent> changes)
        {
            foreach (var change in changes)
            {
                string? key = change.ItemIdentifier;
                if (key == null) continue;
                _networkBaseline[key] = change.NewValue ?? "";
            }
        }

        /// <summary>
        /// Updates the configuration baseline after changes are approved.
        /// </summary>
        public void ApplyConfigurationChanges(List<ChangeEvent> changes)
        {
            foreach (var change in changes)
            {
                string? key = change.ItemIdentifier;
                if (key == null) continue;
                switch (change.ChangeType)
                {
                    case "added":
                        _configurationBaseline[key] = change.NewValue ?? "";
                        break;
                    case "removed":
                        _configurationBaseline.TryRemove(key, out _);
                        break;
                    default:
                        _configurationBaseline[key] = change.NewValue ?? "";
                        break;
                }
            }
        }

        // ──────────────────────────────────────────────
        //  Private Helper Methods
        // ──────────────────────────────────────────────

        /// <summary>
        /// Adds a hardware component to the baseline if the value is not empty.
        /// </summary>
        private void AddHardwareComponent(string key, string value)
        {
            if (!string.IsNullOrEmpty(value))
            {
                _hardwareBaseline[key] = value;
            }
        }

        /// <summary>
        /// Adds a security component to the baseline if the value is not empty.
        /// </summary>
        private void AddSecurityComponent(string key, string value)
        {
            if (!string.IsNullOrEmpty(value) && value != "unknown")
            {
                _securityBaseline[key] = value;
            }
        }

        /// <summary>
        /// Adds a network component to the baseline if the value is not empty.
        /// </summary>
        private void AddNetworkComponent(string key, string? value)
        {
            if (!string.IsNullOrEmpty(value))
            {
                _networkBaseline[key] = value;
            }
        }

        /// <summary>
        /// Adds a configuration component to the baseline if the value is not empty.
        /// </summary>
        private void AddConfigComponent(string key, string? value)
        {
            if (!string.IsNullOrEmpty(value))
            {
                _configurationBaseline[key] = value;
            }
        }

        /// <summary>
        /// Compares a value against the stored baseline and adds a change if different.
        /// Avoids duplicate code across multiple comparison methods.
        /// </summary>
        private void CompareAndAddChange(List<ChangeEvent> networkChanges, ConcurrentDictionary<string, string> baseline,
            string key, string? newValue, string category, string changeType,
            string severity, string itemLabel, string description)
        {
            if (string.IsNullOrEmpty(newValue)) return;
            if (baseline.TryGetValue(key, out var previous))
            {
                if (previous != newValue)
                {
                    networkChanges.Add(new ChangeEvent
                    {
                        Category = category,
                        ChangeType = changeType,
                        Severity = severity,
                        ItemIdentifier = key,
                        ItemLabel = itemLabel,
                        PreviousValue = previous,
                        NewValue = newValue,
                        Description = description,
                        DetectedAt = DateTime.UtcNow
                    });
                }
            }
        }

        /// <summary>
        /// Determines the severity of a hardware change based on the component.
        /// Core components (RAM, CPU, disk, serial) are critical;
        /// BIOS changes are important; OS changes are important.
        /// </summary>
        private string DetermineHardwareSeverity(string componentKey)
        {
            return componentKey switch
            {
                "SerialNumber" or "ProcessorName" or "ProcessorCores" or "ProcessorThreads" or "TotalMemoryBytes" => "critical",
                "BiosVersion" or "BiosVendor" or "Manufacturer" or "Model" => "important",
                "OperatingSystem" or "OsVersion" or "SystemArchitecture" => "important",
                _ => "warning"
            };
        }

        /// <summary>
        /// Determines the severity of a security change.
        /// Disabling antivirus or firewall is critical;
        /// signature out of date is important.
        /// </summary>
        private string DetermineSecuritySeverity(string componentKey, string newValue)
        {
            bool isDisabled = newValue == "False" || newValue == "false" || newValue == "Disabled" || newValue == "disabled";

            return componentKey switch
            {
                "real_time_protection_enabled" when isDisabled => "critical",
                "firewall_domain_enabled" when isDisabled => "critical",
                "firewall_private_enabled" when isDisabled => "critical",
                "firewall_public_enabled" when isDisabled => "critical",
                "signature_up_to_date" when isDisabled => "important",
                _ => "warning"
            };
        }

        /// <summary>
        /// Converts a security component key to a human-readable label.
        /// </summary>
        private string FormatSecurityLabel(string key)
        {
            return key switch
            {
                "real_time_protection_enabled" => "Real-time Protection",
                "signature_up_to_date" => "Virus Definitions",
                "antivirus_display_name" => "Antivirus",
                "firewall_domain_enabled" => "Firewall (Domain)",
                "firewall_private_enabled" => "Firewall (Private)",
                "firewall_public_enabled" => "Firewall (Public)",
                "antivirus_status" => "Antivirus Status",
                _ => key
            };
        }
    }

    /// <summary>
    /// Represents a single detected change event.
    /// Sent to the backend for storage and further processing.
    /// Includes severity classification for the Severity Engine (Phase 6).
    /// </summary>
    public class ChangeEvent
    {
        /// <summary>Category: hardware, software, security, network, peripheral, configuration.</summary>
        public string Category { get; set; } = string.Empty;

        /// <summary>Type of change: added, removed, modified, updated, enabled, disabled, connected, disconnected.</summary>
        public string ChangeType { get; set; } = string.Empty;

        /// <summary>
        /// Severity level for the Severity Engine: information, warning, important, critical.
        /// Determines alert generation, email notifications, and UI badge rendering.
        /// </summary>
        public string Severity { get; set; } = "information";

        /// <summary>Unique identifier for the changed item (e.g., serial number, software name, setting key).</summary>
        public string? ItemIdentifier { get; set; }

        /// <summary>Human-readable label for display in the UI.</summary>
        public string? ItemLabel { get; set; }

        /// <summary>The previous value from the baseline before the change.</summary>
        public string? PreviousValue { get; set; }

        /// <summary>The new value detected in the current state.</summary>
        public string? NewValue { get; set; }

        /// <summary>Human-readable description of the change.</summary>
        public string? Description { get; set; }

        /// <summary>UTC timestamp when the change was detected.</summary>
        public DateTime DetectedAt { get; set; } = DateTime.UtcNow;
    }
}
