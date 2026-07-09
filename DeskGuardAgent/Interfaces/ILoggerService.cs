/// <summary>
/// Defines the contract for the logging service abstraction.
/// While Serilog is used directly, this interface provides a seam for testing
/// and allows switching logging implementations without changing consuming code.
/// </summary>
namespace DeskGuardAgent.Interfaces
{
    /// <summary>
    /// Service interface for application logging operations.
    /// </summary>
    public interface ILoggerService
    {
        /// <summary>
        /// Logs a message at the Information level.
        /// Used for normal application flow events like startup, shutdown, and successful operations.
        /// </summary>
        /// <param name="message">The log message.</param>
        void LogInformation(string message);

        /// <summary>
        /// Logs a message at the Warning level.
        /// Used for recoverable issues that do not prevent the agent from functioning.
        /// </summary>
        /// <param name="message">The log message.</param>
        void LogWarning(string message);

        /// <summary>
        /// Logs a message at the Warning level with an associated exception.
        /// </summary>
        /// <param name="message">The warning message describing the issue.</param>
        /// <param name="exception">The exception that caused the warning.</param>
        void LogWarning(string message, Exception? exception = null);

        /// <summary>
        /// Logs a message at the Error level with an associated exception.
        /// Used for failures that need investigation but should not crash the agent.
        /// </summary>
        /// <param name="message">The error message describing what failed.</param>
        /// <param name="exception">The exception that caused the failure.</param>
        void LogError(string message, Exception? exception = null);

        /// <summary>
        /// Logs a message at the Debug level.
        /// Used for detailed diagnostic information useful during development and troubleshooting.
        /// </summary>
        /// <param name="message">The debug message.</param>
        void LogDebug(string message);
    }
}
