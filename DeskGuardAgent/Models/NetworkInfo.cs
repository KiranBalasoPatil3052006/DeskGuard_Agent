/// <summary>
/// Represents network adapter status and traffic metrics.
/// Collects data for each active network interface on the system.
/// </summary>
namespace DeskGuardAgent.Models
{
    public class NetworkInfo
    {
        /// <summary>
        /// Gets or sets the name of the network adapter.
        /// Example: "Ethernet", "Wi-Fi", "vEthernet (Default Switch)".
        /// </summary>
        public string AdapterName { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets whether the network adapter is currently connected and has connectivity.
        /// </summary>
        public bool IsConnected { get; set; }

        /// <summary>
        /// Gets or sets the IPv4 address assigned to this adapter.
        /// </summary>
        public string IpAddressV4 { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the IPv6 address assigned to this adapter, if available.
        /// </summary>
        public string IpAddressV6 { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the MAC address (physical address) of the adapter.
        /// Format: "XX-XX-XX-XX-XX-XX".
        /// </summary>
        public string MacAddress { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the connection speed in Mbps.
        /// </summary>
        public long? ConnectionSpeedMbps { get; set; }

        /// <summary>
        /// Gets or sets the bytes received since the adapter started.
        /// </summary>
        public long BytesReceived { get; set; }

        /// <summary>
        /// Gets or sets the bytes sent since the adapter started.
        /// </summary>
        public long BytesSent { get; set; }

        /// <summary>
        /// Gets or sets the adapter type (e.g., "Ethernet", "Wireless", "Virtual").
        /// </summary>
        public string AdapterType { get; set; } = string.Empty;

        /// <summary>
        /// Gets or sets the timestamp when the data was collected.
        /// </summary>
        public DateTime CollectedAt { get; set; } = DateTime.UtcNow;
    }
}
