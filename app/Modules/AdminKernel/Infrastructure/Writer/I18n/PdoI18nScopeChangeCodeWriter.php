<?php

declare(strict_types=1);

namespace Maatify\AdminKernel\Infrastructure\Writer\I18n;

use Maatify\AdminKernel\Domain\I18n\Scope\Writer\I18nScopeChangeCodeWriterInterface;
use PDO;
use RuntimeException;

final readonly class PdoI18nScopeChangeCodeWriter implements I18nScopeChangeCodeWriterInterface
{
    public function __construct(
        private PDO $pdo
    ) {}

    public function existsById(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM i18n_scopes WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetchColumn() !== false;
    }

    public function existsByCode(string $code): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM i18n_scopes WHERE code = :code LIMIT 1'
        );
        $stmt->execute(['code' => $code]);

        return $stmt->fetchColumn() !== false;
    }

    public function isCodeInUse(string $code): bool
    {
        // 1) Check usage in i18n_keys
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM i18n_keys WHERE scope = :code LIMIT 1'
        );
        $stmt->execute(['code' => $code]);

        if ($stmt->fetchColumn() !== false) {
            return true;
        }

        // 2) Check usage in i18n_domain_scopes
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM i18n_domain_scopes WHERE scope_code = :code LIMIT 1'
        );
        $stmt->execute(['code' => $code]);

        return $stmt->fetchColumn() !== false;
    }


    public function getCurrentCode(int $id): string
    {
        $stmt = $this->pdo->prepare(
            'SELECT code FROM i18n_scopes WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);

        $code = $stmt->fetchColumn();

        if (!is_string($code)) {
            throw new RuntimeException('Failed to fetch current scope code');
        }

        return $code;
    }

    public function changeCode(int $id, string $newCode): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE i18n_scopes SET code = :code WHERE id = :id'
        );

        $ok = $stmt->execute([
            'id' => $id,
            'code' => $newCode
        ]);

        if ($ok === false) {
            throw new RuntimeException('Failed to change scope code');
        }
    }
}
