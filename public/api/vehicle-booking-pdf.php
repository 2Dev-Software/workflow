<?php

declare(strict_types=1);

use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

// Production-grade PDF responses must never be corrupted by PHP notices/warnings.
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('html_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

$__pdf_initial_ob_level = ob_get_level();
ob_start();

set_error_handler(static function (int $severity, string $message, string $file = '', int $line = 0): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    error_log('PHP error [' . $severity . '] ' . $message . ' in ' . $file . ':' . $line);

    return true; // Prevent default handler from outputting to the response.
});

$__pdf_abort = static function (int $status) use ($__pdf_initial_ob_level): void {
    while (ob_get_level() > $__pdf_initial_ob_level) {
        ob_end_clean();
    }

    if (ob_get_level() > 0) {
        ob_clean();
    }
    http_response_code($status);
    exit();
};

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../app/modules/audit/logger.php';

if (empty($_SESSION['pID'])) {
    if (function_exists('audit_log')) {
        audit_log('security', 'AUTH_REQUIRED', 'DENY', null, null, 'vehicle_booking_pdf', [], 'GET', 401);
    }
    $__pdf_abort(401);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    if (function_exists('audit_log')) {
        audit_log('vehicle', 'PDF_VIEW', 'FAIL', 'dh_vehicle_bookings', null, 'method_not_allowed', [], null, 405);
    }
    $__pdf_abort(405);
}

$booking_id = filter_input(INPUT_GET, 'booking_id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);

if (!$booking_id) {
    if (function_exists('audit_log')) {
        audit_log('vehicle', 'PDF_VIEW', 'FAIL', 'dh_vehicle_bookings', null, 'invalid_booking_id', [
            'bookingID' => $booking_id ?: null,
        ], 'GET', 400);
    }
    $__pdf_abort(400);
}

require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../app/db/db.php';
require_once __DIR__ . '/../../app/rbac/roles.php';
require_once __DIR__ . '/../../app/modules/system/system.php';
require_once __DIR__ . '/../../app/modules/system/positions.php';
require_once __DIR__ . '/../../src/Services/vehicle/vehicle-reservation-utils.php';

$actor_pid = (string) ($_SESSION['pID'] ?? '');

// Authorization: requester OR director OR vehicle officer
$booking_row = null;

try {
    $booking_row = db_fetch_one(
        'SELECT bookingID, requesterPID FROM dh_vehicle_bookings WHERE bookingID = ? AND deletedAt IS NULL LIMIT 1',
        'i',
        $booking_id
    );
} catch (Throwable $e) {
    error_log('Database Exception (booking lookup pdf): ' . $e->getMessage());

    if (function_exists('audit_log')) {
        audit_log('vehicle', 'PDF_VIEW', 'FAIL', 'dh_vehicle_bookings', $booking_id, 'booking_lookup_failed', [], 'GET', 500);
    }
    $__pdf_abort(500);
}

if (!$booking_row) {
    if (function_exists('audit_log')) {
        audit_log('vehicle', 'PDF_VIEW', 'FAIL', 'dh_vehicle_bookings', $booking_id, 'booking_not_found', [], 'GET', 404);
    }
    $__pdf_abort(404);
}

$authorized = (string) ($booking_row['requesterPID'] ?? '') === $actor_pid;

if (!$authorized) {
    $is_director = system_get_current_director_pid() === $actor_pid;
    $is_vehicle_officer = rbac_user_has_role($connection, $actor_pid, ROLE_VEHICLE)
        || rbac_user_has_role($connection, $actor_pid, ROLE_ADMIN);

    // Backward-compatible roles (legacy teacher.roleID)
    if (!$is_vehicle_officer) {
        try {
            $legacy_role = db_fetch_one('SELECT roleID FROM teacher WHERE pID = ? AND status = 1 LIMIT 1', 's', $actor_pid);
            $legacy_role_id = (int) ($legacy_role['roleID'] ?? 0);

            if (in_array($legacy_role_id, [1, 3], true)) {
                $is_vehicle_officer = true;
            }
        } catch (Throwable $e) {
            error_log('Database Exception (legacy role pdf): ' . $e->getMessage());
        }
    }

    $authorized = $is_director || $is_vehicle_officer;
}

if (!$authorized) {
    if (function_exists('audit_log')) {
        audit_log('vehicle', 'PDF_VIEW', 'DENY', 'dh_vehicle_bookings', $booking_id, 'not_authorized', [], 'GET', 403);
    }
    $__pdf_abort(403);
}

$thai_months = [
    1 => 'มกราคม',
    2 => 'กุมภาพันธ์',
    3 => 'มีนาคม',
    4 => 'เมษายน',
    5 => 'พฤษภาคม',
    6 => 'มิถุนายน',
    7 => 'กรกฎาคม',
    8 => 'สิงหาคม',
    9 => 'กันยายน',
    10 => 'ตุลาคม',
    11 => 'พฤศจิกายน',
    12 => 'ธันวาคม',
];

$format_thai_date = static function (?string $date) use ($thai_months): string {
    $date = trim((string) $date);

    if ($date === '' || strpos($date, '0000-00-00') === 0) {
        return '-';
    }
    $obj = DateTime::createFromFormat('Y-m-d', $date);

    if ($obj === false) {
        return $date;
    }
    $day = (int) $obj->format('j');
    $month = (int) $obj->format('n');
    $year = (int) $obj->format('Y') + 543;
    $month_label = $thai_months[$month] ?? '';

    return trim($day . ' ' . $month_label . ' ' . $year);
};

$format_thai_time = static function (?string $datetime): string {
    $datetime = trim((string) $datetime);

    if ($datetime === '' || strpos($datetime, '0000-00-00') === 0) {
        return '-';
    }
    $obj = DateTime::createFromFormat('Y-m-d H:i:s', $datetime);

    if ($obj === false) {
        $obj = DateTime::createFromFormat('Y-m-d H:i', $datetime);
    }

    if ($obj === false) {
        return $datetime;
    }

    return str_replace(':', '.', $obj->format('H:i'));
};

$fuel_label = static function (?string $fuel): string {
    $fuel = strtolower(trim((string) $fuel));

    return match ($fuel) {
        'central' => 'ส่วนกลาง',
        'project' => 'โครงการ',
        'user' => 'ผู้ใช้',
        default => $fuel !== '' ? $fuel : '-',
    };
};

$safe_file_to_data_uri = static function (?string $relative_path): ?string {
    $relative_path = trim((string) $relative_path);

    if ($relative_path === '') {
        return null;
    }

    // Only allow local project files.
    $project_root = realpath(__DIR__ . '/../..');

    if ($project_root === false) {
        return null;
    }

    $relative_path = ltrim($relative_path, '/');

    // Allowlist signature paths only (prevents reading arbitrary local files).
    if (!preg_match('#^assets/img/signature/#', $relative_path)) {
        return null;
    }
    $candidate = realpath($project_root . '/' . $relative_path);

    if ($candidate === false || strpos($candidate, $project_root) !== 0 || !is_file($candidate)) {
        return null;
    }

    $ext = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
    $mime = match ($ext) {
        'png' => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        default => null,
    };

    if ($mime === null) {
        return null;
    }

    $contents = @file_get_contents($candidate);

    if ($contents === false || $contents === '') {
        return null;
    }

    return 'data:' . $mime . ';base64,' . base64_encode($contents);
};

$vehicle_columns = vehicle_reservation_get_table_columns($connection, 'dh_vehicle_bookings');
$has_assigned = vehicle_reservation_has_column($vehicle_columns, 'assignedByPID');

$select_fields = [
    'b.bookingID',
    'b.requesterPID',
    'b.vehicleID',
    'b.driverPID',
    'b.driverName',
    vehicle_reservation_has_column($vehicle_columns, 'driverTel')
        ? "COALESCE(NULLIF(drv.telephone, ''), b.driverTel) AS driverTel"
        : 'drv.telephone AS driverTel',
    'b.startAt',
    'b.endAt',
    'b.status',
    'b.statusReason',
    'b.approvedByPID',
    'b.approvedAt',
    'b.createdAt',
    'b.updatedAt',
];

$optional_columns = [
    'department',
    'purpose',
    'location',
    'passengerCount',
    'fuelSource',
    'writeDate',
    'companionIds',
    'requesterDisplayName',
    'assignedByPID',
    'assignedAt',
    'assignedNote',
    'approvalNote',
];

foreach ($optional_columns as $column) {
    if (vehicle_reservation_has_column($vehicle_columns, $column)) {
        $select_fields[] = 'b.' . $column;
    }
}

$req_position = system_position_join($connection, 'req', 'preq');
$asg_position = system_position_join($connection, 'asg', 'pasg');
$app_position = system_position_join($connection, 'app', 'papp');

$assigned_join = '';
$assigned_select = '';

if ($has_assigned) {
    $assigned_select = ',
        asg.fName AS assigned_name,
        asg.signature AS assigned_signature,
        ' . $asg_position['name'] . ' AS assigned_position';
    $assigned_join = 'LEFT JOIN teacher AS asg ON b.assignedByPID = asg.pID
        ' . $asg_position['join'];
}

$sql = 'SELECT ' . implode(', ', $select_fields) . ',
        req.fName AS requester_name,
        req.telephone AS requester_phone,
        req.signature AS requester_signature,
        ' . $req_position['name'] . ' AS requester_position,
        dep.dName AS requester_department,
        v.vehiclePlate,
        v.vehicleType,
        v.vehicleBrand,
        v.vehicleModel
        ' . $assigned_select . ',
        app.fName AS approver_name,
        app.signature AS approver_signature,
        ' . $app_position['name'] . ' AS approver_position
    FROM dh_vehicle_bookings AS b
    LEFT JOIN teacher AS req ON b.requesterPID = req.pID
    LEFT JOIN teacher AS drv ON b.driverPID = drv.pID
    LEFT JOIN department AS dep ON req.dID = dep.dID
    ' . $req_position['join'] . '
    LEFT JOIN dh_vehicles AS v ON b.vehicleID = v.vehicleID
    ' . $assigned_join . '
    LEFT JOIN teacher AS app ON b.approvedByPID = app.pID
    ' . $app_position['join'] . '
    WHERE b.bookingID = ? AND b.deletedAt IS NULL
    LIMIT 1';

$row = null;

try {
    $stmt = mysqli_prepare($connection, $sql);

    if ($stmt === false) {
        error_log('Database Error (prepare pdf): ' . mysqli_error($connection));
        $__pdf_abort(500);
    }
    mysqli_stmt_bind_param($stmt, 'i', $booking_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);
} catch (mysqli_sql_exception $exception) {
    error_log('Database Exception (pdf): ' . $exception->getMessage());
}

if (!$row) {
    $__pdf_abort(404);
}

$school_name = 'โรงเรียนดีบุกพังงาวิทยายน';

$status_key = strtoupper(trim((string) ($row['status'] ?? 'PENDING')));
$is_approved = in_array($status_key, ['APPROVED', 'COMPLETED'], true);
$is_rejected = in_array($status_key, ['REJECTED', 'CANCELLED'], true);

$created_at = trim((string) ($row['createdAt'] ?? ''));
$write_date = (string) ($row['writeDate'] ?? '');

if ($write_date === '' || strpos($write_date, '0000-00-00') === 0) {
    $write_date = $created_at !== '' ? substr($created_at, 0, 10) : '';
}

if ($write_date === '') {
    $start_at_fallback = (string) ($row['startAt'] ?? '');
    $write_date = $start_at_fallback !== '' ? substr($start_at_fallback, 0, 10) : '';
}

$start_at = (string) ($row['startAt'] ?? '');
$end_at = (string) ($row['endAt'] ?? '');
$start_date = $start_at !== '' ? substr($start_at, 0, 10) : '';
$end_date = $end_at !== '' ? substr($end_at, 0, 10) : $start_date;

$day_count_label = '-';

try {
    if ($start_date !== '' && $end_date !== '') {
        $start_obj = DateTime::createFromFormat('Y-m-d', $start_date);
        $end_obj = DateTime::createFromFormat('Y-m-d', $end_date);

        if ($start_obj && $end_obj) {
            $diff = $start_obj->diff($end_obj);
            $days = (int) $diff->days + 1;
            $day_count_label = (string) max(1, $days);
        }
    }
} catch (Exception $e) {
    // ignore
}

$requester_name = trim((string) ($row['requesterDisplayName'] ?? ''));

if ($requester_name === '') {
    $requester_name = trim((string) ($row['requester_name'] ?? ''));
}

$requester_position = trim((string) ($row['requester_position'] ?? ''));
$requester_department = trim((string) ($row['department'] ?? ''));

if ($requester_department === '') {
    $requester_department = trim((string) ($row['requester_department'] ?? ''));
}
$requester_phone = trim((string) ($row['requester_phone'] ?? ''));

$purpose = trim((string) ($row['purpose'] ?? ''));
$location = trim((string) ($row['location'] ?? ''));
$passengers = (string) ($row['passengerCount'] ?? $row['companionCount'] ?? '');
$passengers = $passengers !== '' ? $passengers : '-';
$fuel = $fuel_label((string) ($row['fuelSource'] ?? ''));

$companion_names = [];
$companion_ids_raw = (string) ($row['companionIds'] ?? '');
$companion_ids = [];

if ($companion_ids_raw !== '') {
    $decoded = json_decode($companion_ids_raw, true);

    if (is_array($decoded)) {
        foreach ($decoded as $pid) {
            $pid = trim((string) $pid);

            if ($pid !== '') {
                $companion_ids[] = $pid;
            }
        }
    }
}
$companion_ids = array_values(array_unique(array_filter($companion_ids)));

if (!empty($companion_ids)) {
    try {
        $placeholders = implode(', ', array_fill(0, count($companion_ids), '?'));
        $types = str_repeat('s', count($companion_ids));

        $stmt = mysqli_prepare($connection, 'SELECT pID, fName FROM teacher WHERE status = 1 AND pID IN (' . $placeholders . ')');

        if ($stmt) {
            $bind_params = array_merge([$stmt, $types], $companion_ids);
            $refs = [];

            foreach ($bind_params as $index => $value) {
                $refs[$index] = &$bind_params[$index];
            }

            if (call_user_func_array('mysqli_stmt_bind_param', $refs) !== false) {
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $name_map = [];

                while ($result && ($r = mysqli_fetch_assoc($result))) {
                    $pid = trim((string) ($r['pID'] ?? ''));

                    if ($pid !== '') {
                        $name_map[$pid] = trim((string) ($r['fName'] ?? ''));
                    }
                }

                foreach ($companion_ids as $pid) {
                    if (!empty($name_map[$pid])) {
                        $companion_names[] = $name_map[$pid];
                    }
                }
            }
            mysqli_stmt_close($stmt);
        }
    } catch (mysqli_sql_exception $exception) {
        error_log('Database Exception (companion pdf): ' . $exception->getMessage());
    }
}

$companion_label = '';

if (!empty($companion_names)) {
    $companion_label = implode(', ', $companion_names);
}

$vehicle_plate = trim((string) ($row['vehiclePlate'] ?? ''));
$vehicle_type = trim((string) ($row['vehicleType'] ?? ''));
$vehicle_model = trim((string) ($row['vehicleModel'] ?? ''));
$vehicle_label = trim(($vehicle_type !== '' ? $vehicle_type : '') . ' ' . ($vehicle_plate !== '' ? $vehicle_plate : ''));
$vehicle_label = trim($vehicle_label) !== '' ? trim($vehicle_label) : '-';

$driver_name = trim((string) ($row['driverName'] ?? ''));
$driver_tel = trim((string) ($row['driverTel'] ?? ''));

$assigned_name = trim((string) ($row['assigned_name'] ?? ''));
$assigned_position = trim((string) ($row['assigned_position'] ?? ''));
$assigned_note = trim((string) ($row['assignedNote'] ?? ''));

$approved_by_pid = trim((string) ($row['approvedByPID'] ?? ''));
$approved_at = trim((string) ($row['approvedAt'] ?? ''));
$approved_at = ($approved_at === '' || strpos($approved_at, '0000-00-00') === 0) ? '' : $approved_at;

$approval_note = trim((string) ($row['approvalNote'] ?? ''));

$requester_sig = $safe_file_to_data_uri((string) ($row['requester_signature'] ?? ''));
$assigned_sig = $safe_file_to_data_uri((string) ($row['assigned_signature'] ?? ''));

$resolve_director_at = static function (mysqli $connection, string $datetime): array {
    $datetime = trim($datetime);

    if ($datetime === '' || strpos($datetime, '0000-00-00') === 0) {
        $pid = (string) (system_get_current_director_pid() ?? '');
        $acting_now = (string) (system_get_acting_director_pid() ?? '');

        return [
            'pID' => $pid,
            'acting' => $acting_now !== '' && $acting_now === $pid,
        ];
    }

    try {
        $stmt = mysqli_prepare(
            $connection,
            'SELECT pID FROM dh_exec_duty_logs
                WHERE dutyStatus = 2
                AND created_at <= ?
                AND (end_at IS NULL OR end_at >= ?)
                ORDER BY created_at DESC, dutyLogID DESC
                LIMIT 1'
        );

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ss', $datetime, $datetime);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = $result ? mysqli_fetch_assoc($result) : null;
            mysqli_stmt_close($stmt);

            $acting_pid = $row ? trim((string) ($row['pID'] ?? '')) : '';

            if ($acting_pid !== '') {
                return [
                    'pID' => $acting_pid,
                    'acting' => true,
                ];
            }
        }
    } catch (mysqli_sql_exception $exception) {
        error_log('Database Exception (resolve director pdf): ' . $exception->getMessage());
    }

    return [
        'pID' => (string) (system_get_director_pid() ?? ''),
        'acting' => false,
    ];
};

$director_ref_dt = $approved_at;

if ($director_ref_dt === '') {
    if ($write_date !== '' && strpos($write_date, '0000-00-00') !== 0) {
        $director_ref_dt = $write_date . ' 00:00:00';
    } elseif ($created_at !== '' && strpos($created_at, '0000-00-00') !== 0) {
        $director_ref_dt = $created_at;
    }
}

$director_context = $resolve_director_at($connection, $director_ref_dt);
$boss_pid = trim((string) ($director_context['pID'] ?? ''));
$boss_is_acting = !empty($director_context['acting']);

$boss_name = '';
$boss_signature_path = '';

if ($boss_pid !== '') {
    try {
        $boss_position = system_position_join($connection, 't', 'p');
        $boss_row = db_fetch_one(
            'SELECT t.fName, t.signature, ' . $boss_position['name'] . ' AS position_name
                FROM teacher AS t
                ' . $boss_position['join'] . '
                WHERE t.pID = ? AND t.status = 1
                LIMIT 1',
            's',
            $boss_pid
        );

        if ($boss_row) {
            $boss_name = trim((string) ($boss_row['fName'] ?? ''));
            $boss_signature_path = trim((string) ($boss_row['signature'] ?? ''));
        }
    } catch (Throwable $e) {
        error_log('Database Exception (boss profile pdf): ' . $e->getMessage());
    }
}

$boss_signature = $safe_file_to_data_uri($boss_signature_path);
$boss_decision_by_director = $boss_pid !== '' && $approved_by_pid !== '' && $approved_by_pid === $boss_pid && ($is_approved || $is_rejected);
$boss_signature_for_doc = $boss_decision_by_director ? $boss_signature : null;
$boss_note_for_doc = $boss_decision_by_director ? $approval_note : '';

$boss_position_line_1 = $boss_is_acting
    ? 'รองผู้อำนวยการ' . $school_name
    : 'ผู้อำนวยการ' . $school_name;
$boss_position_line_2 = $boss_is_acting
    ? 'รักษาราชการแทนผู้อำนวยการ' . $school_name
    : '';

$order_allow_checked = $boss_decision_by_director && $is_approved;
$order_deny_checked = $boss_decision_by_director && $is_rejected;
$order_pending_label = !$boss_decision_by_director ? 'รอพิจารณา' : '';

$requester_position_label = $requester_position !== '' ? $requester_position : '-';
$requester_department_label = $requester_department !== '' ? $requester_department : '';
$purpose_label = $purpose !== '' ? $purpose : '-';
$location_label = $location !== '' ? $location : '-';
$companion_inline = $companion_label !== '' ? ('พร้อมด้วย ' . $companion_label . ' ') : '';

$paragraph_lines = [];
$paragraph_lines[] = trim('ข้าพเจ้า ' . ($requester_name !== '' ? $requester_name : '-') . ' ตำแหน่ง ' . $requester_position_label
    . ($requester_department_label !== '' ? (' ' . $requester_department_label) : '')
    . ' สังกัด ' . $school_name);
$paragraph_lines[] = trim($companion_inline . 'ขออนุญาตใช้รถเพื่อ ' . $purpose_label);
$paragraph_lines[] = trim('ณ ' . $location_label . ' มีคนนั่ง ' . $passengers . ' คน');
$paragraph_lines[] = trim('ตั้งแต่วันที่ ' . $format_thai_date($start_date) . ' เวลา ' . $format_thai_time($start_at) . ' น. ถึงวันที่ ' . $format_thai_date($end_date) . ' เวลา ' . $format_thai_time($end_at) . ' น.');
$paragraph_lines[] = trim('จำนวน ' . $day_count_label . ' วัน โดยใช้น้ำมันเชื้อเพลิงจาก ' . $fuel);
$order_status_label = $order_allow_checked ? 'อนุญาต' : ($order_deny_checked ? 'ไม่อนุญาต' : 'รอพิจารณา');

require_once __DIR__ . '/../../app/views/vehicle/vehicle-booking-pdf-template.php';
$html = vehicle_booking_pdf_render_html([
    'school_name' => $school_name,
    'write_date_label' => $format_thai_date($write_date),
    'paragraph_lines' => $paragraph_lines,
    'requester_signature' => $requester_sig,
    'requester_name' => $requester_name !== '' ? $requester_name : '-',
    'requester_position' => $requester_position !== '' ? $requester_position : '-',
    'vehicle_label' => $vehicle_label,
    'driver_name' => $driver_name,
    'driver_tel' => $driver_tel,
    'assigned_note' => $assigned_note,
    'assigned_signature' => $assigned_sig,
    'assigned_name' => $assigned_name !== '' ? $assigned_name : '-',
    'assigned_position' => $assigned_position !== '' ? $assigned_position : '-',
    'boss_note' => $boss_note_for_doc,
    'boss_name' => $boss_name !== '' ? $boss_name : '-',
    'boss_position_line_1' => $boss_position_line_1,
    'boss_position_line_2' => $boss_position_line_2,
    'boss_signature' => $boss_signature_for_doc,
    'order_allow_checked' => $order_allow_checked,
    'order_deny_checked' => $order_deny_checked,
    'order_status_label' => $order_status_label,
]);

try {
    $mpdf_font_dir = __DIR__ . '/../../assets/fonts/sarabun';
    $has_sarabun = is_dir($mpdf_font_dir)
        && is_file($mpdf_font_dir . '/Sarabun-Regular.ttf')
        && is_file($mpdf_font_dir . '/Sarabun-Bold.ttf')
        && is_file($mpdf_font_dir . '/Sarabun-Italic.ttf')
        && is_file($mpdf_font_dir . '/Sarabun-BoldItalic.ttf');

    // Use a versioned temp dir to avoid stale/corrupt font-cache artifacts across font updates.
    // Cache key changes automatically when Sarabun font binaries change.
    $font_sig_parts = [];

    foreach (['Sarabun-Regular.ttf', 'Sarabun-Bold.ttf', 'Sarabun-Italic.ttf', 'Sarabun-BoldItalic.ttf'] as $font_file) {
        $path = $mpdf_font_dir . '/' . $font_file;

        if (is_file($path)) {
            $font_sig_parts[] = $font_file . ':' . filesize($path) . ':' . filemtime($path);
        }
    }
    $cache_key = substr(sha1(implode('|', $font_sig_parts) . '|sarabun|otl=255|winTypo'), 0, 12);
    $mpdf_tmp = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'workflow-mpdf-' . $cache_key;

    if (!is_dir($mpdf_tmp)) {
        @mkdir($mpdf_tmp, 0777, true);
    }

    if (!is_dir($mpdf_tmp) || !is_writable($mpdf_tmp)) {
        $mpdf_tmp = sys_get_temp_dir();
    }

    $config_vars = (new ConfigVariables())->getDefaults();
    $font_dirs = $config_vars['fontDir'];

    $font_vars = (new FontVariables())->getDefaults();
    $font_data = $font_vars['fontdata'];

    if ($has_sarabun) {
        $font_dirs[] = $mpdf_font_dir;
        $font_data['sarabun'] = [
            'R' => 'Sarabun-Regular.ttf',
            'B' => 'Sarabun-Bold.ttf',
            'I' => 'Sarabun-Italic.ttf',
            'BI' => 'Sarabun-BoldItalic.ttf',
            // Enable OTL shaping for correct Thai vowels/tone marks placement.
            // We patch Sarabun fonts to include an invisible U+200B glyph so mPDF Thai shaper won't show tofu squares.
            'useOTL' => 0xFF,
        ];
    }

    $mpdf = new Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'default_font' => $has_sarabun ? 'sarabun' : 'garuda',
        // All inputs are UTF-8 already; disabling conversion avoids iconv warnings corrupting PDF output.
        'allow_charset_conversion' => false,
        // mPDF dictionary line breaking inserts U+200B (ZWSP) which can render as tofu squares
        // in some viewers/fonts. We prefer no boxes over dictionary-based Thai line breaking.
        'useDictionaryLBR' => false,
        'tempDir' => $mpdf_tmp,
        // Better vertical metrics for Thai (prevents tone marks/combining marks from clipping).
        'fontDescriptor' => 'winTypo',
        'fontDir' => $font_dirs,
        'fontdata' => $font_data,
        // Narrower body column (closer to official form layout)
        'margin_left' => 24,
        'margin_right' => 24,
        'margin_top' => 16,
        'margin_bottom' => 16,
    ]);
} catch (Throwable $e) {
    error_log('PDF init failed: ' . $e->getMessage());

    if (function_exists('audit_log')) {
        audit_log('vehicle', 'PDF_VIEW', 'FAIL', 'dh_vehicle_bookings', $booking_id, 'pdf_init_failed', [], 'GET', 500);
    }
    $__pdf_abort(500);
}

$mpdf->SetTitle('Vehicle booking #' . $booking_id);

try {
    // Guard against stray zero-width spaces from copy/paste which can render as tofu squares in PDFs.
    $html_clean = preg_replace('/[\\x{200B}\\x{FEFF}]/u', '', $html);

    if (is_string($html_clean) && $html_clean !== '') {
        $html = $html_clean;
    }
    $mpdf->WriteHTML($html);

    $download = isset($_GET['download']) && $_GET['download'] === '1';
    $disposition = $download ? 'attachment' : 'inline';

    // Official-friendly filename (Thai + ASCII fallback) for government documents.
    $filename_date = '';

    try {
        if ($write_date !== '' && strpos($write_date, '0000-00-00') !== 0) {
            $dt = DateTime::createFromFormat('Y-m-d', $write_date);

            if ($dt instanceof DateTime) {
                $filename_date = ((int) $dt->format('Y') + 543) . $dt->format('md');
            }
        }
    } catch (Throwable $e) {
        // ignore
    }
    $filename_date_suffix_th = $filename_date !== '' ? ('_วันที่' . $filename_date) : '';
    $filename_date_suffix_en = $filename_date !== '' ? ('_' . $filename_date) : '';

    $school_slug = preg_replace('/\\s+/u', '', (string) $school_name);
    $booking_no = str_pad((string) $booking_id, 4, '0', STR_PAD_LEFT);
    $filename_th = 'แบบขออนุญาตใช้รถยนต์ราชการ_' . $school_slug . '_เลขที่คำขอ' . $booking_no . ($filename_date !== '' ? ('_ลงวันที่' . $filename_date) : '') . '.pdf';
    $filename_ascii = 'gov_vehicle_request_' . $booking_no . $filename_date_suffix_en . '.pdf';
    $filename_ascii = preg_replace('/[^A-Za-z0-9._-]+/', '_', (string) $filename_ascii);

    $mpdf->SetTitle('แบบขออนุญาตใช้รถยนต์ราชการ เลขที่คำขอ ' . $booking_no);

    // Output as string so we can set a robust Content-Disposition with UTF-8 filename*.
    $pdf = $mpdf->Output('', 'S');

    if (!is_string($pdf) || $pdf === '') {
        throw new RuntimeException('PDF output is empty');
    }

    // Drop any stray output so the PDF stream always starts with %PDF.
    while (ob_get_level() > $__pdf_initial_ob_level) {
        ob_end_clean();
    }

    if (ob_get_level() > 0) {
        ob_clean();
    }

    header('Content-Type: application/pdf');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Content-Length: ' . strlen($pdf));

    if (function_exists('audit_log')) {
        $audit_action = $download ? 'PDF_DOWNLOAD' : 'PDF_VIEW';
        audit_log('vehicle', $audit_action, 'SUCCESS', 'dh_vehicle_bookings', $booking_id, null, [
            'disposition' => $disposition,
        ], 'GET', 200);
    }

    // RFC 5987 filename* for UTF-8; keep ASCII fallback for older clients.
    $filename_star = rawurlencode($filename_th);
    header('Content-Disposition: ' . $disposition . '; filename="' . $filename_ascii . '"; filename*=UTF-8\'\'' . $filename_star);

    echo $pdf;
} catch (Throwable $e) {
    error_log('PDF render failed: ' . $e->getMessage());

    if (function_exists('audit_log')) {
        audit_log('vehicle', 'PDF_VIEW', 'FAIL', 'dh_vehicle_bookings', $booking_id, 'pdf_render_failed', [], 'GET', 500);
    }
    $__pdf_abort(500);
}
exit();
