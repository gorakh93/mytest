<?php
// Seed script for test_series database
// Reuse the shared PDO connection from db/connection.php which loads .env
require_once __DIR__ . '/connection.php';

global $mysqli;
if (!isset($mysqli) || !$mysqli) {
    echo "No mysqli connection available. Check db/connection.php or .env settings.\n";
    exit(1);
}

function getOrCreateUser($mysqli, $name, $email, $password, $role)
{
    $stmt = $mysqli->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    if (!empty($row['id'])) return (int)$row['id'];

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $mysqli->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('ssss', $name, $email, $hash, $role);
    $stmt->execute();
    return $mysqli->insert_id;
}

function getOrCreateExam($mysqli, $name, $slug, $description = null)
{
    $stmt = $mysqli->prepare('SELECT id FROM exams WHERE slug = ?');
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    if (!empty($row['id'])) return (int)$row['id'];

    $stmt = $mysqli->prepare('INSERT INTO exams (name, slug, description) VALUES (?, ?, ?)');
    $stmt->bind_param('sss', $name, $slug, $description);
    $stmt->execute();
    return $mysqli->insert_id;
}

function getOrCreateTestPaper($mysqli, $examId, $authorId, $title, $slug, $description = null, $duration = 0, $totalMarks = 0.0, $passing = 0.0, $published = 0)
{
    $stmt = $mysqli->prepare('SELECT id FROM test_papers WHERE slug = ?');
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    if (!empty($row['id'])) return (int)$row['id'];

    $stmt = $mysqli->prepare('INSERT INTO test_papers (exam_id, author_id, title, slug, description, duration_minutes, total_marks, passing_marks, is_published) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('iisssiddi', $examId, $authorId, $title, $slug, $description, $duration, $totalMarks, $passing, $published);
    $stmt->execute();
    return $mysqli->insert_id;
}

function getOrCreateQuestion($mysqli, $authorId, $text, $explanation, $type = 'mcq', $marks = 1.00, $negative = 0.00)
{
    $stmt = $mysqli->prepare('SELECT id FROM questions WHERE author_id = ? AND question_text = ?');
    $stmt->bind_param('is', $authorId, $text);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    if (!empty($row['id'])) return (int)$row['id'];

    $stmt = $mysqli->prepare('INSERT INTO questions (author_id, question_text, explanation, question_type, default_marks, negative_marks) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('isssdd', $authorId, $text, $explanation, $type, $marks, $negative);
    $stmt->execute();
    return $mysqli->insert_id;
}

function getOrCreateOption($mysqli, $questionId, $label, $text, $isCorrect)
{
    $stmt = $mysqli->prepare('SELECT id FROM options WHERE question_id = ? AND option_label = ?');
    $stmt->bind_param('is', $questionId, $label);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    if (!empty($row['id'])) return (int)$row['id'];

    $stmt = $mysqli->prepare('INSERT INTO options (question_id, option_label, option_text, is_correct) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('issi', $questionId, $label, $text, $isCorrect);
    $stmt->execute();
    return $mysqli->insert_id;
}

function ensureTestPaperQuestion($mysqli, $testPaperId, $questionId, $position, $marks)
{
    $stmt = $mysqli->prepare('SELECT id FROM test_paper_questions WHERE test_paper_id = ? AND question_id = ?');
    $stmt->bind_param('ii', $testPaperId, $questionId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    if (!empty($row['id'])) return (int)$row['id'];
    $stmt = $mysqli->prepare('INSERT INTO test_paper_questions (test_paper_id, question_id, position, marks) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('iiid', $testPaperId, $questionId, $position, $marks);
    $stmt->execute();
    return $mysqli->insert_id;
}

try {
    $mysqli->begin_transaction();

    $adminId = getOrCreateUser($mysqli, 'Admin User', 'admin@example.com', 'adminpass', 'admin');
    $studentId = getOrCreateUser($mysqli, 'Test Student', 'student@example.com', 'studentpass', 'student');

    $examId = getOrCreateExam($mysqli, 'Sample Exam', 'sample-exam', 'A sample exam category');

    $testPaperId = getOrCreateTestPaper($mysqli, $examId, $adminId, 'Sample Test Paper 1', 'sample-test-1', 'A short sample test', 30, 10.00, 4.00, 1);

    // Question 1
    $q1 = getOrCreateQuestion($mysqli, $adminId, 'What is the capital of France?', 'Paris is the capital of France.', 'mcq', 1.00, 0.00);
    getOrCreateOption($mysqli, $q1, 'A', 'Berlin', 0);
    getOrCreateOption($mysqli, $q1, 'B', 'Madrid', 0);
    getOrCreateOption($mysqli, $q1, 'C', 'Paris', 1);
    getOrCreateOption($mysqli, $q1, 'D', 'Rome', 0);

    // Question 2
    $q2 = getOrCreateQuestion($mysqli, $adminId, 'Select prime numbers', '2 and 3 are primes (example).', 'multi-select', 2.00, 0.00);
    getOrCreateOption($mysqli, $q2, 'A', '2', 1);
    getOrCreateOption($mysqli, $q2, 'B', '3', 1);
    getOrCreateOption($mysqli, $q2, 'C', '4', 0);
    getOrCreateOption($mysqli, $q2, 'D', '6', 0);

    ensureTestPaperQuestion($mysqli, $testPaperId, $q1, 1, 1.00);
    ensureTestPaperQuestion($mysqli, $testPaperId, $q2, 2, 2.00);

    $mysqli->commit();
    echo "Seed data inserted/verified successfully.\n";
    echo "Admin ID: $adminId, Student ID: $studentId, Exam ID: $examId, TestPaper ID: $testPaperId\n";
} catch (Exception $e) {
    if ($mysqli) $mysqli->rollback();
    echo "Seeding failed: " . $e->getMessage() . "\n";
    exit(1);
}
