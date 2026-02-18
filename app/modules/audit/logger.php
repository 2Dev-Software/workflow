<?php

declare(strict_types=1);

require_once __DIR__ . '/../../db/db.php';
require_once __DIR__ . '/../../helpers.php';

if (!function_exists('audit_log')) {
    function audit_log(
        string $module,
        string $action,
        string $status = 'SUCCESS',
        ?string $entityName = null,
        $entityId = null,
        ?string $message = null,
        array $payload = [],
        ?string $httpMethod = null,
        ?int $httpStatus = null
    ): void {
        $connection = db_connection();

        if (!db_table_exists($connection, 'dh_logs')) {
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $actor_pid = (string) ($_SESSION['pID'] ?? '');
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
        $method = $httpMethod ?? ($_SERVER['REQUEST_METHOD'] ?? null);
        $request_url = (string) ($_SERVER['REQUEST_URI'] ?? '');
        $server_name = (string) ($_SERVER['SERVER_NAME'] ?? '');

        $payload_json = $payload ? json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;

        if ($payload_json === false) {
            $payload_json = null;
        }

        $numeric_pid = null;

        if ($actor_pid !== '' && ctype_digit($actor_pid)) {
            // dh_logs.pID is unsigned int(10); guard against overflow in strict SQL mode.
            if (strlen($actor_pid) <= 10 && $actor_pid <= '4294967295') {
                $numeric_pid = (int) $actor_pid;
            }
        }

        $has_actor_pid = db_column_exists($connection, 'dh_logs', 'actorPID');
        $has_request_id = db_column_exists($connection, 'dh_logs', 'requestID');
        $has_session_id = db_column_exists($connection, 'dh_logs', 'sessionID');

        $log_level = $status === 'FAIL' ? 'ERROR' : ($status === 'DENY' ? 'SECURITY' : 'AUDIT');
        $entity_id = is_numeric($entityId) ? (int) $entityId : null;
        $action_status = strtoupper($status);

        $columns = ['pID'];
        $values = [$numeric_pid];
        $types = 'i';

        if ($has_actor_pid) {
            $columns[] = 'actorPID';
            $values[] = $actor_pid;
            $types .= 's';
        }

        if ($has_session_id) {
            $session_id = session_id();

            if (strlen($session_id) > 64) {
                $session_id = substr($session_id, 0, 64);
            }
            $columns[] = 'sessionID';
            $values[] = $session_id;
            $types .= 's';
        }

        if ($has_request_id) {
            $request_id = app_request_id();

            if (strlen($request_id) > 26) {
                $request_id = substr($request_id, 0, 26);
            }
            $columns[] = 'requestID';
            $values[] = $request_id;
            $types .= 's';
        }

        $columns = array_merge($columns, [
            'logLevel', 'moduleName', 'actionName', 'actionStatus', 'entityName', 'entityID', 'logMessage',
            'payloadData', 'httpMethod', 'requestURL', 'httpStatus', 'ipAddress', 'userAgent', 'serverName',
        ]);
        $values = array_merge($values, [
            $log_level,
            $module,
            $action,
            $action_status,
            $entityName,
            $entity_id,
            $message,
            $payload_json,
            $method,
            $request_url,
            $httpStatus,
            $ip,
            $ua,
            $server_name,
        ]);
        $types .= 'sssssissssisss';

        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $sql = 'INSERT INTO dh_logs (' . implode(', ', $columns) . ') VALUES (' . $placeholders . ')';

        try {
            $stmt = db_query($sql, $types, ...$values);
            mysqli_stmt_close($stmt);
        } catch (Throwable $e) {
            error_log('Audit log failed: ' . $e->getMessage());
        }
    }
}
