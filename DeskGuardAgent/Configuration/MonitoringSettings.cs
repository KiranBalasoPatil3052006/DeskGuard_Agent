/// <summary>
/// Defines monitoring intervals and feature toggles for the DeskGuard Agent.
/// Maps to the "MonitoringSettings" section in appsettings.json.
/// Controls how frequently each collector runs and whether specific monitoring features are enabled.
/// </summary>
namespace DeskGuardAgent.Configuration
{
    public class MonitoringSettings
    {
        /// <summary>
        /// Gets or sets the interval in seconds between each full collection cycle.
        /// All enabled collectors will run during each cycle.
        /// Default value is 300 seconds (5 minutes) to balance freshness with resource usage.
        /// </summary>
        public int CollectionIntervalSeconds { get; set; } = 300;

        /// <summary>
        /// Gets or sets whether CPU monitoring is enabled.
        /// When disabled, the CpuCollector will be skipped during collection cycles.
        /// </summary>
        public bool EnableCpuMonitoring { get; set; } = true;

        /// <summary>
        /// Gets or sets whether CPU temperature monitoring is enabled.
        /// Requires LibreHardwareMonitorLib and administrative privileges on some systems.
        /// </summary>
        public bool EnableCpuTemperatureMonitoring { get; set; } = true;

        /// <summary>
        /// Gets or sets whether RAM/memory monitoring is enabled.
        /// Collects total, used, and available memory metrics.
        /// </summary>
        public bool EnableMemoryMonitoring { get; set; } = true;

        /// <summary>
        /// Gets or sets whether disk monitoring is enabled.
        /// Collects disk usage, free space, and SMART health status.
        /// </summary>
        public bool EnableDiskMonitoring { get; set; } = true;

        /// <summary>
        /// Gets or sets whether network monitoring is enabled.
        /// Collects network adapter status, IP addresses, and traffic statistics.
        /// </summary>
        public bool EnableNetworkMonitoring { get; set; } = true;

        /// <summary>
        /// Gets or sets whether battery monitoring is enabled.
        /// Only relevant for laptops and portable devices.
        /// Collects battery percentage, health, and charging status.
        /// </summary>
        public bool EnableBatteryMonitoring { get; set; } = true;

        /// <summary>
        /// Gets or sets whether hardware inventory collection is enabled.
        /// Collects system manufacturer, model, serial number, and BIOS information.
        /// Runs less frequently than health metrics.
        /// </summary>
        public bool EnableHardwareInventory { get; set; } = true;

        /// <summary>
        /// Gets or sets whether software inventory collection is enabled.
        /// Enumerates all installed applications from the registry.
        /// This can be resource-intensive and may be scheduled less frequently.
        /// </summary>
        public bool EnableSoftwareInventory { get; set; } = true;

        /// <summary>
        /// Gets or sets whether Windows service monitoring is enabled.
        /// Checks status of critical Windows services.
        /// </summary>
        public bool EnableServiceMonitoring { get; set; } = true;

        /// <summary>
        /// Gets or sets whether security monitoring is enabled.
        /// Includes antivirus and firewall status checks.
        /// </summary>
        public bool EnableSecurityMonitoring { get; set; } = true;

        /// <summary>
        /// Gets or sets whether Windows Update monitoring is enabled.
        /// Checks for pending updates and update status.
        /// </summary>
        public bool EnableUpdateMonitoring { get; set; } = true;

        /// <summary>
        /// Gets or sets whether event log monitoring is enabled.
        /// Reads critical and error events from Windows Event Log.
        /// </summary>
        public bool EnableEventLogMonitoring { get; set; } = true;

        /// <summary>
        /// Gets or sets whether firewall monitoring is enabled.
        /// Checks if Windows Firewall is enabled and active.
        /// </summary>
        public bool EnableFirewallMonitoring { get; set; } = true;

        /// <summary>
        /// Gets or sets whether startup program monitoring is enabled.
        /// Enumerates programs configured to run at system startup.
        /// </summary>
        public bool EnableStartupProgramMonitoring { get; set; } = true;

        /// <summary>
        /// Gets or sets whether login activity monitoring is enabled.
        /// Tracks successful and failed login attempts via event logs.
        /// </summary>
        public bool EnableLoginActivityMonitoring { get; set; } = true;

        /// <summary>
        /// Gets or sets whether USB device activity monitoring is enabled.
        /// Tracks USB device connections and disconnections via event logs.
        /// </summary>
        public bool EnableUsbMonitoring { get; set; } = true;

        /// <summary>
        /// Gets or sets whether process monitoring is enabled.
        /// Collects list of running processes and their resource usage.
        /// </summary>
        public bool EnableProcessMonitoring { get; set; } = true;

        /// <summary>
        /// Gets or sets the interval in hours for full hardware inventory collection.
        /// Hardware inventory changes infrequently, so a longer interval is appropriate.
        /// Default value is 24 hours.
        /// </summary>
        public int HardwareInventoryIntervalHours { get; set; } = 24;

        /// <summary>
        /// Gets or sets the interval in hours for full software inventory collection.
        /// Software inventory changes infrequently, so a longer interval is appropriate.
        /// Default value is 24 hours.
        /// </summary>
        public int SoftwareInventoryIntervalHours { get; set; } = 24;

        /// <summary>
        /// Gets or sets the maximum number of event log entries to collect per cycle.
        /// Prevents the agent from sending excessive data and overwhelming the API.
        /// Default value is 50 entries.
        /// </summary>
        public int MaxEventLogEntries { get; set; } = 50;

        /// <summary>
        /// Gets or sets whether peripheral device monitoring is enabled.
        /// Collects connected peripherals via Win32_PnPEntity.
        /// </summary>
        public bool EnablePeripheralMonitoring { get; set; } = true;

        /// <summary>
        /// Gets or sets whether real-time device change event watching is enabled.
        /// Uses ManagementEventWatcher for instant device connect/disconnect detection.
        /// </summary>
        public bool EnableDeviceEventWatcher { get; set; } = true;

        /// <summary>
        /// Gets or sets the interval in minutes for periodic full peripheral device scans.
        /// Recommended: 30 minutes.
        /// </summary>
        public int DeviceScanIntervalMinutes { get; set; } = 30;

    }
}
