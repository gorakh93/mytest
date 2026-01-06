<?php
// DB connection helper for the admin UI

// Simple .env loader: reads project root .env and sets env variables if not already set
function load_dotenv($path)
{
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        if (strpos($line, '=') === false) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if ((strlen($value) >= 2) && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))) {
            $value = substr($value, 1, -1);
        }
        if (getenv($name) === false) {
            putenv("{$name}={$value}");
        }
        if (!isset($_ENV[$name])) {
            $_ENV[$name] = $value;
        }
    }
}

// Load .env from project root (one level up from this file)
$projectRoot = dirname(__DIR__);

echo $projectRoot . DIRECTORY_SEPARATOR . '.env';die;
load_dotenv($projectRoot . DIRECTORY_SEPARATOR . '.env');

// Read DB config from environment with sensible defaults
$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_NAME = getenv('DB_NAME') ?: 'test_series';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: '';
$DB_PORT = getenv('DB_PORT') ?: null;

$port = $DB_PORT ? (int)$DB_PORT : 3306;
// Create mysqli connection and set charset
$mysqli = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $port);
if ($mysqli->connect_errno) {
    http_response_code(500);
    echo "Database connection failed: " . htmlspecialchars($mysqli->connect_error);
    exit;
}

echo "heree";die;
if (!$mysqli->set_charset('utf8mb4')) {
    // Not fatal, but try to continue
    error_log('Failed to set charset: ' . $mysqli->error);
}

// Expose $mysqli globally for legacy scripts
// NOTE: previous code used $pdo; update scripts to use $mysqli instead
