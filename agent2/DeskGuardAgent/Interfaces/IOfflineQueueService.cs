/// <summary>
/// Defines the contract for the offline queue service.
/// When the agent cannot reach the backend API, payloads are stored locally
/// in a queue file and retried later. This service manages the queue lifecycle
/// including enqueue, dequeue, and retry operations.
/// </summary>
namespace DeskGuardAgent.Interfaces
{
    /// <summary>
    /// Service interface for managing the offline payload queue.
    /// </summary>
    public interface IOfflineQueueService
    {
        /// <summary>
        /// Adds a payload to the offline queue for later retry.
        /// The payload is serialized to JSON and appended to the queue file.
        /// </summary>
        /// <param name="endpoint">The API endpoint this payload was destined for.</param>
        /// <param name="payload">The serialized JSON payload string.</param>
        /// <returns>A task representing the asynchronous enqueue operation.</returns>
        Task EnqueueAsync(string endpoint, string payload);

        /// <summary>
        /// Retrieves and removes all queued payloads from the offline queue.
        /// Returns payloads in FIFO order (oldest first).
        /// </summary>
        /// <returns>A list of queued payload items.</returns>
        Task<List<QueuedPayload>> DequeueAllAsync();

        /// <summary>
        /// Gets the current count of items in the offline queue.
        /// Used for monitoring and logging purposes.
        /// </summary>
        /// <returns>The number of queued payloads.</returns>
        Task<int> GetQueueCountAsync();

        /// <summary>
        /// Clears all payloads from the offline queue.
        /// Called after successfully flushing all queued items to the API.
        /// </summary>
        /// <returns>A task representing the asynchronous clear operation.</returns>
        Task ClearQueueAsync();
    }

    /// <summary>
    /// Represents a single payload item stored in the offline queue.
    /// Contains the target endpoint and the serialized payload data.
    /// </summary>
    public class QueuedPayload
    {
        /// <summary>
        /// Gets or sets the unique identifier for this queued payload.
        /// </summary>
        public string Id { get; set; } = Guid.NewGuid().ToString();

        /// <summary>
        /// Gets or sets the API endpoint this payload was destined for.
        /// </summary>
        public string Endpoint { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the serialized JSON payload content.
        /// </summary>
        public string Payload { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the timestamp when this payload was queued.
        /// </summary>
        public DateTime QueuedAt { get; set; } = DateTime.UtcNow;

        /// <summary>
        /// Gets or sets the number of retry attempts made for this payload.
        /// </summary>
        public int RetryCount { get; set; } = 0;
    }
}
