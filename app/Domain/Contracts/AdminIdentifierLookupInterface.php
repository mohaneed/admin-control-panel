<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\AdminEmailIdentifierDTO;

interface AdminIdentifierLookupInterface
{
    public function findByBlindIndex(string $blindIndex): ?AdminEmailIdentifierDTO;
}
