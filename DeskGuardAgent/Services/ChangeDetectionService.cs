/// <summary>
/// Orchestrates the change detection workflow by coordinating between
/// BaselineManager and ApiSenderService.
///
/// Flow:
/// 1. Collector gathers current state data
/// 2. BaselineManager compares current data against stored baseline
/// 3. This service collects the detected changes
/// 4. Changes are sent to the backend via ApiSenderService
/// 5. Baselines are updated to reflect the new state
///
/// The machine_uid is obtained from AgentSettings to ensure the backend
/// can correctly associate changes with the originating machine.
/// </summary>
using DeskGuardAgent.Configuration;
using DeskGuardAgent.Interfaces;
using DeskGuardAgent.Models;

namespace DeskGuardAgent.Services
{
    public class ChangeDetectionService
    {
        private readonly ILoggerService _logger;
        private readonly IApiSenderService _apiSender;
        private readonly BaselineManager _baselineManager;
        private readonly AgentSettings _agentSettings;

        public ChangeDetectionService(
            ILoggerService logger,
            IApiSenderService apiSender,
            BaselineManager baselineManager,
            AgentSettings agentSettings)
        {
            _logger = logger;
            _apiSender = apiSender;
            _baselineManager = baselineManager;
            _agentSettings = agentSettings;
        }

        /// <summary>
        /// Detects hardware changes by comparing current inventory against baseline.
        /// Sends any detected changes to the backend and updates the baseline.
        /// </summary>
        public async Task ProcessHardwareChanges(HardwareInventory inventory)
        {
            var changes = _baselineManager.DetectHardwareChanges(inventory);
            if (changes.Count > 0)
            {
                _logger.LogInformation($"Detected {changes.Count} hardware changes");
                await SendChangesToBackend(changes);
                _baselineManager.InitializeHardwareBaseline(inventory);
            }
        }

        /// <summary>
        /// Detects software changes (added, removed, updated) by comparing
        /// current software list against the software baseline.
        /// </summary>
        public async Task ProcessSoftwareChanges(List<SoftwareInventory> softwareList)
        {
            var changes = _baselineManager.DetectSoftwareChanges(softwareList);
            if (changes.Count > 0)
            {
                _logger.LogInformation($"Detected {changes.Count} software changes");
                await SendChangesToBackend(changes);
                _baselineManager.ApplySoftwareChanges(changes);
            }
        }

        /// <summary>
        /// Detects peripheral changes (devices connected or disconnected)
        /// by comparing current peripherals against the peripheral baseline.
        /// </summary>
        public async Task ProcessPeripheralChanges(List<PeripheralInfo> peripherals)
        {
            var changes = _baselineManager.DetectPeripheralChanges(peripherals);
            if (changes.Count > 0)
            {
                _logger.LogInformation($"Detected {changes.Count} peripheral changes");
                await SendChangesToBackend(changes);
                _baselineManager.ApplyPeripheralChanges(changes);
            }
        }

        /// <summary>
        /// Detects security changes (antivirus/firewall status changes)
        /// by comparing current security posture against the security baseline.
        /// </summary>
        public async Task ProcessSecurityChanges(AntivirusInfo? antivirus, FirewallInfo? firewall)
        {
            var changes = _baselineManager.DetectSecurityChanges(antivirus, firewall);
            if (changes.Count > 0)
            {
                _logger.LogInformation($"Detected {changes.Count} security changes");
                await SendChangesToBackend(changes);
                _baselineManager.ApplySecurityChanges(changes);
            }
        }

        /// <summary>
        /// Detects network changes (adapter config changes) by comparing
        /// current network state against the network baseline.
        /// </summary>
        public async Task ProcessNetworkChanges(List<NetworkInfo> networkAdapters)
        {
            var changes = _baselineManager.DetectNetworkChanges(networkAdapters);
            if (changes.Count > 0)
            {
                _logger.LogInformation($"Detected {changes.Count} network changes");
                await SendChangesToBackend(changes);
                _baselineManager.ApplyNetworkChanges(changes);
            }
        }

        /// <summary>
        /// Detects configuration changes (service status, startup programs)
        /// by comparing current state against the configuration baseline.
        /// </summary>
        public async Task ProcessConfigurationChanges(List<ServiceInfo>? services, List<ProcessInfo>? startupPrograms)
        {
            var changes = _baselineManager.DetectConfigurationChanges(services, startupPrograms);
            if (changes.Count > 0)
            {
                _logger.LogInformation($"Detected {changes.Count} configuration changes");
                await SendChangesToBackend(changes);
                _baselineManager.ApplyConfigurationChanges(changes);
            }
        }

        /// <summary>
        /// Sends the detected changes to the backend API.
        /// The machine_uid is obtained from AgentSettings so the backend
        /// can correctly associate changes with the originating machine.
        /// </summary>
        private async Task SendChangesToBackend(List<ChangeEvent> changes)
        {
            try
            {
                var payload = new
                {
                    machine_uid = _agentSettings.AgentId,
                    changes = changes.Select(c => new
                    {
                        category = c.Category,
                        change_type = c.ChangeType,
                        severity = c.Severity,
                        item_identifier = c.ItemIdentifier,
                        item_label = c.ItemLabel,
                        previous_value = c.PreviousValue,
                        new_value = c.NewValue,
                        description = c.Description,
                        detected_at = c.DetectedAt.ToString("O"),
                    }).ToList()
                };

                await _apiSender.SendChangeDetectionAsync(payload);
            }
            catch (Exception ex)
            {
                _logger.LogError("Failed to send change events to backend", ex);
            }
        }
    }
}
