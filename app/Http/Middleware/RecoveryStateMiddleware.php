<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Service\RecoveryStateService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;


final readonly class RecoveryStateMiddleware implements MiddlewareInterface
{
    public function __construct(
        private RecoveryStateService $recoveryState
    ) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Monitor state transitions on every request to ensure authoritative audit
        $this->recoveryState->monitorState();
        return $handler->handle($request);
    }
}
