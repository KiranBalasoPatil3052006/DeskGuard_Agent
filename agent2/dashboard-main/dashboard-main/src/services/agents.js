import api from './api';

export function getAgents(params = {}) {
  return api.get('/machines', { params });
}

export function getAgentDetail(id) {
  return api.get(`/machines/${id}`);
}

export function getAgentAlerts(machineId) {
  return api.get('/alerts', { params: { machine_id: machineId, per_page: 20 } });
}

export function getAgentSummary() {
  return api.get('/dashboard/company');
}
