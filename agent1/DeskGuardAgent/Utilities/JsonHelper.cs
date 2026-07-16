/// <summary>
/// Provides JSON serialization and deserialization helper methods.
/// Uses Newtonsoft.Json for flexible serialization with configurable settings.
/// Centralizes JSON handling to ensure consistent formatting across the application.
/// </summary>
using Newtonsoft.Json;
using Newtonsoft.Json.Serialization;

namespace DeskGuardAgent.Utilities
{
    /// <summary>
    /// Static helper class for JSON operations using Newtonsoft.Json.
    /// Configures camelCase naming convention for API compatibility.
    /// </summary>
    public static class JsonHelper
    {
        /// <summary>
        /// Default JSON serializer settings used throughout the application.
        /// Uses camelCase naming convention for compatibility with JavaScript/TypeScript APIs.
        /// Ignores null values to reduce payload size.
        /// Uses indented formatting for local file storage readability.
        /// </summary>
        private static readonly JsonSerializerSettings _settings = new JsonSerializerSettings
        {
            // Use camelCase for property names to match standard API conventions.
            ContractResolver = new CamelCasePropertyNamesContractResolver(),
            // Ignore null properties to reduce payload size during transmission.
            NullValueHandling = NullValueHandling.Ignore,
            // Format dates in ISO 8601 format for cross-platform compatibility.
            DateFormatString = "yyyy-MM-ddTHH:mm:ss.fffZ",
            // Use indented formatting for readability when storing locally.
            Formatting = Formatting.Indented
        };

        /// <summary>
        /// JSON serializer settings with compact formatting (no indentation).
        /// Used for API transmission to minimize payload size.
        /// </summary>
        private static readonly JsonSerializerSettings _compactSettings = new JsonSerializerSettings
        {
            ContractResolver = new CamelCasePropertyNamesContractResolver(),
            NullValueHandling = NullValueHandling.Ignore,
            DateFormatString = "yyyy-MM-ddTHH:mm:ss.fffZ",
            // Compact formatting to reduce payload size for network transmission.
            Formatting = Formatting.None
        };

        /// <summary>
        /// Serializes an object to a JSON string using default indented formatting.
        /// </summary>
        /// <param name="obj">The object to serialize.</param>
        /// <returns>A JSON string representation of the object.</returns>
        public static string Serialize(object obj)
        {
            // Convert object to indented JSON string for readability.
            return JsonConvert.SerializeObject(obj, _settings);
        }

        /// <summary>
        /// Serializes an object to a compact JSON string (no indentation).
        /// Used for API payloads where size matters.
        /// </summary>
        /// <param name="obj">The object to serialize.</param>
        /// <returns>A compact JSON string representation of the object.</returns>
        public static string SerializeCompact(object obj)
        {
            // Convert object to compact JSON string for network transmission.
            return JsonConvert.SerializeObject(obj, _compactSettings);
        }

        /// <summary>
        /// Deserializes a JSON string to the specified type.
        /// </summary>
        /// <typeparam name="T">The target type to deserialize to.</typeparam>
        /// <param name="json">The JSON string to deserialize.</param>
        /// <returns>The deserialized object, or default(T) if deserialization fails.</returns>
        public static T? Deserialize<T>(string json)
        {
            try
            {
                // Attempt to parse the JSON string into the target type.
                return JsonConvert.DeserializeObject<T>(json, _settings);
            }
            catch (JsonException)
            {
                // Return default value if JSON is invalid.
                return default;
            }
        }

        /// <summary>
        /// Attempts to deserialize a JSON string and returns a boolean indicating success.
        /// Useful for non-throwing deserialization patterns.
        /// </summary>
        /// <typeparam name="T">The target type to deserialize to.</typeparam>
        /// <param name="json">The JSON string to deserialize.</param>
        /// <param name="result">The deserialized object if successful, otherwise default.</param>
        /// <returns>True if deserialization succeeded, false otherwise.</returns>
        public static bool TryDeserialize<T>(string json, out T? result)
        {
            try
            {
                // Attempt to parse the JSON string.
                result = JsonConvert.DeserializeObject<T>(json, _settings);
                return result != null;
            }
            catch (JsonException)
            {
                // Set result to default and return false on failure.
                result = default;
                return false;
            }
        }
    }
}
