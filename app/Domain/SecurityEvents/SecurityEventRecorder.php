<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-15 09:41
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Domain\SecurityEvents;

use App\Modules\SecurityEvents\Contracts\SecurityEventLoggerInterface;
use App\Modules\SecurityEvents\Contracts\SecurityEventContextInterface;
use App\Modules\SecurityEvents\DTO\SecurityEventDTO;
use App\Modules\SecurityEvents\Enum\SecurityEventTypeEnum;
use App\Modules\SecurityEvents\Enum\SecurityEventSeverityEnum;

/**
 * Application-layer recorder for security events.
 *
 * Responsibilities:
 * - Build SecurityEventDTO from application context
 * - Enforce correct usage of event types & severity
 * - Delegate persistence to SecurityEventLoggerInterface
 *
 * This class MUST NOT:
 * - Perform persistence logic
 * - Access HTTP / Request objects
 * - Throw exceptions that break the main flow
 */
final readonly class SecurityEventRecorder
{
    public function __construct(
        private SecurityEventLoggerInterface $logger
    )
    {
    }

    /**
     * Record a security event.
     *
     * @param   SecurityEventContextInterface  $context
     * @param   SecurityEventTypeEnum          $eventType
     * @param   SecurityEventSeverityEnum      $severity
     * @param   int|null                       $actorAdminId
     * @param   array<string, mixed>           $metadata
     */
    public function record(
        SecurityEventContextInterface $context,
        SecurityEventTypeEnum $eventType,
        SecurityEventSeverityEnum $severity,
        ?int $actorAdminId = null,
        array $metadata = []
    ): void
    {
        $event = new SecurityEventDTO(
            eventType   : $eventType,
            severity    : $severity,
            actorAdminId: $actorAdminId,
            requestId   : $context->getRequestId(),
            ipAddress   : $context->getIpAddress(),
            userAgent   : $context->getUserAgent(),
            routeName   : $context->getRouteName(),
            metadata    : $metadata
        );

        // Best-effort delegation
        $this->logger->log($event);
    }
}
