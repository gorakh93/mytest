<?php
require_once __DIR__ . '/../auth.php';
require_login();
function h($s){return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo "Invalid method"; exit; }

$userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : (current_user()['id'] ?? null);
$testPaperId = isset($_POST['test_paper_id']) ? (int)$_POST['test_paper_id'] : 0;
$answers = $_POST['answers'] ?? [];
$answers_text = $_POST['answers_text'] ?? [];

if (!$testPaperId) { echo "Missing test id"; exit; }

global $mysqli;
try {
    $mysqli->begin_transaction();

    // Create attempt
    $stmt = $mysqli->prepare('INSERT INTO user_attempts (user_id, test_paper_id, started_at, finished_at, status) VALUES (?, ?, NOW(), NOW(), ?)');
    $status = 'completed';
    $stmt->bind_param('iis', $userId, $testPaperId, $status);
    $stmt->execute();
    $attemptId = $mysqli->insert_id;

    // Load questions and correct options
    $stmt = $mysqli->prepare('SELECT tpq.question_id AS qid, COALESCE(tpq.marks,q.default_marks) AS marks, q.question_type FROM test_paper_questions tpq JOIN questions q ON q.id = tpq.question_id WHERE tpq.test_paper_id = ?');
    $stmt->bind_param('i', $testPaperId);
    $stmt->execute();
    $res = $stmt->get_result();
    $qrows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

    // Prepare insert for user_answers
    $stmtInsert = $mysqli->prepare('INSERT INTO user_answers (attempt_id, question_id, selected_option_id, answer_text, is_correct, marks_obtained, answered_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');

    $totalScore = 0.0;
    $correctCount = 0;
    $wrongCount = 0;

    foreach ($qrows as $qr) {
        $qid = (int)$qr['qid'];
        $qtype = $qr['question_type'];
        $marks = (float)$qr['marks'];

        if ($qtype === 'mcq' || $qtype === 'multi-select') {
            // correct option ids
            $stmtC = $mysqli->prepare('SELECT id FROM options WHERE question_id = ? AND is_correct = 1');
            $stmtC->bind_param('i', $qid);
            $stmtC->execute();
            $resC = $stmtC->get_result();
            $correct = array_map('intval', array_column($resC ? $resC->fetch_all(MYSQLI_ASSOC) : [], 'id'));

            $selected = [];
            if (isset($answers[$qid])) {
                if (is_array($answers[$qid])) $selected = array_map('intval', $answers[$qid]);
                else $selected = [ (int)$answers[$qid] ];
            }

            sort($correct); sort($selected);
            $isCorrect = ($correct === $selected) ? 1 : 0;
            $marksObtained = $isCorrect ? $marks : 0.0;

            if (empty($selected)) {
                $null = null;
                $stmtInsert->bind_param('iissid', $attemptId, $qid, $null, $null, $isCorrect, $marksObtained);
                // For binding nulls in mysqli, use null values after specifying types; mysqli will convert
                $stmtInsert->execute();
                $wrongCount++;
            } else {
                foreach ($selected as $sid) {
                    $sid = (int)$sid;
                    $stmtInsert->bind_param('iiisid', $attemptId, $qid, $sid, $null, $isCorrect, $marksObtained);
                    $stmtInsert->execute();
                }
                if ($isCorrect) $correctCount++; else $wrongCount++;
            }
            $totalScore += $marksObtained;
        } else {
            $text = isset($answers_text[$qid]) ? trim($answers_text[$qid]) : null;
            $stmtInsert->bind_param('iissid', $attemptId, $qid, $null, $text, $null, $null);
            $stmtInsert->execute();
        }
    }

    // Update attempt with results
    $stmtU = $mysqli->prepare('UPDATE user_attempts SET score = ?, correct_count = ?, wrong_count = ? WHERE id = ?');
    $stmtU->bind_param('diii', $totalScore, $correctCount, $wrongCount, $attemptId);
    $stmtU->execute();

    $mysqli->commit();

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
    if ($mysqli) $mysqli->rollback();
    echo 'Submission failed: ' . h($e->getMessage());
    exit;
}
