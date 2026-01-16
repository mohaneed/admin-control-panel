<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Application\Telemetry\HttpTelemetryRecorderFactory;
use App\Context\AdminContext;
use App\Context\RequestContext;
use App\Modules\Telemetry\Enum\TelemetryEventTypeEnum;
use App\Modules\Telemetry\Enum\TelemetrySeverityEnum;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

final class HttpRequestTelemetryMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly HttpTelemetryRecorderFactory $telemetryFactory
    ) {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $start = microtime(true);

        try {
            $response = $handler->handle($request);
        } catch (Throwable $e) {
            // Let global error handlers deal with exception telemetry
            throw $e;
        } finally {
            try {
                /** @var RequestContext|null $context */
                $context = $request->getAttribute(RequestContext::class);

                if (!$context instanceof RequestContext) {
                    return $response ?? throw new \RuntimeException('Response missing');
                }

                $durationMs = (int) ((microtime(true) - $start) * 1000);

                $metadata = [
                    'method'       => $request->getMethod(),
                    'route_name'   => $context->getRouteName(),
                    'status_code'  => isset($response) ? $response->getStatusCode() : 500,
                    'duration_ms'  => $durationMs,
                ];

                /** @var AdminContext|null $adminContext */
                $adminContext = $request->getAttribute(AdminContext::class);

                if ($adminContext instanceof AdminContext) {
                    // ADMIN request
                    $this->telemetryFactory
                        ->admin($context)
                        ->record(
                            $adminContext->adminId,
                            TelemetryEventTypeEnum::HTTP_REQUEST_END,
                            TelemetrySeverityEnum::INFO,
                            $metadata
                        );
                } else {
                    // SYSTEM / unauthenticated request
                    $this->telemetryFactory
                        ->system($context)
                        ->record(
                            TelemetryEventTypeEnum::HTTP_REQUEST_END,
                            TelemetrySeverityEnum::INFO,
                            $metadata
                        );
                }
            } catch (Throwable) {
                // swallow — telemetry must never affect request flow
            }
        }

        return $response;
    }
}
