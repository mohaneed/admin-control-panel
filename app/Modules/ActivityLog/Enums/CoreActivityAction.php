<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-11 19:55
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\ActivityLog\Enums;

use App\Modules\ActivityLog\Contracts\ActivityActionInterface;

enum CoreActivityAction: string implements ActivityActionInterface
{
    // Auth
    case ADMIN_LOGIN = 'admin.auth.login';
    case ADMIN_LOGOUT = 'admin.auth.logout';

    // User management
    case ADMIN_USER_CREATE = 'admin.user.create';
    case ADMIN_USER_UPDATE = 'admin.user.update';
    case ADMIN_USER_DELETE = 'admin.user.delete';

    // System
    case SYSTEM_SETTINGS_UPDATE = 'system.settings.update';

    public function toString(): string
    {
        return $this->value;
    }
}
