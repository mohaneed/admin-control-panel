<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\AdminPasswordRecordDTO;

interface AdminPasswordRepositoryInterface
{
    public function savePassword(int $adminId, string $passwordHash, string $pepperId): void;

    public function getPasswordRecord(int $adminId): ?AdminPasswordRecordDTO;
}
