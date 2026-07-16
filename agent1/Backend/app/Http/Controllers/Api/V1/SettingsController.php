<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\EmailRecipient;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * SettingsController
 *
 * Manages application settings including notification email recipients
 * and alert notification preferences. Scoped per company.
 *
 * @package App\Http\Controllers\Api\V1
 */
class SettingsController extends Controller
{
    use ApiResponseTrait;

    /**
     * List all email recipients for the authenticated user's company.
     *
     * @return JsonResponse
     */
    public function listEmailRecipients(): JsonResponse
    {
        try {
            $companyId = (int) Auth::user()->company_id;

            $recipients = EmailRecipient::where('company_id', $companyId)
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->successResponse($recipients, 'Email recipients retrieved successfully.');
        } catch (Exception $e) {
            Log::error('SettingsController::listEmailRecipients - Failed', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve email recipients.', [], 500);
        }
    }

    /**
     * Add a new email recipient for alert notifications.
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function addEmailRecipient(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email|max:255',
                'name'  => 'nullable|string|max:255',
            ]);

            $companyId = (int) Auth::user()->company_id;

            // Check for duplicate within the same company.
            $exists = EmailRecipient::where('company_id', $companyId)
                ->where('email', $validated['email'])
                ->exists();

            if ($exists) {
                return $this->errorResponse('This email address is already registered.', [], 422);
            }

            $recipient = EmailRecipient::create([
                'company_id' => $companyId,
                'email'      => $validated['email'],
                'name'       => $validated['name'] ?? null,
                'is_active'  => true,
            ]);

            Log::info('Email recipient added', [
                'recipient_id' => $recipient->id,
                'company_id'   => $companyId,
                'email'        => $validated['email'],
            ]);

            return $this->successResponse($recipient, 'Email recipient added successfully.', 201);
        } catch (Exception $e) {
            Log::error('SettingsController::addEmailRecipient - Failed', [
                'data'  => $request->all(),
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse(
                $e->getMessage() ?: 'Failed to add email recipient.',
                [],
                method_exists($e, 'getCode') && $e->getCode() ? $e->getCode() : 500
            );
        }
    }

    /**
     * Update an email recipient (toggle active status or change name).
     *
     * @param  Request $request
     * @param  int     $id
     * @return JsonResponse
     */
    public function updateEmailRecipient(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email'     => 'sometimes|email|max:255',
                'name'      => 'nullable|string|max:255',
                'is_active' => 'sometimes|boolean',
            ]);

            $companyId = (int) Auth::user()->company_id;

            $recipient = EmailRecipient::where('company_id', $companyId)
                ->findOrFail($id);

            $recipient->update($validated);
            $recipient->refresh();

            Log::info('Email recipient updated', [
                'recipient_id' => $id,
                'data'         => $validated,
            ]);

            return $this->successResponse($recipient, 'Email recipient updated successfully.');
        } catch (Exception $e) {
            Log::error('SettingsController::updateEmailRecipient - Failed', [
                'recipient_id' => $id,
                'error'        => $e->getMessage(),
            ]);
            return $this->errorResponse(
                $e->getMessage() ?: 'Failed to update email recipient.',
                [],
                method_exists($e, 'getCode') && $e->getCode() ? $e->getCode() : 500
            );
        }
    }

    /**
     * Remove an email recipient.
     *
     * @param  int $id
     * @return JsonResponse
     */
    public function removeEmailRecipient(int $id): JsonResponse
    {
        try {
            $companyId = (int) Auth::user()->company_id;

            $recipient = EmailRecipient::where('company_id', $companyId)
                ->findOrFail($id);

            $recipient->delete();

            Log::info('Email recipient removed', [
                'recipient_id' => $id,
                'company_id'   => $companyId,
            ]);

            return $this->successResponse(null, 'Email recipient removed successfully.');
        } catch (Exception $e) {
            Log::error('SettingsController::removeEmailRecipient - Failed', [
                'recipient_id' => $id,
                'error'        => $e->getMessage(),
            ]);
            return $this->errorResponse(
                $e->getMessage() ?: 'Failed to remove email recipient.',
                [],
                method_exists($e, 'getCode') && $e->getCode() ? $e->getCode() : 500
            );
        }
    }

    /**
     * Get notification settings for the authenticated user's company.
     * Returns email recipients and default alert thresholds.
     *
     * @return JsonResponse
     */
    public function getNotificationSettings(): JsonResponse
    {
        try {
            $companyId = (int) Auth::user()->company_id;

            $recipients = EmailRecipient::where('company_id', $companyId)
                ->where('is_active', true)
                ->get();

            return $this->successResponse([
                'email_recipients'       => $recipients,
                'email_alerts_enabled'   => true,
                'alert_severity_filters' => [
                    'critical' => true,
                    'warning'  => true,
                    'info'     => false,
                ],
            ], 'Notification settings retrieved successfully.');
        } catch (Exception $e) {
            Log::error('SettingsController::getNotificationSettings - Failed', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve notification settings.', [], 500);
        }
    }
}
