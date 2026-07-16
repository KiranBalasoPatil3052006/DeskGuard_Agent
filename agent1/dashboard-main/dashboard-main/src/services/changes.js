import api from './api';

export function getChanges(params) {
  return api.get('/changes', { params });
}

export function getRecentChanges(limit = 5) {
  return api.get('/changes', { params: { per_page: limit, sort: 'detected_at', order: 'desc' } });
}

export function updateChangeStatus(changeId, status, note) {
  return api.put(`/changes/${changeId}/status`, { status, note });
}
