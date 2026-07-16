/// <summary>
/// Collects network adapter status and traffic metrics from the local machine.
/// Uses WMI Win32_NetworkAdapter and Win32_NetworkAdapterConfiguration classes
/// to enumerate active network interfaces and their properties.
/// </summary>
using System.Management;
using System.Net.NetworkInformation;
using DeskGuardAgent.Interfaces;
using DeskGuardAgent.Models;

namespace DeskGuardAgent.Collectors
{
    /// <summary>
    /// Collector responsible for retrieving network adapter metrics including
    /// connection status, IP addresses, MAC addresses, and traffic statistics.
    /// Implements ICollector&lt;List&lt;NetworkInfo&gt;&gt; to support multiple adapters.
    /// </summary>
    public class NetworkCollector : ICollector<List<NetworkInfo>>
    {
        private readonly ILoggerService _logger;

        /// <summary>
        /// Initializes a new instance of the NetworkCollector class.
        /// </summary>
        /// <param name="logger">Service for logging collector operations and errors.</param>
        public NetworkCollector(ILoggerService logger)
        {
            _logger = logger;
        }

        /// <summary>
        /// Executes network metric collection for all active adapters.
        /// Enumerates network interfaces, gets IP configuration, and traffic stats.
        /// Never throws - all exceptions are caught and logged.
        /// </summary>
        /// <returns>A list of NetworkInfo objects, one per active adapter.</returns>
        public async Task<List<NetworkInfo>> CollectAsync()
        {
            _logger.LogDebug("Starting network metrics collection.");

            var networkList = new List<NetworkInfo>();

            try
            {
                // Get all network interfaces on the system.
                NetworkInterface[] interfaces = NetworkInterface.GetAllNetworkInterfaces();

                await Task.Run(() =>
                {
                    foreach (NetworkInterface ni in interfaces)
                    {
                        // Skip loopback and tunnel adapters (they are not physical).
                        if (ni.NetworkInterfaceType == NetworkInterfaceType.Loopback ||
                            ni.NetworkInterfaceType == NetworkInterfaceType.Tunnel)
                            continue;

                        var networkInfo = new NetworkInfo
                        {
                            AdapterName = ni.Name,
                            AdapterType = ni.NetworkInterfaceType.ToString(),
                            MacAddress = ni.GetPhysicalAddress().ToString(),
                            IsConnected = ni.OperationalStatus == OperationalStatus.Up,
                            ConnectionSpeedMbps = ni.Speed > 0 ? ni.Speed / 1000000 : null,
                            CollectedAt = DateTime.UtcNow
                        };

                        // Get IP addresses from the adapter's configuration.
                        try
                        {
                            IPInterfaceProperties ipProps = ni.GetIPProperties();

                            // Get IPv4 address.
                            foreach (UnicastIPAddressInformation ip in ipProps.UnicastAddresses)
                            {
                                if (ip.Address.AddressFamily == System.Net.Sockets.AddressFamily.InterNetwork)
                                {
                                    networkInfo.IpAddressV4 = ip.Address.ToString();
                                    break;
                                }
                            }

                            // Get IPv6 address.
                            foreach (UnicastIPAddressInformation ip in ipProps.UnicastAddresses)
                            {
                                if (ip.Address.AddressFamily == System.Net.Sockets.AddressFamily.InterNetworkV6)
                                {
                                    networkInfo.IpAddressV6 = ip.Address.ToString();
                                    break;
                                }
                            }
                        }
                        catch (Exception ex)
                        {
                            _logger.LogWarning($"Failed to get IP configuration for adapter {ni.Name}.", ex);
                        }

                        // Get traffic statistics using WMI.
                        try
                        {
                            using (ManagementObjectSearcher searcher = new ManagementObjectSearcher(
                                $"SELECT BytesReceivedPersec, BytesSentPersec FROM Win32_PerfRawData_Tcpip_NetworkInterface WHERE Name = '{ni.Name.Replace("'", "''")}'"))
                            {
                                using (ManagementObjectCollection results = searcher.Get())
                                {
                                    foreach (ManagementObject obj in results)
                                    {
                                        object rawRecv = obj["BytesReceivedPersec"];
                                        object rawSent = obj["BytesSentPersec"];
                                        if (rawRecv != null && rawRecv != DBNull.Value)
                                            networkInfo.BytesReceived = Convert.ToInt64(rawRecv);
                                        if (rawSent != null && rawSent != DBNull.Value)
                                            networkInfo.BytesSent = Convert.ToInt64(rawSent);
                                    }
                                }
                            }
                        }
                        catch (Exception ex)
                        {
                            _logger.LogWarning($"Failed to get traffic stats for adapter {ni.Name}.", ex);
                        }

                        networkList.Add(networkInfo);
                    }
                });

                _logger.LogDebug($"Network collection complete. Found {networkList.Count} adapters.");
            }
            catch (Exception ex)
            {
                _logger.LogError("Failed to collect network metrics.", ex);
            }

            return networkList;
        }
    }
}
