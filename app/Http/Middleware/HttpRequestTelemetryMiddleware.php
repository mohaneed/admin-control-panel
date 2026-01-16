<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-16 07:02
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Application\Telemetry\HttpTelemetryRecorderFactory;
use App\Context\RequestContext;
use App\Domain\Telemetry\Enum\TelemetryActorTypeEnum;
use App\Modules\Telemetry\Enum\TelemetryEventTypeEnum;
use App\Modules\Telemetry\Enum\TelemetrySeverityEnum;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

/**
 * Global HTTP request telemetry (write-side only).
 *
 * - Records HTTP_REQUEST_END
 * - Best-effort (never breaks request flow)
 * - Requires RequestContext (produced by RequestContextMiddleware)
 */
final readonly class HttpRequestTelemetryMiddleware implements MiddlewareInterface
{
    public function __construct(
        private HttpTelemetryRecorderFactory $telemetryFactory
    )
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $start = microtime(true);

        $response = $handler->handle($request);

        $durationMs = (int)round((microtime(true) - $start) * 1000);

        try {
            /** @var RequestContext|null $context */
            $context = $request->getAttribute(RequestContext::class);

            if (! $context instanceof RequestContext) {
                // No context → do nothing (best-effort)
                return $response;
            }

            $statusCode = $response->getStatusCode();

            $severity = match (true) {
                $statusCode >= 500 => TelemetrySeverityEnum::ERROR,
                $statusCode >= 400 => TelemetrySeverityEnum::WARN,
                default => TelemetrySeverityEnum::INFO,
            };

            // Resolve actor type
            $actorType = $request->getAttribute('admin_id') !== null
                ? TelemetryActorTypeEnum::ADMIN
                : TelemetryActorTypeEnum::SYSTEM;

            $recorder = match ($actorType) {
                TelemetryActorTypeEnum::ADMIN => $this->telemetryFactory->admin($context),
                TelemetryActorTypeEnum::SYSTEM => $this->telemetryFactory->system($context),
            };

            $recorder->record(
                TelemetryEventTypeEnum::HTTP_REQUEST_END,
                $severity,
                [
                    'method'      => $request->getMethod(),
                    'route_name'  => $context->getRouteName(),
                    'status_code' => $statusCode,
                    'duration_ms' => $durationMs,
                ]
            );
        } catch (Throwable) {
            // swallow – telemetry must never affect request flow
        }

        return $response;
    }
}
