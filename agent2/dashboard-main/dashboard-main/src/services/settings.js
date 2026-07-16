/**
 * Settings API Service
 *
 * Provides API calls for managing application settings,
 * including email recipients and notification preferences.
 */
import api from './api';

/** Get notification settings (recipients + preferences) */
export function getNotificationSettings() {
  return api.get('/settings/notifications');
}

/** List all email recipients */
export function getEmailRecipients() {
  return api.get('/settings/email-recipients');
}

/** Add a new email recipient */
export function addEmailRecipient(email, name = '') {
  return api.post('/settings/email-recipients', { email, name });
}

/** Update an email recipient */
export function updateEmailRecipient(id, data) {
  return api.put(`/settings/email-recipients/${id}`, data);
}

/** Remove an email recipient */
export function removeEmailRecipient(id) {
  return api.delete(`/settings/email-recipients/${id}`);
}
