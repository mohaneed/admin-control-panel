<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-16 11:32
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\Telemetry\Contracts;

use App\Modules\Telemetry\DTO\TelemetryTraceListQueryDTO;
use App\Modules\Telemetry\DTO\TelemetryTraceReadDTO;

interface TelemetryTraceReaderInterface
{
    /**
     * @return TelemetryTraceReadDTO[]
     */
    public function paginate(
        TelemetryTraceListQueryDTO $query,
        int $page,
        int $perPage
    ): array;

    public function count(TelemetryTraceListQueryDTO $query): int;

    public function findById(int $id): ?TelemetryTraceReadDTO;
}

