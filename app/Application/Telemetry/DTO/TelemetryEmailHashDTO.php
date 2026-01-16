<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-16 05:40
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Application\Telemetry\DTO;

/**
 * Telemetry email hash result (non-reversible).
 *
 * - hash: hex HMAC-SHA256 output (stable)
 * - keyId: active rotation key identifier used to derive the HMAC key
 * - algo: version marker for future-proofing
 */
final readonly class TelemetryEmailHashDTO
{
    public function __construct(
        public string $hash,
        public string $keyId,
        public string $algo
    )
    {
    }
}
