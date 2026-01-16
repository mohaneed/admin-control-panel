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

namespace App\Application\Telemetry\Contracts;

use App\Application\Telemetry\DTO\TelemetryEmailHashDTO;

/**
 * Best-effort telemetry email hashing.
 *
 * IMPORTANT:
 * - Must never return reversible data.
 * - Prefer returning null instead of throwing (caller can omit metadata).
 */
interface TelemetryEmailHasherInterface
{
    public function hashEmail(string $email): ?TelemetryEmailHashDTO;
}
