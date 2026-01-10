<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-10 17:11
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\Notification\DTO;

use App\Modules\Notification\Enum\NotificationChannel;
use DateTimeImmutable;

/**
 * NotificationDeliveryResultDTO
 *
 * Represents the final outcome of a delivery attempt.
 */
final readonly class NotificationDeliveryResultDTO
{
    public function __construct(
        public string $intentId,
        public NotificationChannel $channel,
        public string $status, // sent | failed | skipped
        public ?DateTimeImmutable $sentAt = null,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
    )
    {
    }
}
