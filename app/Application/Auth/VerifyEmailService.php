<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-29 09:55
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Application\Auth;

use App\Application\Auth\DTO\VerifyEmailRequestDTO;
use App\Application\Auth\DTO\VerifyEmailResultDTO;
use App\Application\Crypto\AdminIdentifierCryptoServiceInterface;
use App\Domain\Contracts\AdminIdentifierLookupInterface;
use App\Domain\Contracts\VerificationCodeValidatorInterface;
use App\Domain\Enum\IdentityTypeEnum;
use App\Domain\Enum\VerificationPurposeEnum;
use App\Domain\Service\AdminEmailVerificationService;
use Throwable;

final readonly class VerifyEmailService
{
    public function __construct(
        private AdminIdentifierCryptoServiceInterface $cryptoService,
        private AdminIdentifierLookupInterface $lookupInterface,
        private VerificationCodeValidatorInterface $validator,
        private AdminEmailVerificationService $verificationService,
    )
    {
    }

    public function verify(VerifyEmailRequestDTO $request): VerifyEmailResultDTO
    {
        // 1️⃣ Resolve identity
        $blindIndex = $this->cryptoService->deriveEmailBlindIndex($request->email);
        $identifierDTO = $this->lookupInterface->findByBlindIndex($blindIndex);

        if ($identifierDTO === null) {
            return new VerifyEmailResultDTO(false);
        }

        $adminId = (string)$identifierDTO->adminId;

        // 2️⃣ Validate OTP
        $result = $this->validator->validate(
            IdentityTypeEnum::Admin,
            $adminId,
            VerificationPurposeEnum::EmailVerification,
            $request->otp
        );

        if (! $result->success) {
            return new VerifyEmailResultDTO(false);
        }

        // 3️⃣ Authoritative verification (idempotent)
        try {
            $this->verificationService->selfVerify(
                $identifierDTO->emailId,
                $request->requestContext
            );
        } catch (Throwable) {
            // idempotent / already verified
        }

        return new VerifyEmailResultDTO(true);
    }
}
