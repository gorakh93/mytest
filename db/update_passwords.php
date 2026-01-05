<?php
// Update passwords for admin and test student to 'test'
require_once __DIR__ . '/connection.php';

global $mysqli;
if (!isset($mysqli) || !$mysqli) {
    echo "No DB connection available.\n";
    exit(1);
}

$newPass = 'test';
$hash = password_hash($newPass, PASSWORD_DEFAULT);

$emails = ['admin@example.com', 'student@example.com'];

$stmt = $mysqli->prepare('UPDATE users SET password_hash = ? WHERE email = ?');
foreach ($emails as $email) {
    $stmt->bind_param('ss', $hash, $email);
    if ($stmt->execute()) {
        echo "Updated password for $email\n";
    } else {
        echo "Failed to update $email: " . $stmt->error . "\n";
    }
}

echo "Done.\n";
