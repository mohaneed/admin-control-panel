<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-19 14:22
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Http\Controllers\Ui;

use App\Context\AdminContext;
use App\Context\RequestContext;
use App\Domain\Exception\TwoFactorAlreadyEnrolledException;
use App\Domain\Service\TwoFactorEnrollmentService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

/**
 * UI Controller for Two-Factor Authentication (TOTP) Setup
 *
 * Responsibilities:
 * - Render 2FA setup page (QR + secret)
 * - Handle OTP submission to enable 2FA
 * - Redirect safely based on domain exceptions
 */
final class UiTwoFactorSetupController
{
    public function __construct(
        private readonly TwoFactorEnrollmentService $enrollmentService,
        private readonly Twig $view
    ) {
    }

    /**
     * GET /2fa/setup
     *
     * Render TOTP enrollment page.
     */
    public function index(Request $request, Response $response): Response
    {
        /** @var AdminContext $adminContext */
        $adminContext = $request->getAttribute(AdminContext::class);

        /** @var RequestContext $requestContext */
        $requestContext = $request->getAttribute(RequestContext::class);

        try {
            $dto = $this->enrollmentService->prepareEnrollment(
                adminId: $adminContext->adminId,
                context: $requestContext
            );
        } catch (TwoFactorAlreadyEnrolledException) {
            // Admin already enrolled → redirect to dashboard
            return $response
                ->withHeader('Location', '/dashboard')
                ->withStatus(302);
        }

        $qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data='
                      . rawurlencode($dto->qrUri);

        return $this->view->render(
            $response,
            'auth/2fa_setup.twig',
            [
                'provisioning_uri' => $dto->qrUri,
                'qr_image_url'     => $qrImageUrl,
                'secret'           => $dto->secret,
            ]
        );
    }

    /**
     * POST /2fa/setup
     *
     * Verify OTP and enable TOTP enrollment.
     */
    public function enable(Request $request, Response $response): Response
    {
        /** @var AdminContext $adminContext */
        $adminContext = $request->getAttribute(AdminContext::class);

        /** @var RequestContext $requestContext */
        $requestContext = $request->getAttribute(RequestContext::class);

        $data = (array) $request->getParsedBody();
        $otpCode = trim((string) ($data['otp'] ?? ''));

        if ($otpCode === '') {
            // Missing OTP → redirect back to setup
            return $response
                ->withHeader('Location', '/2fa/setup')
                ->withStatus(302);
        }

        try {
            $this->enrollmentService->enableEnrollment(
                adminId: $adminContext->adminId,
                otpCode: $otpCode,
                context: $requestContext
            );
        } catch (TwoFactorAlreadyEnrolledException) {
            // Edge case: enrolled between GET and POST
            return $response
                ->withHeader('Location', '/dashboard')
                ->withStatus(302);
        }

        // Success → redirect to dashboard
        return $response
            ->withHeader('Location', '/dashboard')
            ->withStatus(302);
    }
}

