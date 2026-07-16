import api from './api';

export function getAlertProfiles(params) {
  return api.get('/alert-profiles', { params });
}

export function getAlertProfile(id) {
  return api.get(`/alert-profiles/${id}`);
}

export function createAlertProfile(data) {
  return api.post('/alert-profiles', data);
}

export function updateAlertProfile(id, data) {
  return api.put(`/alert-profiles/${id}`, data);
}

export function deleteAlertProfile(id) {
  return api.delete(`/alert-profiles/${id}`);
}

export function duplicateAlertProfile(id) {
  return api.post(`/alert-profiles/${id}/duplicate`);
}

export function assignProfileToCompany(profileId, companyId) {
  return api.post(`/alert-profiles/${profileId}/companies`, { company_id: companyId });
}

export function unassignProfileFromCompany(profileId, companyId) {
  return api.delete(`/alert-profiles/${profileId}/companies/${companyId}`);
}

export function assignProfileToMachine(profileId, machineId) {
  return api.post(`/alert-profiles/${profileId}/machines`, { machine_id: machineId });
}

export function unassignProfileFromMachine(profileId, machineId) {
  return api.delete(`/alert-profiles/${profileId}/machines/${machineId}`);
}
