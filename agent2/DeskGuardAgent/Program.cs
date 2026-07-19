/// <summary>
/// Application entry point for the DeskGuard Agent.
/// Configures the host builder, dependency injection, Serilog logging,
/// and Windows Service integration.
/// 
/// The agent can run as a console application (for debugging) or as a Windows Service.
/// </summary>
using DeskGuardAgent.Collectors;
using DeskGuardAgent.Configuration;
using DeskGuardAgent.Interfaces;
using DeskGuardAgent.Services;
using DeskGuardAgent.Utilities;
using Serilog;
using System;
using System.IO;
using System.Reflection;

namespace DeskGuardAgent
{
    /// <summary>
    /// Main application class that configures and starts the DeskGuard Agent.
    /// </summary>
    public class Program
    {
        /// <summary>
        /// Application entry point.
        /// Configures Serilog, builds the host, and runs the agent.
        /// </summary>
        /// <param name="args">Command-line arguments.</param>
        public static void Main(string[] args)
        {
            // Early Serilog bootstrap from configuration (appsettings.json)
            var configuration = new ConfigurationBuilder()
                .SetBasePath(AppDomain.CurrentDomain.BaseDirectory)
                .AddJsonFile("appsettings.json", optional: false, reloadOnChange: true)
                .Build();

            Log.Logger = new LoggerConfiguration()
                .ReadFrom.Configuration(configuration)
                .Enrich.FromLogContext()
                .Enrich.WithThreadId()
                .CreateBootstrapLogger();

            try
            {
                Log.Information("DeskGuard Agent starting up.");

                // Initialize diagnostics store
                var agentVersion = Assembly.GetExecutingAssembly().GetName().Version?.ToString() ?? "unknown";
                var machineId = MachineIdentifier.GenerateMachineId();
                DiagnosticsStore.Instance.Initialize(agentVersion, machineId);
                DiagnosticsStore.Instance.SetServiceStatus("Starting");

                // Configure unhandled exception handlers
                AppDomain.CurrentDomain.UnhandledException += (sender, e) =>
                {
                    if (e.ExceptionObject is Exception ex)
                    {
                        Log.Fatal(ex, "Unhandled AppDomain exception");
                        DiagnosticsStore.Instance.RecordUnhandledException(ex);
                    }
                };
                TaskScheduler.UnobservedTaskException += (sender, e) =>
                {
                    Log.Fatal(e.Exception, "Unobserved task exception");
                    DiagnosticsStore.Instance.RecordUnhandledException(e.Exception);
                    e.SetObserved();
                };

                // Create the host builder
                var builder = Host.CreateApplicationBuilder(args);
                builder.Configuration.AddConfiguration(configuration);

                // Register configuration sections
                builder.Services.Configure<AgentSettings>(builder.Configuration.GetSection("AgentSettings"));
                builder.Services.Configure<MonitoringSettings>(builder.Configuration.GetSection("MonitoringSettings"));

                // Bind settings for early use
                var agentSettings = new AgentSettings();
                builder.Configuration.GetSection("AgentSettings").Bind(agentSettings);
                var monitoringSettings = new MonitoringSettings();
                builder.Configuration.GetSection("MonitoringSettings").Bind(monitoringSettings);

                // Generate AgentId if missing
                if (string.IsNullOrWhiteSpace(agentSettings.AgentId))
                    agentSettings.AgentId = MachineIdentifier.GenerateMachineId();

                // Register settings as singletons
                builder.Services.AddSingleton(agentSettings);
                builder.Services.AddSingleton(monitoringSettings);
                builder.Services.AddSingleton(DiagnosticsStore.Instance);

                // Register services (same as before)
                builder.Services.AddHttpClient<IApiSenderService, ApiSenderService>(client =>
                {
                    client.BaseAddress = new Uri(agentSettings.ApiBaseUrl.TrimEnd('/') + "/");
                    client.Timeout = TimeSpan.FromSeconds(agentSettings.RequestTimeoutSeconds);
                });
                builder.Services.AddHttpClient<MonitoringService>(client =>
                {
                    client.BaseAddress = new Uri(agentSettings.ApiBaseUrl.TrimEnd('/') + "/");
                    client.Timeout = TimeSpan.FromSeconds(agentSettings.RequestTimeoutSeconds);
                });

                builder.Services.AddSingleton<ILoggerService>(sp =>
                {
                    var logger = sp.GetRequiredService<ILogger<Program>>();
                    return new LoggerService(logger);
                });
                builder.Services.AddSingleton<RetryService>();
                builder.Services.AddSingleton<IOfflineQueueService, OfflineQueueService>();
                builder.Services.AddSingleton<BaselineManager>();
                builder.Services.AddSingleton<ChangeDetectionService>();

                // Collectors
                builder.Services.AddTransient<CpuCollector>();
                builder.Services.AddTransient<MemoryCollector>();
                builder.Services.AddTransient<DiskCollector>();
                builder.Services.AddTransient<BatteryCollector>();
                builder.Services.AddTransient<NetworkCollector>();
                builder.Services.AddTransient<ProcessCollector>();
                builder.Services.AddTransient<SystemInfoCollector>();
                builder.Services.AddTransient<HardwareInventoryCollector>();
                builder.Services.AddTransient<SoftwareInventoryCollector>();
                builder.Services.AddTransient<ServiceCollector>();
                builder.Services.AddTransient<SecurityCollector>();
                builder.Services.AddTransient<UpdateCollector>();
                builder.Services.AddTransient<EventLogCollector>();
                builder.Services.AddTransient<FirewallCollector>();
                builder.Services.AddTransient<StartupProgramCollector>();
                builder.Services.AddTransient<LoginActivityCollector>();
                builder.Services.AddTransient<UsbCollector>();
                builder.Services.AddSingleton<Collectors.PeripheralCollector>();

                builder.Services.AddSingleton<IMonitoringService, MonitoringService>();
                builder.Services.AddSingleton<SchedulerService>();
                builder.Services.AddSingleton<DeviceEventWatcher>();

                builder.Services.AddHostedService<Worker>();

                builder.Services.AddWindowsService(options =>
                {
                    options.ServiceName = Constants.AgentConstants.ServiceName;
                });

                builder.Logging.ClearProviders();
                builder.Logging.AddSerilog(dispose: true);

                var host = builder.Build();

                // Log lifecycle start
                LifecycleLogger.Log(LifecycleEvent.ServiceStart, $"Agent version {agentVersion} on machine {machineId}");
                DiagnosticsStore.Instance.SetServiceStatus("Running");

                host.Run();

                // Service stop lifecycle
                LifecycleLogger.Log(LifecycleEvent.ServiceStop, "Service stopped gracefully");
                DiagnosticsStore.Instance.SetServiceStatus("Stopped");
            }
            catch (Exception ex)
            {
                Log.Fatal(ex, "DeskGuard Agent failed to start.");
                DiagnosticsStore.Instance.RecordUnhandledException(ex);
                throw;
            }
            finally
            {
                Log.CloseAndFlush();
            }
        }
    }

    /// <summary>
    /// Implementation of ILoggerService wrapping Microsoft.Extensions.Logging.ILogger.
    /// Provides structured logging with configurable log levels.
    /// </summary>
    public class LoggerService : ILoggerService
    {
        private readonly Microsoft.Extensions.Logging.ILogger _logger;

        /// <summary>
        /// Initializes a new instance of the LoggerService class.
        /// </summary>
        /// <param name="logger">The underlying Microsoft ILogger instance.</param>
        public LoggerService(Microsoft.Extensions.Logging.ILogger logger)
        {
            _logger = logger;
        }

        /// <summary>
        /// Logs an informational message.
        /// </summary>
        /// <param name="message">The message to log.</param>
        public void LogInformation(string message)
        {
            _logger.LogInformation("{Message}", message);
        }

        /// <summary>
        /// Logs a warning message.
        /// </summary>
        /// <param name="message">The warning message.</param>
        public void LogWarning(string message)
        {
            _logger.LogWarning("{Message}", message);
        }

        /// <summary>
        /// Logs a warning message with an associated exception.
        /// </summary>
        /// <param name="message">The warning message.</param>
        /// <param name="exception">The associated exception, if any.</param>
        public void LogWarning(string message, Exception? exception = null)
        {
            if (exception != null)
                _logger.LogWarning(exception, "{Message}", message);
            else
                _logger.LogWarning("{Message}", message);
        }

        /// <summary>
        /// Logs an error message with an optional exception.
        /// </summary>
        /// <param name="message">The error message.</param>
        /// <param name="exception">The associated exception, if any.</param>
        public void LogError(string message, Exception? exception = null)
        {
            if (exception != null)
                _logger.LogError(exception, "{Message}", message);
            else
                _logger.LogError("{Message}", message);
        }

        /// <summary>
        /// Logs a debug message.
        /// </summary>
        /// <param name="message">The debug message.</param>
        public void LogDebug(string message)
        {
            _logger.LogDebug("{Message}", message);
        }
    }
}
