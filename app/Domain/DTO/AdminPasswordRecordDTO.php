<?php

declare(strict_types=1);

namespace App\Domain\DTO;

final readonly class AdminPasswordRecordDTO
{
    public function __construct(
        public string $hash,
        public string $pepperId,
        public bool $mustChangePassword
    ) {
    }
}
