<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\Contracts\Roles;

use Maatify\AdminKernel\Domain\DTO\Roles\RoleAdminsQueryResponseDTO;
use Maatify\AdminKernel\Domain\List\ListQueryDTO;
use Maatify\AdminKernel\Infrastructure\Query\ResolvedListFilters;

interface RoleAdminsRepositoryInterface
{
    public function assign(int $roleId, int $adminId): void;

    public function unassign(int $roleId, int $adminId): void;

    public function queryAdminsForRole(
        int $roleId,
        ListQueryDTO $query,
        ResolvedListFilters $filters
    ): RoleAdminsQueryResponseDTO;
}
