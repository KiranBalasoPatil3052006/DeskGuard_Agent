using System.Management;
using DeskGuardAgent.Configuration;
using DeskGuardAgent.Interfaces;
using DeskGuardAgent.Models;

namespace DeskGuardAgent.Services
{
    public class DeviceEventWatcher : IDisposable
    {
        private readonly ILoggerService _logger;
        private readonly IApiSenderService _apiSender;
        private readonly AgentSettings _agentSettings;

        private ManagementEventWatcher? _insertWatcher;
        private ManagementEventWatcher? _removeWatcher;
        private bool _isRunning;
        private List<PeripheralInfo> _lastPeripheralSnapshot = new();

        public event Action<DeviceEventInfo>? OnDeviceEvent;

        public DeviceEventWatcher(ILoggerService logger, IApiSenderService apiSender, AgentSettings agentSettings)
        {
            _logger = logger;
            _apiSender = apiSender;
            _agentSettings = agentSettings;
        }

        public void Start()
        {
            if (_isRunning) return;

            _isRunning = true;

            try
            {
                var insertQuery = new WqlEventQuery(
                    "SELECT * FROM Win32_DeviceChangeEvent WHERE EventType = 1");

                _insertWatcher = new ManagementEventWatcher(insertQuery);
                _insertWatcher.EventArrived += OnDeviceConnected;
                _insertWatcher.Start();

                var removeQuery = new WqlEventQuery(
                    "SELECT * FROM Win32_DeviceChangeEvent WHERE EventType = 2");

                _removeWatcher = new ManagementEventWatcher(removeQuery);
                _removeWatcher.EventArrived += OnDeviceRemoved;
                _removeWatcher.Start();

                _logger.LogInformation("Device event watcher started (real-time monitoring).");
            }
            catch (Exception ex)
            {
                _logger.LogError("Failed to start device event watcher.", ex);
                _isRunning = false;
            }
        }

        public void Stop()
        {
            if (!_isRunning) return;

            _isRunning = false;

            try
            {
                _insertWatcher?.Stop();
                _insertWatcher?.Dispose();
                _insertWatcher = null;

                _removeWatcher?.Stop();
                _removeWatcher?.Dispose();
                _removeWatcher = null;

                _logger.LogInformation("Device event watcher stopped.");
            }
            catch (Exception ex)
            {
                _logger.LogError("Error stopping device event watcher.", ex);
            }
        }

        private async void OnDeviceConnected(object sender, EventArrivedEventArgs e)
        {
            try
            {
                var deviceEvent = new DeviceEventInfo
                {
                    EventType = "Connected",
                    EventTime = DateTime.UtcNow,
                };

                OnDeviceEvent?.Invoke(deviceEvent);

                await Task.Delay(1000);

                var peripherals = await CollectCurrentPeripheralsAsync();
                var newDevice = FindNewDevice(peripherals);
                if (newDevice != null)
                {
                    deviceEvent.DeviceName = newDevice.DeviceName;
                    deviceEvent.DeviceType = newDevice.DeviceType;
                    deviceEvent.Manufacturer = newDevice.Manufacturer;
                    deviceEvent.ConnectionType = newDevice.ConnectionType;
                    deviceEvent.DeviceId = newDevice.DeviceId;
                    _logger.LogInformation($"Device connected: {newDevice.DeviceName} ({newDevice.DeviceType}, {newDevice.ConnectionType})");
                }
                else if (peripherals.Count > 0)
                {
                    var firstNew = peripherals[^1];
                    deviceEvent.DeviceName = firstNew.DeviceName;
                    deviceEvent.DeviceType = firstNew.DeviceType;
                    deviceEvent.Manufacturer = firstNew.Manufacturer;
                    deviceEvent.ConnectionType = firstNew.ConnectionType;
                    deviceEvent.DeviceId = firstNew.DeviceId;
                    _logger.LogInformation($"Device connected (best guess): {firstNew.DeviceName}");
                }
                else
                {
                    deviceEvent.DeviceName = "Unknown Device";
                    deviceEvent.DeviceType = "Unknown";
                    _logger.LogInformation("Device connected (name could not be determined).");
                }

                _lastPeripheralSnapshot = peripherals;

                if (!_agentSettings.IsOfflineMode)
                {
                    var payload = new
                    {
                        machine_uid = _agentSettings.AgentId,
                        device_name = deviceEvent.DeviceName,
                        device_type = deviceEvent.DeviceType,
                        manufacturer = deviceEvent.Manufacturer,
                        connection_type = deviceEvent.ConnectionType,
                        device_id = deviceEvent.DeviceId,
                        event_type = deviceEvent.EventType,
                        event_time = deviceEvent.EventTime
                    };
                    await _apiSender.SendDeviceEventAsync(payload);
                }
                else
                    _logger.LogDebug($"Offline mode: device event queued locally ({deviceEvent.DeviceName})");
            }
            catch (Exception ex)
            {
                _logger.LogError("Error handling device connected event.", ex);
            }
        }

        private async void OnDeviceRemoved(object sender, EventArrivedEventArgs e)
        {
            try
            {
                _logger.LogDebug("Device removed event detected.");

                var deviceEvent = new DeviceEventInfo
                {
                    DeviceName = "Unknown Device",
                    DeviceType = "Unknown",
                    EventType = "Removed",
                    EventTime = DateTime.UtcNow,
                };

                OnDeviceEvent?.Invoke(deviceEvent);

                if (!_agentSettings.IsOfflineMode)
                {
                    var payload = new
                    {
                        machine_uid = _agentSettings.AgentId,
                        device_name = deviceEvent.DeviceName,
                        device_type = deviceEvent.DeviceType,
                        manufacturer = deviceEvent.Manufacturer,
                        connection_type = deviceEvent.ConnectionType,
                        device_id = deviceEvent.DeviceId,
                        event_type = deviceEvent.EventType,
                        event_time = deviceEvent.EventTime
                    };
                    await _apiSender.SendDeviceEventAsync(payload);
                }
                else
                    _logger.LogDebug("Offline mode: device removal event logged locally.");
            }
            catch (Exception ex)
            {
                _logger.LogError("Error handling device removed event.", ex);
            }
        }

        public async Task<List<PeripheralInfo>> CollectCurrentPeripheralsAsync()
        {
            var peripherals = new List<PeripheralInfo>();

            try
            {
                await Task.Run(() =>
                {
                    try
                    {
                        using var searcher = new ManagementObjectSearcher(
                            @"root\cimv2",
                            "SELECT Name, Manufacturer, PNPClass, Description, PNPDeviceID, ConfigManagerErrorCode, DriverVersion FROM Win32_PnPEntity");

                        foreach (ManagementObject obj in searcher.Get())
                        {
                            var name = obj["Name"]?.ToString() ?? "";
                            var manufacturer = obj["Manufacturer"]?.ToString() ?? "";
                            var pnpClass = obj["PNPClass"]?.ToString() ?? "";
                            var description = obj["Description"]?.ToString() ?? "";
                            var pnpDeviceId = obj["PNPDeviceID"]?.ToString() ?? "";
                            var configManagerErrorCode = obj["ConfigManagerErrorCode"];
                            var driverVersion = obj["DriverVersion"]?.ToString() ?? "";

                            if (string.IsNullOrWhiteSpace(name))
                                continue;

                            var deviceType = ClassifyDeviceType(pnpClass, name, description);
                            var connectionType = ClassifyConnectionType(pnpClass, name);
                            var (hasProblem, problemDescription) = GetDeviceProblemInfo(configManagerErrorCode);

                            peripherals.Add(new PeripheralInfo
                            {
                                DeviceName = name,
                                DeviceType = deviceType,
                                Manufacturer = manufacturer,
                                ConnectionType = connectionType,
                                Status = hasProblem ? "error" : "connected",
                                LastSeen = DateTime.UtcNow,
                                DeviceId = pnpDeviceId,
                                HasProblem = hasProblem,
                                ProblemDescription = problemDescription,
                                DriverVersion = driverVersion
                            });
                        }
                    }
                    catch (Exception ex)
                    {
                        _logger.LogWarning("Failed to query peripherals for event details.", ex);
                    }
                });
            }
            catch (Exception ex)
            {
                _logger.LogError("Failed to collect current peripherals.", ex);
            }

            return peripherals;
        }

        public void Dispose()
        {
            Stop();
        }

        private PeripheralInfo? FindNewDevice(List<PeripheralInfo> current)
        {
            if (_lastPeripheralSnapshot.Count == 0)
                return null;

            var prevNames = new HashSet<string>(_lastPeripheralSnapshot.Select(p => p.DeviceName));
            return current.Find(p => !prevNames.Contains(p.DeviceName));
        }

        private static (bool HasProblem, string? ProblemDescription) GetDeviceProblemInfo(object? configManagerErrorCodeObj)
        {
            if (configManagerErrorCodeObj == null)
                return (false, null);

            if (!int.TryParse(configManagerErrorCodeObj.ToString(), out int errorCode))
                return (false, null);

            if (errorCode == 0)
                return (false, null);

            string description = errorCode switch
            {
                1 => "Device is not configured correctly.",
                2 => "Windows cannot load the driver for this device.",
                3 => "Driver for this device might be corrupted, or system may be low on resources.",
                4 => "Device is not working properly. One of its drivers or registry might be corrupted.",
                5 => "Driver for this device needs a resource that Windows cannot manage.",
                6 => "Boot configuration for this device conflicts with other devices.",
                7 => "Cannot filter.",
                8 => "Driver loader for the device is missing.",
                9 => "Device is not working properly because the controlling firmware is reporting resources incorrectly.",
                10 => "Device cannot start.",
                11 => "Device failed.",
                12 => "Device cannot find enough free resources.",
                13 => "Windows cannot verify this device's resources.",
                14 => "Device cannot work properly until computer restarts.",
                15 => "Device is not working properly due to a re-enumeration problem.",
                16 => "Windows cannot identify all resources this device uses.",
                17 => "Device is requesting an unknown resource type.",
                18 => "Reinstall drivers for this device.",
                19 => "Registry may be corrupted.",
                20 => "Failure using the VxD loader.",
                21 => "System failure: Try changing the driver for this device.",
                22 => "Device is disabled.",
                23 => "System failure: Try changing the driver for this device.",
                24 => "Device is not present, not working properly, or does not have all its drivers installed.",
                25 => "Windows is still setting up this device.",
                26 => "Windows is still setting up this device.",
                27 => "Device does not have valid log configuration.",
                28 => "Drivers for this device are not installed.",
                29 => "Device is disabled; firmware did not provide required resources.",
                30 => "Device is using an IRQ resource that another device is using.",
                31 => "Device is not working properly because Windows cannot load the drivers required.",
                _ => $"Device has a problem (Error Code: {errorCode})."
            };

            return (true, description);
        }

        private static string ClassifyDeviceType(string pnpClass, string name, string description)
        {
            if (string.IsNullOrWhiteSpace(pnpClass))
                pnpClass = description;

            var lowerName = name.ToLowerInvariant();
            var lowerClass = pnpClass.ToLowerInvariant();
            var lowerDesc = description.ToLowerInvariant();

            if (lowerClass.Contains("phone") || lowerName.Contains("phone") || lowerDesc.Contains("phone"))
                return "Phone";
            if (lowerClass.Contains("mobile") || lowerName.Contains("mobile") || lowerDesc.Contains("mobile"))
                return "Mobile";
            if (lowerClass.Contains("tablet") || lowerName.Contains("tablet") || lowerDesc.Contains("tablet"))
                return "Tablet";
            if (lowerClass.Contains("printer") || lowerName.Contains("printer"))
                return "Printer";
            if (lowerClass.Contains("scanner") || lowerName.Contains("scanner"))
                return "Scanner";
            if (lowerClass.Contains("camera") || lowerName.Contains("camera") || lowerName.Contains("webcam"))
                return "Camera";
            if (lowerClass.Contains("keyboard") || lowerName.Contains("keyboard"))
                return "Keyboard";
            if (lowerClass.Contains("mouse") || lowerName.Contains("mouse") || lowerName.Contains("pointing"))
                return "Mouse";
            if (lowerClass.Contains("monitor") || lowerName.Contains("monitor") || lowerClass.Contains("display"))
                return "Monitor";
            if (lowerClass.Contains("usb") || lowerName.Contains("usb"))
                return "USB";
            if (lowerClass.Contains("bluetooth") || lowerName.Contains("bluetooth"))
                return "Bluetooth";
            if (lowerClass.Contains("diskdrive") || lowerName.Contains("drive") || lowerClass.Contains("storage"))
                return "Storage";
            if (lowerClass.Contains("audio") || lowerName.Contains("audio") || lowerName.Contains("sound"))
                return "Audio";
            if (lowerClass.Contains("network") || lowerName.Contains("network") || lowerClass.Contains("net"))
                return "Network";
            if (lowerClass.Contains("dock") || lowerName.Contains("dock"))
                return "DockingStation";
            if (lowerClass.Contains("hids") || lowerName.Contains("hid"))
                return "HID";

            return string.IsNullOrWhiteSpace(pnpClass) ? "Unknown" : pnpClass;
        }

        private static string ClassifyConnectionType(string pnpClass, string name)
        {
            var lowerName = name.ToLowerInvariant();
            var lowerClass = pnpClass.ToLowerInvariant();

            if (lowerClass.Contains("usb") || lowerName.Contains("usb"))
                return "USB";
            if (lowerClass.Contains("bluetooth") || lowerName.Contains("bluetooth"))
                return "Bluetooth";
            if (lowerClass.Contains("network") || lowerName.Contains("wireless") || lowerName.Contains("wifi"))
                return "Network";
            if (lowerName.Contains("dock") || lowerClass.Contains("dock"))
                return "Docking";
            if (lowerClass.Contains("hdaudio") || lowerClass.Contains("pci"))
                return "Internal";
            if (lowerClass.Contains("display") || lowerClass.Contains("monitor"))
                return "DisplayPort";

            return "Other";
        }
    }
}