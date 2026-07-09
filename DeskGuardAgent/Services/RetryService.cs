/// <summary>
/// Implements a robust retry mechanism for transient operation failures.
/// Uses exponential backoff to gradually increase delay between retries,
/// preventing overwhelming the target system during outages.
/// </summary>
using DeskGuardAgent.Configuration;
using DeskGuardAgent.Interfaces;

namespace DeskGuardAgent.Services
{
    /// <summary>
    /// Service responsible for executing operations with automatic retry capabilities.
    /// Implements exponential backoff with configurable maximum attempts and base delay.
    /// Used by ApiSenderService and other components that communicate with external systems.
    /// </summary>
    public class RetryService
    {
        private readonly AgentSettings _settings;
        private readonly ILoggerService _logger;

        /// <summary>
        /// Initializes a new instance of the RetryService class.
        /// </summary>
        /// <param name="settings">Agent settings containing retry configuration values.</param>
        /// <param name="logger">Service for logging retry attempts and outcomes.</param>
        public RetryService(AgentSettings settings, ILoggerService logger)
        {
            _settings = settings;
            _logger = logger;
        }

        /// <summary>
        /// Executes an asynchronous operation with retry logic using exponential backoff.
        /// Retries the operation up to MaxRetryAttempts times if it fails.
        /// </summary>
        /// <typeparam name="T">The return type of the operation.</typeparam>
        /// <param name="operation">A delegate representing the async operation to retry.</param>
        /// <param name="operationName">A descriptive name for the operation (used in logging).</param>
        /// <returns>The result of the operation if successful; default(T) if all retries fail.</returns>
        public async Task<T?> ExecuteWithRetryAsync<T>(
            Func<Task<T>> operation,
            string operationName)
        {
            // Track the last exception for logging purposes.
            Exception? lastException = null;

            // Attempt the operation up to MaxRetryAttempts times.
            for (int attempt = 1; attempt <= _settings.MaxRetryAttempts; attempt++)
            {
                try
                {
                    // Execute the operation on this attempt.
                    T result = await operation();
                    return result;
                }
                catch (Exception ex)
                {
                    // Capture the exception for potential rethrow or logging.
                    lastException = ex;

                    // Log the failure with attempt number (message only, no stack trace).
                    // Full stack trace is logged only on final failure to avoid log bloat.
                    _logger.LogWarning(
                        $"Operation '{operationName}' failed on attempt {attempt}/{_settings.MaxRetryAttempts}. " +
                        $"Error: {ex.Message}");

                    // If this was the last attempt, do not delay - just return default.
                    if (attempt >= _settings.MaxRetryAttempts)
                        break;

                    // Calculate delay using exponential backoff.
                    // Base delay doubles with each attempt: 10s, 20s, 40s, etc.
                    int delayMs = (int)Math.Pow(2, attempt - 1) * _settings.RetryDelaySeconds * 1000;

                    // Cap the delay at a maximum of 5 minutes to avoid excessively long waits.
                    delayMs = Math.Min(delayMs, 300000);

                    _logger.LogDebug($"Retrying '{operationName}' in {delayMs / 1000} seconds...");

                    // Wait before the next retry attempt.
                    await Task.Delay(delayMs);
                }
            }

            // Log that all retry attempts were exhausted.
            _logger.LogError(
                $"Operation '{operationName}' failed after {_settings.MaxRetryAttempts} attempts.",
                lastException);

            // Return default value since all retries failed.
            return default;
        }

        /// <summary>
        /// Executes an asynchronous operation with retry logic that does not return a value.
        /// </summary>
        /// <param name="operation">A delegate representing the async operation to retry.</param>
        /// <param name="operationName">A descriptive name for the operation (used in logging).</param>
        /// <returns>True if the operation succeeded, false if all retries failed.</returns>
        public async Task<bool> ExecuteWithRetryAsync(
            Func<Task> operation,
            string operationName)
        {
            // Track the last exception for logging purposes.
            Exception? lastException = null;

            // Attempt the operation up to MaxRetryAttempts times.
            for (int attempt = 1; attempt <= _settings.MaxRetryAttempts; attempt++)
            {
                try
                {
                    // Execute the operation on this attempt.
                    await operation();
                    return true;
                }
                catch (Exception ex)
                {
                    // Capture the exception for logging.
                    lastException = ex;

                    // Log the failure with attempt number (message only, no stack trace).
                    _logger.LogWarning(
                        $"Operation '{operationName}' failed on attempt {attempt}/{_settings.MaxRetryAttempts}. " +
                        $"Error: {ex.Message}");

                    // If this was the last attempt, do not delay.
                    if (attempt >= _settings.MaxRetryAttempts)
                        break;

                    // Calculate delay using exponential backoff.
                    int delayMs = (int)Math.Pow(2, attempt - 1) * _settings.RetryDelaySeconds * 1000;
                    delayMs = Math.Min(delayMs, 300000);

                    _logger.LogDebug($"Retrying '{operationName}' in {delayMs / 1000} seconds...");

                    // Wait before the next retry attempt.
                    await Task.Delay(delayMs);
                }
            }

            // Log that all retry attempts were exhausted.
            _logger.LogError(
                $"Operation '{operationName}' failed after {_settings.MaxRetryAttempts} attempts.",
                lastException);

            return false;
        }
    }
}
