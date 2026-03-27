<?php

declare(strict_types=1);

final class BaselineSkipException extends RuntimeException
{
}

final class BaselineTestRunner
{
    private int $passed = 0;
    private int $failed = 0;
    private int $skipped = 0;

    public function run(string $name, callable $test): void
    {
        try {
            $test($this);
            $this->passed++;
            $this->write('PASS', $name);
        } catch (BaselineSkipException $exception) {
            $this->skipped++;
            $this->write('SKIP', $name, $exception->getMessage());
        } catch (Throwable $exception) {
            $this->failed++;
            $this->write('FAIL', $name, $exception->getMessage());
        }
    }

    public function assertTrue(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new RuntimeException($message);
        }
    }

    public function assertSame($expected, $actual, string $message): void
    {
        if ($expected !== $actual) {
            $expected_text = $this->stringify($expected);
            $actual_text = $this->stringify($actual);

            throw new RuntimeException($message . ' (expected ' . $expected_text . ', got ' . $actual_text . ')');
        }
    }

    public function assertArrayHasKey($key, array $array, string $message): void
    {
        if (!array_key_exists($key, $array)) {
            throw new RuntimeException($message . ' (missing key: ' . $this->stringify($key) . ')');
        }
    }

    public function skip(string $message): void
    {
        throw new BaselineSkipException($message);
    }

    public function summary(): int
    {
        echo PHP_EOL;
        echo 'Summary: '
            . $this->passed . ' passed, '
            . $this->failed . ' failed, '
            . $this->skipped . ' skipped'
            . PHP_EOL;

        return $this->failed === 0 ? 0 : 1;
    }

    private function write(string $status, string $name, string $message = ''): void
    {
        $line = sprintf('[%s] %s', $status, $name);

        if ($message !== '') {
            $line .= ' - ' . $message;
        }

        echo $line . PHP_EOL;
    }

    private function stringify($value): string
    {
        if (is_scalar($value) || $value === null) {
            return var_export($value, true);
        }

        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($encoded === false) {
            return gettype($value);
        }

        return $encoded;
    }
}
