/// <summary>
/// Defines the contract for the central monitoring orchestration service.
/// The monitoring service coordinates all collectors, manages the collection cycle,
/// and triggers data transmission to the backend API.
/// </summary>
namespace DeskGuardAgent.Interfaces
{
    /// <summary>
    /// Service interface for orchestrating the complete monitoring lifecycle.
    /// </summary>
    public interface IMonitoringService
    {
        /// <summary>
        /// Executes a single complete collection cycle.
        /// Runs all enabled collectors, packages the results into a health payload,
        /// and sends the payload to the backend API.
        /// </summary>
        /// <returns>A task representing the asynchronous collection cycle.</returns>
        Task ExecuteCollectionCycleAsync();

        /// <summary>
        /// Starts the monitoring service, beginning periodic collection cycles.
        /// Called once when the service starts up.
        /// </summary>
        /// <param name="cancellationToken">Cancellation token to stop the monitoring loop.</param>
        /// <returns>A task representing the asynchronous monitoring operation.</returns>
        Task StartAsync(CancellationToken cancellationToken);

        /// <summary>
        /// Stops the monitoring service gracefully.
        /// Called when the service is shutting down.
        /// </summary>
        /// <returns>A task representing the asynchronous stop operation.</returns>
        Task StopAsync();

        /// <summary>
        /// Sends a device sync payload with the current set of connected peripherals.
        /// </summary>
        /// <param name="devices">The list of connected peripherals.</param>
        /// <returns>A task representing the asynchronous operation.</returns>
        Task SendDeviceSyncAsync(List<Models.PeripheralInfo> devices);
    }
}
