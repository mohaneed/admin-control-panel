<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-15 10:01
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\SecurityEvents\DTO;

use App\Modules\SecurityEvents\Enum\SecurityEventTypeEnum;
use App\Modules\SecurityEvents\Enum\SecurityEventSeverityEnum;

/**
 * Read-only DTO representing a persisted security event.
 *
 * This DTO is used exclusively for read/query operations
 * (lists, filters, dashboards, APIs).
 *
 * It MUST NOT be reused for write operations.
 */
final readonly class SecurityEventReadDTO
{
    public function __construct(
        public int $id,

        public string $actorType,
        public ?int $actorId,

        public SecurityEventTypeEnum $eventType,
        public SecurityEventSeverityEnum $severity,

        public ?string $requestId,
        public ?string $routeName,

        public ?string $ipAddress,
        public ?string $userAgent,

        /**
         * Arbitrary metadata stored with the event.
         *
         * @var array<string, mixed>
         */
        public array $metadata,

        public \DateTimeImmutable $occurredAt
    )
    {
    }
}
