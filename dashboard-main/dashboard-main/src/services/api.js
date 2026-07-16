import axios from 'axios';

const rawBase = import.meta.env.VITE_API_URL || '/api/v1';
const BASE_URL = rawBase.includes('/api/v1') ? rawBase : `${rawBase.replace(/\/+$/, '')}/api/v1`;
console.log('[API] Base URL:', BASE_URL);

const api = axios.create({
  baseURL: BASE_URL,
  headers: { Accept: 'application/json' },
});

api.interceptors.request.use((config) => {
  console.log('[API] Request:', config.method?.toUpperCase(), config.baseURL + config.url);
  const token = localStorage.getItem('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

api.interceptors.response.use(
  (response) => response.data,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token');
      localStorage.removeItem('auth_user');
      window.location.href = '/login';
    }
    return Promise.reject(error.response?.data || error);
  },
);

export function setAuthToken(token) {
  if (token) {
    localStorage.setItem('auth_token', token);
  } else {
    localStorage.removeItem('auth_token');
  }
}

export default api;
