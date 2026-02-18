<?php

declare(strict_types=1);

require_once __DIR__ . '/../views/view.php';
require_once __DIR__ . '/../../src/Services/auth/auth-guard.php';
require_once __DIR__ . '/../../src/Services/security/security-service.php';
require_once __DIR__ . '/../../src/Services/teacher/teacher-phone-save.php';
require_once __DIR__ . '/../../src/Services/teacher/teacher-profile-image-upload.php';
require_once __DIR__ . '/../../src/Services/teacher/teacher-signature-upload.php';
require_once __DIR__ . '/../../src/Services/teacher/teacher-password-change.php';
require_once __DIR__ . '/../../src/Services/teacher/teacher-profile.php';

if (!function_exists('profile_index')) {
    function profile_index(): void
    {
        global $teacher;

        $profile_alert = $_SESSION['profile_alert'] ?? null;
        unset($_SESSION['profile_alert']);

        $active_tab = $_GET['tab'] ?? 'personal';
        $allowed_tabs = ['personal', 'signature', 'password'];

        if (!in_array($active_tab, $allowed_tabs, true)) {
            $active_tab = 'personal';
        }

        $profile_picture_raw = trim((string) ($teacher['picture'] ?? ''));
        $profile_picture = '';

        if ($profile_picture_raw !== '' && strtoupper($profile_picture_raw) !== 'EMPTY') {
            $profile_picture = $profile_picture_raw;
        }

        $signature_path = (string) ($teacher['signature'] ?? '');

        view_render('profile/index', [
            'teacher' => $teacher,
            'alert' => $profile_alert,
            'active_tab' => $active_tab,
            'profile_picture' => $profile_picture,
            'signature_path' => $signature_path,
        ]);
    }
}
