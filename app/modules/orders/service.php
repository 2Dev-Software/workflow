<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/../system/system.php';
require_once __DIR__ . '/../circulars/service.php';
require_once __DIR__ . '/../audit/logger.php';
require_once __DIR__ . '/../../services/uploads.php';
require_once __DIR__ . '/../../services/document-service.php';

if (!function_exists('order_document_number')) {
    function order_document_number(array $order): string
    {
        $orderNo = trim((string) ($order['orderNo'] ?? ''));

        if ($orderNo !== '') {
            return $orderNo;
        }
        $orderID = (int) ($order['orderID'] ?? 0);

        return $orderID > 0 ? 'ORDER-' . $orderID : '';
    }
}

if (!function_exists('order_sync_document')) {
    function order_sync_document(int $orderID): ?int
    {
        $order = order_get($orderID);

        if (!$order) {
            return null;
        }

        $documentNumber = order_document_number($order);

        if ($documentNumber === '') {
            return null;
        }

        return document_upsert([
            'documentType' => 'ORDER',
            'documentNumber' => $documentNumber,
            'subject' => (string) ($order['subject'] ?? ''),
            'content' => (string) ($order['detail'] ?? ''),
            'status' => (string) ($order['status'] ?? ''),
            'senderName' => (string) ($order['creatorName'] ?? ''),
            'createdByPID' => (string) ($order['createdByPID'] ?? ''),
            'updatedByPID' => $order['updatedByPID'] ?? null,
        ]);
    }
}

if (!function_exists('order_resolve_recipients')) {
    function order_resolve_recipients(array $factionIds, array $roleIds, array $personIds): array
    {
        return circular_resolve_person_ids($factionIds, $roleIds, $personIds);
    }
}

if (!function_exists('order_generate_number')) {
    function order_generate_number(int $year): array
    {
        $row = db_fetch_one('SELECT orderSeq FROM dh_orders WHERE dh_year = ? ORDER BY orderSeq DESC LIMIT 1 FOR UPDATE', 'i', $year);
        $seq = $row ? ((int) $row['orderSeq'] + 1) : 1;
        $orderNo = $seq . '/' . $year;

        return [$orderNo, $seq];
    }
}

if (!function_exists('order_preview_number')) {
    function order_preview_number(int $year): string
    {
        $row = db_fetch_one('SELECT orderSeq FROM dh_orders WHERE dh_year = ? ORDER BY orderSeq DESC LIMIT 1', 'i', $year);
        $seq = $row ? ((int) $row['orderSeq'] + 1) : 1;

        return $seq . '/' . $year;
    }
}

if (!function_exists('order_count_new_uploads')) {
    function order_count_new_uploads(array $files): int
    {
        $normalized = upload_normalize_files($files);
        $normalized = array_values(array_filter($normalized, static function (array $file): bool {
            return (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        }));

        return count($normalized);
    }
}

if (!function_exists('order_update_draft')) {
    function order_update_draft(int $orderID, string $actorPID, array $data): void
    {
        $order = order_get_for_owner($orderID, $actorPID);

        if (!$order) {
            throw new RuntimeException('ไม่พบคำสั่งหรือไม่มีสิทธิ์แก้ไข');
        }

        $status = (string) ($order['status'] ?? '');

        if (!in_array($status, [ORDER_STATUS_WAITING_ATTACHMENT, ORDER_STATUS_COMPLETE], true)) {
            throw new RuntimeException('แก้ไขได้เฉพาะคำสั่งที่ยังไม่ส่ง');
        }

        $subject = trim((string) ($data['subject'] ?? ''));
        $detail = trim((string) ($data['detail'] ?? ''));

        if ($subject === '') {
            throw new RuntimeException('กรุณากรอกหัวข้อ');
        }

        db_begin();

        try {
            order_update_record($orderID, [
                'subject' => $subject,
                'detail' => $detail !== '' ? $detail : null,
                'updatedByPID' => $actorPID,
            ]);
            order_sync_document($orderID);
            db_commit();
            audit_log('orders', 'UPDATE', 'SUCCESS', 'dh_orders', $orderID);
        } catch (Throwable $e) {
            db_rollback();
            error_log('Order update failed: ' . $e->getMessage());
            audit_log('orders', 'UPDATE', 'FAIL', 'dh_orders', $orderID, $e->getMessage());
            throw $e;
        }
    }
}

if (!function_exists('order_create_draft')) {
    function order_create_draft(array $data, array $files = []): int
    {
        db_begin();

        try {
            [$orderNo, $seq] = order_generate_number((int) $data['dh_year']);
            $data['orderNo'] = $orderNo;
            $data['orderSeq'] = $seq;
            $orderID = order_create_record($data);
            order_add_route($orderID, 'CREATE', $data['createdByPID'], null, null);

            if (!empty($files)) {
                upload_store_files($files, ORDER_MODULE_NAME, ORDER_ENTITY_NAME, (string) $orderID, (string) $data['createdByPID'], [
                    'max_files' => 5,
                ]);
                order_update_record($orderID, [
                    'status' => ORDER_STATUS_COMPLETE,
                    'updatedByPID' => $data['createdByPID'],
                ]);
            }

            order_sync_document($orderID);

            db_commit();
            audit_log('orders', 'CREATE', 'SUCCESS', 'dh_orders', $orderID);

            return $orderID;
        } catch (Throwable $e) {
            db_rollback();
            error_log('Order create failed: ' . $e->getMessage());
            audit_log('orders', 'CREATE', 'FAIL', 'dh_orders', null, $e->getMessage());
            throw $e;
        }
    }
}

if (!function_exists('order_attach_files')) {
    function order_attach_files(int $orderID, string $actorPID, array $files): void
    {
        $new_count = order_count_new_uploads($files);

        if ($new_count <= 0) {
            return;
        }

        $order = order_get_for_owner($orderID, $actorPID);

        if (!$order) {
            throw new RuntimeException('ไม่พบคำสั่งหรือไม่มีสิทธิ์แนบไฟล์');
        }

        $status = (string) ($order['status'] ?? '');

        if (!in_array($status, [ORDER_STATUS_WAITING_ATTACHMENT, ORDER_STATUS_COMPLETE], true)) {
            throw new RuntimeException('ไม่สามารถแนบไฟล์ในสถานะปัจจุบัน');
        }

        $existing_count = count(order_get_attachments($orderID));

        if (($existing_count + $new_count) > 5) {
            throw new RuntimeException('แนบไฟล์ได้สูงสุด 5 ไฟล์');
        }

        db_begin();

        try {
            upload_store_files($files, ORDER_MODULE_NAME, ORDER_ENTITY_NAME, (string) $orderID, $actorPID, [
                'max_files' => 5,
            ]);
            order_update_record($orderID, [
                'status' => ORDER_STATUS_COMPLETE,
                'updatedByPID' => $actorPID,
            ]);
            order_sync_document($orderID);
            db_commit();
            audit_log('orders', 'ATTACH', 'SUCCESS', 'dh_orders', $orderID);
        } catch (Throwable $e) {
            db_rollback();
            error_log('Order attach failed: ' . $e->getMessage());
            audit_log('orders', 'ATTACH', 'FAIL', 'dh_orders', $orderID, $e->getMessage());
            throw $e;
        }
    }
}

if (!function_exists('order_send')) {
    function order_send(int $orderID, string $senderPID, array $recipients): void
    {
        $order = order_get($orderID);

        if (!$order || (string) ($order['createdByPID'] ?? '') !== $senderPID || ($order['status'] ?? '') !== ORDER_STATUS_COMPLETE) {
            throw new RuntimeException('ไม่สามารถส่งคำสั่งได้');
        }

        if (count(order_get_attachments($orderID)) <= 0) {
            throw new RuntimeException('กรุณาแนบไฟล์คำสั่งก่อนส่ง');
        }

        db_begin();

        try {
            if (!empty($recipients['targets'])) {
                order_add_recipients($orderID, $recipients['targets']);
            }
            $recipientPIDs = array_filter(array_unique(array_diff($recipients['pids'], [$senderPID])));

            if (empty($recipientPIDs)) {
                throw new RuntimeException('กรุณาเลือกผู้รับอย่างน้อย 1 คน');
            }
            order_add_inboxes($orderID, $recipientPIDs, $senderPID);
            order_update_record($orderID, [
                'status' => ORDER_STATUS_SENT,
                'updatedByPID' => $senderPID,
            ]);
            order_add_route($orderID, 'SEND', $senderPID, null, null);
            $documentID = order_sync_document($orderID);

            if ($documentID) {
                document_add_recipients($documentID, $recipientPIDs, INBOX_TYPE_NORMAL);
            }
            db_commit();
            audit_log('orders', 'SEND', 'SUCCESS', 'dh_orders', $orderID);
        } catch (Throwable $e) {
            db_rollback();
            error_log('Order send failed: ' . $e->getMessage());
            audit_log('orders', 'SEND', 'FAIL', 'dh_orders', $orderID, $e->getMessage());
            throw $e;
        }
    }
}

if (!function_exists('order_recall')) {
    function order_recall(int $orderID, string $actorPID): void
    {
        $order = order_get_for_owner($orderID, $actorPID);

        if (!$order) {
            throw new RuntimeException('ไม่พบคำสั่งหรือไม่มีสิทธิ์ดึงกลับ');
        }

        if ((string) ($order['status'] ?? '') !== ORDER_STATUS_SENT) {
            throw new RuntimeException('ดึงกลับได้เฉพาะคำสั่งที่ส่งแล้ว');
        }

        if (order_has_any_read($orderID)) {
            throw new RuntimeException('ไม่สามารถดึงกลับได้ เนื่องจากมีผู้รับเปิดอ่านแล้ว');
        }

        db_begin();

        try {
            order_clear_delivery($orderID);
            order_update_record($orderID, [
                'status' => ORDER_STATUS_COMPLETE,
                'updatedByPID' => $actorPID,
            ]);
            order_add_route($orderID, 'RECALL', $actorPID, null, null);
            $documentID = order_sync_document($orderID);

            if ($documentID && db_table_exists(db_connection(), 'dh_document_recipients')) {
                db_execute(
                    'UPDATE dh_document_recipients
                     SET inboxStatus = "ARCHIVED"
                     WHERE documentID = ? AND inboxType = ?',
                    'is',
                    $documentID,
                    INBOX_TYPE_NORMAL
                );
            }

            db_commit();
            audit_log('orders', 'RECALL', 'SUCCESS', 'dh_orders', $orderID);
        } catch (Throwable $e) {
            db_rollback();
            error_log('Order recall failed: ' . $e->getMessage());
            audit_log('orders', 'RECALL', 'FAIL', 'dh_orders', $orderID, $e->getMessage());
            throw $e;
        }
    }
}
