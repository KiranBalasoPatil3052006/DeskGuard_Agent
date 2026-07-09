import api from './api';

export function getEmailRecipients() {
  return api.get('/settings/email-recipients');
}

export function addEmailRecipient(data) {
  return api.post('/settings/email-recipients', data);
}

export function updateEmailRecipient(id, data) {
  return api.put(`/settings/email-recipients/${id}`, data);
}

export function removeEmailRecipient(id) {
  return api.delete(`/settings/email-recipients/${id}`);
}

export function getNotificationSettings() {
  return api.get('/settings/notifications');
}