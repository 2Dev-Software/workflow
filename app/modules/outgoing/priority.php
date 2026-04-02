<?php

declare(strict_types=1);

if (!function_exists('outgoing_priority_options')) {
    function outgoing_priority_options(): array
    {
        return [
            'normal' => 'ปกติ',
            'urgent' => 'ด่วน',
            'high' => 'ด่วนมาก',
            'highest' => 'ด่วนที่สุด',
        ];
    }
}

if (!function_exists('outgoing_normalize_priority_key')) {
    function outgoing_normalize_priority_key(?string $value): string
    {
        $value = strtolower(trim((string) $value));
        $options = outgoing_priority_options();

        if (isset($options[$value])) {
            return $value;
        }

        $matched_key = array_search(trim((string) $value), $options, true);

        return is_string($matched_key) ? $matched_key : 'normal';
    }
}

if (!function_exists('outgoing_priority_label_from_key')) {
    function outgoing_priority_label_from_key(?string $key): string
    {
        $normalized_key = outgoing_normalize_priority_key($key);
        $options = outgoing_priority_options();

        return $options[$normalized_key] ?? $options['normal'];
    }
}

if (!function_exists('outgoing_detect_priority_key_from_text')) {
    function outgoing_detect_priority_key_from_text(?string $text): ?string
    {
        $text = trim((string) $text);

        if ($text === '') {
            return null;
        }

        $priority_patterns = [
            'highest' => ['ด่วนที่สุด'],
            'high' => ['ด่วนมาก'],
            'urgent' => ['ด่วน'],
            'normal' => ['ปกติ'],
        ];

        foreach ($priority_patterns as $priority_key => $labels) {
            foreach ($labels as $label) {
                if (mb_stripos($text, $label) !== false) {
                    return $priority_key;
                }
            }
        }

        return null;
    }
}

if (!function_exists('outgoing_extract_explicit_priority_key_from_text')) {
    function outgoing_extract_explicit_priority_key_from_text(?string $text): ?string
    {
        $text = trim((string) $text);

        if ($text === '') {
            return null;
        }

        $lines = preg_split('/\R/u', $text) ?: [];

        foreach ($lines as $line) {
            $line = trim((string) $line);

            if ($line === '') {
                continue;
            }

            if (preg_match('/^ประเภท:\s*(.+)$/u', $line, $matches) !== 1) {
                continue;
            }

            return outgoing_normalize_priority_key((string) ($matches[1] ?? ''));
        }

        return null;
    }
}

if (!function_exists('outgoing_split_owner_names')) {
    function outgoing_split_owner_names(string $value): array
    {
        $names = preg_split('/\s*,\s*/u', trim($value)) ?: [];

        return array_values(array_filter(array_map(static function ($name): string {
            return trim((string) $name);
        }, $names), static function (string $name): bool {
            return $name !== '';
        }));
    }
}

if (!function_exists('outgoing_build_detail')) {
    function outgoing_build_detail(string $effective_date, string $issuer_name, array $owner_names, ?string $priority_key = null): string
    {
        $lines = [
            'ประเภท: ' . outgoing_priority_label_from_key($priority_key),
            'ลงวันที่: ' . ($effective_date !== '' ? $effective_date : '-'),
            'ผู้ออกเลข: ' . ($issuer_name !== '' ? $issuer_name : '-'),
            'เจ้าของเรื่อง: ' . (!empty($owner_names) ? implode(', ', $owner_names) : '-'),
        ];

        return implode("\n", $lines);
    }
}

if (!function_exists('outgoing_parse_detail_meta')) {
    function outgoing_parse_detail_meta(?string $detail): array
    {
        $meta = [
            'priority_label' => outgoing_priority_label_from_key('normal'),
            'priority_key' => 'normal',
            'effective_date' => '',
            'issuer_name' => '',
            'owner_names' => [],
            'priority_explicit' => false,
        ];

        $detail = trim((string) $detail);

        if ($detail === '') {
            return $meta;
        }

        $lines = preg_split('/\R/u', $detail) ?: [];

        foreach ($lines as $line) {
            $line = trim((string) $line);

            if ($line === '') {
                continue;
            }

            if (preg_match('/^ประเภท:\s*(.+)$/u', $line, $matches) === 1) {
                $priority_key = outgoing_normalize_priority_key((string) ($matches[1] ?? ''));
                $meta['priority_key'] = $priority_key;
                $meta['priority_label'] = outgoing_priority_label_from_key($priority_key);
                $meta['priority_explicit'] = true;
                continue;
            }

            if (preg_match('/^ลงวันที่:\s*(.+)$/u', $line, $matches) === 1) {
                $meta['effective_date'] = trim((string) ($matches[1] ?? ''));
                continue;
            }

            if (preg_match('/^ผู้ออกเลข:\s*(.+)$/u', $line, $matches) === 1) {
                $meta['issuer_name'] = trim((string) ($matches[1] ?? ''));
                continue;
            }

            if (preg_match('/^เจ้าของเรื่อง:\s*(.+)$/u', $line, $matches) === 1) {
                $meta['owner_names'] = outgoing_split_owner_names((string) ($matches[1] ?? ''));
            }
        }

        if (!$meta['priority_explicit']) {
            $detected_key = outgoing_detect_priority_key_from_text($detail);

            if ($detected_key !== null) {
                $meta['priority_key'] = $detected_key;
                $meta['priority_label'] = outgoing_priority_label_from_key($detected_key);
            }
        }

        return $meta;
    }
}

if (!function_exists('outgoing_resolve_priority_meta')) {
    function outgoing_resolve_priority_meta(
        ?string $detail,
        ?string $subject = null,
        ?string $document_content = null,
        ?string $document_subject = null
    ): array {
        $detail_meta = outgoing_parse_detail_meta($detail);
        $document_meta = outgoing_parse_detail_meta($document_content);
        $implicit_sources = [
            trim((string) $subject),
            trim((string) $document_subject),
            trim((string) $detail),
            trim((string) $document_content),
        ];

        if (($detail_meta['priority_explicit'] ?? false) === true && ($detail_meta['priority_key'] ?? 'normal') !== 'normal') {
            $priority_key = outgoing_normalize_priority_key((string) $detail_meta['priority_key']);

            return [
                'priority_key' => $priority_key,
                'priority_label' => outgoing_priority_label_from_key($priority_key),
                'source' => 'detail_explicit',
            ];
        }

        if (($document_meta['priority_explicit'] ?? false) === true && ($document_meta['priority_key'] ?? 'normal') !== 'normal') {
            $priority_key = outgoing_normalize_priority_key((string) $document_meta['priority_key']);

            return [
                'priority_key' => $priority_key,
                'priority_label' => outgoing_priority_label_from_key($priority_key),
                'source' => 'document_explicit',
            ];
        }

        foreach ($implicit_sources as $source_text) {
            $detected_key = outgoing_detect_priority_key_from_text($source_text);

            if ($detected_key !== null && $detected_key !== 'normal') {
                return [
                    'priority_key' => $detected_key,
                    'priority_label' => outgoing_priority_label_from_key($detected_key),
                    'source' => 'legacy_text',
                ];
            }
        }

        if (($detail_meta['priority_explicit'] ?? false) === true) {
            $priority_key = outgoing_normalize_priority_key((string) $detail_meta['priority_key']);

            return [
                'priority_key' => $priority_key,
                'priority_label' => outgoing_priority_label_from_key($priority_key),
                'source' => 'detail_explicit',
            ];
        }

        if (($document_meta['priority_explicit'] ?? false) === true) {
            $priority_key = outgoing_normalize_priority_key((string) $document_meta['priority_key']);

            return [
                'priority_key' => $priority_key,
                'priority_label' => outgoing_priority_label_from_key($priority_key),
                'source' => 'document_explicit',
            ];
        }

        foreach ($implicit_sources as $source_text) {
            $detected_key = outgoing_detect_priority_key_from_text($source_text);

            if ($detected_key !== null) {
                return [
                    'priority_key' => $detected_key,
                    'priority_label' => outgoing_priority_label_from_key($detected_key),
                    'source' => 'legacy_text',
                ];
            }
        }

        return [
            'priority_key' => 'normal',
            'priority_label' => outgoing_priority_label_from_key('normal'),
            'source' => 'default',
        ];
    }
}

if (!function_exists('outgoing_apply_priority_to_detail')) {
    function outgoing_apply_priority_to_detail(?string $detail, string $priority_key): string
    {
        $normalized_priority_key = outgoing_normalize_priority_key($priority_key);
        $priority_label = outgoing_priority_label_from_key($normalized_priority_key);
        $detail = str_replace(["\r\n", "\r"], "\n", trim((string) $detail));
        $lines = $detail !== '' ? (preg_split('/\n/u', $detail) ?: []) : [];
        $remaining_lines = [];

        foreach ($lines as $line) {
            $line = trim((string) $line);

            if ($line === '') {
                continue;
            }

            if (preg_match('/^ประเภท:\s*(ด่วนที่สุด|ด่วนมาก|ด่วน|ปกติ)\s*(.*)$/u', $line, $matches) === 1) {
                $tail = trim((string) ($matches[2] ?? ''));

                if ($tail !== '') {
                    $remaining_lines[] = $tail;
                }

                continue;
            }

            $remaining_lines[] = $line;
        }

        return 'ประเภท: ' . $priority_label
            . ($remaining_lines !== [] ? ("\n" . implode("\n", $remaining_lines)) : '');
    }
}
