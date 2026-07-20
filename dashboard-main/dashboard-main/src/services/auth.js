import api from './api';

export function loginUser(email, password) {
  return api.post('/auth/login', { email, password });
}

export function logoutUser() {
  return api.post('/auth/logout');
}

export function getUser() {
  return api.get('/auth/user');
}
