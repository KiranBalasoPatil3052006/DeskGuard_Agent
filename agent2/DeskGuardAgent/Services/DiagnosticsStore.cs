using Serilog;
using System;
using System.IO;
using System.Text.Json;
using System.Threading;

namespace DeskGuardAgent.Services
{
    public sealed class DiagnosticsStore
    {
        private static readonly Lazy<DiagnosticsStore> _instance = new(() => new DiagnosticsStore());
        public static DiagnosticsStore Instance => _instance.Value;

        private readonly string _filePath;
        private readonly ReaderWriterLockSlim _lock = new();
        private DiagnosticsData _data;

        private DiagnosticsStore()
        {
            var dir = Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.CommonApplicationData), "DeskGuardAgent", "Diagnostics");
            Directory.CreateDirectory(dir);
            _filePath = Path.Combine(dir, "diagnostics.json");
            _data = new DiagnosticsData();
            Load();
        }

        public void Initialize(string agentVersion, string machineId)
        {
            _lock.EnterWriteLock();
            try
            {
                _data.AgentVersion = agentVersion;
                _data.MachineId = machineId;
                _data.InstallTimeUtc = DateTime.UtcNow;
                _data.ServiceStatus = "Initializing";
                Save();
            }
            finally { _lock.ExitWriteLock(); }
        }

        public void SetServiceStatus(string status)
        {
            _lock.EnterWriteLock();
            try
            {
                _data.ServiceStatus = status;
                Save();
            }
            finally { _lock.ExitWriteLock(); }
        }

        public void RecordHeartbeat()
        {
            _lock.EnterWriteLock();
            try
            {
                _data.LastHeartbeatUtc = DateTime.UtcNow;
                Save();
            }
            finally { _lock.ExitWriteLock(); }
        }

        public void RecordUpload(bool success, string error = null)
        {
            _lock.EnterWriteLock();
            try
            {
                _data.LastUploadUtc = DateTime.UtcNow;
                if (!success) _data.LastError = error;
                Save();
            }
            finally { _lock.ExitWriteLock(); }
        }

        public void RecordCollectorHealth(string collectorName, bool success, string error = null)
        {
            _lock.EnterWriteLock();
            try
            {
                _data.CollectorHealth[collectorName] = new CollectorHealth
                {
                    LastRunUtc = DateTime.UtcNow,
                    Success = success,
                    LastError = error
                };
                Save();
            }
            finally { _lock.ExitWriteLock(); }
        }

        public void RecordUnhandledException(Exception ex)
        {
            _lock.EnterWriteLock();
            try
            {
                _data.LastError = ex.ToString();
                _data.LastErrorUtc = DateTime.UtcNow;
                Save();
            }
            finally { _lock.ExitWriteLock(); }
        }

        private void Load()
        {
            if (!File.Exists(_filePath)) return;
            try
            {
                var json = File.ReadAllText(_filePath);
                var loaded = JsonSerializer.Deserialize<DiagnosticsData>(json);
                if (loaded != null) _data = loaded;
            }
            catch { /* ignore corrupt file */ }
        }

        private void Save()
        {
            try
            {
                var json = JsonSerializer.Serialize(_data, new JsonSerializerOptions { WriteIndented = true });
                var tmp = _filePath + ".tmp";
                File.WriteAllText(tmp, json);
                File.Move(tmp, _filePath, true);
            }
            catch (Exception ex)
            {
                Log.Error(ex, "Failed to save diagnostics file");
            }
        }

        private class DiagnosticsData
        {
            public string? AgentVersion { get; set; }
            public string? MachineId { get; set; }
            public DateTime InstallTimeUtc { get; set; }
            public DateTime? LastHeartbeatUtc { get; set; }
            public DateTime? LastUploadUtc { get; set; }
            public string? LastError { get; set; }
            public DateTime? LastErrorUtc { get; set; }
            public string? ServiceStatus { get; set; }
            public System.Collections.Generic.Dictionary<string, CollectorHealth> CollectorHealth { get; set; } = new();
        }

        private class CollectorHealth
        {
            public DateTime LastRunUtc { get; set; }
            public bool Success { get; set; }
            public string? LastError { get; set; }
        }
    }
}