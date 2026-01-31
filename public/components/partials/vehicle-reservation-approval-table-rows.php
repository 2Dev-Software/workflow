<?php if (empty($vehicle_booking_requests)): ?>
    <tr>
        <td colspan="7" class="booking-empty">ไม่มีรายการรออนุมัติ</td>
    </tr>
<?php else: ?>
    <?php foreach ($vehicle_booking_requests as $request_item): ?>
        <?php
        $status_key = strtoupper(trim((string) ($request_item['status'] ?? 'PENDING')));
        $status_meta = $vehicle_approval_status_labels[$status_key] ?? $vehicle_approval_status_labels['PENDING'];
        $status_label = $status_meta['label'] ?? 'รออนุมัติ';
        $status_class = $status_meta['class'] ?? 'pending';
        if ($status_key === 'ASSIGNED') {
            $viewer_mode = $vehicle_approval_mode ?? 'officer';
            if ($viewer_mode === 'officer') {
                $status_label = 'มอบหมายแล้ว';
                $status_class = 'approved';
            } else {
                $status_label = 'รอผู้บริหารอนุมัติ';
                $status_class = 'pending';
            }
        }

        $start_at = (string) ($request_item['startAt'] ?? '');
        $end_at = (string) ($request_item['endAt'] ?? '');
        $start_date = $start_at !== '' ? substr($start_at, 0, 10) : '';
        $end_date = $end_at !== '' ? substr($end_at, 0, 10) : '';
        $date_range = $format_thai_date_range($start_date, $end_date !== '' ? $end_date : $start_date);
        $time_range = '-';
        if ($start_at !== '' && $end_at !== '') {
            $start_time = substr($start_at, 11, 5);
            $end_time = substr($end_at, 11, 5);
            $time_range = trim($start_time . '-' . $end_time);
        }

        $requester_name = trim((string) ($request_item['requesterDisplayName'] ?? ''));
        if ($requester_name === '') {
            $requester_name = trim((string) ($request_item['requester_name'] ?? ''));
        }
        $department_name = trim((string) ($request_item['department'] ?? ''));
        if ($department_name === '') {
            $department_name = trim((string) ($request_item['department_name'] ?? ''));
        }
        $contact_phone = trim((string) ($request_item['requester_phone'] ?? ''));
        $purpose_text = trim((string) ($request_item['purpose'] ?? ''));
        $purpose_text = $purpose_text !== '' ? $purpose_text : '-';
        $location_text = trim((string) ($request_item['location'] ?? ''));
        $location_text = $location_text !== '' ? $location_text : '-';
        $passenger_count = (string) ($request_item['passengerCount'] ?? $request_item['companionCount'] ?? '-');
        $driver_pid = trim((string) ($request_item['driverPID'] ?? ''));
        $driver_name = trim((string) ($request_item['driverName'] ?? ''));
        $driver_tel = trim((string) ($request_item['driverTel'] ?? ''));
        $driver_label = $driver_name !== '' ? $driver_name : '-';
        if ($driver_tel !== '') {
            $driver_label .= ' (' . $driver_tel . ')';
        }

        $vehicle_id = trim((string) ($request_item['vehicleID'] ?? ''));
        $vehicle_plate = trim((string) ($request_item['vehiclePlate'] ?? ''));
        $vehicle_type = trim((string) ($request_item['vehicleType'] ?? ''));
        $vehicle_label = $vehicle_plate !== '' ? $vehicle_plate : $vehicle_type;
        if ($vehicle_label === '') {
            $vehicle_label = (string) ($request_item['vehicleID'] ?? '-');
        }
        $vehicle_detail = trim($vehicle_type . ' ' . (string) ($request_item['vehicleModel'] ?? ''));
        $vehicle_detail = trim($vehicle_detail) !== '' ? trim($vehicle_detail) : '-';

        $status_reason = trim((string) ($request_item['statusReason'] ?? ''));
        if (in_array($status_key, ['REJECTED', 'CANCELLED'], true) && $status_reason === '') {
            $status_reason = 'ไม่ระบุเหตุผล';
        }

        $approval_name = trim((string) ($request_item['approver_name'] ?? ''));
        if ($approval_name === '' && $status_key !== 'PENDING') {
            $approval_name = 'เจ้าหน้าที่ระบบ';
        }
        $approval_name = $status_key === 'PENDING' ? 'รอการอนุมัติ' : $approval_name;
        $approval_at = $format_thai_datetime((string) ($request_item['approvedAt'] ?? ''));
        $created_label = $format_thai_datetime((string) ($request_item['createdAt'] ?? ''));
        $updated_label = $format_thai_datetime((string) ($request_item['updatedAt'] ?? ''));

        $attachments = $vehicle_booking_attachments[(string) ($request_item['bookingID'] ?? '')] ?? [];
        $attachments_json = htmlspecialchars(
            json_encode($attachments, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ENT_QUOTES,
            'UTF-8'
        );
        ?>
        <tr class="approval-row <?= htmlspecialchars($status_class, ENT_QUOTES, 'UTF-8') ?>">
            <td>
                <?= htmlspecialchars($date_range, ENT_QUOTES, 'UTF-8') ?><br>
                <span class="detail-subtext"><?= htmlspecialchars($time_range, ENT_QUOTES, 'UTF-8') ?></span>
            </td>
            <td>
                <?= htmlspecialchars($requester_name !== '' ? $requester_name : '-', ENT_QUOTES, 'UTF-8') ?>
                <div class="detail-subtext"><?= htmlspecialchars($department_name !== '' ? $department_name : '-', ENT_QUOTES, 'UTF-8') ?></div>
                <div class="detail-subtext">โทร <?= htmlspecialchars($contact_phone !== '' ? $contact_phone : '-', ENT_QUOTES, 'UTF-8') ?></div>
            </td>
            <td>
                <?= htmlspecialchars($vehicle_label, ENT_QUOTES, 'UTF-8') ?>
                <div class="detail-subtext"><?= htmlspecialchars($vehicle_detail, ENT_QUOTES, 'UTF-8') ?></div>
            </td>
            <td>
                <?= htmlspecialchars($purpose_text, ENT_QUOTES, 'UTF-8') ?>
            </td>
            <td>
                <?= htmlspecialchars($location_text, ENT_QUOTES, 'UTF-8') ?>
            </td>
            <td>
                <span class="status-pill <?= htmlspecialchars($status_class, ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($status_label, ENT_QUOTES, 'UTF-8') ?>
                </span>
                <?php if (in_array($status_key, ['REJECTED', 'CANCELLED'], true)): ?>
                    <div class="status-reason">เหตุผล: <?= htmlspecialchars($status_reason, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
            </td>
            <td class="booking-action-cell">
                <div class="booking-action-group">
                    <button type="button" class="booking-action-btn secondary" data-vehicle-approval-action="detail"
                        data-approval-id="<?= htmlspecialchars((string) ($request_item['bookingID'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>"
                        data-approval-code="<?= htmlspecialchars((string) ($request_item['bookingID'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>"
                        data-approval-vehicle-id="<?= htmlspecialchars($vehicle_id, ENT_QUOTES, 'UTF-8') ?>"
                        data-approval-vehicle="<?= htmlspecialchars($vehicle_label, ENT_QUOTES, 'UTF-8') ?>"
                        data-approval-driver-id="<?= htmlspecialchars($driver_pid, ENT_QUOTES, 'UTF-8') ?>"
                        data-approval-driver-name="<?= htmlspecialchars($driver_name, ENT_QUOTES, 'UTF-8') ?>"
                        data-approval-driver-tel="<?= htmlspecialchars($driver_tel, ENT_QUOTES, 'UTF-8') ?>"
                        data-approval-date="<?= htmlspecialchars($date_range, ENT_QUOTES, 'UTF-8') ?>"
                        data-approval-time="<?= htmlspecialchars($time_range, ENT_QUOTES, 'UTF-8') ?>"
                        data-approval-requester="<?= htmlspecialchars($requester_name !== '' ? $requester_name : '-', ENT_QUOTES, 'UTF-8') ?>"
                        data-approval-department="<?= htmlspecialchars($department_name !== '' ? $department_name : '-', ENT_QUOTES, 'UTF-8') ?>"
                        data-approval-contact="<?= htmlspecialchars($contact_phone !== '' ? $contact_phone : '-', ENT_QUOTES, 'UTF-8') ?>"
                        data-approval-purpose="<?= htmlspecialchars($purpose_text, ENT_QUOTES, 'UTF-8') ?>"
                        data-approval-location="<?= htmlspecialchars($location_text, ENT_QUOTES, 'UTF-8') ?>"
                        data-approval-passengers="<?= htmlspecialchars((string) $passenger_count, ENT_QUOTES, 'UTF-8') ?>"
                        data-approval-driver="<?= htmlspecialchars($driver_label, ENT_QUOTES, 'UTF-8') ?>"
                        data-approval-status="<?= htmlspecialchars($status_key, ENT_QUOTES, 'UTF-8') ?>"
                        data-approval-status-label="<?= htmlspecialchars($status_label, ENT_QUOTES, 'UTF-8') ?>"
                        data-approval-status-class="<?= htmlspecialchars($status_class, ENT_QUOTES, 'UTF-8') ?>"
                        data-approval-reason="<?= htmlspecialchars($status_reason, ENT_QUOTES, 'UTF-8') ?>"
                        data-approval-name="<?= htmlspecialchars($approval_name, ENT_QUOTES, 'UTF-8') ?>"
                        data-approval-at="<?= htmlspecialchars($approval_at, ENT_QUOTES, 'UTF-8') ?>"
                        data-approval-created="<?= htmlspecialchars($created_label, ENT_QUOTES, 'UTF-8') ?>"
                        data-approval-updated="<?= htmlspecialchars($updated_label, ENT_QUOTES, 'UTF-8') ?>"
                        data-approval-attachments="<?= $attachments_json ?>">
                        ดูรายละเอียด
                    </button>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
<?php endif; ?>
