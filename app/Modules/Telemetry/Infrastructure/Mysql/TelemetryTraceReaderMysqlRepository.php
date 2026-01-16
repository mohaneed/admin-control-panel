<?php

/**
 * @copyright   ©2026 Maatify.dev
 * @Library     maatify/admin-control-panel
 * @Project     maatify:admin-control-panel
 * @author      Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @since       2026-01-16 11:34
 * @see         https://www.maatify.dev Maatify.dev
 * @link        https://github.com/Maatify/admin-control-panel view Project on GitHub
 * @note        Distributed in the hope that it will be useful - WITHOUT WARRANTY.
 */

declare(strict_types=1);

namespace App\Modules\Telemetry\Infrastructure\Mysql;

use App\Modules\Telemetry\Contracts\TelemetryTraceReaderInterface;
use App\Modules\Telemetry\DTO\TelemetryTraceListQueryDTO;
use App\Modules\Telemetry\DTO\TelemetryTraceReadDTO;
use App\Modules\Telemetry\Enum\TelemetrySeverityEnum;
use App\Modules\Telemetry\Exceptions\TelemetryTraceRowMappingException;
use DateTimeImmutable;
use PDO;
use Throwable;

final readonly class TelemetryTraceReaderMysqlRepository implements TelemetryTraceReaderInterface
{
    public function __construct(
        private PDO $pdo
    ) {}

    public function paginate(
        TelemetryTraceListQueryDTO $query,
        int $page,
        int $perPage
    ): array {
        $offset = ($page - 1) * $perPage;

        [$whereSql, $params] = $this->buildWhere($query);

        $sql = "
            SELECT
                id,
                event_key,
                severity,
                route_name,
                request_id,
                actor_type,
                actor_id,
                ip_address,
                user_agent,
                metadata,
                occurred_at
            FROM telemetry_traces
            {$whereSql}
            ORDER BY occurred_at DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
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

    public function count(TelemetryTraceListQueryDTO $query): int
    {
        [$whereSql, $params] = $this->buildWhere($query);

        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM telemetry_traces {$whereSql}"
        );

        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function findById(int $id): ?TelemetryTraceReadDTO
    {
        $stmt = $this->pdo->prepare(
            "
            SELECT
                id,
                event_key,
                severity,
                route_name,
                request_id,
                actor_type,
                actor_id,
                ip_address,
                user_agent,
                metadata,
                occurred_at
            FROM telemetry_traces
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
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildWhere(TelemetryTraceListQueryDTO $q): array
    {
        $where = [];
        $params = [];

        if ($q->actorType !== null) {
            $where[] = 'actor_type = :actor_type';
            $params['actor_type'] = $q->actorType;
        }

        if ($q->actorId !== null) {
            $where[] = 'actor_id = :actor_id';
            $params['actor_id'] = $q->actorId;
        }

        if ($q->eventKey !== null) {
            $where[] = 'event_key = :event_key';
            $params['event_key'] = $q->eventKey;
        }

        if ($q->severity !== null) {
            $where[] = 'severity = :severity';
            $params['severity'] = $q->severity;
        }

        if ($q->requestId !== null) {
            $where[] = 'request_id = :request_id';
            $params['request_id'] = $q->requestId;
        }

        if ($q->routeName !== null) {
            $where[] = 'route_name = :route_name';
            $params['route_name'] = $q->routeName;
        }

        if ($q->dateFrom !== null) {
            $where[] = 'occurred_at >= :date_from';
            $params['date_from'] = $q->dateFrom;
        }

        if ($q->dateTo !== null) {
            $where[] = 'occurred_at <= :date_to';
            $params['date_to'] = $q->dateTo;
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        return [$whereSql, $params];
    }

    /**
     * @param array<string, mixed> $row
     *
     * @throws TelemetryTraceRowMappingException
     */
    private function mapRowToDTO(array $row): TelemetryTraceReadDTO
    {
        if (!isset($row['id']) || !is_numeric($row['id'])) {
            throw new TelemetryTraceRowMappingException('Invalid telemetry_traces.id');
        }

        if (!isset($row['event_key']) || !is_string($row['event_key'])) {
            throw new TelemetryTraceRowMappingException('Invalid telemetry_traces.event_key');
        }

        if (!isset($row['severity']) || !is_string($row['severity'])) {
            throw new TelemetryTraceRowMappingException('Invalid telemetry_traces.severity');
        }

        if (!isset($row['actor_type']) || !is_string($row['actor_type'])) {
            throw new TelemetryTraceRowMappingException('Invalid telemetry_traces.actor_type');
        }

        if (!isset($row['occurred_at']) || !is_string($row['occurred_at'])) {
            throw new TelemetryTraceRowMappingException('Invalid telemetry_traces.occurred_at');
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
            throw new TelemetryTraceRowMappingException(
                'Invalid telemetry_traces.occurred_at',
                previous: $e
            );
        }

        return new TelemetryTraceReadDTO(
            id         : (int) $row['id'],
            eventKey   : $row['event_key'],
            severity   : TelemetrySeverityEnum::from($row['severity']),
            routeName  : isset($row['route_name']) && is_string($row['route_name'])
                ? $row['route_name']
                : null,
            requestId  : isset($row['request_id']) && is_string($row['request_id'])
                ? $row['request_id']
                : null,
            actorType  : $row['actor_type'],
            actorId    : isset($row['actor_id']) && is_numeric($row['actor_id'])
                ? (int) $row['actor_id']
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
