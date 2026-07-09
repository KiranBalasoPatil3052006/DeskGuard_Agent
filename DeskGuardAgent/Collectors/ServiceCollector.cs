/// <summary>
/// Collects the status of Windows services on the local machine.
/// Enumerates all installed services and reports their current status,
/// startup type, and whether they are running as expected.
/// Used to monitor critical infrastructure services.
/// </summary>
using System.ServiceProcess;
using DeskGuardAgent.Interfaces;
using DeskGuardAgent.Models;

namespace DeskGuardAgent.Collectors
{
    /// <summary>
    /// Collector responsible for retrieving Windows service status information.
    /// Enumerates all services and captures their name, status, and startup configuration.
    /// Implements ICollector&lt;List&lt;ServiceInfo&gt;&gt; for standardized collection.
    /// </summary>
    public class ServiceCollector : ICollector<List<ServiceInfo>>
    {
        private readonly ILoggerService _logger;

        /// <summary>
        /// Initializes a new instance of the ServiceCollector class.
        /// </summary>
        /// <param name="logger">Service for logging collector operations and errors.</param>
        public ServiceCollector(ILoggerService logger)
        {
            _logger = logger;
        }

        /// <summary>
        /// Executes Windows service status collection.
        /// Enumerates all services and captures their status and startup type.
        /// Never throws - all exceptions are caught and logged.
        /// </summary>
        /// <returns>A list of ServiceInfo objects for all installed services.</returns>
        public async Task<List<ServiceInfo>> CollectAsync()
        {
            _logger.LogDebug("Starting Windows service collection.");

            var serviceList = new List<ServiceInfo>();

            try
            {
                // Use Task.Run to avoid blocking the main thread during service enumeration.
                await Task.Run(() =>
                {
                    // Get all Windows services installed on the system.
                    ServiceController[] services = ServiceController.GetServices();

                    foreach (ServiceController service in services)
                    {
                        try
                        {
                            var serviceInfo = new ServiceInfo
                            {
                                ServiceName = service.ServiceName,
                                DisplayName = service.DisplayName,
                                Status = service.Status.ToString(),
                                IsRunning = service.Status == ServiceControllerStatus.Running,
                                CollectedAt = DateTime.UtcNow
                            };

                            // Determine the startup type using additional query.
                            // ServiceController does not directly expose startup type,
                            // so we use a WMI fallback for that information.
                            serviceInfo.StartType = GetServiceStartType(service.ServiceName);

                            serviceList.Add(serviceInfo);
                        }
                        catch (Exception ex)
                        {
                            _logger.LogWarning($"Failed to collect info for service {service.ServiceName}.", ex);
                        }
                    }
                });

                _logger.LogDebug($"Service collection complete. Found {serviceList.Count} services.");
            }
            catch (Exception ex)
            {
                _logger.LogError("Failed to collect Windows service information.", ex);
            }

            return serviceList;
        }

        /// <summary>
        /// Retrieves the startup type of a Windows service using WMI.
        /// Startup type values: "Automatic", "Manual", "Disabled", "Automatic (Delayed Start)".
        /// </summary>
        /// <param name="serviceName">The name of the service to query.</param>
        /// <returns>The startup type string, or "Unknown" if it cannot be determined.</returns>
        private static string GetServiceStartType(string serviceName)
        {
            try
            {
                using (var searcher = new System.Management.ManagementObjectSearcher(
                    $"SELECT StartMode FROM Win32_Service WHERE Name = '{serviceName.Replace("'", "''")}'"))
                {
                    using (var results = searcher.Get())
                    {
                        foreach (System.Management.ManagementObject obj in results)
                        {
                            string? startMode = obj["StartMode"]?.ToString();
                            return startMode switch
                            {
                                "Auto" => "Automatic",
                                "Manual" => "Manual",
                                "Disabled" => "Disabled",
                                "Boot" => "Automatic (Boot)",
                                "System" => "Automatic (System)",
                                _ => startMode ?? "Unknown"
                            };
                        }
                    }
                }
            }
            catch
            {
                // Silently fail - startup type is not critical.
            }

            return "Unknown";
        }
    }
}
