export const AlertSeverity = Object.freeze({
  CRITICAL: 'critical',
  WARNING: 'warning',
  INFO: 'info',
});

export const AlertStatus = Object.freeze({
  OPEN: 'open',
  ACKNOWLEDGED: 'acknowledged',
  RESOLVED: 'resolved',
});

export const MachineStatus = Object.freeze({
  ONLINE: 'online',
  OFFLINE: 'offline',
  PENDING: 'pending',
});

export const DeviceEventType = Object.freeze({
  CONNECTED: 'Connected',
  REMOVED: 'Removed',
});

export const LoginEventType = Object.freeze({
  LOGON: 'Logon',
  LOGOFF: 'Logoff',
  FAILED: 'Failed Logon',
  LOCKED: 'Workstation Locked',
  UNLOCKED: 'Workstation Unlocked',
  RECONNECTED: 'Session Reconnected',
  DISCONNECTED: 'Session Disconnected',
});

export const EventLogLevel = Object.freeze({
  ERROR: 'Error',
  WARNING: 'Warning',
  INFO: 'Information',
  CRITICAL: 'Critical',
});