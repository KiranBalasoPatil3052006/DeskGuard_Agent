import api from './api';

export function loginUser(email, password) {
  return api.post('/auth/login', { email, password });
}

export function registerUser(name, email, password) {
  return api.post('/auth/register', { name, email, password });
}

export function logoutUser() {
  return api.post('/auth/logout');
}

export function getUser() {
  return api.get('/auth/user');
}
