<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-15 16:53
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\Telemetry\Infrastructure\Contracts;

use App\Modules\Telemetry\DTO\TelemetryEventDTO;
use App\Modules\Telemetry\Exceptions\TelemetryStorageException;

/**
 * Low-level storage contract (implementation detail).
 *
 * - Implementations MAY throw TelemetryStorageException.
 * - Swallowing is NOT allowed here.
 */
interface TelemetryStorageInterface
{
    /**
     * @throws TelemetryStorageException
     */
    public function store(TelemetryEventDTO $event): void;
}

