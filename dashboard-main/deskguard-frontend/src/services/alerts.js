import api from './api';

export function getAlerts(params = {}) {
  return api.get('/alerts', { params });
}

export function getCriticalAlerts() {
  return api.get('/alerts/critical');
}

export function getAlert(id) {
  return api.get(`/alerts/${id}`);
}

export function acknowledgeAlert(id) {
  return api.post(`/alerts/${id}/acknowledge`);
}

export function resolveAlert(id, note = '') {
  return api.post(`/alerts/${id}/resolve`, { resolution_note: note });
}

export function getAlertRules() {
  return api.get('/alert-rules');
}

export function updateAlertRule(id, data) {
  return api.put(`/alert-rules/${id}`, data);
}