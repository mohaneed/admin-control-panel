<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-13 09:52
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Application\Crypto;

use App\Application\Crypto\DTO\EncryptedTotpSecretDTO;

/**
 * TotpSecretCryptoServiceInterface
 *
 * CANONICAL AUTHORITY for TOTP secret encryption at rest.
 *
 * This interface defines the ONLY allowed access path for:
 * - Encrypting TOTP seeds
 * - Decrypting TOTP seeds
 *
 * HARD RULES:
 * - No plaintext TOTP secret storage is allowed.
 * - Repositories MUST NOT implement their own crypto.
 *
 * STATUS: LOCKED (Skeleton Phase)
 */
interface TotpSecretCryptoServiceInterface
{
    /**
     * Encrypt a TOTP seed.
     *
     * @param   string  $plainSeed
     *
     * @return EncryptedTotpSecretDTO Encrypted TOTP seed DTO
     */
    public function encryptTotpSeed(string $plainSeed): EncryptedTotpSecretDTO;

    /**
     * Decrypt a TOTP seed.
     *
     * @param   EncryptedTotpSecretDTO  $encryptedSeed
     *
     * @return string Plain TOTP seed
     */
    public function decryptTotpSeed(EncryptedTotpSecretDTO $encryptedSeed): string;
}
