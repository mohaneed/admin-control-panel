<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Domain\I18n\Scope\Writer;

interface I18nScopeChangeCodeWriterInterface
{
    public function existsById(int $id): bool;

    public function existsByCode(string $code): bool;

    public function isCodeInUse(string $code): bool;

    public function getCurrentCode(int $id): string;

    public function changeCode(int $id, string $newCode): void;
}
