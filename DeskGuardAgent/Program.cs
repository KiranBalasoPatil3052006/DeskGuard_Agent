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
            try
            {
                // Create the host builder first to access configuration.
                var builder = Host.CreateApplicationBuilder(args);

                // Bind AgentSettings early so we can use LogPath for Serilog configuration.
                AgentSettings agentSettings = new AgentSettings();
                builder.Configuration.GetSection("AgentSettings").Bind(agentSettings);

                // Determine the log directory from configuration or default to "Logs".
                string logPath = Path.Combine(
                    AppDomain.CurrentDomain.BaseDirectory,
                    string.IsNullOrWhiteSpace(agentSettings.LogPath) ? "Logs" : agentSettings.LogPath);

                // Configure Serilog as the primary logging provider.
                // Logs are written to both console (for debugging) and file (for persistence).
                Log.Logger = new LoggerConfiguration()
                    .MinimumLevel.Information()
                    .MinimumLevel.Override("Microsoft", Serilog.Events.LogEventLevel.Warning)
                    .MinimumLevel.Override("System", Serilog.Events.LogEventLevel.Warning)
                    .Enrich.FromLogContext()
                    .WriteTo.Console()
                    .WriteTo.File(
                        path: Path.Combine(logPath, "deskguard-.log"),
                        rollingInterval: RollingInterval.Day,
                        retainedFileCountLimit: 30,
                        outputTemplate: "{Timestamp:yyyy-MM-dd HH:mm:ss.fff zzz} [{Level:u3}] {Message:lj}{NewLine}{Exception}")
                    .CreateLogger();

                // Log application startup.
                Log.Information("DeskGuard Agent starting up.");

                // Configure Serilog as the logging provider for the host.
                builder.Logging.ClearProviders();
                builder.Logging.AddSerilog();

                // Configure Windows Service support.
                // When running as a Windows Service, the agent will respond to SCM commands.
                builder.Services.AddWindowsService(options =>
                {
                    options.ServiceName = Constants.AgentConstants.ServiceName;
                });

                // Register configuration sections from appsettings.json.
                builder.Services.Configure<AgentSettings>(
                    builder.Configuration.GetSection("AgentSettings"));
                builder.Services.Configure<MonitoringSettings>(
                    builder.Configuration.GetSection("MonitoringSettings"));

                // Bind MonitoringSettings for direct injection.
                MonitoringSettings monitoringSettings = new MonitoringSettings();
                builder.Configuration.GetSection("MonitoringSettings").Bind(monitoringSettings);

                // Register configuration objects as singletons for DI.
                builder.Services.AddSingleton(agentSettings);
                builder.Services.AddSingleton(monitoringSettings);

                // Generate a machine-specific agent ID if not already configured.
                if (string.IsNullOrWhiteSpace(agentSettings.AgentId))
                {
                    agentSettings.AgentId = MachineIdentifier.GenerateMachineId();
                }

                // Validate and prepare API base URL.
                if (string.IsNullOrWhiteSpace(agentSettings.ApiBaseUrl))
                {
                    Log.Warning("ApiBaseUrl is not configured in appsettings.json. " +
                        "The agent will start in offline/test mode. " +
                        "Collectors will run and log data, but API calls will fail gracefully.");
                    agentSettings.ApiBaseUrl = "http://localhost/";
                }

                // Register the HttpClient for API communication.
                builder.Services.AddHttpClient<IApiSenderService, ApiSenderService>(client =>
                {
                    client.BaseAddress = new Uri(agentSettings.ApiBaseUrl.TrimEnd('/') + "/");
                    client.Timeout = TimeSpan.FromSeconds(agentSettings.RequestTimeoutSeconds);
                });

                // Register HttpClient for MonitoringService (used for offline queue flushing).
                builder.Services.AddHttpClient<MonitoringService>(client =>
                {
                    client.BaseAddress = new Uri(agentSettings.ApiBaseUrl.TrimEnd('/') + "/");
                    client.Timeout = TimeSpan.FromSeconds(agentSettings.RequestTimeoutSeconds);
                });

                // Register logging service.
                builder.Services.AddSingleton<ILoggerService>(sp =>
                {
                    ILogger<Program> logger = sp.GetRequiredService<ILogger<Program>>();
                    return new LoggerService(logger);
                });

                // Register RetryService.
                builder.Services.AddSingleton<RetryService>();

                // Register OfflineQueueService.
                builder.Services.AddSingleton<IOfflineQueueService, OfflineQueueService>();

                // Register Change Detection Services.
                builder.Services.AddSingleton<BaselineManager>();
                builder.Services.AddSingleton<ChangeDetectionService>();

                // Register all collectors.
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

                // Register MonitoringService.
                builder.Services.AddSingleton<IMonitoringService, MonitoringService>();

                // Register SchedulerService.
                builder.Services.AddSingleton<SchedulerService>();

                // Register DeviceEventWatcher (real-time device monitoring).
                builder.Services.AddSingleton<DeviceEventWatcher>();

                // Register the Worker as a hosted service.
                builder.Services.AddHostedService<Worker>();

                // Build and run the host.
                var host = builder.Build();
                host.Run();
            }
            catch (Exception ex)
            {
                // Log any fatal startup exceptions.
                Log.Fatal(ex, "DeskGuard Agent failed to start.");
                throw;
            }
            finally
            {
                // Ensure Serilog flushes all logs before exit.
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
