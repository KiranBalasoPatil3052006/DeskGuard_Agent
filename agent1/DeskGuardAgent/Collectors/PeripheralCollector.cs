using System.Management;
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

            try
            {
                await Task.Run(() =>
                {
                    try
                    {
                        using var searcher = new ManagementObjectSearcher(
                            @"root\cimv2",
                            "SELECT Name, Manufacturer, PNPClass, Description FROM Win32_PnPEntity WHERE ConfigManagerErrorCode = 0");

                        foreach (ManagementObject obj in searcher.Get())
                        {
                            var name = obj["Name"]?.ToString() ?? "";
                            var manufacturer = obj["Manufacturer"]?.ToString() ?? "";
                            var pnpClass = obj["PNPClass"]?.ToString() ?? "";
                            var description = obj["Description"]?.ToString() ?? "";

                            if (string.IsNullOrWhiteSpace(name))
                                continue;

                            var deviceType = ClassifyDeviceType(pnpClass, name, description);
                            var connectionType = ClassifyConnectionType(pnpClass, name);

                            peripherals.Add(new PeripheralInfo
                            {
                                DeviceName = name,
                                DeviceType = deviceType,
                                Manufacturer = manufacturer,
                                ConnectionType = connectionType,
                                Status = "connected",
                                LastSeen = DateTime.UtcNow,
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
                            "SELECT Name, Manufacturer FROM Win32_PnPEntity WHERE PNPClass = 'Bluetooth'");

                        foreach (ManagementObject obj in searcher.Get())
                        {
                            var name = obj["Name"]?.ToString() ?? "";
                            var manufacturer = obj["Manufacturer"]?.ToString() ?? "";

                            if (string.IsNullOrWhiteSpace(name))
                                continue;

                            if (peripherals.Any(p => p.DeviceName == name))
                                continue;

                            peripherals.Add(new PeripheralInfo
                            {
                                DeviceName = name,
                                DeviceType = "Bluetooth",
                                Manufacturer = manufacturer,
                                ConnectionType = "Bluetooth",
                                Status = "connected",
                                LastSeen = DateTime.UtcNow,
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

        private static string ClassifyDeviceType(string pnpClass, string name, string description)
        {
            if (string.IsNullOrWhiteSpace(pnpClass))
                pnpClass = description;

            var lowerName = name.ToLowerInvariant();
            var lowerClass = pnpClass.ToLowerInvariant();
            var lowerDesc = description.ToLowerInvariant();

            if (lowerClass.Contains("usb") || lowerName.Contains("usb"))
                return "USB";
            if (lowerClass.Contains("keyboard") || lowerName.Contains("keyboard"))
                return "Keyboard";
            if (lowerClass.Contains("mouse") || lowerName.Contains("mouse") || lowerName.Contains("pointing"))
                return "Mouse";
            if (lowerClass.Contains("monitor") || lowerName.Contains("monitor") || lowerClass.Contains("display"))
                return "Monitor";
            if (lowerClass.Contains("printer") || lowerName.Contains("printer"))
                return "Printer";
            if (lowerClass.Contains("scanner") || lowerName.Contains("scanner"))
                return "Scanner";
            if (lowerClass.Contains("camera") || lowerName.Contains("camera") || lowerName.Contains("webcam"))
                return "Camera";
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
