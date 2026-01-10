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

use App\Modules\Notification\Enum\NotificationChannel;
use DateTimeImmutable;

/**
 * NotificationDeliveryDTO
 *
 * Represents a single delivery instruction for one channel.
 */
final readonly class NotificationDeliveryDTO
{
    /**
     * @param   array<string, mixed>  $channelMeta
     */
    public function __construct(
        public string $intentId,
        public NotificationChannel $channel,
        public string $entityType,
        public string $entityId,
        public string $recipient,
        public string $payload,
        public array $channelMeta,
        public int $priority,
        public DateTimeImmutable $scheduledAt,
    )
    {
    }
}
