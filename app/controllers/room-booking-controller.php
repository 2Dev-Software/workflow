<?php

declare(strict_types=1);

require_once __DIR__ . '/../views/view.php';
require_once __DIR__ . '/../../src/Services/auth/auth-guard.php';
require_once __DIR__ . '/../../src/Services/security/security-service.php';
require_once __DIR__ . '/../auth/csrf.php';
require_once __DIR__ . '/../rbac/current_user.php';
require_once __DIR__ . '/../modules/system/system.php';

if (!function_exists('room_booking_index')) {
    function room_booking_index(): void
    {
        $current_user = current_user() ?? [];
        $teacher_name = (string) ($current_user['fName'] ?? '');

        $dh_year_value = system_get_dh_year();
        $currentThaiYear = (int) date('Y') + 543;

        if ($dh_year_value < 2500) {
            $dh_year_value = $currentThaiYear;
        }

        $room_booking_year = $dh_year_value;

        require __DIR__ . '/../../src/Services/room/room-booking-save.php';
        require __DIR__ . '/../../src/Services/room/room-booking-data.php';

        $booking_alert = $_SESSION['room_booking_alert'] ?? null;
        unset($_SESSION['room_booking_alert']);

        view_render('room/booking', [
            'teacher_name' => $teacher_name,
            'dh_year_value' => $dh_year_value,
            'room_booking_room_list' => $room_booking_room_list ?? [],
            'room_booking_total' => $room_booking_total ?? 0,
            'room_booking_approved_total' => $room_booking_approved_total ?? 0,
            'room_booking_pending_total' => $room_booking_pending_total ?? 0,
            'my_booking_subtitle' => $my_booking_subtitle ?? '',
            'my_bookings_latest' => $my_bookings_latest ?? [],
            'my_bookings_sorted' => $my_bookings_sorted ?? [],
            'room_booking_events' => $room_booking_events ?? [],
            'booking_alert' => $booking_alert,
        ]);
    }
}
