/// <summary>
/// Central orchestration service that coordinates all collectors and manages the
/// complete data collection lifecycle. Collects metrics from all enabled collectors,
/// packages them into a health payload, and sends the payload to the backend API.
/// Implements the IMonitoringService interface for standardized lifecycle management.
/// </summary>
using System.Text.Json;
using DeskGuardAgent.Collectors;
using DeskGuardAgent.Configuration;
using DeskGuardAgent.Constants;
using DeskGuardAgent.Interfaces;
using DeskGuardAgent.Models;
using DeskGuardAgent.Utilities;

namespace DeskGuardAgent.Services
{
    /// <summary>
    /// Service responsible for orchestrating the complete monitoring workflow.
    /// Coordinates collector execution, payload assembly, and data transmission.
    /// Implements IMonitoringService for standardized start/stop/execute lifecycle.
    /// </summary>
    public class MonitoringService : IMonitoringService
    {
        private readonly ILoggerService _logger;
        private readonly AgentSettings _agentSettings;
        private readonly MonitoringSettings _monitoringSettings;
        private readonly IApiSenderService _apiSender;
        private readonly IOfflineQueueService _offlineQueue;
        private readonly HttpClient _httpClient;

        // Collectors for system health metrics.
        private readonly CpuCollector _cpuCollector;
        private readonly MemoryCollector _memoryCollector;
        private readonly DiskCollector _diskCollector;
        private readonly BatteryCollector _batteryCollector;
        private readonly NetworkCollector _networkCollector;
        private readonly ProcessCollector _processCollector;
        private readonly SystemInfoCollector _systemInfoCollector;

        // Collectors for inventory and security.
        private readonly HardwareInventoryCollector _hardwareInventoryCollector;
        private readonly SoftwareInventoryCollector _softwareInventoryCollector;
        private readonly ServiceCollector _serviceCollector;
        private readonly SecurityCollector _securityCollector;
        private readonly UpdateCollector _updateCollector;
        private readonly EventLogCollector _eventLogCollector;
        private readonly FirewallCollector _firewallCollector;
        private readonly StartupProgramCollector _startupProgramCollector;
        private readonly LoginActivityCollector _loginActivityCollector;
        private readonly UsbCollector _usbCollector;

        // Prevents concurrent collection cycles when a timer fires before the previous cycle finishes.
        private readonly SemaphoreSlim _executionLock = new SemaphoreSlim(1, 1);

        // Tracks when inventory was last collected to avoid excessive runs.
        private DateTime _lastHardwareInventoryTime = DateTime.MinValue;
        private DateTime _lastSoftwareInventoryTime = DateTime.MinValue;
        private DateTime _lastPeripheralSnapshotTime = DateTime.MinValue;
        private bool _baselineInitialized = false;
        private bool _hardwareBaselineInitialized = false;
        private bool _softwareBaselineInitialized = false;

        // Cached JSON snapshot of stable metric sections from the previous cycle.
        // Used for differential payload — only sends sections that actually changed,
        // dramatically reducing payload size on 10K+ agents where most fields are static.
        private string? _previousStableSnapshot = null;

        // Collector for peripheral devices.
        private readonly PeripheralCollector _peripheralCollector;

        // Change detection services.
        private readonly BaselineManager _baselineManager;
        private readonly ChangeDetectionService _changeDetectionService;

        /// <summary>
        /// Initializes a new instance of the MonitoringService class.
        /// All dependencies are injected via constructor injection following DI patterns.
        /// </summary>
        public MonitoringService(
            ILoggerService logger,
            AgentSettings agentSettings,
            MonitoringSettings monitoringSettings,
            IApiSenderService apiSender,
            IOfflineQueueService offlineQueue,
            HttpClient httpClient,
            CpuCollector cpuCollector,
            MemoryCollector memoryCollector,
            DiskCollector diskCollector,
            BatteryCollector batteryCollector,
            NetworkCollector networkCollector,
            ProcessCollector processCollector,
            SystemInfoCollector systemInfoCollector,
            HardwareInventoryCollector hardwareInventoryCollector,
            SoftwareInventoryCollector softwareInventoryCollector,
            ServiceCollector serviceCollector,
            SecurityCollector securityCollector,
            UpdateCollector updateCollector,
            EventLogCollector eventLogCollector,
            FirewallCollector firewallCollector,
            StartupProgramCollector startupProgramCollector,
            LoginActivityCollector loginActivityCollector,
            UsbCollector usbCollector,
            PeripheralCollector peripheralCollector,
            BaselineManager baselineManager,
            ChangeDetectionService changeDetectionService)
        {
            _logger = logger;
            _agentSettings = agentSettings;
            _monitoringSettings = monitoringSettings;
            _apiSender = apiSender;
            _offlineQueue = offlineQueue;
            _httpClient = httpClient;

            _cpuCollector = cpuCollector;
            _memoryCollector = memoryCollector;
            _diskCollector = diskCollector;
            _batteryCollector = batteryCollector;
            _networkCollector = networkCollector;
            _processCollector = processCollector;
            _systemInfoCollector = systemInfoCollector;
            _hardwareInventoryCollector = hardwareInventoryCollector;
            _softwareInventoryCollector = softwareInventoryCollector;
            _serviceCollector = serviceCollector;
            _securityCollector = securityCollector;
            _updateCollector = updateCollector;
            _eventLogCollector = eventLogCollector;
            _firewallCollector = firewallCollector;
            _startupProgramCollector = startupProgramCollector;
            _loginActivityCollector = loginActivityCollector;
            _usbCollector = usbCollector;
            _peripheralCollector = peripheralCollector;
            _baselineManager = baselineManager;
            _changeDetectionService = changeDetectionService;
        }

        /// <summary>
        /// Starts the monitoring service.
        /// Logs the startup event and begins the collection cycle.
        /// </summary>
        /// <param name="cancellationToken">Cancellation token for shutdown.</param>
        public async Task StartAsync(CancellationToken cancellationToken)
        {
            _logger.LogInformation($"DeskGuard Agent v{AgentConstants.AgentVersion} starting up.");

            // Log the machine identifier for debugging purposes.
            string machineId = MachineIdentifier.GenerateMachineId();
            _logger.LogInformation($"Machine ID: {machineId}");

            // Attempt to flush any queued offline payloads from previous runs.
            await FlushOfflineQueueAsync();
        }

        /// <summary>
        /// Stops the monitoring service gracefully.
        /// Logs the shutdown event.
        /// </summary>
        public Task StopAsync()
        {
            _logger.LogInformation("DeskGuard Agent shutting down.");
            return Task.CompletedTask;
        }

        /// <summary>
        /// Executes a single complete collection cycle.
        /// Runs all enabled collectors, builds a health payload, and sends it to the API.
        /// Also handles inventory collection on longer intervals.
        /// </summary>
        public async Task ExecuteCollectionCycleAsync()
        {
            // Prevent concurrent cycles — if a timer fires while the previous cycle is still running,
            // skip this tick to avoid overlapping mutations and excessive resource contention.
            if (!await _executionLock.WaitAsync(0))
            {
                _logger.LogWarning("Previous collection cycle still running. Skipping this tick.");
                return;
            }

            try
            {
                _logger.LogDebug("Starting collection cycle.");

                // Step 1: Collect all health metrics in parallel for efficiency.
                var healthMetricsTask = CollectHealthMetricsAsync();

                // Step 2: Collect inventory data if enough time has passed.
                Task<HardwareInventory?> hardwareTask = ShouldCollectHardwareInventory()
                    ? CollectHardwareInventoryAsync()
                    : Task.FromResult<HardwareInventory?>(null);

                Task<List<SoftwareInventory>?> softwareTask = ShouldCollectSoftwareInventory()
                    ? CollectSoftwareInventoryAsync()
                    : Task.FromResult<List<SoftwareInventory>?>(null);

                // Wait for all collection tasks to complete.
                await Task.WhenAll(healthMetricsTask, hardwareTask, softwareTask);

                // Step 3: Build the health payload from collected metrics.
                var (cpu, memory, disks, battery, network, processes, systemInfo, services,
                     startupPrograms, antivirus, firewall, updates, events, loginActivities,
                     usbActivities, peripherals) = healthMetricsTask.Result;

                // Build stable fields snapshot for differential comparison.
                // Only truly static fields are included — disks, network, and peripherals
                // change frequently and would invalidate the cache on every cycle.
                string currentStable = JsonSerializer.Serialize(new
                {
                    systemInfo,
                    antivirus,
                    firewall,
                    updates,
                    services,
                    startupPrograms
                });

                bool isFirstCycle = _previousStableSnapshot == null;
                bool stableUnchanged = !isFirstCycle && currentStable == _previousStableSnapshot;

                _previousStableSnapshot = currentStable;

                var healthPayload = new HealthPayload
                {
                    AgentId = _agentSettings.AgentId,
                    TenantId = _agentSettings.TenantId,
                    AgentVersion = AgentConstants.AgentVersion,
                    MachineId = _agentSettings.AgentId,
                    EmployeeMobileNumber = _agentSettings.EmployeeMobileNumber,
                    Timestamp = DateTime.UtcNow,
                    SystemInfo = isFirstCycle || !stableUnchanged ? systemInfo : null,
                    CpuInfo = cpu,
                    MemoryInfo = memory,
                    DiskInfo = disks,
                    BatteryInfo = battery,
                    NetworkInfo = network,
                    ProcessInfo = processes,
                    ServiceInfo = isFirstCycle || !stableUnchanged ? services : null,
                    StartupProgramInfo = isFirstCycle || !stableUnchanged ? startupPrograms : null,
                    AntivirusInfo = isFirstCycle || !stableUnchanged ? antivirus : null,
                    FirewallInfo = isFirstCycle || !stableUnchanged ? firewall : null,
                    UpdateInfo = isFirstCycle || !stableUnchanged ? updates : null,
                    EventLogInfo = events,
                    LoginActivityInfo = loginActivities,
                    UsbActivityInfo = usbActivities,
                    PeripheralInfo = peripherals
                };

                // Step 4: Send the health payload to the backend API.
                bool healthSent = await _apiSender.SendHealthPayloadAsync(healthPayload);
                if (!healthSent)
                {
                    _logger.LogWarning("Health payload delivery failed. Payload queued for offline retry.");
                }

                // Step 4b: Initialize baselines for security, network, and configuration
                // on the first cycle, then detect changes on subsequent cycles.
                if (!_baselineInitialized)
                {
                    // Initialize security baseline from current antivirus/firewall state.
                    if (antivirus != null || firewall != null)
                    {
                        _baselineManager.InitializeSecurityBaseline(antivirus, firewall);
                    }
                    // Initialize network baseline from current network adapters.
                    if (network != null && network.Count > 0)
                    {
                        _baselineManager.InitializeNetworkBaseline(network);
                    }
                    // Initialize configuration baseline from current services and startup programs.
                    if (services != null || startupPrograms != null)
                    {
                        _baselineManager.InitializeConfigurationBaseline(services, startupPrograms);
                    }

                    _baselineInitialized = true;
                }
                else
                {
                    // Detect security posture changes (antivirus/firewall).
                    if (antivirus != null || firewall != null)
                    {
                        await _changeDetectionService.ProcessSecurityChanges(antivirus, firewall);
                    }
                    // Detect network configuration changes.
                    if (network != null && network.Count > 0)
                    {
                        await _changeDetectionService.ProcessNetworkChanges(network);
                    }
                    // Detect configuration changes (services, startup programs).
                    if (services != null || startupPrograms != null)
                    {
                        await _changeDetectionService.ProcessConfigurationChanges(services, startupPrograms);
                    }
                }

                // Step 5: Send inventory data if it was collected this cycle.
                if (hardwareTask.Result != null)
                {
                    var hwPayload = new
                    {
                        machineId = _agentSettings.AgentId,
                        items = hardwareTask.Result
                    };
                    bool hwSent = await _apiSender.SendHardwareInventoryAsync(hwPayload);
                    if (!hwSent)
                    {
                        _logger.LogWarning("Hardware inventory delivery failed. Payload queued.");
                    }

                    if (!_hardwareBaselineInitialized)
                    {
                        _baselineManager.InitializeHardwareBaseline(hardwareTask.Result);
                        _hardwareBaselineInitialized = true;
                    }
                    else
                    {
                        await _changeDetectionService.ProcessHardwareChanges(hardwareTask.Result);
                    }

                    _lastHardwareInventoryTime = DateTime.UtcNow;
                }

                if (softwareTask.Result != null)
                {
                    var swPayload = new
                    {
                        machineId = _agentSettings.AgentId,
                        items = softwareTask.Result
                    };
                    bool swSent = await _apiSender.SendSoftwareInventoryAsync(swPayload);
                    if (!swSent)
                    {
                        _logger.LogWarning("Software inventory delivery failed. Payload queued.");
                    }

                    if (!_softwareBaselineInitialized)
                    {
                        _baselineManager.InitializeSoftwareBaseline(softwareTask.Result);
                        _softwareBaselineInitialized = true;
                    }
                    else
                    {
                        await _changeDetectionService.ProcessSoftwareChanges(softwareTask.Result);
                    }

                    _lastSoftwareInventoryTime = DateTime.UtcNow;
                }

                // Step 6: Send the same peripheral snapshot to the device-sync endpoint
                // when this cycle was responsible for collecting it.
                if (peripherals != null)
                {
                    if (peripherals.Count > 0)
                    {
                        await SendDeviceSyncAsync(peripherals);

                        if (_baselineInitialized)
                        {
                            await _changeDetectionService.ProcessPeripheralChanges(peripherals);
                        }
                        _baselineManager.InitializePeripheralBaseline(peripherals);
                    }

                    _lastPeripheralSnapshotTime = DateTime.UtcNow;
                }

                // Step 7: Attempt to flush any offline queued payloads (only if online).
                await FlushOfflineQueueAsync();

                _logger.LogDebug("Collection cycle completed successfully.");
            }
            catch (Exception ex)
            {
                // Ensure the agent never crashes - log and continue.
                _logger.LogError("Collection cycle failed with unexpected error.", ex);
            }
            finally
            {
                _executionLock.Release();
            }
        }

        /// <summary>
        /// Collects all health-related metrics in parallel for maximum efficiency.
        /// Each collector runs independently and failures in one do not affect others.
        /// </summary>
        /// <returns>A tuple containing all collected health metrics.</returns>
        private async Task<(
            CpuInfo? cpu,
            MemoryInfo? memory,
            List<DiskInfo>? disks,
            BatteryInfo? battery,
            List<NetworkInfo>? network,
            List<ProcessInfo>? processes,
            SystemInfo? systemInfo,
            List<ServiceInfo>? services,
            List<ProcessInfo>? startupPrograms,
            AntivirusInfo? antivirus,
            FirewallInfo? firewall,
            UpdateInfo? updates,
            List<EventLogInfo>? events,
            List<EventLogInfo>? loginActivities,
            List<EventLogInfo>? usbActivities,
            List<PeripheralInfo>? peripherals)> CollectHealthMetricsAsync()
        {
            // Create a list to hold all collection tasks.
            var tasks = new List<Task>();

            // Track collected results.
            CpuInfo? cpu = null;
            MemoryInfo? memory = null;
            List<DiskInfo>? disks = null;
            BatteryInfo? battery = null;
            List<NetworkInfo>? network = null;
            List<ProcessInfo>? processes = null;
            SystemInfo? systemInfo = null;
            List<ServiceInfo>? services = null;
            List<ProcessInfo>? startupPrograms = null;
            AntivirusInfo? antivirus = null;
            FirewallInfo? firewall = null;
            UpdateInfo? updates = null;
            List<EventLogInfo>? events = null;
            List<EventLogInfo>? loginActivities = null;
            List<EventLogInfo>? usbActivities = null;
            List<PeripheralInfo>? peripherals = null;

            // System Info is always collected.
            tasks.Add(Task.Run(async () => { systemInfo = await _systemInfoCollector.CollectAsync(); }));

            // CPU monitoring.
            if (_monitoringSettings.EnableCpuMonitoring)
            {
                tasks.Add(Task.Run(async () => { cpu = await _cpuCollector.CollectAsync(); }));
            }

            // Memory monitoring.
            if (_monitoringSettings.EnableMemoryMonitoring)
            {
                tasks.Add(Task.Run(async () => { memory = await _memoryCollector.CollectAsync(); }));
            }

            // Disk monitoring.
            if (_monitoringSettings.EnableDiskMonitoring)
            {
                tasks.Add(Task.Run(async () =>
                {
                    var result = await _diskCollector.CollectAsync();
                    disks = result?.Count > 0 ? result : null;
                }));
            }

            // Battery monitoring.
            if (_monitoringSettings.EnableBatteryMonitoring)
            {
                tasks.Add(Task.Run(async () => { battery = await _batteryCollector.CollectAsync(); }));
            }

            // Network monitoring.
            if (_monitoringSettings.EnableNetworkMonitoring)
            {
                tasks.Add(Task.Run(async () =>
                {
                    var result = await _networkCollector.CollectAsync();
                    network = result?.Count > 0 ? result : null;
                }));
            }

            // Process monitoring.
            if (_monitoringSettings.EnableProcessMonitoring)
            {
                tasks.Add(Task.Run(async () =>
                {
                    var result = await _processCollector.CollectAsync();
                    processes = result?.Count > 0 ? result : null;
                }));
            }

            // Service monitoring.
            if (_monitoringSettings.EnableServiceMonitoring)
            {
                tasks.Add(Task.Run(async () =>
                {
                    var result = await _serviceCollector.CollectAsync();
                    services = result?.Count > 0 ? result : null;
                }));
            }

            // Startup program monitoring.
            if (_monitoringSettings.EnableStartupProgramMonitoring)
            {
                tasks.Add(Task.Run(async () =>
                {
                    var result = await _startupProgramCollector.CollectAsync();
                    startupPrograms = result?.Count > 0 ? result : null;
                }));
            }

            // Security monitoring (antivirus).
            if (_monitoringSettings.EnableSecurityMonitoring)
            {
                tasks.Add(Task.Run(async () => { antivirus = await _securityCollector.CollectAsync(); }));
            }

            // Firewall monitoring.
            if (_monitoringSettings.EnableFirewallMonitoring)
            {
                tasks.Add(Task.Run(async () => { firewall = await _firewallCollector.CollectAsync(); }));
            }

            // Update monitoring.
            if (_monitoringSettings.EnableUpdateMonitoring)
            {
                tasks.Add(Task.Run(async () => { updates = await _updateCollector.CollectAsync(); }));
            }

            // Event log monitoring.
            if (_monitoringSettings.EnableEventLogMonitoring)
            {
                tasks.Add(Task.Run(async () =>
                {
                    var result = await _eventLogCollector.CollectAsync();
                    events = result?.Count > 0 ? result : null;
                }));
            }

            // Login activity monitoring.
            if (_monitoringSettings.EnableLoginActivityMonitoring)
            {
                tasks.Add(Task.Run(async () =>
                {
                    var result = await _loginActivityCollector.CollectAsync();
                    loginActivities = result?.Count > 0 ? result : null;
                }));
            }

            // USB activity monitoring.
            if (_monitoringSettings.EnableUsbMonitoring)
            {
                tasks.Add(Task.Run(async () =>
                {
                    var result = await _usbCollector.CollectAsync();
                    usbActivities = result?.Count > 0 ? result : null;
                }));
            }

            // Connected device snapshots are comparatively heavier, so collect
            // them only on the configured device scan cadence.
            if (ShouldCollectPeripheralSnapshot())
            {
                tasks.Add(Task.Run(async () =>
                {
                    var result = await _peripheralCollector.CollectAsync();
                    peripherals = result ?? new List<PeripheralInfo>();
                }));
            }

            // Wait for all collectors to complete.
            await Task.WhenAll(tasks);

            return (cpu, memory, disks, battery, network, processes, systemInfo, services,
                    startupPrograms, antivirus, firewall, updates, events, loginActivities,
                    usbActivities, peripherals);
        }

        /// <summary>
        /// Determines whether it is time to collect hardware inventory based on the configured interval.
        /// </summary>
        /// <returns>True if hardware inventory should be collected.</returns>
        private bool ShouldCollectHardwareInventory()
        {
            return _monitoringSettings.EnableHardwareInventory &&
                   DateTime.UtcNow - _lastHardwareInventoryTime >=
                   TimeSpan.FromHours(_monitoringSettings.HardwareInventoryIntervalHours);
        }

        /// <summary>
        /// Determines whether it is time to collect software inventory based on the configured interval.
        /// </summary>
        /// <returns>True if software inventory should be collected.</returns>
        private bool ShouldCollectSoftwareInventory()
        {
            return _monitoringSettings.EnableSoftwareInventory &&
                   DateTime.UtcNow - _lastSoftwareInventoryTime >=
                   TimeSpan.FromHours(_monitoringSettings.SoftwareInventoryIntervalHours);
        }

        /// <summary>
        /// Determines whether it is time to include a connected-device snapshot.
        /// </summary>
        private bool ShouldCollectPeripheralSnapshot()
        {
            return _monitoringSettings.EnablePeripheralMonitoring &&
                   DateTime.UtcNow - _lastPeripheralSnapshotTime >=
                   TimeSpan.FromMinutes(_monitoringSettings.DeviceScanIntervalMinutes);
        }

        /// <summary>
        /// Collects hardware inventory data.
        /// </summary>
        /// <returns>The collected hardware inventory, or null if not enabled.</returns>
        private async Task<HardwareInventory?> CollectHardwareInventoryAsync()
        {
            try
            {
                _logger.LogDebug("Collecting hardware inventory.");
                return await _hardwareInventoryCollector.CollectAsync();
            }
            catch (Exception ex)
            {
                _logger.LogError("Failed to collect hardware inventory.", ex);
                return null;
            }
        }

        /// <summary>
        /// Collects software inventory data.
        /// </summary>
        /// <returns>The collected software inventory list, or null if not enabled.</returns>
        private async Task<List<SoftwareInventory>?> CollectSoftwareInventoryAsync()
        {
            try
            {
                _logger.LogDebug("Collecting software inventory.");
                var result = await _softwareInventoryCollector.CollectAsync();
                return result?.Count > 0 ? result : null;
            }
            catch (Exception ex)
            {
                _logger.LogError("Failed to collect software inventory.", ex);
                return null;
            }
        }

        /// <summary>
        /// Attempts to flush any queued offline payloads to the API.
        /// Called on startup and after each collection cycle.
        /// In offline mode, skip flush — no backend to send to.
        /// </summary>
        private async Task FlushOfflineQueueAsync()
        {
            try
            {
                // Skip flush when running in offline/test mode (no backend configured).
                if (_agentSettings.IsOfflineMode)
                    return;

                // Check if there are any queued payloads.
                int queueCount = await _offlineQueue.GetQueueCountAsync();
                if (queueCount == 0)
                    return;

                _logger.LogInformation($"Flushing {queueCount} queued payloads from offline storage.");

                // Dequeue all queued payloads.
                var queuedPayloads = await _offlineQueue.DequeueAllAsync();

                // Track success count for logging.
                int successCount = 0;
                int failCount = 0;

                // Attempt to send each queued payload.
                foreach (var queuedItem in queuedPayloads)
                {
                    try
                    {
                        // Determine which endpoint method to call based on the endpoint path.
                        bool sent = queuedItem.Endpoint switch
                        {
                            var e when e.Contains(ApiRoutes.HealthEndpoint) =>
                                await SendRawPayloadAsync(queuedItem.Endpoint, queuedItem.Payload),
                            var e when e.Contains(ApiRoutes.HardwareInventoryEndpoint) =>
                                await SendRawPayloadAsync(queuedItem.Endpoint, queuedItem.Payload),
                            var e when e.Contains(ApiRoutes.SoftwareInventoryEndpoint) =>
                                await SendRawPayloadAsync(queuedItem.Endpoint, queuedItem.Payload),
                            var e when e.Contains(ApiRoutes.EventLogEndpoint) =>
                                await SendRawPayloadAsync(queuedItem.Endpoint, queuedItem.Payload),
                            var e when e.Contains(ApiRoutes.SecurityEndpoint) =>
                                await SendRawPayloadAsync(queuedItem.Endpoint, queuedItem.Payload),
                            _ => await SendRawPayloadAsync(queuedItem.Endpoint, queuedItem.Payload)
                        };

                        if (sent)
                        {
                            successCount++;
                        }
                        else
                        {
                            // Re-enqueue the payload if it failed to send.
                            await _offlineQueue.EnqueueAsync(queuedItem.Endpoint, queuedItem.Payload);
                            failCount++;
                        }
                    }
                    catch (Exception ex)
                    {
                        _logger.LogWarning($"Failed to flush queued payload {queuedItem.Id}. Re-queuing.", ex);
                        // Re-enqueue the payload on exception to prevent data loss.
                        await _offlineQueue.EnqueueAsync(queuedItem.Endpoint, queuedItem.Payload);
                        failCount++;
                    }
                }

                _logger.LogInformation($"Offline queue flush complete. Sent: {successCount}, Failed: {failCount}.");
            }
            catch (Exception ex)
            {
                _logger.LogError("Failed to flush offline queue.", ex);
            }
        }

        /// <summary>
        /// Sends a device sync payload to the backend with the current set of connected peripherals.
        /// </summary>
        /// <param name="devices">The list of connected peripherals.</param>
        public async Task SendDeviceSyncAsync(List<PeripheralInfo> devices)
        {
            try
            {
                _logger.LogDebug($"Sending device sync with {devices.Count} peripherals.");

                var payload = new
                {
                    machine_uid = _agentSettings.AgentId,
                    devices = devices.Select(d => new
                    {
                        device_name = d.DeviceName,
                        device_type = d.DeviceType,
                        manufacturer = d.Manufacturer,
                        connection_type = d.ConnectionType,
                        device_status = d.Status,
                        last_seen = d.LastSeen.ToString("O"),
                        device_id = d.DeviceId,
                        has_problem = d.HasProblem,
                        problem_description = d.ProblemDescription,
                        driver_version = d.DriverVersion
                    }).ToList()
                };

                bool syncSent = await _apiSender.SendDeviceSyncAsync(payload);
                if (syncSent)
                {
                    _logger.LogDebug("Device sync sent successfully.");
                }
                else
                {
                    _logger.LogWarning("Device sync delivery failed.");
                }
            }
            catch (Exception ex)
            {
                _logger.LogError("Failed to send device sync.", ex);
            }
        }

        /// <summary>
        /// Sends a raw JSON payload string to the specified API endpoint.
        /// Used for resending queued offline payloads.
        /// </summary>
        /// <param name="endpoint">The API endpoint to send to.</param>
        /// <param name="rawPayload">The raw JSON payload string.</param>
        /// <returns>True if the payload was sent successfully.</returns>
        /// <summary>
        /// Sends a raw JSON payload string to the specified API endpoint.
        /// Used for resending queued offline payloads.
        /// </summary>
        /// <param name="endpoint">The API endpoint to send to.</param>
        /// <param name="rawPayload">The raw JSON payload string.</param>
        /// <returns>True if the payload was sent successfully.</returns>
        private async Task<bool> SendRawPayloadAsync(string endpoint, string rawPayload)
        {
            try
            {
                string baseUrl = _agentSettings.ApiBaseUrl.TrimEnd('/');
                string fullUrl = baseUrl + endpoint;

                using (var request = new HttpRequestMessage(HttpMethod.Post, fullUrl)
                {
                    Content = new StringContent(rawPayload, System.Text.Encoding.UTF8, "application/json")
                })
                {
                    if (!string.IsNullOrWhiteSpace(_agentSettings.ApiKey))
                    {
                        request.Headers.Authorization =
                            new System.Net.Http.Headers.AuthenticationHeaderValue("Bearer", _agentSettings.ApiKey);
                    }
                    request.Headers.Accept.Add(
                        new System.Net.Http.Headers.MediaTypeWithQualityHeaderValue("application/json"));

                    HttpResponseMessage response = await _httpClient.SendAsync(request);
                    return response.IsSuccessStatusCode;
                }
            }
            catch (Exception ex)
            {
                _logger.LogWarning($"SendRawPayloadAsync failed for {endpoint}: {ex.GetType().Name} - {ex.Message}");
                return false;
            }
        }
    }
}
