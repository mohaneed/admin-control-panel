<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Contracts\AdminPasswordRepositoryInterface;
use App\Domain\DTO\AdminPasswordRecordDTO;
use PDO;

class AdminPasswordRepository implements AdminPasswordRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function savePassword(int $adminId, string $passwordHash, string $pepperId): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO admin_passwords (admin_id, password_hash, pepper_id)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                password_hash = VALUES(password_hash),
                pepper_id = VALUES(pepper_id)
        ");
        $stmt->execute([$adminId, $passwordHash, $pepperId]);
    }

    public function getPasswordRecord(int $adminId): ?AdminPasswordRecordDTO
    {
        $stmt = $this->pdo->prepare("SELECT password_hash, pepper_id FROM admin_passwords WHERE admin_id = ?");
        $stmt->execute([$adminId]);
        
        /** @var array{password_hash: string, pepper_id: string}|false $result */
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result === false) {
            return null;
        }

        return new AdminPasswordRecordDTO(
            hash: $result['password_hash'],
            pepperId: $result['pepper_id']
        );
    }
}
