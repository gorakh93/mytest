<?php
require_once __DIR__ . '/../auth.php';
require_login();
function h($s){return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');}

$testId = isset($_GET['test_id']) ? (int)$_GET['test_id'] : 0;
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : (current_user()['id'] ?? null);
if (!$testId) { echo "Missing test_id"; exit; }

// Load test paper
$stmt = $pdo->prepare('SELECT id, title, description, duration_minutes FROM test_papers WHERE id = ? AND is_published = 1');
$stmt->execute([$testId]);
$test = $stmt->fetch();
if (!$test) { echo "Test not found or not published."; exit; }

// Load questions for the test paper ordered by position
$stmt = $pdo->prepare('SELECT q.id, q.question_text, q.question_type, COALESCE(tpq.marks, q.default_marks) AS marks FROM test_paper_questions tpq JOIN questions q ON q.id = tpq.question_id WHERE tpq.test_paper_id = ? ORDER BY tpq.position');
$stmt->execute([$testId]);
$questions = $stmt->fetchAll();

// Fetch options for all question ids
$qIds = array_map(function($r){return $r['id'];}, $questions);
$options = [];
if (!empty($qIds)){
    $in = implode(',', array_fill(0,count($qIds),'?'));
    $stmt = $pdo->prepare("SELECT id, question_id, option_label, option_text FROM options WHERE question_id IN ($in) ORDER BY id");
    $stmt->execute($qIds);
    foreach ($stmt->fetchAll() as $opt) {
        $options[$opt['question_id']][] = $opt;
    }
}

?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?php echo h($test['title']); ?> — Take Test</title>
  <link rel="stylesheet" href="/mytest/styles.css">
  <style>.question{background:#fff;padding:12px;border-radius:6px;margin-bottom:12px}</style>
</head>
<body>
<div class="inner">
  <header style="padding:12px 0"><h1><?php echo h($test['title']); ?></h1></header>
  <p><?php echo h($test['description']); ?></p>
  <form method="post" action="submit_attempt.php">
    <input type="hidden" name="test_paper_id" value="<?php echo h($testId); ?>">
    <input type="hidden" name="user_id" value="<?php echo h($userId); ?>">

    <?php foreach ($questions as $i => $q): ?>
      <div class="question">
        <strong>Q<?php echo $i+1;?>.</strong> <?php echo h($q['question_text']); ?> <em>(<?php echo h($q['marks']); ?> pts)</em>
        <div style="margin-top:8px">
          <?php if ($q['question_type'] === 'mcq'): ?>
            <?php foreach ($options[$q['id']] ?? [] as $opt): ?>
              <label><input type="radio" name="answers[<?php echo h($q['id']); ?>]" value="<?php echo h($opt['id']); ?>"> <?php echo h($opt['option_label']) . '. ' . h($opt['option_text']); ?></label><br>
            <?php endforeach; ?>
          <?php elseif ($q['question_type'] === 'multi-select'): ?>
            <?php foreach ($options[$q['id']] ?? [] as $opt): ?>
              <label><input type="checkbox" name="answers[<?php echo h($q['id']); ?>][]" value="<?php echo h($opt['id']); ?>"> <?php echo h($opt['option_label']) . '. ' . h($opt['option_text']); ?></label><br>
            <?php endforeach; ?>
          <?php else: ?>
            <textarea name="answers_text[<?php echo h($q['id']); ?>]" rows="3" style="width:100%"></textarea>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>

    <button type="submit">Submit Test</button>
  </form>

  <footer style="margin-top:24px">&copy; <?php echo date('Y'); ?></footer>
</div>
</body>
</html>
