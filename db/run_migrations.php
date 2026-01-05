<?php
// Run migrations from create_schema.sql using mysqli->multi_query
$sqlFile = __DIR__ . '/create_schema.sql';

// Load DB config from environment (falls back to defaults)
$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: '';
$DB_PORT = getenv('DB_PORT') ?: null;

if (!file_exists($sqlFile)) {
    echo "SQL file not found: $sqlFile\n";
    exit(1);
}

$sql = file_get_contents($sqlFile);
if ($sql === false) {
    echo "Failed to read SQL file.\n";
    exit(1);
}

if ($DB_PORT) {
    $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, '', (int)$DB_PORT);
} else {
    $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS);
}
if ($mysqli->connect_errno) {
    echo "Connect failed: ({$mysqli->connect_errno}) {$mysqli->connect_error}\n";
    exit(1);
}

// Enable multi statements (mysqli supports it by default with multi_query)
if (!$mysqli->multi_query($sql)) {
    echo "Migration failed: {$mysqli->error}\n";
    $mysqli->close();
    exit(1);
}

// Consume results
$success = true;
while (true) {
    if ($result = $mysqli->store_result()) {
        $result->free();
    }
    if (!$mysqli->more_results()) break;
    if (!$mysqli->next_result()) {
        echo "Error on next_result: {$mysqli->error}\n";
        $success = false;
        break;
    }
}

$mysqli->close();
if ($success) {
    echo "Migrations executed successfully.\n";
    exit(0);
} else {
    echo "Migrations completed with errors.\n";
    exit(1);
}
