/// <summary>
/// Provides encryption and decryption utilities for securing sensitive data.
/// Uses .NET's built-in cryptographic libraries for secure string encryption.
/// Primarily used for encrypting API keys and sensitive configuration values at rest.
/// </summary>
using System.Security.Cryptography;
using System.Text;

namespace DeskGuardAgent.Utilities
{
    /// <summary>
    /// Static helper class for encryption operations using AES-256.
    /// Provides methods to encrypt and decrypt strings with a machine-specific key.
    /// </summary>
    public static class EncryptionHelper
    {
        /// <summary>
        /// The size of the AES encryption key in bits. Using 256-bit for strong security.
        /// </summary>
        private const int KeySize = 256;

        /// <summary>
        /// The size of the salt used in key derivation in bytes.
        /// </summary>
        private const int SaltSize = 16;

        /// <summary>
        /// The number of iterations for PBKDF2 key derivation.
        /// Higher values increase security but reduce performance.
        /// 100,000 iterations is the current OWASP recommendation.
        /// </summary>
        private const int DerivationIterations = 100000;

        /// <summary>
        /// Encrypts a plain text string using AES-256 encryption with a password-derived key.
        /// </summary>
        /// <param name="plainText">The plain text string to encrypt.</param>
        /// <param name="password">The password used to derive the encryption key.</param>
        /// <returns>Base64-encoded encrypted string containing salt, IV, and ciphertext.</returns>
        public static string Encrypt(string plainText, string password)
        {
            // Convert the plain text to bytes for encryption.
            byte[] plainBytes = Encoding.UTF8.GetBytes(plainText);

            // Generate a random salt for key derivation to prevent rainbow table attacks.
            byte[] salt = RandomNumberGenerator.GetBytes(SaltSize);

            // Derive a strong key from the password using PBKDF2.
            byte[] key = DeriveKey(password, salt, DerivationIterations);

            using (Aes aes = Aes.Create())
            {
                // Set the derived key for AES encryption.
                aes.Key = key;

                // Generate a random IV (Initialization Vector) for each encryption.
                aes.GenerateIV();
                byte[] iv = aes.IV;

                using (MemoryStream memoryStream = new MemoryStream())
                {
                    // Write the salt to the output first (needed for decryption).
                    memoryStream.Write(salt, 0, salt.Length);

                    // Write the IV to the output (needed for decryption).
                    memoryStream.Write(iv, 0, iv.Length);

                    using (CryptoStream cryptoStream = new CryptoStream(
                        memoryStream, aes.CreateEncryptor(), CryptoStreamMode.Write))
                    {
                        // Encrypt the plain text bytes.
                        cryptoStream.Write(plainBytes, 0, plainBytes.Length);
                        cryptoStream.FlushFinalBlock();
                    }

                    // Return the complete encrypted payload as a Base64 string.
                    return Convert.ToBase64String(memoryStream.ToArray());
                }
            }
        }

        /// <summary>
        /// Decrypts a previously encrypted string back to plain text.
        /// </summary>
        /// <param name="cipherText">The Base64-encoded encrypted string containing salt, IV, and ciphertext.</param>
        /// <param name="password">The password used to derive the decryption key.</param>
        /// <returns>The decrypted plain text string.</returns>
        public static string Decrypt(string cipherText, string password)
        {
            // Convert the Base64 cipher text back to bytes.
            byte[] cipherBytes = Convert.FromBase64String(cipherText);

            using (MemoryStream memoryStream = new MemoryStream(cipherBytes))
            {
                // Read the salt from the beginning of the encrypted data.
                byte[] salt = new byte[SaltSize];
                memoryStream.Read(salt, 0, SaltSize);

                // Read the IV that follows the salt.
                byte[] iv = new byte[16];
                memoryStream.Read(iv, 0, 16);

                // Derive the same key using the stored salt and password.
                byte[] key = DeriveKey(password, salt, DerivationIterations);

                using (Aes aes = Aes.Create())
                {
                    aes.Key = key;
                    aes.IV = iv;

                    using (CryptoStream cryptoStream = new CryptoStream(
                        memoryStream, aes.CreateDecryptor(), CryptoStreamMode.Read))
                    {
                        using (StreamReader reader = new StreamReader(cryptoStream))
                        {
                            // Read and return the decrypted text.
                            return reader.ReadToEnd();
                        }
                    }
                }
            }
        }

        /// <summary>
        /// Derives an AES key from a password using PBKDF2 with HMAC-SHA256.
        /// </summary>
        /// <param name="password">The password to derive the key from.</param>
        /// <param name="salt">The cryptographic salt to prevent rainbow table attacks.</param>
        /// <param name="iterations">The number of PBKDF2 iterations.</param>
        /// <returns>A derived key of KeySize bits.</returns>
        private static byte[] DeriveKey(string password, byte[] salt, int iterations)
        {
            // Use PBKDF2 to derive a secure key from the password.
            return Rfc2898DeriveBytes.Pbkdf2(
                Encoding.UTF8.GetBytes(password),
                salt,
                iterations,
                HashAlgorithmName.SHA256,
                KeySize / 8);
        }
    }
}
