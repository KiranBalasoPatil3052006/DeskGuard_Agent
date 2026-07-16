/// <summary>
/// Manages the scheduling of periodic tasks for the agent.
/// Handles timing logic for regular collection cycles and long-interval inventory collection.
/// Uses a simple timer-based approach to manage collection intervals.
/// </summary>
using DeskGuardAgent.Collectors;
using DeskGuardAgent.Configuration;
using DeskGuardAgent.Interfaces;

namespace DeskGuardAgent.Services
{
    /// <summary>
    /// Service responsible for scheduling and triggering collection cycles at configured intervals.
    /// Manages both the regular health check cycle and the less frequent inventory collection.
    /// </summary>
    public class SchedulerService
    {
        private readonly MonitoringSettings _monitoringSettings;
        private readonly ILoggerService _logger;
        private readonly IMonitoringService _monitoringService;
        private readonly PeripheralCollector _peripheralCollector;

        /// <summary>
        /// Timer for regular health metric collection cycles.
        /// Inventory timing is handled internally by MonitoringService.
        /// </summary>
        private Timer? _collectionTimer;

        /// <summary>
        /// Timer for periodic device scan (runs every 30 minutes by default).
        /// </summary>
        private Timer? _deviceScanTimer;

        /// <summary>
        /// Indicates whether the scheduler is currently running.
        /// </summary>
        private bool _isRunning;

        /// <summary>
        /// CancellationTokenSource for stopping scheduled operations gracefully.
        /// </summary>
        private CancellationTokenSource? _cancellationTokenSource;

        /// <summary>
        /// Initializes a new instance of the SchedulerService class.
        /// </summary>
        /// <param name="monitoringSettings">Monitoring settings containing interval configuration.</param>
        /// <param name="logger">Service for logging scheduler operations.</param>
        /// <param name="monitoringService">The monitoring service to invoke on each cycle.</param>
        public SchedulerService(
            MonitoringSettings monitoringSettings,
            ILoggerService logger,
            IMonitoringService monitoringService,
            PeripheralCollector peripheralCollector)
        {
            _monitoringSettings = monitoringSettings;
            _logger = logger;
            _monitoringService = monitoringService;
            _peripheralCollector = peripheralCollector;
        }

        /// <summary>
        /// Starts the scheduler, beginning periodic collection cycles.
        /// Creates timers for health, hardware inventory, and software inventory collection.
        /// </summary>
        /// <param name="cancellationToken">Cancellation token for graceful shutdown.</param>
        public void Start(CancellationToken cancellationToken)
        {
            if (_isRunning)
            {
                _logger.LogWarning("Scheduler is already running.");
                return;
            }

            _isRunning = true;
            _cancellationTokenSource = CancellationTokenSource.CreateLinkedTokenSource(cancellationToken);

            _logger.LogInformation("Scheduler starting with collection interval of " +
                $"{_monitoringSettings.CollectionIntervalSeconds} seconds.");

            // Register the cancellation callback to stop timers.
            cancellationToken.Register(Stop);

            // Start the main collection timer.
            // Initial collection happens immediately, then repeats at the configured interval.
            // Inventory collection timing is handled internally by MonitoringService
            // through ShouldCollectHardwareInventory/ShouldCollectSoftwareInventory checks.
            _collectionTimer = new Timer(
                async _ => await ExecuteCollectionCycleAsync(),
                null,
                TimeSpan.Zero, // Start immediately on first run.
                TimeSpan.FromSeconds(_monitoringSettings.CollectionIntervalSeconds));

            // Start the periodic device scan timer if peripheral monitoring is enabled.
            if (_monitoringSettings.EnablePeripheralMonitoring)
            {
                // Full device scan runs every N minutes (default 30).
                // Initial run is delayed by 2 minutes to allow the agent to stabilize.
                _deviceScanTimer = new Timer(
                    async _ => await ExecuteDeviceScanAsync(),
                    null,
                    TimeSpan.FromMinutes(2),
                    TimeSpan.FromMinutes(_monitoringSettings.DeviceScanIntervalMinutes));
            }

            _logger.LogInformation("Scheduler started successfully.");
        }

        /// <summary>
        /// Stops the scheduler and disposes all timers.
        /// Waits for any in-progress collection to complete gracefully.
        /// </summary>
        public void Stop()
        {
            if (!_isRunning)
                return;

            _logger.LogInformation("Scheduler stopping...");

            _isRunning = false;

            // Cancel any in-progress operations.
            _cancellationTokenSource?.Cancel();

            // Dispose and nullify all timers.
            _collectionTimer?.Dispose();
            _collectionTimer = null;

            _deviceScanTimer?.Dispose();
            _deviceScanTimer = null;

            _cancellationTokenSource?.Dispose();
            _cancellationTokenSource = null;

            _logger.LogInformation("Scheduler stopped.");
        }

        /// <summary>
        /// Executes a single collection cycle via the monitoring service.
        /// Called by the collection timer at each interval.
        /// </summary>
        private async Task ExecuteCollectionCycleAsync()
        {
            try
            {
                _logger.LogDebug("Collection cycle triggered by scheduler.");

                // Execute the full collection cycle.
                await _monitoringService.ExecuteCollectionCycleAsync();

                _logger.LogDebug("Collection cycle completed.");
            }
            catch (Exception ex)
            {
                // Catch any unexpected exceptions to prevent the timer from crashing.
                _logger.LogError("Unhandled exception during collection cycle.", ex);
            }
        }

        /// <summary>
        /// Executes a periodic full device scan using PeripheralCollector.
        /// </summary>
        private async Task ExecuteDeviceScanAsync()
        {
            try
            {
                _logger.LogDebug("Periodic device scan triggered by scheduler.");

                var devices = await _peripheralCollector.CollectAsync();

                if (devices.Count > 0)
                {
                    await _monitoringService.SendDeviceSyncAsync(devices);
                }

                _logger.LogDebug($"Device scan completed. Found {devices.Count} devices.");
            }
            catch (Exception ex)
            {
                _logger.LogError("Unhandled exception during device scan.", ex);
            }
        }


    }
}
