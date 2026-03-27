<?php

declare(strict_types=1);

namespace Tests\Support;

use mysqli;
use PHPUnit\Framework\TestCase;

abstract class WorkflowTestCase extends TestCase
{
    public function connection(): mysqli
    {
        return db_connection();
    }

    public function currentDhYear(): int
    {
        return (int) system_get_dh_year();
    }

    public function requireScalarValue(string $sql, string $message, string $types = '', mixed ...$params): string
    {
        $row = db_fetch_one($sql, $types, ...$params);

        if ($row === null) {
            $this->markTestSkipped($message);
        }

        $value = trim((string) reset($row));

        if ($value === '') {
            $this->markTestSkipped($message);
        }

        return $value;
    }

    public function requireRows(array $rows, string $message): array
    {
        if ($rows === []) {
            $this->markTestSkipped($message);
        }

        return $rows;
    }
}
