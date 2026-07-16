/**
 * Dashboard API Service
 *
 * Provides all API calls for dashboard data including
 * company dashboard, employee dashboard, and chart trends.
 */
import api from './api';

/** Get the company dashboard (summary cards + chart data) */
export function getCompanyDashboard() {
  return api.get('/dashboard/company');
}

/** Get the employee dashboard (assigned machine data) */
export function getEmployeeDashboard() {
  return api.get('/dashboard/employee');
}

/** Get CPU usage trend data */
export function getCpuTrend(hours = 24) {
  return api.get('/dashboard/charts/cpu', { params: { hours } });
}

/** Get RAM usage trend data */
export function getRamTrend(hours = 24) {
  return api.get('/dashboard/charts/ram', { params: { hours } });
}

/** Get alert trend data (daily counts by severity) */
/** Get recent changes */
export function getRecentChanges(limit = 5) {
  return api.get('/changes', { params: { per_page: limit, sort: 'detected_at', order: 'desc', category: 'hardware' } });
}

export function getAlertTrend(days = 7) {
  return api.get('/dashboard/charts/alerts', { params: { days } });
}
