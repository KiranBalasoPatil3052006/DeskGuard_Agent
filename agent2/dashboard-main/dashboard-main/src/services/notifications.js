/**
 * Notifications API Service
 *
 * Provides API calls for managing in-app notifications
 * including listing, marking as read, and unread counts.
 */
import api from './api';

/** Get all notifications for the authenticated user */
export function getNotifications(params) {
  return api.get('/notifications', { params });
}

/** Mark a single notification as read */
export function markNotificationAsRead(id) {
  return api.post(`/notifications/${id}/read`);
}

/** Mark all notifications as read */
export function markAllNotificationsAsRead() {
  return api.post('/notifications/read-all');
}

/** Get count of unread notifications */
export function getUnreadNotificationCount() {
  return api.get('/notifications/unread-count');
}
