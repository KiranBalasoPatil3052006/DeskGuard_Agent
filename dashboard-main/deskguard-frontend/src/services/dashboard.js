import api from './api';

export function getCompanyDashboard() {
  return api.get('/dashboard/company');
}

export function getEmployeeDashboard() {
  return api.get('/dashboard/employee');
}

export function getCpuTrend(hours = 24) {
  return api.get(`/dashboard/charts/cpu?hours=${hours}`);
}

export function getRamTrend(hours = 24) {
  return api.get(`/dashboard/charts/ram?hours=${hours}`);
}

export function getAlertTrend(days = 7) {
  return api.get(`/dashboard/charts/alerts?days=${days}`);
}