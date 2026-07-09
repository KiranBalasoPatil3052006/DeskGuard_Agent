# Changelog

## v2.0.0 - 2026-06-23

### Added
- **UserRegistrationService**: OTP-based registration flow (mobile prompt → OTP entry → token storage)
- **PeripheralCollector**: Win32_PnPEntity-based collector for connected peripherals (USB, keyboard, mouse, monitor, printer, scanner, camera, Bluetooth, storage, audio, network, docking station)
- **DeviceEventWatcher**: Real-time device change event monitoring via ManagementEventWatcher (Win32_DeviceChangeEvent)
- **Periodic device scan**: 30-minute full peripheral snapshot sent to backend
- **OTP registration support**: appsettings.json flags (EnableOtpRegistration, EnablePeripheralMonitoring, EnableDeviceEventWatcher)
- **Device event/sync API routes**: /api/v1/agent/device-events, /api/v1/agent/device-sync
- New models: PeripheralInfo, DeviceEventInfo, OtpResponse/VerifyOtpResponse
- New AgentConstants: DeviceScanIntervalMinutes
- New MonitoringSettings: EnablePeripheralMonitoring, EnableDeviceEventWatcher, EnableOtpRegistration, DeviceScanIntervalMinutes

### Changed
- **Worker.cs**: Integrated DeviceEventWatcher start/stop, registration flow before monitoring start
- **Program.cs**: Registered PeripheralCollector, UserRegistrationService, DeviceEventWatcher in DI
- **SchedulerService.cs**: Added periodic device scan timer (30 min)
- **IApiSenderService.cs**: Added SendDeviceEventAsync, SendDeviceSyncAsync
- **ApiSenderService.cs**: Implemented device event/sync transmission
- **ApiRoutes.cs**: Added DeviceEventEndpoint, DeviceSyncEndpoint, RequestOtpEndpoint, VerifyOtpEndpoint
- **IMonitoringService.cs**: Added SendDeviceSyncAsync method
- **MonitoringService.cs**: Implemented SendDeviceSyncAsync for peripheral snapshot transmission
- **appsettings.json**: Added peripheral monitoring and OTP registration settings
