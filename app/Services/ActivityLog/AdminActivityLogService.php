<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-12 09:01
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Services\ActivityLog;

use App\Modules\ActivityLog\Contracts\ActivityActionInterface;
use App\Modules\ActivityLog\Service\ActivityLogService;

/**
 * Admin-scoped Activity Log Facade.
 *
 * - Fixes actor_type = admin
 * - Accepts admin_id from the outside
 * - Delegates writing to ActivityLogService
 *
 * This class is:
 * - Admin-aware
 * - Stateless
 * - Reusable from any context (HTTP / Job / CLI / Tests)
 *
 * It MUST NOT:
 * - Resolve admin from session
 * - Perform authorization
 * - Access request globals
 */
final readonly class AdminActivityLogService
{
    public function __construct(
        private ActivityLogService $activityLogService,
    )
    {
    }

    /**
     * Log an admin activity.
     *
     * @param   int                             $adminId
     * @param   ActivityActionInterface|string  $action
     * @param   string|null                     $entityType
     * @param   int|null                        $entityId
     * @param   array<string, mixed>|null       $metadata
     * @param   string|null                     $ipAddress
     * @param   string|null                     $userAgent
     * @param   string|null                     $requestId
     */
    public function log(
        int $adminId,
        ActivityActionInterface|string $action,

        ?string $entityType = null,
        ?int $entityId = null,

        ?array $metadata = null,

        ?string $ipAddress = null,
        ?string $userAgent = null,

        ?string $requestId = null,
    ): void
    {
        $this->activityLogService->log(
            action    : $action,

            actorType : 'admin',
            actorId   : $adminId,

            entityType: $entityType,
            entityId  : $entityId,

            metadata  : $metadata,

            ipAddress : $ipAddress,
            userAgent : $userAgent,

            requestId : $requestId,
        );
    }
}
