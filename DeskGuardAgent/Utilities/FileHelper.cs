/// <summary>
/// Provides file system utility methods for reading, writing, and managing files.
/// Centralizes file I/O operations with proper error handling and thread safety.
/// Primarily used by the offline queue service for persistent storage.
/// </summary>
using System.Text;

namespace DeskGuardAgent.Utilities
{
    /// <summary>
    /// Static helper class for file system operations with built-in error handling.
    /// Ensures atomic file writes and provides safe read/write abstractions.
    /// </summary>
    public static class FileHelper
    {
        /// <summary>
        /// The default encoding used for file operations. UTF-8 without BOM for maximum compatibility.
        /// </summary>
        private static readonly Encoding DefaultEncoding = new UTF8Encoding(false);

        /// <summary>
        /// The file access lock object for thread-safe file operations.
        /// Ensures only one thread writes to the queue file at a time.
        /// </summary>
        private static readonly object _fileLock = new object();

        /// <summary>
        /// Writes text content to a file atomically using a temporary file and rename pattern.
        /// This prevents file corruption if the write operation is interrupted.
        /// </summary>
        /// <param name="filePath">The full path to the file to write.</param>
        /// <param name="content">The text content to write to the file.</param>
        /// <returns>True if the write succeeded, false otherwise.</returns>
        public static bool WriteFile(string filePath, string content)
        {
            // Ensure the directory exists before writing.
            string? directory = Path.GetDirectoryName(filePath);
            if (!string.IsNullOrEmpty(directory) && !Directory.Exists(directory))
            {
                Directory.CreateDirectory(directory);
            }

            lock (_fileLock)
            {
                try
                {
                    // Write to a temporary file first to ensure atomicity.
                    string tempFile = filePath + ".tmp";
                    File.WriteAllText(tempFile, content, DefaultEncoding);

                    // Replace the original file with the temp file (atomic on same volume).
                    if (File.Exists(filePath))
                    {
                        File.Delete(filePath);
                    }
                    File.Move(tempFile, filePath);

                    return true;
                }
                catch (Exception)
                {
                    // Logged at caller level; return false to indicate failure.
                    return false;
                }
            }
        }

        /// <summary>
        /// Reads the text content of a file if it exists.
        /// </summary>
        /// <param name="filePath">The full path to the file to read.</param>
        /// <returns>The file content as a string, or null if the file does not exist or cannot be read.</returns>
        public static string? ReadFile(string filePath)
        {
            try
            {
                // Check if the file exists before attempting to read.
                if (!File.Exists(filePath))
                {
                    return null;
                }

                // Read and return the file contents.
                return File.ReadAllText(filePath, DefaultEncoding);
            }
            catch (Exception)
            {
                // Return null if the file cannot be read.
                return null;
            }
        }

        /// <summary>
        /// Deletes a file if it exists. Does not throw if the file does not exist.
        /// </summary>
        /// <param name="filePath">The full path to the file to delete.</param>
        /// <returns>True if the file was deleted (or did not exist), false on error.</returns>
        public static bool DeleteFile(string filePath)
        {
            try
            {
                // Only attempt deletion if the file exists.
                if (File.Exists(filePath))
                {
                    File.Delete(filePath);
                }
                return true;
            }
            catch (Exception)
            {
                return false;
            }
        }

        /// <summary>
        /// Gets the file size in bytes. Returns -1 if the file does not exist.
        /// </summary>
        /// <param name="filePath">The full path to the file.</param>
        /// <returns>The file size in bytes, or -1 if the file does not exist.</returns>
        public static long GetFileSize(string filePath)
        {
            try
            {
                // Check if the file exists before getting its size.
                if (!File.Exists(filePath))
                {
                    return -1;
                }

                // Get and return the file size.
                return new FileInfo(filePath).Length;
            }
            catch (Exception)
            {
                return -1;
            }
        }

        /// <summary>
        /// Ensures that a directory exists, creating it if necessary.
        /// </summary>
        /// <param name="directoryPath">The directory path to ensure exists.</param>
        /// <returns>True if the directory exists or was created, false on error.</returns>
        public static bool EnsureDirectoryExists(string directoryPath)
        {
            try
            {
                // Create the directory if it does not already exist.
                if (!Directory.Exists(directoryPath))
                {
                    Directory.CreateDirectory(directoryPath);
                }
                return true;
            }
            catch (Exception)
            {
                return false;
            }
        }
    }
}
