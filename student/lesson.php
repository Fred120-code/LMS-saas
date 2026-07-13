<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

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
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LMS — <?= htmlspecialchars($lesson['titre']) ?></title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/lesson.css">
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
            <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none">
              <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
            </svg>
            <?= htmlspecialchars($lesson['module_titre']) ?> / <?= htmlspecialchars($lesson['course_titre']) ?>
          </a>
        </div>

        <div class="lesson-header">
          <h2><?= htmlspecialchars($lesson['titre']) ?></h2>
          <span class="lesson-type-badge">
            <?php if ($lesson['type'] === 'video'): ?>
              <svg viewBox="0 0 24 24">
                <path d="M23 7l-7 5 7 5V7z"></path>
                <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
              </svg>
              Cours Vidéo
            <?php else: ?>
              <svg viewBox="0 0 24 24">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
              </svg>
              Cours PDF
            <?php endif; ?>
          </span>
        </div>

        <!-- Status du cours -->
        <div class="lesson-gate <?= $is_completed ? 'state-done' : 'state-locked' ?>" id="lesson-gate">
          <div class="gate-icon">
            <?php if ($is_completed): ?>
              <svg viewBox="0 0 24 24">
                <path d="M20 6L9 17l-5-5"></path>
              </svg>
            <?php else: ?>
              <svg viewBox="0 0 24 24">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
              </svg>
            <?php endif; ?>
          </div>
          <div class="gate-text">
            <?php if ($is_completed): ?>
              <div class="gate-title">Leçon terminée</div>
              <div class="gate-sub">Vous avez complété cette leçon avec succès.</div>
            <?php elseif ($lesson['type'] === 'video'): ?>
              <div class="gate-title" id="gate-title">Terminez la vidéo pour débloquer le quiz</div>
              <div class="gate-sub" id="gate-sub">La progression se met à jour automatiquement.</div>
            <?php else: ?>
              <div class="gate-title" id="gate-title">Lisez le PDF jusqu'à la fin pour débloquer le quiz</div>
              <div class="gate-sub" id="gate-sub">Faites défiler jusqu'à la dernière page pour valider la leçon.</div>
            <?php endif; ?>
          </div>
          <?php if ($is_completed && $quiz): ?>
            <a href="quizz.php?id=<?= $lesson_id ?>" class="btn btn-primary">
              Faire le quiz
            </a>
          <?php elseif (!$is_completed): ?>
            <div id="quiz-btn-slot" style="display:none;">
            </div>
          <?php endif; ?>
        </div>

        <?php if ($lesson['type'] === 'video' && !$is_completed): ?>
          <div class="video-progress-wrap">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="var(--primary)" fill="none" stroke-width="2">
              <polygon points="5 3 19 12 5 21 5 3"></polygon>
            </svg>
            <div class="video-track-bar">
              <div class="video-track-fill" id="vid-track"></div>
            </div>
            <span id="vid-pct">0%</span>
          </div>
        <?php endif; ?>

        <!--Media Viewer -->
        <div class="lesson-viewer" style="margin-top: 24px;">
          <?php if ($lesson['type'] === 'video'): ?>
            <video id="lesson-video" controls style="max-height: 560px; background: #000; width: 100%;">
              <source src="../uploads/videos/<?= htmlspecialchars($lesson['fichier']) ?>" type="video/mp4">
              Votre navigateur ne supporte pas la lecture de cette vidéo.
            </video>
          <?php else: ?>
            <iframe id="lesson-pdf" src="../uploads/pdfs/<?= htmlspecialchars($lesson['fichier']) ?>#toolbar=1&navpanes=1"
              style="height: 680px; width: 100%; border: none; display: block;">
            </iframe>
          <?php endif; ?>
        </div>

        <?php if ($lesson['type'] === 'pdf'): ?>
          <div class="pdf-hint">
            <svg viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="8" x2="12" y2="12"></line>
              <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <span>Faites défiler le PDF jusqu'à la dernière page pour valider automatiquement votre progression.</span>
          </div>
          <p class="text-center mt-3 mb-4">
            <a href="../uploads/pdfs/<?= htmlspecialchars($lesson['fichier']) ?>" class="btn btn-ghost" target="_blank">
              Ouvrir le PDF dans un nouvel onglet
            </a>
          </p>
        <?php endif; ?>

      </main>
    </div>
  </div>

  <div id="toast-container" class="toast-container"></div>

  <script src="../assets/js/app.js"></script>
  <script>
    //Configuration globale pour le tracking 
    window.LESSON_ID = <?= $lesson_id ?>;
    window.LESSON_TYPE = '<?= $lesson['type'] ?>';
    window.HAS_QUIZ = <?= $quiz ? 'true' : 'false' ?>;
    window.QUIZ_URL = 'quizz.php?id=<?= $lesson_id ?>';
    window.IS_ALREADY_DONE = <?= $is_completed ? 'true' : 'false' ?>;

    <?php if (isset($_GET['notice']) && $_GET['notice'] === 'complete_first'): ?>
      window.addEventListener('DOMContentLoaded', function () {
        Toast.warn('Vous devez d\'abord terminer cette leçon avant de faire le quiz.');
      });
    <?php endif; ?>
  </script>
  <script src="../assets/js/tracking/core.js"></script>
  <?php if ($lesson['type'] === 'video'): ?>
    <script src="../assets/js/tracking/video.js"></script>
  <?php elseif ($lesson['type'] === 'pdf'): ?>
    <script src="../assets/js/tracking/pdf.js"></script>
  <?php endif; ?>
</body>

</html>