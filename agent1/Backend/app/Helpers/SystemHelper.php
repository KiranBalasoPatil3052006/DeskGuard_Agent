<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Support\Str;

/**
 * Class SystemHelper
 *
 * Static utility class that provides commonly used system-level helpers
 * such as UUID generation, token creation, byte formatting, and JSON
 * validation. All methods are stateless and callable without instantiation.
 *
 * @package App\Helpers
 */
class SystemHelper
{
    /**
     * Generate a version 4 (random) UUID string.
     *
     * @return string A 36-character UUID v4, e.g. "f47ac10b-58cc-4372-a567-0e02b2c3d479"
     */
    public static function generateMachineUid(): string
    {
        return Str::uuid()->toString();
    }

    /**
     * Generate a cryptographically secure 32-character hexadecimal activation token.
     *
     * @return string A 32-character random hex string
     */
    public static function generateActivationToken(): string
    {
        return Str::random(32);
    }

    /**
     * Generate a cryptographically secure 64-character hexadecimal API token.
     *
     * @return string A 64-character random hex string
     */
    public static function generateApiToken(): string
    {
        return Str::random(64);
    }

    /**
     * Convert a byte value into a human-readable format (e.g., "1.23 MB").
     *
     * @param  int  $bytes     The number of bytes to format
     * @param  int  $precision Number of decimal places (default 2)
     * @return string The formatted string with the appropriate unit suffix
     */
    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        $bytes = max($bytes, 0);
        $pow   = (int) floor(($bytes > 0 ? log($bytes) : 0) / log(1024));
        $pow   = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Safely calculate a percentage, returning 0.0 when the total is zero
     * to avoid a division-by-zero error.
     *
     * @param  int|float $value The numerator (partial amount)
     * @param  int|float $total The denominator (total amount)
     * @return float The calculated percentage (0–100)
     */
    public static function calculatePercentage(int|float $value, int|float $total): float
    {
        if ($total == 0) {
            return 0.0;
        }

        return ($value / $total) * 100;
    }

    /**
     * Determine whether a given string contains valid JSON.
     *
     * @param  string $string The string to test
     * @return bool True if the string decodes as valid JSON, false otherwise
     */
    public static function isJson(string $string): bool
    {
        if ($string === '') {
            return false;
        }

        json_decode($string);

        return json_last_error() === JSON_ERROR_NONE;
    }
}
