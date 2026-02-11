<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/connection.php';

$connection = $connection ?? null;
if (!$connection instanceof mysqli) {
    fwrite(STDERR, "Database connection not available.\n");
    exit(1);
}

$dir = __DIR__ . '/../migrations';
$files = glob($dir . '/*.sql');
if ($files === false || empty($files)) {
    fwrite(STDOUT, "No migration files found.\n");
    exit(0);
}

natsort($files);
$files = array_values($files);

$create_migrations_table = <<<SQL
CREATE TABLE IF NOT EXISTS `dh_migrations` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `version` int(11) unsigned NOT NULL,
  `name` varchar(150) NOT NULL,
  `appliedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_migrations_version` (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
SQL;

if (!mysqli_query($connection, $create_migrations_table)) {
    fwrite(STDERR, "Failed to create migrations table: " . mysqli_error($connection) . "\n");
    exit(1);
}

$check_stmt = mysqli_prepare($connection, 'SELECT 1 FROM dh_migrations WHERE version = ? LIMIT 1');
$insert_stmt = mysqli_prepare($connection, 'INSERT INTO dh_migrations (version, name) VALUES (?, ?)');
if ($check_stmt === false || $insert_stmt === false) {
    fwrite(STDERR, "Failed to prepare migration statements.\n");
    exit(1);
}

foreach ($files as $file) {
    $base = basename($file);
    if (!preg_match('/^(\\d+)_/u', $base, $matches)) {
        continue;
    }

    $version = (int) $matches[1];

    mysqli_stmt_bind_param($check_stmt, 'i', $version);
    mysqli_stmt_execute($check_stmt);
    $result = mysqli_stmt_get_result($check_stmt);
    if ($result && mysqli_fetch_assoc($result)) {
        fwrite(STDOUT, "Skip {$base} (already applied)\n");
        continue;
    }

    $sql = file_get_contents($file);
    if ($sql === false || trim($sql) === '') {
        fwrite(STDOUT, "Skip {$base} (empty file)\n");
        continue;
    }

    if (!mysqli_multi_query($connection, $sql)) {
        fwrite(STDERR, "Failed {$base}: " . mysqli_error($connection) . "\n");
        exit(1);
    }

    do {
        $result = mysqli_store_result($connection);
        if ($result instanceof mysqli_result) {
            mysqli_free_result($result);
        }
        if (mysqli_errno($connection)) {
            fwrite(STDERR, "Failed {$base}: " . mysqli_error($connection) . "\n");
            exit(1);
        }
    } while (mysqli_more_results($connection) && mysqli_next_result($connection));

    mysqli_stmt_bind_param($insert_stmt, 'is', $version, $base);
    if (!mysqli_stmt_execute($insert_stmt)) {
        fwrite(STDERR, "Failed to record migration {$base}: " . mysqli_error($connection) . "\n");
        exit(1);
    }

    fwrite(STDOUT, "Applied {$base}\n");
}

mysqli_stmt_close($check_stmt);
mysqli_stmt_close($insert_stmt);

fwrite(STDOUT, "Migrations complete.\n");
