using System.Management;
using System.Collections.Generic;
using DeskGuardAgent.Interfaces;
using DeskGuardAgent.Models;

namespace DeskGuardAgent.Collectors
{
    public class PeripheralCollector : ICollector<List<PeripheralInfo>>
    {
        private readonly ILoggerService _logger;

        public PeripheralCollector(ILoggerService logger)
        {
            _logger = logger;
        }

        public async Task<List<PeripheralInfo>> CollectAsync()
        {
            _logger.LogDebug("Starting peripheral device collection.");

            var peripherals = new List<PeripheralInfo>();
            var driverVersionMap = new Dictionary<string, string>(StringComparer.OrdinalIgnoreCase);

            try
            {
                await Task.Run(() =>
                {
                    try
                    {
                        using var searcher = new ManagementObjectSearcher(
                            @"root\cimv2",
                            "SELECT DeviceID, DriverVersion FROM Win32_PnPSignedDriver");

                        foreach (ManagementObject obj in searcher.Get())
                        {
                            var deviceId = obj["DeviceID"]?.ToString() ?? "";
                            var driverVersion = obj["DriverVersion"]?.ToString() ?? "";
                            if (!string.IsNullOrWhiteSpace(deviceId) && !string.IsNullOrWhiteSpace(driverVersion))
                            {
                                driverVersionMap[deviceId] = driverVersion;
                            }
                        }
                    }
                    catch (Exception ex)
                    {
                        _logger.LogWarning("Failed to query Win32_PnPSignedDriver for driver versions.", ex);
                    }
                });

                await Task.Run(() =>
                {
                    try
                    {
                        using var searcher = new ManagementObjectSearcher(
                            @"root\cimv2",
                            "SELECT Name, Manufacturer, PNPClass, Description, PNPDeviceID, ConfigManagerErrorCode FROM Win32_PnPEntity");

                        foreach (ManagementObject obj in searcher.Get())
                        {
                            var name = obj["Name"]?.ToString() ?? "";
                            var manufacturer = obj["Manufacturer"]?.ToString() ?? "";
                            var pnpClass = obj["PNPClass"]?.ToString() ?? "";
                            var description = obj["Description"]?.ToString() ?? "";
                            var pnpDeviceId = obj["PNPDeviceID"]?.ToString() ?? "";
                            var configManagerErrorCode = obj["ConfigManagerErrorCode"];

                            if (string.IsNullOrWhiteSpace(name))
                                continue;

                            var deviceType = ClassifyDeviceType(pnpClass, name, description);
                            var connectionType = ClassifyConnectionType(pnpClass, name);
                            var (hasProblem, problemDescription) = GetDeviceProblemInfo(configManagerErrorCode);

                            driverVersionMap.TryGetValue(pnpDeviceId, out var driverVersion);

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
                                DriverVersion = driverVersion ?? ""
                            });
                        }
                    }
                    catch (Exception ex)
                    {
                        _logger.LogWarning("Failed to query Win32_PnPEntity.", ex);
                    }
                });

                await Task.Run(() =>
                {
                    try
                    {
                        using var searcher = new ManagementObjectSearcher(
                            @"root\cimv2",
                            "SELECT Name, Manufacturer, PNPDeviceID, ConfigManagerErrorCode FROM Win32_PnPEntity WHERE PNPClass = 'Bluetooth'");

                        foreach (ManagementObject obj in searcher.Get())
                        {
                            var name = obj["Name"]?.ToString() ?? "";
                            var manufacturer = obj["Manufacturer"]?.ToString() ?? "";
                            var pnpDeviceId = obj["PNPDeviceID"]?.ToString() ?? "";
                            var configManagerErrorCode = obj["ConfigManagerErrorCode"];

                            if (string.IsNullOrWhiteSpace(name))
                                continue;

                            if (peripherals.Any(p => p.DeviceName == name))
                                continue;

                            var (hasProblem, problemDescription) = GetDeviceProblemInfo(configManagerErrorCode);

                            driverVersionMap.TryGetValue(pnpDeviceId, out var driverVersion);

                            peripherals.Add(new PeripheralInfo
                            {
                                DeviceName = name,
                                DeviceType = "Bluetooth",
                                Manufacturer = manufacturer,
                                ConnectionType = "Bluetooth",
                                Status = hasProblem ? "error" : "connected",
                                LastSeen = DateTime.UtcNow,
                                DeviceId = pnpDeviceId,
                                HasProblem = hasProblem,
                                ProblemDescription = problemDescription,
                                DriverVersion = driverVersion ?? ""
                            });
                        }
                    }
                    catch (Exception ex)
                    {
                        _logger.LogWarning("Failed to query Bluetooth devices.", ex);
                    }
                });

                _logger.LogDebug($"Peripheral collection complete. Found {peripherals.Count} devices.");
            }
            catch (Exception ex)
            {
                _logger.LogError("Failed to collect peripheral devices.", ex);
            }

            return peripherals;
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