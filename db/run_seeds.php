<?php
// Seed script for test_series database
$host = 'localhost';
$db   = 'test_series';
$user = 'root';
$pass = '';
$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $pdo->beginTransaction();

    // Users
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');
    $stmt->execute(['Admin User', 'admin@example.com', password_hash('adminpass', PASSWORD_DEFAULT), 'admin']);
    $adminId = $pdo->lastInsertId();

    $stmt->execute(['Test Student', 'student@example.com', password_hash('studentpass', PASSWORD_DEFAULT), 'student']);
    $studentId = $pdo->lastInsertId();

    // Exam
    $stmt = $pdo->prepare('INSERT INTO exams (name, slug, description) VALUES (?, ?, ?)');
    $stmt->execute(['Sample Exam', 'sample-exam', 'A sample exam category']);
    $examId = $pdo->lastInsertId();

    // Test paper
    $stmt = $pdo->prepare('INSERT INTO test_papers (exam_id, author_id, title, slug, description, duration_minutes, total_marks, passing_marks, is_published) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$examId, $adminId, 'Sample Test Paper 1', 'sample-test-1', 'A short sample test', 30, 10.00, 4.00, 1]);
    $testPaperId = $pdo->lastInsertId();

    // Prepare statements for questions and options
    $stmtQuestion = $pdo->prepare('INSERT INTO questions (author_id, question_text, explanation, question_type, default_marks, negative_marks) VALUES (?, ?, ?, ?, ?, ?)');
    $stmtOption = $pdo->prepare('INSERT INTO options (question_id, option_label, option_text, is_correct) VALUES (?, ?, ?, ?)');

    // Question 1: single-correct MCQ
    $stmtQuestion->execute([$adminId, 'What is the capital of France?', 'Paris is the capital of France.', 'mcq', 1.00, 0.00]);
    $q1 = $pdo->lastInsertId();
    $stmtOption->execute([$q1, 'A', 'Berlin', 0]);
    $stmtOption->execute([$q1, 'B', 'Madrid', 0]);
    $stmtOption->execute([$q1, 'C', 'Paris', 1]);
    $stmtOption->execute([$q1, 'D', 'Rome', 0]);

    // Question 2: multi-select (two correct answers)
    $stmtQuestion->execute([$adminId, 'Select prime numbers', '2 and 3 are primes (example).', 'multi-select', 2.00, 0.00]);
    $q2 = $pdo->lastInsertId();
    $stmtOption->execute([$q2, 'A', '2', 1]);
    $stmtOption->execute([$q2, 'B', '3', 1]);
    $stmtOption->execute([$q2, 'C', '4', 0]);
    $stmtOption->execute([$q2, 'D', '6', 0]);

    // Map questions to test paper
    $stmt = $pdo->prepare('INSERT INTO test_paper_questions (test_paper_id, question_id, position, marks) VALUES (?, ?, ?, ?)');
    $stmt->execute([$testPaperId, $q1, 1, 1.00]);
    $stmt->execute([$testPaperId, $q2, 2, 2.00]);

    $pdo->commit();
    echo "Seed data inserted successfully.\n";
    echo "Admin ID: $adminId, Student ID: $studentId, Exam ID: $examId, TestPaper ID: $testPaperId\n";
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    echo "Seeding failed: " . $e->getMessage() . "\n";
    exit(1);
}
