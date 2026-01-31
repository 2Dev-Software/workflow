<?php
declare(strict_types=1);

require_once __DIR__ . '/../views/view.php';
require_once __DIR__ . '/../../src/Services/auth/auth-guard.php';
require_once __DIR__ . '/../../src/Services/security/security-service.php';
require_once __DIR__ . '/../auth/csrf.php';
require_once __DIR__ . '/../rbac/current_user.php';
require_once __DIR__ . '/../modules/system/system.php';

if (!function_exists('room_booking_approval_index')) {
    function room_booking_approval_index(): void
    {
        $current_user = current_user() ?? [];
        $current_role_id = (int) ($current_user['roleID'] ?? 0);
        if (!in_array($current_role_id, [1, 5], true)) {
            header('Location: dashboard.php', true, 302);
            exit();
        }

        require __DIR__ . '/../../src/Services/room/room-booking-approval-actions.php';

        $dh_year_value = system_get_dh_year();
        $currentThaiYear = (int) date('Y') + 543;
        if ($dh_year_value < 2500) {
            $dh_year_value = $currentThaiYear;
        }

        require __DIR__ . '/../../src/Services/room/room-booking-approval-data.php';

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

        $format_thai_date = static function (string $date) use ($thai_months): string {
            $date_obj = DateTime::createFromFormat('Y-m-d', $date);
            if ($date_obj === false) {
                return $date;
            }

            $day = (int) $date_obj->format('j');
            $month = (int) $date_obj->format('n');
            $year = (int) $date_obj->format('Y') + 543;
            $month_label = $thai_months[$month] ?? '';

            return trim($day . ' ' . $month_label . ' ' . $year);
        };

        $format_thai_date_range = static function (string $start, string $end) use ($format_thai_date, $thai_months): string {
            if ($end === '' || $start === $end) {
                return $format_thai_date($start);
            }

            $start_obj = DateTime::createFromFormat('Y-m-d', $start);
            $end_obj = DateTime::createFromFormat('Y-m-d', $end);
            if ($start_obj === false || $end_obj === false) {
                return $format_thai_date($start) . ' - ' . $format_thai_date($end);
            }

            $start_day = (int) $start_obj->format('j');
            $start_month = (int) $start_obj->format('n');
            $start_year = (int) $start_obj->format('Y') + 543;
            $end_day = (int) $end_obj->format('j');
            $end_month = (int) $end_obj->format('n');
            $end_year = (int) $end_obj->format('Y') + 543;
            $start_month_label = $thai_months[$start_month] ?? '';
            $end_month_label = $thai_months[$end_month] ?? '';

            if ($start_year === $end_year && $start_month === $end_month) {
                return trim($start_day . '-' . $end_day . ' ' . $start_month_label . ' ' . $start_year);
            }

            if ($start_year === $end_year) {
                return trim($start_day . ' ' . $start_month_label . ' - ' . $end_day . ' ' . $end_month_label . ' ' . $start_year);
            }

            return trim($start_day . ' ' . $start_month_label . ' ' . $start_year . ' - ' . $end_day . ' ' . $end_month_label . ' ' . $end_year);
        };

        $format_thai_datetime = static function (string $datetime) use ($thai_months): string {
            if ($datetime === '' || strpos($datetime, '0000-00-00') === 0) {
                return '-';
            }

            $date_obj = DateTime::createFromFormat('Y-m-d H:i:s', $datetime);
            if ($date_obj === false) {
                $date_obj = DateTime::createFromFormat('Y-m-d H:i', $datetime);
            }
            if ($date_obj === false) {
                return $datetime;
            }

            $day = (int) $date_obj->format('j');
            $month = (int) $date_obj->format('n');
            $year = (int) $date_obj->format('Y') + 543;
            $month_label = $thai_months[$month] ?? '';

            return trim($day . ' ' . $month_label . ' ' . $year . ' เวลา ' . $date_obj->format('H:i'));
        };

        $room_booking_approval_status_labels = [
            0 => ['label' => 'รออนุมัติ', 'class' => 'pending'],
            1 => ['label' => 'อนุมัติแล้ว', 'class' => 'approved'],
            2 => ['label' => 'ไม่อนุมัติ', 'class' => 'rejected'],
        ];

        $room_booking_approval_requests = $room_booking_approval_requests ?? [];
        $room_booking_approval_total = $room_booking_approval_total ?? count($room_booking_approval_requests);
        $room_booking_approval_pending_total = $room_booking_approval_pending_total ?? 0;
        $room_booking_approval_approved_total = $room_booking_approval_approved_total ?? 0;
        $room_booking_approval_rejected_total = $room_booking_approval_rejected_total ?? 0;
        $room_booking_approval_query = $room_booking_approval_query ?? '';
        $room_booking_approval_status = $room_booking_approval_status ?? 'all';
        $room_booking_approval_room = $room_booking_approval_room ?? 'all';

        $room_booking_approval_alert = $_SESSION['room_booking_approval_alert'] ?? null;
        unset($_SESSION['room_booking_approval_alert']);

        $room_booking_approval_return_url = 'room-booking-approval.php';
        if (!empty($_SERVER['QUERY_STRING'])) {
            $room_booking_approval_return_url .= '?' . $_SERVER['QUERY_STRING'];
        }

        if (isset($_GET['ajax_filter'])) {
            require __DIR__ . '/../../public/components/partials/room-booking-approval-table-rows.php';
            exit();
        }

        view_render('room/booking-approval', [
            'room_booking_room_list' => $room_booking_room_list ?? [],
            'room_booking_approval_requests' => $room_booking_approval_requests,
            'room_booking_approval_total' => $room_booking_approval_total,
            'room_booking_approval_pending_total' => $room_booking_approval_pending_total,
            'room_booking_approval_approved_total' => $room_booking_approval_approved_total,
            'room_booking_approval_rejected_total' => $room_booking_approval_rejected_total,
            'room_booking_approval_query' => $room_booking_approval_query,
            'room_booking_approval_status' => $room_booking_approval_status,
            'room_booking_approval_room' => $room_booking_approval_room,
            'room_booking_approval_alert' => $room_booking_approval_alert,
            'room_booking_approval_return_url' => $room_booking_approval_return_url,
            'room_booking_approval_status_labels' => $room_booking_approval_status_labels,
            'format_thai_date_range' => $format_thai_date_range,
            'format_thai_datetime' => $format_thai_datetime,
        ]);
    }
}
