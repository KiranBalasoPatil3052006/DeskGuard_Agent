using DeskGuardAgent.Configuration;
using DeskGuardAgent.Interfaces;
using DeskGuardAgent.Services;

namespace DeskGuardAgent
{
    public class Worker : BackgroundService
    {
        private readonly ILogger<Worker> _logger;
        private readonly IMonitoringService _monitoringService;
        private readonly SchedulerService _schedulerService;
        private readonly DeviceEventWatcher _deviceEventWatcher;
        private readonly MonitoringSettings _monitoringSettings;
        private readonly IApiSenderService _apiSender;

        public Worker(
            ILogger<Worker> logger,
            IMonitoringService monitoringService,
            SchedulerService schedulerService,
            DeviceEventWatcher deviceEventWatcher,
            MonitoringSettings monitoringSettings,
            IApiSenderService apiSender)
        {
            _logger = logger;
            _monitoringService = monitoringService;
            _schedulerService = schedulerService;
            _deviceEventWatcher = deviceEventWatcher;
            _monitoringSettings = monitoringSettings;
            _apiSender = apiSender;
        }

        protected override async Task ExecuteAsync(CancellationToken stoppingToken)
        {
            _logger.LogInformation("DeskGuard Agent Worker started.");

            try
            {
                await _monitoringService.StartAsync(stoppingToken);

                if (_monitoringSettings.EnableDeviceEventWatcher)
                {
                    _deviceEventWatcher.Start();
                }

                _schedulerService.Start(stoppingToken);

                await Task.Delay(Timeout.Infinite, stoppingToken);
            }
            catch (OperationCanceledException)
            {
                _logger.LogInformation("DeskGuard Agent worker cancellation requested.");
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "DeskGuard Agent worker encountered an unexpected error.");
            }
            finally
            {
                await StopAsync(stoppingToken);
            }
        }

        public override async Task StopAsync(CancellationToken cancellationToken)
        {
            _logger.LogInformation("DeskGuard Agent Worker stopping...");

            _deviceEventWatcher.Stop();
            _schedulerService.Stop();
            await _monitoringService.StopAsync();

            // Notify backend that agent is shutting down gracefully
            try
            {
                await _apiSender.SendShutdownNotificationAsync();
            }
            catch (Exception ex)
            {
                _logger.LogWarning(ex, "Failed to send shutdown notification (non-fatal)");
            }

            _logger.LogInformation("DeskGuard Agent Worker stopped.");

            await base.StopAsync(cancellationToken);
        }
    }
}
