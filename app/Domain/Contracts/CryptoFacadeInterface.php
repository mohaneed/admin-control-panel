<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-09 21:54
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\Crypto\EncryptedPayloadDTO;

/**
 * Canonical Crypto Facade Interface
 *
 * This is the ONLY allowed crypto entry point for application features.
 *
 * ❌ Features must NOT:
 *   - Call OpenSSL
 *   - Call HKDF
 *   - Call KeyRotation
 *   - Manage keys or contexts
 *
 * ✔️ Features MUST use this facade only.
 */
interface CryptoFacadeInterface
{
    /* ===============================
     * Reversible Encryption
     * =============================== */

    /**
     * Encrypt reversible sensitive data.
     *
     * @param   string  $context  One of CryptoContext::* constants
     * @param   string  $plaintext
     */
    public function encrypt(
        string $context,
        string $plaintext
    ): EncryptedPayloadDTO;

    /**
     * Decrypt previously encrypted data.
     *
     * @param   string  $context  One of CryptoContext::* constants
     */
    public function decrypt(
        string $context,
        EncryptedPayloadDTO $payload
    ): string;

    /* ===============================
     * One-way Secrets (Passwords / OTP)
     * =============================== */

    /**
     * Hash a secret (password, OTP, verification code).
     */
    public function hashSecret(string $plaintext): string;

    /**
     * Verify a hashed secret.
     */
    public function verifySecret(string $plaintext, string $hash): bool;
}
