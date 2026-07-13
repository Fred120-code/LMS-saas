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
  <style>
    /* ── Lesson Status Banner ── */
    .lesson-gate {
      display: flex;
      align-items: center;
      gap: 16px;
      padding: 20px 24px;
      border-radius: var(--radius);
      border: 1px solid var(--border);
      background: var(--surface);
      margin-bottom: 24px;
      transition: all 0.4s ease;
    }

    .lesson-gate.state-locked {
      border-color: var(--border);
      background: var(--surface);
    }

    .lesson-gate.state-done {
      border-color: #A7F3D0;
      background: var(--success-light);
      animation: pulse-in 0.5s ease;
    }

    @keyframes pulse-in {
      0% { transform: scale(0.98); opacity: 0.6; }
      60% { transform: scale(1.01); }
      100% { transform: scale(1); opacity: 1; }
    }

    .gate-icon {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      transition: all 0.4s ease;
    }

    .state-locked .gate-icon {
      background: var(--surface2);
      color: var(--text-muted);
    }

    .state-done .gate-icon {
      background: var(--success);
      color: #fff;
    }

    .gate-icon svg {
      width: 22px;
      height: 22px;
      stroke: currentColor;
      fill: none;
      stroke-width: 2.5;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .gate-text { flex: 1; }
    .gate-title {
      font-weight: 600;
      font-size: 1rem;
      color: var(--text);
      margin-bottom: 2px;
    }

    .state-done .gate-title { color: #065F46; }

    .gate-sub {
      font-size: 0.875rem;
      color: var(--text-muted);
    }

    .state-done .gate-sub { color: #065F46; opacity: 0.8; }

    /* ── Video progress ring ── */
    .video-progress-wrap {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-top: 12px;
      font-size: 0.85rem;
      color: var(--text-muted);
    }

    .video-track-bar {
      flex: 1;
      height: 4px;
      background: var(--surface2);
      border-radius: 99px;
      overflow: hidden;
    }

    .video-track-fill {
      height: 100%;
      background: var(--primary);
      border-radius: 99px;
      width: 0%;
      transition: width 0.5s;
    }

    /* ── PDF hint ── */
    .pdf-hint {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 0.875rem;
      color: var(--text-muted);
      margin-top: 12px;
      padding: 12px 16px;
      background: var(--surface2);
      border-radius: var(--radius-sm);
      border: 1px solid var(--border-light);
    }

    .pdf-hint svg {
      flex-shrink: 0;
      width: 18px;
      height: 18px;
      stroke: var(--text-light);
      fill: none;
      stroke-width: 2;
    }

    /* lesson meta */
    .back-nav { margin-bottom: 20px; }
    .back-nav a { color: var(--primary); font-weight: 500; display: inline-flex; align-items: center; gap: 6px; font-size: 0.875rem; }
    .back-nav a:hover { text-decoration: underline; }

    .lesson-header { margin-bottom: 24px; }
    .lesson-header h2 { margin-bottom: 4px; }
    .lesson-type-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 0.8125rem;
      color: var(--text-muted);
      font-weight: 500;
    }
    .lesson-type-badge svg {
      width: 14px;
      height: 14px;
      stroke: currentColor;
      fill: none;
      stroke-width: 2;
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
              <svg viewBox="0 0 24 24"><path d="M23 7l-7 5 7 5V7z"></path><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>
              Cours Vidéo
            <?php else: ?>
              <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
              Cours PDF
            <?php endif; ?>
          </span>
        </div>

        <!-- ── Status Gate (replaces the manual "mark complete" button) ── -->
        <div class="lesson-gate <?= $is_completed ? 'state-done' : 'state-locked' ?>" id="lesson-gate">
          <div class="gate-icon">
            <?php if ($is_completed): ?>
              <svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"></path></svg>
            <?php else: ?>
              <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
            <?php endif; ?>
          </div>
          <div class="gate-text">
            <?php if ($is_completed): ?>
              <div class="gate-title">✓ Leçon terminée</div>
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
              Faire le quiz →
            </a>
          <?php elseif (!$is_completed): ?>
            <div id="quiz-btn-slot" style="display:none;">
              <!-- revealed dynamically -->
            </div>
          <?php endif; ?>
        </div>

        <?php if ($lesson['type'] === 'video' && !$is_completed): ?>
          <div class="video-progress-wrap">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="var(--primary)" fill="none" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
            <div class="video-track-bar"><div class="video-track-fill" id="vid-track"></div></div>
            <span id="vid-pct">0%</span>
          </div>
        <?php endif; ?>

        <!-- ── Media Viewer ── -->
        <div class="lesson-viewer" style="margin-top: 24px;">
          <?php if ($lesson['type'] === 'video'): ?>
            <video id="lesson-video" controls style="max-height: 560px; background: #000; width: 100%;">
              <source src="../uploads/videos/<?= htmlspecialchars($lesson['fichier']) ?>" type="video/mp4">
              Votre navigateur ne supporte pas la lecture de cette vidéo.
            </video>
          <?php else: ?>
            <iframe
              id="lesson-pdf"
              src="../uploads/pdfs/<?= htmlspecialchars($lesson['fichier']) ?>#toolbar=1&navpanes=1"
              style="height: 680px; width: 100%; border: none; display: block;">
            </iframe>
          <?php endif; ?>
        </div>

        <?php if ($lesson['type'] === 'pdf'): ?>
          <div class="pdf-hint">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
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
    // ─── Configuration ───────────────────────────────────────────────────────
    const LESSON_ID   = <?= $lesson_id ?>;
    const LESSON_TYPE = '<?= $lesson['type'] ?>';
    const HAS_QUIZ    = <?= $quiz ? 'true' : 'false' ?>;
    const QUIZ_URL    = 'quizz.php?id=<?= $lesson_id ?>';
    const IS_ALREADY_DONE = <?= $is_completed ? 'true' : 'false' ?>;

    // ─── Shared: mark lesson complete via API ─────────────────────────────────
    function completeLessonAPI() {
      ajax('../api/progress_update.php', { lesson_id: LESSON_ID })
        .then(res => {
          if (res.error) {
            console.warn('Completion error:', res.error);
            return;
          }
          showCompletedUI();
        })
        .catch(err => console.error('Network error:', err));
    }

    function showCompletedUI() {
      const gate  = document.getElementById('lesson-gate');
      const slot  = document.getElementById('quiz-btn-slot');
      const track = document.getElementById('vid-track');
      const pct   = document.getElementById('vid-pct');

      // Animate the gate to "done" state
      gate.classList.remove('state-locked');
      gate.classList.add('state-done');
      gate.querySelector('.gate-icon').innerHTML = `
        <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M20 6L9 17l-5-5"></path>
        </svg>`;
      gate.querySelector('.gate-title').textContent = '✓ Leçon terminée';
      gate.querySelector('.gate-sub').textContent   = 'Vous avez complété cette leçon avec succès.';

      // Fill the video track bar to 100%
      if (track) { track.style.width = '100%'; }
      if (pct)   { pct.textContent   = '100%'; }

      // Show the quiz button if applicable
      if (HAS_QUIZ && slot) {
        slot.style.display = 'block';
        slot.innerHTML = `<a href="${QUIZ_URL}" class="btn btn-primary">Faire le quiz →</a>`;
      }

      Toast.success('Leçon validée ! Vous pouvez maintenant passer au quiz.');
    }

    // ─── VIDEO TRACKING ───────────────────────────────────────────────────────
    if (!IS_ALREADY_DONE && LESSON_TYPE === 'video') {
      const video   = document.getElementById('lesson-video');
      const fill    = document.getElementById('vid-track');
      const pctEl   = document.getElementById('vid-pct');

      let maxWatched = 0; // highest point the user has reached (anti-scrub)
      let completed  = false;

      video.addEventListener('timeupdate', () => {
        const current  = video.currentTime;
        const duration = video.duration;
        if (!duration) return;

        // Only advance if the user is watching linearly (within 3s ahead of max)
        // This prevents scrubbing forward to the end instantly
        if (current > maxWatched + 3) {
          // The user jumped ahead — reset to maxWatched
          video.currentTime = maxWatched;
          Toast.info('Veillez regarder la vidéo complètement pour valider.');
          return;
        }

        if (current > maxWatched) maxWatched = current;

        const progress = Math.min((maxWatched / duration) * 100, 100);
        if (fill)  fill.style.width  = progress.toFixed(1) + '%';
        if (pctEl) pctEl.textContent = Math.floor(progress) + '%';
      });

      video.addEventListener('ended', () => {
        if (!completed) {
          completed = true;
          completeLessonAPI();
        }
      });
    }

    // ─── PDF TRACKING ─────────────────────────────────────────────────────────
    // Strategy: use a scroll-detection trick via a transparent overlay div
    // that sits OVER the iframe. We can't read iframe internals due to same-origin
    // restrictions on some browsers, so we use a fallback page-count approach:
    // We embed the PDF via a URL with a hash listener and use a polling trick
    // to detect when the user has scrolled to the bottom of the embedded viewer.

    if (!IS_ALREADY_DONE && LESSON_TYPE === 'pdf') {
      let pdfCompleted = false;

      // Primary method: postMessage from PDF.js viewer (works if XAMPP serves pdfs)
      // Secondary method: use an IntersectionObserver on a sentinel element placed
      // BELOW the iframe, meaning the user has scrolled past the iframe entirely.
      const iframe = document.getElementById('lesson-pdf');

      // Create a sentinel div below the iframe
      const sentinel = document.createElement('div');
      sentinel.id = 'pdf-sentinel';
      sentinel.style.cssText = 'height:2px;width:100%;margin-top:-2px;';
      iframe.parentNode.insertBefore(sentinel, iframe.nextSibling);

      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting && !pdfCompleted) {
            pdfCompleted = true;
            observer.disconnect();
            completeLessonAPI();
          }
        });
      }, { threshold: 1.0 });

      // Only start observing after a minimum reading time (prevents instant scroll)
      const MIN_READ_TIME_MS = 15000; // 15 seconds minimum
      let readTimerDone = false;
      setTimeout(() => { readTimerDone = true; }, MIN_READ_TIME_MS);

      // Wrapper observer that also checks the read timer
      const guardedObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting && !pdfCompleted) {
            if (!readTimerDone) {
              Toast.info('Continuez de lire le PDF — faites défiler jusqu\'à la fin.');
              return;
            }
            pdfCompleted = true;
            guardedObserver.disconnect();
            completeLessonAPI();
          }
        });
      }, { threshold: 0.9 });

      guardedObserver.observe(sentinel);

      <?php if (isset($_GET['notice']) && $_GET['notice'] === 'complete_first'): ?>
        window.addEventListener('DOMContentLoaded', function () {
          Toast.warn('Vous devez d\'abord terminer cette leçon avant de faire le quiz.');
        });
      <?php endif; ?>
    }
  </script>
</body>

</html>