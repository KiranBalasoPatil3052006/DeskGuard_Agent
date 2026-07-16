/**
 * Reports API Service
 *
 * Provides API calls for report generation, listing, and downloading.
 */
import api from './api';

/** List all reports with optional pagination */
export function getReports(params) {
  return api.get('/reports', { params });
}

/** Generate a new report */
export function generateReport(data) {
  return api.post('/reports/generate', data);
}

/** Download a report by ID */
export function downloadReport(id) {
  return api.get(`/reports/${id}/download`, { responseType: 'blob' });
}

/** Delete a report */
export function deleteReport(id) {
  return api.delete(`/reports/${id}`);
}
