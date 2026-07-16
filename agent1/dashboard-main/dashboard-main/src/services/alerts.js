/**
 * Alerts API Service
 *
 * Provides all API calls for alert management including
 * listing, filtering, acknowledging, resolving, and rule management.
 */
import api from './api';

/** List alerts with optional severity/status/search filters */
export function getAlerts(params) {
  return api.get('/alerts', { params });
}

/** Get critical alerts only */
export function getCriticalAlerts() {
  return api.get('/alerts/critical');
}

/** Get a single alert by ID */
export function getAlert(id) {
  return api.get(`/alerts/${id}`);
}

/** Acknowledge an alert — marks it as seen by an admin */
export function acknowledgeAlert(id) {
  return api.post(`/alerts/${id}/acknowledge`);
}

/** Resolve an alert with an optional resolution note */
export function resolveAlert(id, note = '') {
  return api.post(`/alerts/${id}/resolve`, { note });
}

/** Get all alert rules for the company */
export function getAlertRules() {
  return api.get('/alert-rules');
}

/** Update an alert rule (threshold, severity, enabled) */
export function updateAlertRule(id, data) {
  return api.put(`/alert-rules/${id}`, data);
}
