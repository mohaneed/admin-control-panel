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
        $response = null;

        try {
            $response = $handler->handle($request);
            return $response;
        } finally {
            try {
                $context = $request->getAttribute(RequestContext::class);

                if ($context instanceof RequestContext && $response instanceof ResponseInterface) {
                    $durationMs = (int) ((microtime(true) - $start) * 1000);

                    $metadata = [
                        'method'      => $request->getMethod(),
                        'route_name'  => $context->getRouteName(),
                        'status_code' => $response->getStatusCode(),
                        'duration_ms' => $durationMs,
                    ];

                    $adminContext = $request->getAttribute(AdminContext::class);

                    if ($adminContext instanceof AdminContext) {
                        $this->telemetryFactory
                            ->admin($context)
                            ->record(
                                $adminContext->adminId,
                                TelemetryEventTypeEnum::HTTP_REQUEST_END,
                                TelemetrySeverityEnum::INFO,
                                $metadata
                            );
                    } else {
                        $this->telemetryFactory
                            ->system($context)
                            ->record(
                                TelemetryEventTypeEnum::HTTP_REQUEST_END,
                                TelemetrySeverityEnum::INFO,
                                $metadata
                            );
                    }
                }
            } catch (Throwable) {
                // swallow — telemetry must never affect request flow
            }
        }
    }
}
