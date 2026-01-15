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

namespace App\Modules\SecurityEvents\Contracts;

use App\Modules\SecurityEvents\DTO\SecurityEventReadDTO;

/**
 * Contract for reading security events.
 *
 * This interface defines read-only access patterns
 * for the Security Events module.
 *
 * Implementations MUST be side-effect free.
 */
interface SecurityEventReaderInterface
{
    /**
     * Retrieve a paginated list of security events.
     *
     * Default ordering MUST be by occurred_at DESC.
     *
     * @param array<string, mixed> $filters
     * @param int                 $page
     * @param int                 $perPage
     *
     * @return SecurityEventReadDTO[]
     */
    public function paginate(
        array $filters,
        int $page,
        int $perPage
    ): array;

    /**
     * Count total security events matching the given filters.
     *
     * This method is intended to be used alongside paginate()
     * to build pagination metadata.
     *
     * @param array<string, mixed> $filters
     *
     * @return int
     */
    public function count(array $filters): int;

    /**
     * Retrieve a single security event by its identifier.
     *
     * @param int $id
     *
     * @return SecurityEventReadDTO|null
     */
    public function findById(int $id): ?SecurityEventReadDTO;
}

