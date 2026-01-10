<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-10 17:06
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\Notification\Enum;

/**
 * NotificationChannel
 *
 * Represents supported notification delivery channels.
 * This enum is module-scoped and library-ready.
 *
 * IMPORTANT:
 * - Values MUST match database ENUM values exactly.
 * - Do NOT use raw strings anywhere else in the system.
 */
enum NotificationChannel: string
{
    case EMAIL = 'email';
    case TELEGRAM = 'telegram';
    case SMS = 'sms';
    case PUSH = 'push';

    /**
     * Returns all channel values as string array.
     *
     * @return string[]
     */
    public static function values(): array
    {
        return array_map(
            static fn(self $channel): string => $channel->value,
            self::cases()
        );
    }
}
