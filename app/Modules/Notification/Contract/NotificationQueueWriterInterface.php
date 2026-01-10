<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-10 17:13
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\Notification\Contract;

use App\Modules\Notification\DTO\NotificationDeliveryDTO;
use RuntimeException;

/**
 * NotificationQueueWriterInterface
 *
 * Responsible for persisting delivery instructions
 * into the notification delivery queue.
 *
 * This contract is infrastructure-agnostic and library-ready.
 */
interface NotificationQueueWriterInterface
{
    /**
     * Enqueue a delivery instruction.
     *
     * Implementations are responsible for:
     * - Encryption of recipient and payload
     * - Mapping DTO fields to storage schema
     * - Initial status assignment (pending / skipped)
     *
     * @throws RuntimeException on unrecoverable persistence failure
     */
    public function enqueue(NotificationDeliveryDTO $delivery): void;
}
