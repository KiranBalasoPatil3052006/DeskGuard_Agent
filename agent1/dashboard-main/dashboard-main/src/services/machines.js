/**
 * Machines API Service
 *
 * Provides all API calls related to machine management, status,
 * history, and sub-resources (inventory, security, devices, timeline).
 * Every function returns a promise from the axios instance.
 */
import api from './api';

/** List machines with optional pagination/filter params */
export function getMachines(params) {
  return api.get('/machines', { params });
}

/** Get a single machine by ID with current status */
export function getMachine(id) {
  return api.get(`/machines/${id}`);
}

/** Get the current status snapshot for a machine */
export function getMachineStatus(id) {
  return api.get(`/machines/${id}/status`);
}

/**
 * Get health log history for a machine.
 * @param {number} id - Machine ID
 * @param {object} params - { from: 'YYYY-MM-DD', to: 'YYYY-MM-DD' } (optional, defaults to last 24h)
 */
export function getMachineHistory(id, params) {
  return api.get(`/machines/${id}/history`, { params });
}

/** Get hardware + software inventory for a machine */
export function getMachineInventory(id) {
  return api.get(`/machines/${id}/inventory`);
}

/** Get security data (antivirus, firewall, logins, updates) for a machine */
export function getMachineSecurity(id) {
  return api.get(`/machines/${id}/security`);
}

/** Get connected devices and USB/device activity for a machine */
export function getMachineDevices(id, params = {}) {
  return api.get(`/machines/${id}/devices`, { params });
}

/** Get detailed device info + issues for a specific device by name */
export function getDeviceIssues(id, deviceName) {
  return api.get(`/machines/${id}/device-issues`, { params: { device_name: deviceName } });
}

/** Get alerts specific to a machine */
export function getMachineAlerts(id) {
  return api.get(`/machines/${id}/alerts`);
}

/** Get combined activity timeline (alerts, logins, USB, devices) */
export function getMachineTimeline(id, params) {
  return api.get(`/machines/${id}/timeline`, { params });
}

/** Get running processes for a machine */
export function getMachineProcesses(id) {
  return api.get(`/machines/${id}/processes`);
}

/** Get Windows services for a machine */
export function getMachineServices(id) {
  return api.get(`/machines/${id}/services`);
}

/** Get startup programs for a machine */
export function getMachineStartupPrograms(id) {
  return api.get(`/machines/${id}/startup-programs`);
}

/** Get event logs for a machine */
export function getMachineEventLogs(id) {
  return api.get(`/machines/${id}/event-logs`);
}

/** Get network adapters and disks for a machine */
export function getMachineNetwork(id) {
  return api.get(`/machines/${id}/network`);
}

/** Get changes for a specific machine */
export function getMachineChanges(id, params) {
  return api.get(`/machines/${id}/changes`, { params });
}

/** Get count of online machines */
export function getOnlineMachines() {
  return api.get('/machines/online');
}

/** Get count of offline machines */
export function getOfflineMachines() {
  return api.get('/machines/offline');
}
