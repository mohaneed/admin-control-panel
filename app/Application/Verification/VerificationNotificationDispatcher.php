<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-20 10:01
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Application\Verification;

use App\Application\Verification\Enum\NotificationSenderTypeEnum;
use App\Domain\Enum\IdentityTypeEnum;
use App\Domain\Enum\VerificationPurposeEnum;
use App\Modules\Email\Queue\DTO\EmailQueuePayloadDTO;
use App\Modules\Email\Queue\EmailQueueWriterInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final class VerificationNotificationDispatcher implements VerificationNotificationDispatcherInterface
{
    public function __construct(
        private readonly EmailQueueWriterInterface $emailQueue,
        private readonly LoggerInterface $logger
    )
    {
    }

    public function dispatch(
        IdentityTypeEnum $identityType,
        string $identityId,
        VerificationPurposeEnum $purpose,
        string $recipient,
        string $plainCode,
        array $context,
        string $language
    ): void
    {
        try {
            match ($purpose) {
                VerificationPurposeEnum::EmailVerification =>
                $this->dispatchEmail(
                    $identityType,
                    $identityId,
                    $recipient,
                    $plainCode,
                    $context,
                    $language
                ),

                default => null, // future channels (Telegram, SMS, ...)
            };
        } catch (Throwable $e) {
            // PSR-3 diagnostic ONLY (infra failure)
            $this->logger->warning(
                'Verification notification dispatch failed',
                [
                    'purpose'       => $purpose->value,
                    'identity_type' => $identityType->value,
                    'identity_id'   => $identityId,
                    'exception'     => $e,
                ]
            );
        }
    }

    /**
     * @param   array<string, mixed>  $context
     */
    private function dispatchEmail(
        IdentityTypeEnum $identityType,
        string $identityId,
        string $recipientEmail,
        string $plainCode,
        array $context,
        string $language
    ): void
    {
        $payload = new EmailQueuePayloadDTO(
            context    : array_merge($context, [
                'code' => $plainCode,
            ]),
            templateKey: 'email_verification_code',
            language   : $language
        );

        $this->emailQueue->enqueue(
            entityType    : $identityType->value,
            entityId      : $identityId,
            recipientEmail: $recipientEmail,
            payload       : $payload,
            senderType    : NotificationSenderTypeEnum::SENDER_SYSTEM->value
        );
    }
}
