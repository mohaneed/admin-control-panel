<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-15 09:37
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\SecurityEvents\Infrastructure\Mysql;

use App\Modules\SecurityEvents\Contracts\SecurityEventLoggerInterface;
use App\Modules\SecurityEvents\Infrastructure\Contracts\SecurityEventStorageInterface;
use App\Modules\SecurityEvents\DTO\SecurityEventDTO;
use PDO;
use Throwable;

/**
 * MySQL-based repository for persisting security events.
 *
 * This implementation is best-effort and MUST NOT throw
 * exceptions that affect the main execution flow.
 */
final readonly class SecurityEventLoggerMysqlRepository implements
    SecurityEventLoggerInterface,
    SecurityEventStorageInterface
{
    public function __construct(
        private PDO $pdo
    )
    {
    }

    /**
     * {@inheritdoc}
     */
    public function log(SecurityEventDTO $event): void
    {
        $this->store($event);
    }

    /**
     * {@inheritdoc}
     */
    public function store(SecurityEventDTO $event): void
    {
        try {
            $stmt = $this->pdo->prepare(
                <<<SQL
                INSERT INTO security_events (
                    event_type,
                    severity,
                    actor_admin_id,
                    request_id,
                    ip_address,
                    user_agent,
                    route_name,
                    metadata,
                    occurred_at
                ) VALUES (
                    :event_type,
                    :severity,
                    :actor_admin_id,
                    :request_id,
                    :ip_address,
                    :user_agent,
                    :route_name,
                    :metadata,
                    :occurred_at
                )
                SQL
            );

            $stmt->execute([
                'event_type'     => $event->eventType->value,
                'severity'       => $event->severity->value,
                'actor_admin_id' => $event->actorAdminId,
                'request_id'     => $event->requestId,
                'ip_address'     => $event->ipAddress,
                'user_agent'     => $event->userAgent,
                'route_name'     => $event->routeName,
                'metadata'       => json_encode(
                    $event->metadata,
                    JSON_THROW_ON_ERROR
                ),
                'occurred_at'    => ($event->occurredAt ?? new \DateTimeImmutable())
                    ->format('Y-m-d H:i:s'),
            ]);
        } catch (Throwable) {
            /**
             * Best-effort logging:
             * - Swallow all exceptions
             * - Never break authentication / authorization flow
             *
             * Optional:
             * - PSR-3 logger hook can be added later
             */
        }
    }
}
