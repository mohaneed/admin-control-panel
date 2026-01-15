<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-15 09:33
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\SecurityEvents\Infrastructure\Contracts;

use App\Modules\SecurityEvents\DTO\SecurityEventDTO;

/**
 * Low-level storage contract for persisting security events.
 *
 * This interface represents the infrastructure concern only
 * (database, queue, external system).
 *
 * Implementations MUST be best-effort and MUST NOT throw
 * exceptions that affect the main execution flow.
 */
interface SecurityEventStorageInterface
{
    /**
     * Persist a security event.
     *
     * @param   SecurityEventDTO  $event
     */
    public function store(SecurityEventDTO $event): void;
}
