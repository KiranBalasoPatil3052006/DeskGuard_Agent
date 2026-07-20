using System.Collections.Generic;
using System.Threading.Tasks;
using DeskGuardBackend.Entities;

namespace DeskGuardBackend.Services.Interfaces
{
    public interface INotificationService
    {
        Task<Notification> SendNotificationAsync(long userId, string title, string message, string type, object? metadata = null);
        Task<bool> MarkAsReadAsync(long notificationId);
        Task<bool> MarkAllAsReadAsync(long userId);
        Task<IEnumerable<Notification>> GetUserNotificationsAsync(long userId, bool unreadOnly = false);
        Task SendAlertNotificationAsync(Alert alert);
        Task SendEmailNotificationAsync(Alert alert);
    }
}
