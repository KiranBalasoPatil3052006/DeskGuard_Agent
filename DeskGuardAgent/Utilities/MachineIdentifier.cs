/// <summary>
/// Provides machine identification utilities for generating a unique and stable
/// identifier for the local machine. Uses hardware-based identifiers to create
/// a persistent machine ID that survives OS reinstalls.
/// </summary>
using System.Management;
using System.Security.Cryptography;
using System.Text;

namespace DeskGuardAgent.Utilities
{
    /// <summary>
    /// Static helper class for generating and retrieving machine-specific identifiers.
    /// Combines multiple hardware identifiers to create a unique, stable machine fingerprint.
    /// </summary>
    public static class MachineIdentifier
    {
        /// <summary>
        /// Generates a unique machine identifier using a combination of hardware components.
        /// Uses motherboard serial number, processor ID, and disk drive serial number
        /// to create a consistent hash that identifies this specific machine.
        /// </summary>
        /// <returns>A hex string representing the unique machine identifier (64 characters).</returns>
        public static string GenerateMachineId()
        {
            // Collect hardware identifiers from various WMI sources.
            string motherboardSerial = GetWmiProperty("Win32_BaseBoard", "SerialNumber");
            string processorId = GetWmiProperty("Win32_Processor", "ProcessorId");
            string diskSerial = GetWmiProperty("Win32_DiskDrive", "SerialNumber", 0);

            // Combine all identifiers into a single string for hashing.
            string combined = $"{motherboardSerial}{processorId}{diskSerial}";

            // If no hardware info is available, fall back to machine name and OS serial.
            if (string.IsNullOrWhiteSpace(combined))
            {
                combined = $"{Environment.MachineName}{Environment.OSVersion}";
            }

            // Create a SHA256 hash of the combined identifiers for a fixed-length ID.
            byte[] hashBytes = SHA256.HashData(Encoding.UTF8.GetBytes(combined));

            // Convert the hash to a lowercase hex string.
            return Convert.ToHexString(hashBytes).ToLowerInvariant();
        }

        /// <summary>
        /// Retrieves a WMI property value from the specified class and property name.
        /// </summary>
        /// <param name="wmiClass">The WMI class name to query.</param>
        /// <param name="propertyName">The property name to retrieve.</param>
        /// <param name="index">The index of the result to use (for multi-instance classes).</param>
        /// <returns>The property value as a string, or empty string if not found.</returns>
        private static string GetWmiProperty(string wmiClass, string propertyName, int index = 0)
        {
            try
            {
                // Create a WMI query to select the requested property.
                using (ManagementObjectSearcher searcher = new ManagementObjectSearcher(
                    $"SELECT {propertyName} FROM {wmiClass}"))
                {
                    using (ManagementObjectCollection results = searcher.Get())
                    {
                        int currentIndex = 0;

                        // Iterate through results to find the one at the requested index.
                        foreach (ManagementObject obj in results)
                        {
                            if (currentIndex == index)
                            {
                                // Return the property value or empty string if null.
                                return obj[propertyName]?.ToString()?.Trim() ?? string.Empty;
                            }
                            currentIndex++;
                        }
                    }
                }
            }
            catch (Exception)
            {
                // Suppress WMI exceptions - return empty string if property cannot be read.
                // This ensures the agent continues working even without WMI access.
            }

            return string.Empty;
        }
    }
}
