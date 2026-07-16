<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * NotificationController
 *
 * Manages user notifications including listing, marking as read,
 * and retrieving unread counts.
 * Delegates all business logic to NotificationService.
 *
 * @package App\Http\Controllers\Api\V1
 */
class NotificationController extends Controller
{
    use ApiResponseTrait;

    private NotificationService $notificationService;

    /**
     * NotificationController constructor.
     *
     * @param NotificationService $notificationService
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * List notifications for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $userId = (int) Auth::id();

            $validated = $request->validate([
                'unread_only' => 'nullable|boolean',
            ]);

            $notifications = $this->notificationService->getUserNotifications(
                $userId,
                $validated['unread_only'] ?? false
            );

            return $this->successResponse($notifications, 'Notifications retrieved successfully.');
        } catch (Exception $e) {
            Log::error('NotificationController::index - Failed to retrieve notifications', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve notifications.', [], 500);
        }
    }

    /**
     * Mark a single notification as read.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function markRead(int $id): JsonResponse
    {
        try {
            $this->notificationService->markAsRead($id);

            return $this->successResponse(null, 'Notification marked as read.');
        } catch (Exception $e) {
            Log::error('NotificationController::markRead - Failed to mark notification as read', [
                'notification_id' => $id,
                'error'           => $e->getMessage(),
            ]);
            return $this->errorResponse(
                $e->getMessage() ?: 'Failed to mark notification as read.',
                [],
                method_exists($e, 'getCode') && $e->getCode() ? $e->getCode() : 500
            );
        }
    }

    /**
     * Mark all notifications for the authenticated user as read.
     *
     * @return JsonResponse
     */
    public function markAllRead(): JsonResponse
    {
        try {
            $userId = (int) Auth::id();

            $this->notificationService->markAllAsRead($userId);

            return $this->successResponse(null, 'All notifications marked as read.');
        } catch (Exception $e) {
            Log::error('NotificationController::markAllRead - Failed to mark all notifications as read', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to mark notifications as read.', [], 500);
        }
    }

    /**
     * Get the unread notification count for the authenticated user.
     *
     * @return JsonResponse
     */
    public function unreadCount(): JsonResponse
    {
        try {
            $userId = (int) Auth::id();

            $notifications = $this->notificationService->getUserNotifications($userId, true);

            return $this->successResponse(
                ['unread_count' => $notifications->count()],
                'Unread count retrieved successfully.'
            );
        } catch (Exception $e) {
            Log::error('NotificationController::unreadCount - Failed to get unread count', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve unread count.', [], 500);
        }
    }
}
