<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Http\JsonResponse;

/**
 * ApiResponseTrait
 *
 * Provides a set of convenience methods for returning standardised
 * JSON responses from API controllers. Ensures consistent response
 * structure across the entire DeskGuard API surface.
 */
trait ApiResponseTrait
{
    /**
     * Return a success response with data payload.
     *
     * @param mixed       $data    Response payload
     * @param string      $message Human-readable status message
     * @param int         $code    HTTP status code
     * @return JsonResponse
     */
    protected function successResponse(mixed $data, string $message = 'Operation successful.', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    /**
     * Return an error response with optional validation errors.
     *
     * @param string      $message Human-readable error message
     * @param array       $errors  Key-value pairs of field-level errors
     * @param int         $code    HTTP status code
     * @return JsonResponse
     */
    protected function errorResponse(string $message = 'Operation failed.', array $errors = [], int $code = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $code);
    }

    /**
     * Return a 201 Created response.
     *
     * @param mixed       $data    The newly created resource payload
     * @param string      $message Human-readable success message
     * @return JsonResponse
     */
    protected function createdResponse(mixed $data, string $message = 'Created successfully.'): JsonResponse
    {
        return $this->successResponse($data, $message, 201);
    }

    /**
     * Return a 204 No Content response.
     *
     * @param string $message Optional message (ignored by HTTP spec but included for consistency)
     * @return JsonResponse
     */
    protected function noContentResponse(string $message = 'No content.'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
        ], 204);
    }
}
