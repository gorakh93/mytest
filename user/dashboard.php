<?php
require_once __DIR__ . '/../auth.php';
require_login();
function h($s){return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');}

// Determine user (prefer session user)
$sessionUser = current_user();
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : ($sessionUser['id'] ?? null);

// Fetch exams and their test papers
$stmt = $pdo->query('SELECT e.id AS exam_id, e.name AS exam_name, tp.id AS test_id, tp.title AS test_title, tp.duration_minutes FROM exams e LEFT JOIN test_papers tp ON tp.exam_id = e.id AND tp.is_published = 1 ORDER BY e.name, tp.id');
$rows = $stmt->fetchAll();

$exams = [];
foreach ($rows as $r) {
  $eid = $r['exam_id'];
  if (!isset($exams[$eid])) $exams[$eid] = ['name'=>$r['exam_name'],'tests'=>[]];
  if ($r['test_id']) $exams[$eid]['tests'][] = ['id'=>$r['test_id'],'title'=>$r['test_title'],'duration'=>$r['duration_minutes']];
}

?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>User Dashboard</title>
  <link rel="stylesheet" href="/mytest/styles.css">
  <style>.card{background:#fff;padding:12px;border-radius:6px;margin-bottom:12px}</style>
</head>
<body>
<div class="inner">
  <header style="padding:12px 0"><h1>User Dashboard</h1></header>
  <p>Logged as user id: <strong><?php echo h($userId ?? 'none'); ?></strong>. To test as another user append <code>?user_id=ID</code>.</p>

  <?php if (empty($exams)): ?>
    <p>No exams found.</p>
  <?php else: ?>
    <?php foreach ($exams as $eid => $ex): ?>
      <section class="card">
        <h2><?php echo h($ex['name']); ?></h2>
        <?php if (empty($ex['tests'])): ?>
          <p>No published tests.</p>
        <?php else: ?>
          <ul>
            <?php foreach ($ex['tests'] as $t): ?>
              <li>
                <?php echo h($t['title']); ?> — <?php echo h($t['duration']); ?> min
                <a href="take_test.php?test_id=<?php echo h($t['id']); ?>&user_id=<?php echo h($userId); ?>">Start Test</a>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </section>
    <?php endforeach; ?>
  <?php endif; ?>

  <footer style="margin-top:24px">&copy; <?php echo date('Y'); ?></footer>
</div>
</body>
</html>
