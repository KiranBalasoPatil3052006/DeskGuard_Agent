using Serilog;
using System;

namespace DeskGuardAgent.Services
{
    public enum LifecycleEvent
    {
        Install,
        ServiceStart,
        ServiceStop,
        Register,
        Heartbeat,
        Collection,
        UploadSuccess,
        UploadFailure,
        RetryAttempt,
        HardwareInventory,
        SoftwareInventory,
        DeviceEvent,
        UnhandledException
    }

    public static class LifecycleLogger
    {
        public static void Log(LifecycleEvent evt, string? message = null, Exception? ex = null)
        {
            var level = evt switch
            {
                LifecycleEvent.UnhandledException => Serilog.Events.LogEventLevel.Fatal,
                LifecycleEvent.UploadFailure => Serilog.Events.LogEventLevel.Error,
                LifecycleEvent.RetryAttempt => Serilog.Events.LogEventLevel.Warning,
                _ => Serilog.Events.LogEventLevel.Information
            };

            if (ex != null)
                Serilog.Log.Write(level, ex, "Lifecycle {Event}: {Message}", evt, message ?? string.Empty);
            else
                Serilog.Log.Write(level, "Lifecycle {Event}: {Message}", evt, message ?? string.Empty);
        }
    }
}