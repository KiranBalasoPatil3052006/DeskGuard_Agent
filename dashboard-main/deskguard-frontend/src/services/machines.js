import api from './api';

export function getMachines(params = {}) {
  return api.get('/machines', { params });
}

export function getMachine(id) {
  return api.get(`/machines/${id}`);
}

export function getMachineStatus(id) {
  return api.get(`/machines/${id}/status`);
}

export function getMachineHistory(id, params = {}) {
  return api.get(`/machines/${id}/history`, { params });
}

export function getMachineInventory(id) {
  return api.get(`/machines/${id}/inventory`);
}

export function getMachineSecurity(id) {
  return api.get(`/machines/${id}/security`);
}

export function getMachineDevices(id) {
  return api.get(`/machines/${id}/devices`);
}

export function getMachineAlerts(id, params = {}) {
  return api.get(`/machines/${id}/alerts`, { params });
}

export function getMachineTimeline(id) {
  return api.get(`/machines/${id}/timeline`);
}

export function getMachineProcesses(id) {
  return api.get(`/machines/${id}/processes`);
}

export function getMachineServices(id) {
  return api.get(`/machines/${id}/services`);
}

export function getMachineStartupPrograms(id) {
  return api.get(`/machines/${id}/startup-programs`);
}

export function getMachineEventLogs(id) {
  return api.get(`/machines/${id}/event-logs`);
}

export function getMachineNetwork(id) {
  return api.get(`/machines/${id}/network`);
}

export function assignMachine(id, userId) {
  return api.post(`/machines/${id}/assign`, { user_id: userId });
}

export function unassignMachine(id) {
  return api.post(`/machines/${id}/unassign`);
}