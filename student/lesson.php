<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

//role de l'étudiant
requireRole('student');

$lesson_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$lesson_id) {
    header('Location: modules.php');
    exit;
}

$db = getDB();
$user = currentUser();
$student_id = $user['id'];

// recuperer la lecon, le cours et le module
$stmt = $db->prepare("
    SELECT l.*, c.module_id, c.titre AS course_titre, m.titre AS module_titre 
    FROM lessons l 
    JOIN courses c ON c.id = l.course_id 
    JOIN modules m ON m.id = c.module_id 
    WHERE l.id = ? 
    LIMIT 1
");
$stmt->execute([$lesson_id]);
$lesson = $stmt->fetch();

if (!$lesson) {
    header('Location: modules.php');
    exit;
}

$module_id = $lesson['module_id'];

// verifier si la lecon est deja completee
$stmt_check = $db->prepare("SELECT 1 FROM student_lessons WHERE student_id = ? AND lesson_id = ? LIMIT 1");
$stmt_check->execute([$student_id, $lesson_id]);
$is_completed = (bool)$stmt_check->fetch();

// recuperer l'evaluation
$stmt_quiz = $db->prepare("SELECT * FROM quizzes WHERE lesson_id = ? LIMIT 1");
$stmt_quiz->execute([$lesson_id]);
$quiz = $stmt_quiz->fetch();

$questions = [];
$quiz_result = null;

if ($quiz) {
      // recuperer les questions de l'evaluation
    $stmt_questions = $db->prepare("SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY id ASC");
    $stmt_questions->execute([$quiz['id']]);
    $questions = $stmt_questions->fetchAll();
    
    // recuperer les anciens resultats si il y en a
    $stmt_res = $db->prepare("SELECT * FROM results WHERE student_id = ? AND quiz_id = ? LIMIT 1");
    $stmt_res->execute([$student_id, $quiz['id']]);
    $quiz_result = $stmt_res->fetch();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LMS — <?= htmlspecialchars($lesson['titre']) ?></title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .back-nav {
      margin-bottom: 20px;
    }
    .back-nav a {
      color: var(--primary-light);
      font-weight: 500;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .back-nav a:hover {
      text-decoration: underline;
    }
    .lesson-meta {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 16px;
      margin-bottom: 24px;
    }
  </style>
</head>
<body>
<div class="shell">
  <?php include '../includes/sidebar_student.php'; ?>
  <div class="main-content">
    <header class="topbar">
      <button class="menu-toggle">☰</button>
      <span class="topbar-title"><?= htmlspecialchars($lesson['titre']) ?></span>
      <div class="topbar-right">
        <a href="courses.php?module_id=<?= $module_id ?>" class="btn btn-ghost btn-sm">← Retour au cours</a>
      </div>
    </header>

    <main class="page-body">
      <div class="back-nav">
        <a href="courses.php?module_id=<?= $module_id ?>">
          <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none" style="vertical-align: middle; margin-right: 4px;"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
          <?= htmlspecialchars($lesson['module_titre']) ?> / <?= htmlspecialchars($lesson['course_titre']) ?>
        </a>
      </div>

      <div class="lesson-meta">
        <div>
          <h2><?= htmlspecialchars($lesson['titre']) ?></h2>
          <span class="text-muted" style="font-size:.85rem;">Type : <strong><?= $lesson['type'] === 'video' ? 'Vidéo' : 'Fichier PDF' ?></strong></span>
        </div>
        <div>
          <?php if (!$quiz): ?>
            <div id="completion-status-box">
              <?php if ($is_completed): ?>
                <span class="badge badge-green" style="font-size:1rem; padding: 10px 16px;">✓ Leçon complétée</span>
              <?php else: ?>
                <button id="btn-complete-lesson" class="btn btn-success" onclick="markLessonCompleted(<?= $lesson_id ?>)">
                  ✓ Marquer comme terminé
                </button>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Media Viewer -->
      <div class="lesson-viewer">
        <?php if ($lesson['type'] === 'video'): ?>
          <video controls poster="" style="max-height: 520px; background: #000;">
            <source src="../uploads/videos/<?= htmlspecialchars($lesson['fichier']) ?>" type="video/mp4">
            Votre navigateur ne supporte pas la lecture de cette vidéo.
          </video>
        <?php else: ?>
          <iframe src="../uploads/pdfs/<?= htmlspecialchars($lesson['fichier']) ?>" style="height: 600px;"></iframe>
        <?php endif; ?>
      </div>

      <?php if ($lesson['type'] === 'pdf'): ?>
        <p class="text-center mt-2 mb-4">
          <a href="../uploads/pdfs/<?= htmlspecialchars($lesson['fichier']) ?>" class="btn btn-ghost" target="_blank" style="padding: 8px 16px;">📥 Ouvrir ou télécharger le PDF</a>
        </p>
      <?php endif; ?>

      <!-- Evaluation Section (Quiz) -->
      <?php if ($quiz): ?>
        <div style="margin-top: 48px; border-top: 2px solid var(--border); padding-top: 32px;">
          <h2 class="text-center mb-3">Évaluation : <?= htmlspecialchars($quiz['titre']) ?></h2>
          
          <div id="quiz-result" class="<?= $quiz_result ? '' : 'hidden' ?>" style="margin-bottom: 24px;">
            <?php if ($quiz_result): ?>
              <div class="score-card">
                <div class="score-circle"><?= round($quiz_result['pourcentage']) ?>%</div>
                <h2><?= $quiz_result['passed'] ? 'Félicitations ! Réussi' : 'À améliorer' ?></h2>
                <p>Vous avez obtenu <strong><?= $quiz_result['score'] ?> / <?= $quiz_result['total'] ?></strong> bonnes réponses.</p>
                <?php
                // Check if module completed to award certificate
                $stmt_cert = $db->prepare("SELECT id FROM certificates WHERE student_id = ? AND module_id = ? LIMIT 1");
                $stmt_cert->execute([$student_id, $module_id]);
                $has_cert = (bool)$stmt_cert->fetch();
                if ($has_cert):
                ?>
                  <p class="mt-2"><a href="certificates.php" class="btn btn-accent mt-2">Télécharger le certificat</a></p>
                <?php endif; ?>
                <button class="btn btn-ghost mt-3" onclick="showQuizForm()">Repasser l'évaluation</button>
              </div>
            <?php endif; ?>
          </div>

          <div id="quiz-form-container" class="quiz-wrap <?= $quiz_result ? 'hidden' : '' ?>">
            <form id="quiz-form">
              <?php foreach ($questions as $index => $q): ?>
                <div class="quiz-question" data-question-id="<?= $q['id'] ?>">
                  <div class="quiz-question-text">
                    Question <?= $index + 1 ?> : <?= htmlspecialchars($q['question']) ?>
                  </div>
                  <label class="quiz-option">
                    <input type="radio" name="q_<?= $q['id'] ?>" value="A" required>
                    <strong>A.</strong> &nbsp;<?= htmlspecialchars($q['option_a']) ?>
                  </label>
                  <label class="quiz-option">
                    <input type="radio" name="q_<?= $q['id'] ?>" value="B">
                    <strong>B.</strong> &nbsp;<?= htmlspecialchars($q['option_b']) ?>
                  </label>
                  <label class="quiz-option">
                    <input type="radio" name="q_<?= $q['id'] ?>" value="C">
                    <strong>C.</strong> &nbsp;<?= htmlspecialchars($q['option_c']) ?>
                  </label>
                  <label class="quiz-option">
                    <input type="radio" name="q_<?= $q['id'] ?>" value="D">
                    <strong>D.</strong> &nbsp;<?= htmlspecialchars($q['option_d']) ?>
                  </label>
                </div>
              <?php endforeach; ?>
              
              <div class="text-center">
                <button type="button" id="quiz-submit-btn" class="btn btn-primary" style="padding:12px 24px; font-size:1rem;" onclick="submitQuiz(<?= $quiz['id'] ?>, <?= $lesson_id ?>)">
                  Soumettre mes réponses
                </button>
              </div>
            </form>
          </div>
        </div>
      <?php endif; ?>
    </main>
  </div>
</div>

<div id="toast-container" class="toast-container"></div>

<script src="../assets/js/app.js"></script>
<script>
function markLessonCompleted(lessonId) {
  const btn = document.getElementById('btn-complete-lesson');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner" style="width:18px;height:18px;border-width:2px"></span> Validation…';
  
  ajax('../api/progress_update.php', { lesson_id: lessonId })
    .then(res => {
      if (res.error) {
        Toast.error(res.error);
        btn.disabled = false;
        btn.innerHTML = '✓ Marquer comme terminé';
      } else {
        Toast.success('Leçon validée avec succès !');
        const box = document.getElementById('completion-status-box');
        box.innerHTML = '<span class="badge badge-green" style="font-size:1rem; padding: 10px 16px;">✓ Leçon complétée</span>';
      }
    })
    .catch(() => {
      Toast.error('Une erreur est survenue lors de la communication avec le serveur.');
      btn.disabled = false;
      btn.innerHTML = '✓ Marquer comme terminé';
    });
}

function showQuizForm() {
  document.getElementById('quiz-result').classList.add('hidden');
  document.getElementById('quiz-form-container').classList.remove('hidden');
  document.getElementById('quiz-submit-btn').classList.remove('hidden');
  document.getElementById('quiz-submit-btn').disabled = false;
  document.getElementById('quiz-submit-btn').innerHTML = 'Soumettre mes réponses';
  
  // Uncheck all radio buttons and remove correction styles
  const form = document.getElementById('quiz-form');
  form.reset();
  form.querySelectorAll('.quiz-option').forEach(opt => {
    opt.classList.remove('correct', 'wrong');
    opt.style.pointerEvents = 'all';
  });
}
</script>
</body>
</html>
