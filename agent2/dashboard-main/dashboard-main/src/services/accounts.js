import api from './api';

export function getAccounts(params) {
  return api.get('/accounts', { params });
}

export function getAccount(id) {
  return api.get(`/accounts/${id}`);
}

export function createAccount(data) {
  return api.post('/accounts', data);
}

export function updateAccount(id, data) {
  return api.put(`/accounts/${id}`, data);
}

export function deleteAccount(id) {
  return api.delete(`/accounts/${id}`);
}

export function disableAccount(id) {
  return api.patch(`/accounts/${id}/disable`);
}

export function enableAccount(id) {
  return api.patch(`/accounts/${id}/enable`);
}

export function generateEmployeeId() {
  return api.get('/accounts/employee-id/next');
}
