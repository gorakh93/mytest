<?php
require_once __DIR__ . '/../auth.php';
require_admin();
$view = $_GET['view'] ?? 'dashboard';
function h($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
$limit = 200;

$available = [
    'dashboard' => 'Dashboard',
    'users' => 'Users',
    'exams' => 'Exams',
    'test_papers' => 'Test Papers',
    'questions' => 'Questions',
    'options' => 'Options',
    'test_paper_questions' => 'Test Paper Questions',
    'user_attempts' => 'User Attempts',
    'user_answers' => 'User Answers'
];

?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin — Test Series</title>
  <link rel="stylesheet" href="../styles.css">
  <style>table{width:100%;border-collapse:collapse}th,td{padding:8px;border:1px solid #ddd;text-align:left;font-size:14px} .nav a{margin-right:10px}</style>
</head>
<body>
<div class="inner">
  <header style="padding:12px 0">
    <h1>Admin UI</h1>
    <div class="nav">
      <?php foreach ($available as $key => $label): ?>
        <a href="?view=<?php echo h($key); ?>"><?php echo h($label); ?></a>
      <?php endforeach; ?>
      <a href="/mytest/">Return site</a>
    </div>
  </header>

  <main>
    <?php if ($view === 'dashboard'): ?>
      <h2>Dashboard</h2>
      <p>Quick links to view tables. Click a link above to view records.</p>
    <?php else: ?>
      <h2><?php echo h($available[$view] ?? $view); ?></h2>
      <?php
        try {
            switch ($view) {
                case 'users':
                    $stmt = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY id DESC LIMIT {$limit}");
                    $rows = $stmt->fetchAll();
                    $cols = ['id','name','email','role','created_at'];
                    break;
                case 'exams':
                    $stmt = $pdo->query("SELECT id, name, slug, description, created_at FROM exams ORDER BY id DESC LIMIT {$limit}");
                    $rows = $stmt->fetchAll();
                    $cols = ['id','name','slug','description','created_at'];
                    break;
                case 'test_papers':
                    $stmt = $pdo->query("SELECT id, exam_id, author_id, title, duration_minutes, total_marks, is_published, created_at FROM test_papers ORDER BY id DESC LIMIT {$limit}");
                    $rows = $stmt->fetchAll();
                    $cols = ['id','exam_id','author_id','title','duration_minutes','total_marks','is_published','created_at'];
                    break;
                case 'questions':
                    $stmt = $pdo->query("SELECT id, author_id, question_type, default_marks, created_at FROM questions ORDER BY id DESC LIMIT {$limit}");
                    $rows = $stmt->fetchAll();
                    $cols = ['id','author_id','question_type','default_marks','created_at'];
                    break;
                case 'options':
                    $stmt = $pdo->query("SELECT id, question_id, option_label, option_text, is_correct FROM options ORDER BY id DESC LIMIT {$limit}");
                    $rows = $stmt->fetchAll();
                    $cols = ['id','question_id','option_label','option_text','is_correct'];
                    break;
                case 'test_paper_questions':
                    $stmt = $pdo->query("SELECT id, test_paper_id, question_id, position, marks FROM test_paper_questions ORDER BY id DESC LIMIT {$limit}");
                    $rows = $stmt->fetchAll();
                    $cols = ['id','test_paper_id','question_id','position','marks'];
                    break;
                case 'user_attempts':
                    $stmt = $pdo->query("SELECT id, user_id, test_paper_id, status, score, started_at, finished_at FROM user_attempts ORDER BY id DESC LIMIT {$limit}");
                    $rows = $stmt->fetchAll();
                    $cols = ['id','user_id','test_paper_id','status','score','started_at','finished_at'];
                    break;
                case 'user_answers':
                    $stmt = $pdo->query("SELECT id, attempt_id, question_id, selected_option_id, answer_text, is_correct, marks_obtained, answered_at FROM user_answers ORDER BY id DESC LIMIT {$limit}");
                    $rows = $stmt->fetchAll();
                    $cols = ['id','attempt_id','question_id','selected_option_id','answer_text','is_correct','marks_obtained','answered_at'];
                    break;
                default:
                    echo '<div class="notice">Unknown view</div>';
                    $rows = [];
                    $cols = [];
            }
        } catch (Exception $e) {
            echo '<div class="notice error">Query failed: ' . h($e->getMessage()) . '</div>';
            $rows = [];
            $cols = [];
        }

        if (!empty($rows)):
      ?>
        <table>
          <thead>
            <tr>
              <?php foreach ($cols as $c): ?><th><?php echo h($c); ?></th><?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <?php foreach ($cols as $c): ?><td><?php echo h(is_null($r[$c]) ? 'NULL' : (is_string($r[$c]) ? $r[$c] : (string)$r[$c])); ?></td><?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p>No records found.</p>
      <?php endif; ?>
    <?php endif; ?>
  </main>

  <footer style="margin-top:24px">
    <p>&copy; <?php echo date('Y'); ?> Test Series Admin</p>
  </footer>
</div>
</body>
</html>
