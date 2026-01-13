<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-13 09:58
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Application\Crypto\DTO;

/**
 * EncryptedTotpSecretDTO
 *
 * Encrypted TOTP seed DTO.
 *
 * STATUS: LOCKED
 */
final readonly class EncryptedTotpSecretDTO
{
    public function __construct(
        public string $ciphertext,
        public string $iv,
        public string $tag,
        public string $keyId
    )
    {
    }
}
