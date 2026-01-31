<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/../system/system.php';
require_once __DIR__ . '/../circulars/service.php';
require_once __DIR__ . '/../audit/logger.php';
require_once __DIR__ . '/../../services/uploads.php';

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
        $orderNo = $year . '/' . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);

        return [$orderNo, $seq];
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
        if (empty($files)) {
            return;
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
