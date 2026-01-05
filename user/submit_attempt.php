$<?php
require_once __DIR__ . '/../auth.php';
require_login();
function h($s){return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo "Invalid method"; exit; }

$userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : (current_user()['id'] ?? null);
$testPaperId = isset($_POST['test_paper_id']) ? (int)$_POST['test_paper_id'] : 0;
$answers = $_POST['answers'] ?? [];
$answers_text = $_POST['answers_text'] ?? [];

if (!$testPaperId) { echo "Missing test id"; exit; }

try {
    $pdo->beginTransaction();

    // Create attempt
    $stmt = $pdo->prepare('INSERT INTO user_attempts (user_id, test_paper_id, started_at, finished_at, status) VALUES (?, ?, NOW(), NOW(), ?)');
    $status = 'completed';
    $stmt->execute([$userId, $testPaperId, $status]);
    $attemptId = $pdo->lastInsertId();

    // Load questions and correct options
    $stmt = $pdo->prepare('SELECT tpq.question_id AS qid, COALESCE(tpq.marks,q.default_marks) AS marks, q.question_type FROM test_paper_questions tpq JOIN questions q ON q.id = tpq.question_id WHERE tpq.test_paper_id = ?');
    $stmt->execute([$testPaperId]);
    $qrows = $stmt->fetchAll();

    // Prepare insert for user_answers
    $stmtInsert = $pdo->prepare('INSERT INTO user_answers (attempt_id, question_id, selected_option_id, answer_text, is_correct, marks_obtained, answered_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');

    $totalScore = 0.0;
    $correctCount = 0;
    $wrongCount = 0;

    foreach ($qrows as $qr) {
        $qid = $qr['qid'];
        $qtype = $qr['question_type'];
        $marks = (float)$qr['marks'];

        if ($qtype === 'mcq' || $qtype === 'multi-select') {
            // correct option ids
            $stmt = $pdo->prepare('SELECT id FROM options WHERE question_id = ? AND is_correct = 1');
            $stmt->execute([$qid]);
            $correct = array_map('intval', array_column($stmt->fetchAll(), 'id'));

            $selected = [];
            if (isset($answers[$qid])) {
                if (is_array($answers[$qid])) $selected = array_map('intval', $answers[$qid]);
                else $selected = [ (int)$answers[$qid] ];
            }

            // For multi-select compare sets; for mcq expect single selection
            sort($correct); sort($selected);
            $isCorrect = ($correct === $selected) ? 1 : 0;
            $marksObtained = $isCorrect ? $marks : 0.0;

            if (empty($selected)) {
                // insert a null selected_option row to mark unanswered
                $stmtInsert->execute([$attemptId, $qid, null, null, 0, 0.0]);
                $wrongCount++;
            } else {
                // insert one row per selected option
                foreach ($selected as $sid) {
                    $stmtInsert->execute([$attemptId, $qid, $sid, null, $isCorrect, $marksObtained]);
                }
                if ($isCorrect) $correctCount++; else $wrongCount++;
            }
            $totalScore += $marksObtained;
        } else {
            // descriptive or numeric: store text, cannot auto-grade
            $text = isset($answers_text[$qid]) ? trim($answers_text[$qid]) : null;
            $stmtInsert->execute([$attemptId, $qid, null, $text, null, null]);
        }
    }

    // Update attempt with results
    $stmt = $pdo->prepare('UPDATE user_attempts SET score = ?, correct_count = ?, wrong_count = ? WHERE id = ?');
    $stmt->execute([$totalScore, $correctCount, $wrongCount, $attemptId]);

    $pdo->commit();

    // Show simple result
    ?><!doctype html>
    <html><head><meta charset="utf-8"><title>Result</title></head><body>
    <div class="inner">
      <h1>Test Submitted</h1>
      <p>Attempt ID: <?php echo h($attemptId); ?></p>
      <p>Score: <?php echo h($totalScore); ?></p>
      <p>Correct: <?php echo h($correctCount); ?>, Wrong: <?php echo h($wrongCount); ?></p>
      <p><a href="dashboard.php?user_id=<?php echo h($userId); ?>">Return to dashboard</a></p>
    </div>
    </body></html>
    <?php
    exit;

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    echo 'Submission failed: ' . h($e->getMessage());
    exit;
}
