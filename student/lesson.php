<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

//role de l'étudiant
requireRole('student');

$lesson_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
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
$is_completed = (bool) $stmt_check->fetch();

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
            <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none"
              style="vertical-align: middle; margin-right: 4px;">
              <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
            </svg>
            <?= htmlspecialchars($lesson['module_titre']) ?> / <?= htmlspecialchars($lesson['course_titre']) ?>
          </a>
        </div>

        <div class="lesson-meta">
          <div>
            <h2><?= htmlspecialchars($lesson['titre']) ?></h2>
            <span class="text-muted" style="font-size:.85rem;">Type :
              <strong><?= $lesson['type'] === 'video' ? 'Vidéo' : 'Fichier PDF' ?></strong></span>
          </div>
          <div>
            <div id="completion-status-box">
              <?php if ($is_completed): ?>
                <span class="badge badge-green" style="font-size:1rem; padding: 10px 16px;"> Leçon complétée</span>
                <?php if ($quiz): ?>
                  <a href="quizz.php?id=<?= $lesson_id ?>" class="btn btn-primary" style="margin-left:12px;">
                    Faire le quiz
                  </a>
                <?php endif; ?>
              <?php else: ?>
                <button id="btn-complete-lesson" class="btn btn-success" onclick="markLessonCompleted(<?= $lesson_id ?>)">
                  Marquer comme terminé
                </button>
              <?php endif; ?>
            </div>
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
            <a href="../uploads/pdfs/<?= htmlspecialchars($lesson['fichier']) ?>" class="btn btn-ghost" target="_blank"
              style="padding: 8px 16px;"> Ouvrir ou télécharger le PDF</a>
          </p>
        <?php endif; ?>


      </main>
    </div>
  </div>

  <div id="toast-container" class="toast-container"></div>

  <script src="../assets/js/app.js"></script>
  <script>
    <?php if (isset($_GET['notice']) && $_GET['notice'] === 'complete_first'): ?>
        window.addEventListener('DOMContentLoaded', function () {
          Toast.warn('Vous devez d\'abord terminer cette leçon avant de faire le quiz.');
        });
    <?php endif; ?>

      function markLessonCompleted(lessonId) {
        const btn = document.getElementById('btn-complete-lesson');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner" style="width:18px;height:18px;border-width:2px"></span> Validation…';

        const hasQuiz = <?= $quiz ? 'true' : 'false' ?>;
        const quizUrl = 'quizz.php?id=<?= $lesson_id ?>';

        ajax('../api/progress_update.php', { lesson_id: lessonId })
          .then(res => {
            if (res.error) {
              Toast.error(res.error);
              btn.disabled = false;
              btn.innerHTML = 'Marquer comme terminé';
            } else {
              Toast.success('Leçon validée avec succès !');
              const box = document.getElementById('completion-status-box');
              let html = '<span class="badge badge-green" style="font-size:1rem; padding: 10px 16px;">✓ Leçon complétée</span>';
              if (hasQuiz) {
                html += ' <a href="' + quizUrl + '" class="btn btn-primary" style="margin-left:12px;">📝 Faire le quiz</a>';
              }
              box.innerHTML = html;
            }
          })
          .catch(() => {
            Toast.error('Une erreur est survenue lors de la communication avec le serveur.');
            btn.disabled = false;
            btn.innerHTML = 'Marquer comme terminé';
          });
      }
  </script>
</body>

</html>