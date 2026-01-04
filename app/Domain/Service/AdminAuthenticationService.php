<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Contracts\AdminEmailVerificationRepositoryInterface;
use App\Domain\Contracts\AdminIdentifierLookupInterface;
use App\Domain\Contracts\AdminPasswordRepositoryInterface;
use App\Domain\Contracts\AdminSessionRepositoryInterface;
use App\Domain\Enum\VerificationStatus;
use App\Domain\Exception\AuthStateException;
use App\Domain\Exception\InvalidCredentialsException;

readonly class AdminAuthenticationService
{
    public function __construct(
        private AdminIdentifierLookupInterface $lookupRepository,
        private AdminEmailVerificationRepositoryInterface $verificationRepository,
        private AdminPasswordRepositoryInterface $passwordRepository,
        private AdminSessionRepositoryInterface $sessionRepository
    ) {
    }

    public function login(string $blindIndex, string $password): string
    {
        // 1. Look up Admin ID by Blind Index
        $adminId = $this->lookupRepository->findByBlindIndex($blindIndex);
        if ($adminId === null) {
            // Defensive: Do not reveal user existence
            throw new InvalidCredentialsException("Invalid credentials.");
        }

        // 2. Check Verification Status
        $status = $this->verificationRepository->getVerificationStatus($adminId);
        if ($status !== VerificationStatus::VERIFIED) {
            throw new AuthStateException("Identifier is not verified.");
        }

        // 3. Verify Password
        $hash = $this->passwordRepository->getPasswordHash($adminId);
        if ($hash === null || !password_verify($password, $hash)) {
            throw new InvalidCredentialsException("Invalid credentials.");
        }

        // 4. Create Session
        return $this->sessionRepository->createSession($adminId);
    }
}
