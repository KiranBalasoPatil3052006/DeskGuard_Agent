<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EventType;
use App\Mail\AlertNotificationMail;
use App\Models\Alert;
use App\Models\EmailRecipient;
use App\Models\Notification;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Class NotificationService
 *
 * Manages in-app notifications for users. Handles creation, read status
 * tracking, and automated alert notifications.
 *
 * @package App\Services
 */
class NotificationService
{
    /**
     * The audit log service for recording notification events.
     *
     * @var AuditLogService
     */
    private AuditLogService $auditLogService;

    /**
     * NotificationService constructor.
     *
     * @param AuditLogService $auditLogService
     */
    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Send a notification to a specific user.
     *
     * @param  int         $userId
     * @param  string      $title
     * @param  string      $body
     * @param  string      $type
     * @param  array|null  $metadata
     * @return Notification
     */
    public function sendNotification(int $userId, string $title, string $body, string $type, ?array $metadata = null): Notification
    {
        try {
            $user = User::findOrFail($userId);

            $notification = Notification::create([
                'company_id' => $user->company_id,
                'user_id'    => $user->id,
                'title'      => $title,
                'body'       => $body,
                'type'       => $type,
                'is_read'    => false,
                'metadata'   => $metadata,
            ]);

            $this->auditLogService->log(
                EventType::Create->value,
                'Notification sent to user: ' . $user->email . ' - ' . $title,
                null,
                $notification->toArray(),
                $user,
                null
            );

            Log::info('Notification sent', [
                'notification_id' => $notification->id,
                'user_id'         => $userId,
                'type'            => $type,
            ]);

            return $notification;
        } catch (Exception $e) {
            Log::error('NotificationService::sendNotification - Failed to send notification', [
                'user_id' => $userId,
                'title'   => $title,
                'error'   => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Mark a single notification as read.
     *
     * @param  int   $notificationId
     * @return bool
     */
    public function markAsRead(int $notificationId): bool
    {
        try {
            $notification = Notification::findOrFail($notificationId);

            $result = $notification->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

            Log::info('Notification marked as read', [
                'notification_id' => $notificationId,
            ]);

            return $result;
        } catch (Exception $e) {
            Log::error('NotificationService::markAsRead - Failed to mark notification as read', [
                'notification_id' => $notificationId,
                'error'           => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Mark all notifications for a user as read.
     *
     * @param  int   $userId
     * @return bool
     */
    public function markAllAsRead(int $userId): bool
    {
        try {
            $count = Notification::where('user_id', $userId)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);

            Log::info('All notifications marked as read', [
                'user_id' => $userId,
                'count'   => $count,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('NotificationService::markAllAsRead - Failed to mark all notifications as read', [
                'user_id' => $userId,
                'error'   => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get notifications for a user, optionally filtering to unread only.
     *
     * @param  int   $userId
     * @param  bool  $unreadOnly
     * @return Collection<int, Notification>
     */
    public function getUserNotifications(int $userId, bool $unreadOnly = false): Collection
    {
        try {
            $query = Notification::where('user_id', $userId);

            if ($unreadOnly) {
                $query->where('is_read', false);
            }

            return $query->orderBy('created_at', 'desc')->get();
        } catch (Exception $e) {
            Log::error('NotificationService::getUserNotifications - Failed to retrieve notifications', [
                'user_id'     => $userId,
                'unread_only' => $unreadOnly,
                'error'       => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Create a notification for the user assigned to the machine that triggered the alert.
     *
     * @param  Alert  $alert
     * @return void
     */
    public function sendAlertNotification(Alert $alert): void
    {
        try {
            if (!$alert->machine) {
                Log::warning('NotificationService::sendAlertNotification - No machine associated with alert', [
                    'alert_id' => $alert->id,
                ]);
                return;
            }

            $assignedUser = $alert->machine->assignedUser;

            if (!$assignedUser) {
                Log::info('NotificationService::sendAlertNotification - No user assigned to machine', [
                    'alert_id'   => $alert->id,
                    'machine_id' => $alert->machine_id,
                ]);
                return;
            }

            $this->sendNotification(
                $assignedUser->id,
                $alert->title,
                $alert->message ?? 'An alert has been triggered for your machine.',
                'alert',
                [
                    'alert_id'   => $alert->id,
                    'severity'   => $alert->severity,
                    'machine_id' => $alert->machine_id,
                ]
            );

            Log::info('Alert notification sent to assigned user', [
                'alert_id' => $alert->id,
                'user_id'  => $assignedUser->id,
            ]);
        } catch (Exception $e) {
            Log::error('NotificationService::sendAlertNotification - Failed to send alert notification', [
                'alert_id' => $alert->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send email notifications for an alert to all configured email recipients.
     *
     * Fetches all active EmailRecipients for the machine's company
     * and dispatches an AlertNotificationMail to each.
     * Failures are logged per-recipient but never halt execution.
     *
     * @param  Alert  $alert
     * @return void
     */
    public function sendEmailNotification(Alert $alert): void
    {
        try {
            $machine = $alert->machine;

            if (!$machine) {
                Log::warning('NotificationService::sendEmailNotification - No machine for alert', [
                    'alert_id' => $alert->id,
                ]);
                return;
            }

            $companyId = $machine->company_id;

            if (!$companyId) {
                Log::warning('NotificationService::sendEmailNotification - No company for machine', [
                    'alert_id'   => $alert->id,
                    'machine_id' => $machine->id,
                ]);
                return;
            }

            $recipients = EmailRecipient::where('company_id', $companyId)
                ->where('is_active', true)
                ->get();

            if ($recipients->isEmpty()) {
                Log::info('NotificationService::sendEmailNotification - No email recipients configured', [
                    'alert_id'   => $alert->id,
                    'company_id' => $companyId,
                ]);
                return;
            }

            // Load the machine relationship on the alert for the Mailable
            $alert->loadMissing('machine');

            $recipientCount = 0;

            foreach ($recipients as $recipient) {
                try {
                    Mail::to($recipient->email)->send(new AlertNotificationMail($alert));
                    $recipientCount++;

                    Log::info('Alert email sent', [
                        'alert_id'     => $alert->id,
                        'recipient'    => $recipient->email,
                        'severity'     => $alert->severity,
                    ]);
                } catch (Exception $emailException) {
                    Log::error('NotificationService::sendEmailNotification - Failed to send to recipient', [
                        'alert_id'  => $alert->id,
                        'recipient' => $recipient->email,
                        'error'     => $emailException->getMessage(),
                    ]);
                }
            }

            Log::info('Alert email notifications dispatched', [
                'alert_id'        => $alert->id,
                'total_recipients' => $recipients->count(),
                'sent_count'      => $recipientCount,
            ]);
        } catch (Exception $e) {
            Log::error('NotificationService::sendEmailNotification - Failed', [
                'alert_id' => $alert->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}
