<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-11 20:00
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\ActivityLog\Service;

use App\Modules\ActivityLog\Contracts\ActivityActionInterface;
use App\Modules\ActivityLog\DTO\ActivityLogDTO;
use App\Modules\ActivityLog\Contracts\ActivityLogWriterInterface;
use DateTimeImmutable;

final readonly class ActivityLogService
{
    public function __construct(
        private ActivityLogWriterInterface $writer,
    )
    {
    }

    /**
     * Log an activity event.
     *
     * @param   ActivityActionInterface|string  $action
     * @param   string                          $actorType
     * @param   int|null                        $actorId
     * @param   string|null                     $entityType
     * @param   int|null                        $entityId
     * @param   array<string, mixed>|null       $metadata
     * @param   string|null                     $ipAddress
     * @param   string|null                     $userAgent
     * @param   string|null                     $requestId
     * @param   DateTimeImmutable|null          $occurredAt
     */
    public function log(
        ActivityActionInterface|string $action,

        string $actorType,
        ?int $actorId,

        ?string $entityType = null,
        ?int $entityId = null,

        ?array $metadata = null,

        ?string $ipAddress = null,
        ?string $userAgent = null,

        ?string $requestId = null,

        ?DateTimeImmutable $occurredAt = null,
    ): void
    {
        // Resolve action string
        $actionValue = $action instanceof ActivityActionInterface
            ? $action->toString()
            : $action;

        // Enrich timestamp if missing
        $occurredAt ??= new DateTimeImmutable();

        $dto = new ActivityLogDTO(
            action    : $actionValue,

            actorType : $actorType,
            actorId   : $actorId,

            entityType: $entityType,
            entityId  : $entityId,

            metadata  : $metadata,

            ipAddress : $ipAddress,
            userAgent : $userAgent,

            requestId : $requestId,

            occurredAt: $occurredAt,
        );

        // Fail-open: activity logging must not break the flow
        try {
            $this->writer->write($dto);
        } catch (\Throwable $e) {
            // Intentionally swallowed
            // Activity Log must NEVER break user flow
        }
    }
}
