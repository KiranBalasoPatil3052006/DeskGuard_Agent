/// <summary>
/// Manages the offline queue for storing payloads when the backend API is unreachable.
/// Payloads are serialized to JSON and stored in a local file for later retry.
/// The queue uses a thread-safe file-based storage mechanism with FIFO ordering.
/// </summary>
using DeskGuardAgent.Constants;
using DeskGuardAgent.Interfaces;
using DeskGuardAgent.Utilities;

namespace DeskGuardAgent.Services
{
    /// <summary>
    /// Service responsible for maintaining an offline queue of unsent payloads.
    /// When the API is unavailable, payloads are saved to disk and retried later.
    /// Implements IOfflineQueueService for standardized queue management.
    /// </summary>
    public class OfflineQueueService : IOfflineQueueService
    {
        private readonly ILoggerService _logger;
        private readonly string _queueFilePath;
        private readonly SemaphoreSlim _queueLock = new SemaphoreSlim(1, 1);

        /// <summary>
        /// Initializes a new instance of the OfflineQueueService class.
        /// </summary>
        /// <param name="logger">Service for logging queue operations.</param>
        public OfflineQueueService(ILoggerService logger)
        {
            _logger = logger;

            // Determine the queue file path relative to the application base directory.
            string baseDirectory = AppDomain.CurrentDomain.BaseDirectory;
            _queueFilePath = Path.Combine(baseDirectory, "Storage", "queue.json");

            // Ensure the storage directory exists.
            string? directory = Path.GetDirectoryName(_queueFilePath);
            if (!string.IsNullOrEmpty(directory) && !Directory.Exists(directory))
            {
                Directory.CreateDirectory(directory);
            }
        }

        /// <summary>
        /// Adds a payload to the offline queue for later retry.
        /// The payload is appended to the queue file with metadata.
        /// Thread-safe: uses semaphore to prevent concurrent file access.
        /// </summary>
        /// <param name="endpoint">The API endpoint this payload was destined for.</param>
        /// <param name="payload">The serialized JSON payload string.</param>
        /// <returns>A task representing the asynchronous enqueue operation.</returns>
        public async Task EnqueueAsync(string endpoint, string payload)
        {
            // Use semaphore to ensure thread-safe file access.
            await _queueLock.WaitAsync();

            try
            {
                // Read the existing queue from the file.
                var queue = await ReadQueueFromFileAsync();

                // Check if the queue has reached the maximum capacity.
                if (queue.Count >= AgentConstants.MaxQueuedPayloads)
                {
                    // Remove the oldest payload to make room for the new one.
                    queue.RemoveAt(0);
                    _logger.LogWarning("Offline queue reached maximum capacity. Oldest payload discarded.");
                }

                // Create a new queued payload entry with metadata.
                var queuedItem = new QueuedPayload
                {
                    Id = Guid.NewGuid().ToString(),
                    Endpoint = endpoint,
                    Payload = payload,
                    QueuedAt = DateTime.UtcNow,
                    RetryCount = 0
                };

                // Add the new payload to the queue.
                queue.Add(queuedItem);

                // Write the updated queue back to the file.
                await WriteQueueToFileAsync(queue);

                _logger.LogInformation($"Payload queued for offline retry. Endpoint: {endpoint}, Queue size: {queue.Count}");
            }
            catch (Exception ex)
            {
                _logger.LogError("Failed to enqueue payload for offline storage.", ex);
            }
            finally
            {
                // Release the semaphore lock.
                _queueLock.Release();
            }
        }

        /// <summary>
        /// Retrieves and removes all queued payloads from the offline queue.
        /// Returns payloads in FIFO order (oldest first).
        /// Thread-safe: ensures no new items are added during dequeue.
        /// </summary>
        /// <returns>A list of queued payload items.</returns>
        public async Task<List<QueuedPayload>> DequeueAllAsync()
        {
            // Use semaphore to ensure thread-safe file access.
            await _queueLock.WaitAsync();

            try
            {
                // Read all queued payloads from the file.
                var queue = await ReadQueueFromFileAsync();

                // Create a copy of the queue to return.
                var dequeuedItems = new List<QueuedPayload>(queue);

                // Clear the queue file since we are dequeuing everything.
                await WriteQueueToFileAsync(new List<QueuedPayload>());

                if (dequeuedItems.Count > 0)
                {
                    _logger.LogInformation($"Dequeued {dequeuedItems.Count} payloads for retry.");
                }

                return dequeuedItems;
            }
            catch (Exception ex)
            {
                _logger.LogError("Failed to dequeue payloads from offline storage.", ex);
                return new List<QueuedPayload>();
            }
            finally
            {
                // Release the semaphore lock.
                _queueLock.Release();
            }
        }

        /// <summary>
        /// Gets the current count of items in the offline queue.
        /// </summary>
        /// <returns>The number of queued payloads.</returns>
        public async Task<int> GetQueueCountAsync()
        {
            await _queueLock.WaitAsync();

            try
            {
                var queue = await ReadQueueFromFileAsync();
                return queue.Count;
            }
            catch (Exception ex)
            {
                _logger.LogError("Failed to get offline queue count.", ex);
                return 0;
            }
            finally
            {
                _queueLock.Release();
            }
        }

        /// <summary>
        /// Clears all payloads from the offline queue.
        /// Called after successfully flushing all queued items to the API.
        /// </summary>
        /// <returns>A task representing the asynchronous clear operation.</returns>
        public async Task ClearQueueAsync()
        {
            await _queueLock.WaitAsync();

            try
            {
                // Write an empty queue to the file.
                await WriteQueueToFileAsync(new List<QueuedPayload>());
                _logger.LogInformation("Offline queue cleared.");
            }
            catch (Exception ex)
            {
                _logger.LogError("Failed to clear offline queue.", ex);
            }
            finally
            {
                _queueLock.Release();
            }
        }

        /// <summary>
        /// Reads the queue from the JSON file on disk.
        /// Creates a new empty queue if the file does not exist.
        /// </summary>
        /// <returns>The list of queued payloads from the file.</returns>
        private async Task<List<QueuedPayload>> ReadQueueFromFileAsync()
        {
            return await Task.Run(() =>
            {
                try
                {
                    // Check if the queue file exists.
                    if (!File.Exists(_queueFilePath))
                    {
                        return new List<QueuedPayload>();
                    }

                    // Read the file content.
                    string json = File.ReadAllText(_queueFilePath);

                    // Check if the queue file exceeds maximum size.
                    long fileSize = new FileInfo(_queueFilePath).Length;
                    if (fileSize > AgentConstants.MaxQueueFileSizeBytes)
                    {
                        _logger.LogWarning("Queue file exceeds maximum size. Clearing queue.");
                        File.WriteAllText(_queueFilePath, "[]");
                        return new List<QueuedPayload>();
                    }

                    // Deserialize the JSON content to a list of queued payloads.
                    var queue = JsonHelper.Deserialize<List<QueuedPayload>>(json);
                    return queue ?? new List<QueuedPayload>();
                }
                catch (Exception ex)
                {
                    _logger.LogError("Failed to read queue file.", ex);
                    return new List<QueuedPayload>();
                }
            });
        }

        /// <summary>
        /// Writes the queue to the JSON file on disk.
        /// Uses atomic write pattern to prevent file corruption.
        /// </summary>
        /// <param name="queue">The list of queued payloads to write.</param>
        private async Task WriteQueueToFileAsync(List<QueuedPayload> queue)
        {
            await Task.Run(() =>
            {
                try
                {
                    // Serialize the queue to JSON.
                    string json = JsonHelper.Serialize(queue);

                    // Write to a temporary file first for atomic write.
                    string tempFile = _queueFilePath + ".tmp";
                    File.WriteAllText(tempFile, json);

                    // Replace the original file atomically.
                    if (File.Exists(_queueFilePath))
                    {
                        File.Delete(_queueFilePath);
                    }
                    File.Move(tempFile, _queueFilePath);
                }
                catch (Exception ex)
                {
                    _logger.LogError("Failed to write queue file.", ex);
                }
            });
        }
    }
}
