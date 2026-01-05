<?php
// Seed script for test_series database
// Reuse the shared PDO connection from db/connection.php which loads .env
require_once __DIR__ . '/connection.php';

if (!isset($pdo) || !$pdo) {
    echo "No PDO connection available. Check db/connection.php or .env settings.\n";
    exit(1);
}

function getOrCreateUser($pdo, $name, $email, $password, $role)
{
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $id = $stmt->fetchColumn();
    if ($id) return $id;
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');
    $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
    return $pdo->lastInsertId();
}

function getOrCreateExam($pdo, $name, $slug, $description = null)
{
    $stmt = $pdo->prepare('SELECT id FROM exams WHERE slug = ?');
    $stmt->execute([$slug]);
    $id = $stmt->fetchColumn();
    if ($id) return $id;
    $stmt = $pdo->prepare('INSERT INTO exams (name, slug, description) VALUES (?, ?, ?)');
    $stmt->execute([$name, $slug, $description]);
    return $pdo->lastInsertId();
}

function getOrCreateTestPaper($pdo, $examId, $authorId, $title, $slug, $description = null, $duration = 0, $totalMarks = 0.0, $passing = 0.0, $published = 0)
{
    $stmt = $pdo->prepare('SELECT id FROM test_papers WHERE slug = ?');
    $stmt->execute([$slug]);
    $id = $stmt->fetchColumn();
    if ($id) return $id;
    $stmt = $pdo->prepare('INSERT INTO test_papers (exam_id, author_id, title, slug, description, duration_minutes, total_marks, passing_marks, is_published) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$examId, $authorId, $title, $slug, $description, $duration, $totalMarks, $passing, $published]);
    return $pdo->lastInsertId();
}

function getOrCreateQuestion($pdo, $authorId, $text, $explanation, $type = 'mcq', $marks = 1.00, $negative = 0.00)
{
    $stmt = $pdo->prepare('SELECT id FROM questions WHERE author_id = ? AND question_text = ?');
    $stmt->execute([$authorId, $text]);
    $id = $stmt->fetchColumn();
    if ($id) return $id;
    $stmt = $pdo->prepare('INSERT INTO questions (author_id, question_text, explanation, question_type, default_marks, negative_marks) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$authorId, $text, $explanation, $type, $marks, $negative]);
    return $pdo->lastInsertId();
}

function getOrCreateOption($pdo, $questionId, $label, $text, $isCorrect)
{
    $stmt = $pdo->prepare('SELECT id FROM options WHERE question_id = ? AND option_label = ?');
    $stmt->execute([$questionId, $label]);
    $id = $stmt->fetchColumn();
    if ($id) return $id;
    $stmt = $pdo->prepare('INSERT INTO options (question_id, option_label, option_text, is_correct) VALUES (?, ?, ?, ?)');
    $stmt->execute([$questionId, $label, $text, $isCorrect]);
    return $pdo->lastInsertId();
}

function ensureTestPaperQuestion($pdo, $testPaperId, $questionId, $position, $marks)
{
    $stmt = $pdo->prepare('SELECT id FROM test_paper_questions WHERE test_paper_id = ? AND question_id = ?');
    $stmt->execute([$testPaperId, $questionId]);
    $id = $stmt->fetchColumn();
    if ($id) return $id;
    $stmt = $pdo->prepare('INSERT INTO test_paper_questions (test_paper_id, question_id, position, marks) VALUES (?, ?, ?, ?)');
    $stmt->execute([$testPaperId, $questionId, $position, $marks]);
    return $pdo->lastInsertId();
}

try {
    $pdo->beginTransaction();

    $adminId = getOrCreateUser($pdo, 'Admin User', 'admin@example.com', 'adminpass', 'admin');
    $studentId = getOrCreateUser($pdo, 'Test Student', 'student@example.com', 'studentpass', 'student');

    $examId = getOrCreateExam($pdo, 'Sample Exam', 'sample-exam', 'A sample exam category');

    $testPaperId = getOrCreateTestPaper($pdo, $examId, $adminId, 'Sample Test Paper 1', 'sample-test-1', 'A short sample test', 30, 10.00, 4.00, 1);

    // Question 1
    $q1 = getOrCreateQuestion($pdo, $adminId, 'What is the capital of France?', 'Paris is the capital of France.', 'mcq', 1.00, 0.00);
    getOrCreateOption($pdo, $q1, 'A', 'Berlin', 0);
    getOrCreateOption($pdo, $q1, 'B', 'Madrid', 0);
    getOrCreateOption($pdo, $q1, 'C', 'Paris', 1);
    getOrCreateOption($pdo, $q1, 'D', 'Rome', 0);

    // Question 2
    $q2 = getOrCreateQuestion($pdo, $adminId, 'Select prime numbers', '2 and 3 are primes (example).', 'multi-select', 2.00, 0.00);
    getOrCreateOption($pdo, $q2, 'A', '2', 1);
    getOrCreateOption($pdo, $q2, 'B', '3', 1);
    getOrCreateOption($pdo, $q2, 'C', '4', 0);
    getOrCreateOption($pdo, $q2, 'D', '6', 0);

    ensureTestPaperQuestion($pdo, $testPaperId, $q1, 1, 1.00);
    ensureTestPaperQuestion($pdo, $testPaperId, $q2, 2, 2.00);

    $pdo->commit();
    echo "Seed data inserted/verified successfully.\n";
    echo "Admin ID: $adminId, Student ID: $studentId, Exam ID: $examId, TestPaper ID: $testPaperId\n";
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    echo "Seeding failed: " . $e->getMessage() . "\n";
    exit(1);
}
