<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-12 12:32
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Context\Resolver;

use App\Context\AdminContext;
use Psr\Http\Message\ServerRequestInterface;

final class AdminContextResolver
{
    public function resolve(ServerRequestInterface $request): AdminContext
    {
        $adminId = $request->getAttribute('admin_id');

        if (! is_int($adminId)) {
            throw new \RuntimeException('AdminContextResolver called without admin_id');
        }

        return new AdminContext($adminId);
    }
}
