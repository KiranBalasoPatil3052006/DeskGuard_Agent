/// <summary>
/// Defines the contract for all metric collectors in the DeskGuard Agent.
/// Every collector must implement this interface to ensure consistent behavior
/// across the collection pipeline. Collectors gather specific system metrics
/// and return them as generic object results.
/// </summary>
namespace DeskGuardAgent.Interfaces
{
    /// <summary>
    /// Generic interface for system metric collectors.
    /// </summary>
    /// <typeparam name="T">The type of data returned by the collector.</typeparam>
    public interface ICollector<T>
    {
        /// <summary>
        /// Executes the collection logic and returns the collected data.
        /// Implementations should handle all exceptions internally and never throw.
        /// If collection fails, return a default/empty instance of T.
        /// </summary>
        /// <returns>A task representing the asynchronous collection operation, with the collected data.</returns>
        Task<T> CollectAsync();
    }
}
