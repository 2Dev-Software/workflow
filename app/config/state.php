<?php
declare(strict_types=1);

// Standardized state machines for document workflows.

// Internal circulars
const INTERNAL_STATUS_DRAFT = 'INTERNAL_DRAFT';
const INTERNAL_STATUS_SENT = 'INTERNAL_SENT';
const INTERNAL_STATUS_RECALLED = 'INTERNAL_RECALLED';
const INTERNAL_STATUS_ARCHIVED = 'INTERNAL_ARCHIVED';

// External circulars (from outside)
const EXTERNAL_STATUS_SUBMITTED = 'EXTERNAL_SUBMITTED';
const EXTERNAL_STATUS_PENDING_REVIEW = 'EXTERNAL_PENDING_REVIEW';
const EXTERNAL_STATUS_REVIEWED = 'EXTERNAL_REVIEWED';
const EXTERNAL_STATUS_FORWARDED = 'EXTERNAL_FORWARDED';

// Outgoing letters
const OUTGOING_STATUS_WAITING_ATTACHMENT_V2 = 'OUTGOING_WAITING_ATTACHMENT';
const OUTGOING_STATUS_COMPLETE_V2 = 'OUTGOING_COMPLETE';

// Government orders
const ORDER_STATUS_WAITING_ATTACHMENT_V2 = 'ORDER_WAITING_ATTACHMENT';
const ORDER_STATUS_COMPLETE_V2 = 'ORDER_COMPLETE';
const ORDER_STATUS_SENT_V2 = 'ORDER_SENT';

// Inbox types
const INBOX_TYPE_NORMAL = 'normal_inbox';
const INBOX_TYPE_SPECIAL_PRINCIPAL = 'special_principal_inbox';
const INBOX_TYPE_SARABAN_RETURN = 'saraban_return_inbox';
const INBOX_TYPE_ACTING_PRINCIPAL = 'acting_principal_inbox';

if (!function_exists('workflow_state_machine')) {
    function workflow_state_machine(): array
    {
        return [
            'internal' => [
                INTERNAL_STATUS_DRAFT => [INTERNAL_STATUS_SENT],
                INTERNAL_STATUS_SENT => [INTERNAL_STATUS_RECALLED, INTERNAL_STATUS_ARCHIVED],
                INTERNAL_STATUS_RECALLED => [INTERNAL_STATUS_SENT],
                INTERNAL_STATUS_ARCHIVED => [],
            ],
            'external' => [
                EXTERNAL_STATUS_SUBMITTED => [EXTERNAL_STATUS_PENDING_REVIEW],
                EXTERNAL_STATUS_PENDING_REVIEW => [EXTERNAL_STATUS_REVIEWED, EXTERNAL_STATUS_FORWARDED],
                EXTERNAL_STATUS_REVIEWED => [EXTERNAL_STATUS_FORWARDED],
                EXTERNAL_STATUS_FORWARDED => [],
            ],
            'outgoing' => [
                OUTGOING_STATUS_WAITING_ATTACHMENT_V2 => [OUTGOING_STATUS_COMPLETE_V2],
                OUTGOING_STATUS_COMPLETE_V2 => [],
            ],
            'orders' => [
                ORDER_STATUS_WAITING_ATTACHMENT_V2 => [ORDER_STATUS_COMPLETE_V2],
                ORDER_STATUS_COMPLETE_V2 => [ORDER_STATUS_SENT_V2],
                ORDER_STATUS_SENT_V2 => [],
            ],
        ];
    }
}
