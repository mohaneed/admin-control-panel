<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-10 17:10
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\Notification\DTO;

use DateTimeImmutable;

/**
 * NotificationIntentDTO
 *
 * Represents a notification intent before routing or delivery.
 * Channel-agnostic and library-ready.
 */
final readonly class NotificationIntentDTO
{
    /**
     * @param   array<string, mixed>  $context
     */
    public function __construct(
        public string $intentId,
        public string $entityType,
        public string $entityId,
        public string $notificationType,
        public string $title,
        public string $body,
        public array $context,
        public DateTimeImmutable $createdAt,
    )
    {
    }
}
