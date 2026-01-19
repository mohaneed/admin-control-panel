<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm)
 * @since       2026-01-19
 * @note        Two-Factor Authentication (TOTP) Enrollment Service
 *              Handles setup + enable flow using server-side session storage.
 */

declare(strict_types=1);

namespace App\Domain\Service;

use App\Application\Crypto\TotpSecretCryptoServiceInterface;
use App\Context\RequestContext;
use App\Domain\Contracts\AdminTotpSecretRepositoryInterface;
use App\Domain\Contracts\AuthoritativeSecurityAuditWriterInterface;
use App\Domain\Contracts\TotpServiceInterface;
use App\Domain\DTO\TotpEnrollmentConfig;
use App\Domain\DTO\TwoFactorEnrollmentViewDTO;
use App\Domain\Exception\TwoFactorAlreadyEnrolledException;
use App\Domain\Exception\TwoFactorEnrollmentFailedException;
use App\Domain\SecurityEvents\DTO\SecurityEventRecordDTO;
use App\Domain\SecurityEvents\Enum\SecurityEventActorTypeEnum;
use App\Domain\SecurityEvents\Recorder\SecurityEventRecorderInterface;
use App\Domain\Support\CorrelationId;
use App\Modules\SecurityEvents\Enum\SecurityEventSeverityEnum;
use App\Modules\SecurityEvents\Enum\SecurityEventTypeEnum;
use PDO;
use RuntimeException;

/**
 * Two-Factor Authentication (TOTP) Enrollment Service
 *
 *  Handles setup + enable flow using server-side session storage.
 *
 *  - Returns provisioning URI (otpauth://) + plain secret
 *  - Plain secret is required for manual entry in authenticator apps
 */
final class TwoFactorEnrollmentService
{
    /**
     * Session key for pending TOTP enrollment
     */
    private const SESSION_KEY = 'pending_totp_enrollment';

    public function __construct(
        private readonly AdminTotpSecretRepositoryInterface $totpSecretRepository,
        private readonly TotpServiceInterface $totpService,
        private readonly TotpSecretCryptoServiceInterface $crypto,
        private readonly AuthoritativeSecurityAuditWriterInterface $auditWriter,
        private readonly SecurityEventRecorderInterface $securityEventRecorder,
        private readonly TotpEnrollmentConfig $totpEnrollmentConfig,
        private readonly PDO $pdo
    )
    {
    }

    /**
     * STEP 1: Prepare TOTP enrollment
     *
     * - Generate new TOTP secret
     * - Store raw secret temporarily in server-side session
     * - Return provisioning URI + plain secret for UI display / manual entry
     */
    public function prepareEnrollment(
        int $adminId,
        RequestContext $context
    ): TwoFactorEnrollmentViewDTO
    {
        // Fail-closed: already enrolled
        if ($this->totpSecretRepository->get($adminId) !== null) {
            throw new TwoFactorAlreadyEnrolledException(
                sprintf('TOTP already enrolled (request_id=%s)', $context->requestId)
            );
        }

        // Generate raw TOTP secret
        $secret = $this->totpService->generateSecret();

        // Store pending secret in session (server-side only)
        $_SESSION[self::SESSION_KEY] = [
            'secret'    => $secret,
            'issued_at' => time(),
        ];

        // Generate provisioning URI (otpauth://)
        $qrUri = $this->totpService->generateProvisioningUri(
            issuer     : $this->totpEnrollmentConfig->totpIssuer,
            accountName: 'admin:' . $adminId,
            secret     : $secret
        );

        return new TwoFactorEnrollmentViewDTO(
            qrUri : $qrUri,
            secret: $secret
        );
    }

    /**
     * STEP 2: Enable TOTP enrollment
     *
     * - Verify OTP against pending secret
     * - Encrypt secret using TOTP crypto service
     * - Persist encrypted secret
     * - Write authoritative audit
     * - Clear pending session state
     */
    public function enableEnrollment(
        int $adminId,
        string $otpCode,
        RequestContext $context
    ): void
    {
        $pending = $_SESSION[self::SESSION_KEY] ?? null;

        if (! is_array($pending) || ! isset($pending['secret'])) {
            throw new TwoFactorEnrollmentFailedException('No pending TOTP enrollment found');
        }

        $issuedAt = (int) ($pending['issued_at'] ?? 0);

        if ($issuedAt === 0 || (time() - $issuedAt) > $this->totpEnrollmentConfig->totpEnrollmentTtlSeconds) {
            unset($_SESSION[self::SESSION_KEY]);
            throw new TwoFactorEnrollmentFailedException('TOTP enrollment expired');
        }


        $secret = (string)$pending['secret'];

        // Verify OTP code
        if (! $this->totpService->verify($secret, $otpCode)) {
            $this->securityEventRecorder->record(
                new SecurityEventRecordDTO(
                    actorType: SecurityEventActorTypeEnum::ADMIN,
                    actorId  : $adminId,
                    eventType: SecurityEventTypeEnum::STEP_UP_ENROLL_FAILED,
                    severity : SecurityEventSeverityEnum::ERROR,
                    requestId: $context->requestId,
                    routeName: $context->routeName,
                    ipAddress: $context->ipAddress,
                    userAgent: $context->userAgent,
                    metadata : [
                        'reason' => 'invalid_otp_during_enrollment',
                    ]
                )
            );

            throw new TwoFactorEnrollmentFailedException('Invalid OTP code');
        }

        $correlationId = CorrelationId::generate();

        $this->pdo->beginTransaction();
        try {
            // Encrypt TOTP secret (context-bound)
            $encrypted = $this->crypto->encryptTotpSeed($secret);

            // Persist encrypted secret
            $this->totpSecretRepository->save($adminId, $encrypted);

            // Authoritative audit event
            $this->auditWriter->write(
                new \App\Domain\DTO\AuditEventDTO(
                    actor_id      : $adminId,
                    action        : 'totp_enrolled',
                    target_type   : 'admin',
                    target_id     : $adminId,
                    risk_level    : 'HIGH',
                    payload       : [],
                    correlation_id: $correlationId,
                    request_id    : $context->requestId,
                    created_at    : new \DateTimeImmutable()
                )
            );

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        } finally {
            // Always clear pending enrollment state
            unset($_SESSION[self::SESSION_KEY]);
        }
    }
}

