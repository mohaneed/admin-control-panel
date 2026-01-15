<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-15 10:10
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\SecurityEvents\Infrastructure\Mysql;

use App\Modules\SecurityEvents\Contracts\SecurityEventReaderInterface;
use App\Modules\SecurityEvents\DTO\SecurityEventReadDTO;
use App\Modules\SecurityEvents\Enum\SecurityEventSeverityEnum;
use App\Modules\SecurityEvents\Enum\SecurityEventTypeEnum;
use App\Modules\SecurityEvents\Exceptions\SecurityEventRowMappingException;
use DateTimeImmutable;
use PDO;
use Throwable;

final readonly class SecurityEventReaderMysqlRepository implements SecurityEventReaderInterface
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    /**
     * @throws SecurityEventRowMappingException
     */
    public function paginate(
        array $filters,
        int $page,
        int $perPage
    ): array {
        $offset = ($page - 1) * $perPage;

        [$whereSql, $params] = $this->buildWhere($filters);

        $sql = "
            SELECT
                id,
                actor_type,
                actor_id,
                event_type,
                severity,
                request_id,
                route_name,
                ip_address,
                user_agent,
                metadata,
                occurred_at
            FROM security_events
            {$whereSql}
            ORDER BY occurred_at DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }

        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $items = [];

        foreach ($rows as $row) {
            $items[] = $this->mapRowToDTO($row);
        }

        return $items;
    }

    public function count(array $filters): int
    {
        [$whereSql, $params] = $this->buildWhere($filters);

        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM security_events {$whereSql}"
        );

        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @throws SecurityEventRowMappingException
     */
    public function findById(int $id): ?SecurityEventReadDTO
    {
        $stmt = $this->pdo->prepare(
            "
            SELECT
                id,
                actor_type,
                actor_id,
                event_type,
                severity,
                request_id,
                route_name,
                ip_address,
                user_agent,
                metadata,
                occurred_at
            FROM security_events
            WHERE id = :id
            LIMIT 1
            "
        );

        $stmt->execute(['id' => $id]);

        /** @var array<string, mixed>|false $row */
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return $this->mapRowToDTO($row);
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildWhere(array $filters): array
    {
        $where = [];
        $params = [];

        if (isset($filters['actor_type']) && is_string($filters['actor_type'])) {
            $where[] = 'actor_type = :actor_type';
            $params['actor_type'] = $filters['actor_type'];
        }

        if (isset($filters['actor_id']) && is_numeric($filters['actor_id'])) {
            $where[] = 'actor_id = :actor_id';
            $params['actor_id'] = (int) $filters['actor_id'];
        }

        if (isset($filters['event_type']) && is_string($filters['event_type'])) {
            $where[] = 'event_type = :event_type';
            $params['event_type'] = $filters['event_type'];
        }

        if (isset($filters['severity']) && is_string($filters['severity'])) {
            $where[] = 'severity = :severity';
            $params['severity'] = $filters['severity'];
        }

        if (isset($filters['request_id']) && is_string($filters['request_id'])) {
            $where[] = 'request_id = :request_id';
            $params['request_id'] = $filters['request_id'];
        }

        if (isset($filters['date_from']) && is_string($filters['date_from'])) {
            $where[] = 'occurred_at >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }

        if (isset($filters['date_to']) && is_string($filters['date_to'])) {
            $where[] = 'occurred_at <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        return [$whereSql, $params];
    }

    /**
     * @param   array<string, mixed>  $row
     *
     * @throws SecurityEventRowMappingException
     */
    private function mapRowToDTO(array $row): SecurityEventReadDTO
    {
        if (!isset($row['id']) || !is_numeric($row['id'])) {
            throw new SecurityEventRowMappingException('Invalid security_events.id');
        }

        if (!isset($row['actor_type']) || !is_string($row['actor_type'])) {
            throw new SecurityEventRowMappingException('Invalid security_events.actor_type');
        }

        if (!isset($row['event_type']) || !is_string($row['event_type'])) {
            throw new SecurityEventRowMappingException('Invalid security_events.event_type');
        }

        if (!isset($row['severity']) || !is_string($row['severity'])) {
            throw new SecurityEventRowMappingException('Invalid security_events.severity');
        }

        if (!isset($row['occurred_at']) || !is_string($row['occurred_at'])) {
            throw new SecurityEventRowMappingException('Invalid security_events.occurred_at');
        }

        $metadata = [];

        if (isset($row['metadata']) && is_string($row['metadata']) && $row['metadata'] !== '') {
            $decoded = json_decode($row['metadata'], true);
            if (is_array($decoded)) {
                $metadata = $decoded;
            }
        }

        try {
            $occurredAt = new DateTimeImmutable($row['occurred_at']);
        } catch (Throwable $e) {
            throw new SecurityEventRowMappingException(
                'Invalid security_events.occurred_at',
                previous: $e
            );
        }


        return new SecurityEventReadDTO(
            id         : (int) $row['id'],
            actorType  : $row['actor_type'],
            actorId    : isset($row['actor_id']) && is_numeric($row['actor_id'])
                ? (int) $row['actor_id']
                : null,
            eventType  : SecurityEventTypeEnum::from($row['event_type']),
            severity   : SecurityEventSeverityEnum::from($row['severity']),
            requestId  : isset($row['request_id']) && is_string($row['request_id'])
                ? $row['request_id']
                : null,
            routeName  : isset($row['route_name']) && is_string($row['route_name'])
                ? $row['route_name']
                : null,
            ipAddress  : isset($row['ip_address']) && is_string($row['ip_address'])
                ? $row['ip_address']
                : null,
            userAgent  : isset($row['user_agent']) && is_string($row['user_agent'])
                ? $row['user_agent']
                : null,
            metadata   : $metadata,
            occurredAt : $occurredAt
        );

    }
}
