<?php

declare(strict_types=1);

namespace App\Domain\Contracts;

use App\Domain\DTO\AdminEmailIdentifierDTO;

interface AdminEmailVerificationRepositoryInterface
{
    public function getEmailIdentity(int $emailId): AdminEmailIdentifierDTO;

    public function markVerified(int $emailId, string $timestamp): void;

    public function markFailed(int $emailId): void;

    public function markPending(int $emailId): void;
    public function markReplaced(int $emailId): void;
}
