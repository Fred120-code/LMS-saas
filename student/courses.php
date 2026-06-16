<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Ensure student role
requireRole('student');

$module_id = isset($_GET['module_id']) ? (int)$_GET['module_id'] : 0;
if (!$module_id) {
    header('Location: modules.php');
    exit;
}

$db = getDB();
$user = currentUser();
$student_id = $user['id'];

// 1. Fetch Module Info
$stmt_mod = $db->prepare("SELECT * FROM modules WHERE id = ? LIMIT 1");
$stmt_mod->execute([$module_id]);
$module = $stmt_mod->fetch();

if (!$module) {
    header('Location: modules.php');
    exit;
}

// 2. Fetch courses in this module
$stmt_courses = $db->prepare("
    SELECT c.*, u.prenom, u.nom
    FROM courses c
    JOIN users u ON u.id = c.teacher_id
    WHERE c.module_id = ?
    ORDER BY c.id ASC
");
$stmt_courses->execute([$module_id]);
$courses = $stmt_courses->fetchAll();

// 3. For each course, fetch lessons and completion status
$courses_with_lessons = [];
foreach ($courses as $course) {
    $stmt_lessons = $db->prepare("
        SELECT l.*, 
               (SELECT 1 FROM student_lessons WHERE student_id = ? AND lesson_id = l.id LIMIT 1) AS completed,
               (SELECT id FROM quizzes WHERE lesson_id = l.id LIMIT 1) AS quiz_id
        FROM lessons l
        WHERE l.course_id = ?
        ORDER BY l.ordre ASC, l.id ASC
    ");
    $stmt_lessons->execute([$student_id, $course['id']]);
    $lessons = $stmt_lessons->fetchAll();
    
    $course['lessons'] = $lessons;
    $courses_with_lessons[] = $course;
}

// 4. Calculate progress percentage for this module
$stmt_total_l = $db->prepare("
    SELECT COUNT(l.id) 
    FROM lessons l 
    JOIN courses c ON c.id = l.course_id 
    WHERE c.module_id = ?
");
$stmt_total_l->execute([$module_id]);
$total_lessons = (int)$stmt_total_l->fetchColumn();

$stmt_done_l = $db->prepare("
    SELECT COUNT(sl.lesson_id) 
    FROM student_lessons sl 
    JOIN lessons l ON l.id = sl.lesson_id 
    JOIN courses c ON c.id = l.course_id 
    WHERE sl.student_id = ? AND c.module_id = ?
");
$stmt_done_l->execute([$student_id, $module_id]);
$completed_lessons = (int)$stmt_done_l->fetchColumn();

$pourcentage = 0.00;
if ($total_lessons > 0) {
    $pourcentage = round(($completed_lessons / $total_lessons) * 100, 2);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LMS — Cours du Module</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="shell">
  <?php include '../includes/sidebar_student.php'; ?>
  <div class="main-content">
    <header class="topbar">
      <button class="menu-toggle">☰</button>
      <span class="topbar-title">Contenu du module</span>
      <div class="topbar-right">
        <a href="modules.php" class="btn btn-ghost btn-sm">← Retour aux modules</a>
      </div>
    </header>

    <main class="page-body">
      <!-- Header Module -->
      <div class="card mb-3" style="background:var(--primary); color: #fff;">
        <div class="card-body" style="padding: 32px;">
          <h1 style="color:#fff; margin-bottom:12px; font-family:'Playfair Display', serif;"><?= htmlspecialchars($module['titre']) ?></h1>
          <p style="color: rgba(255,255,255,.8); font-size:1rem; margin-bottom:20px; max-width:800px;">
            <?= htmlspecialchars($module['description']) ?>
          </p>
          <div style="display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
            <div class="progress-bar-wrap" style="flex:1; max-width:300px; background: rgba(255,255,255,0.2);">
              <div class="progress-bar-fill" data-width="<?= $pourcentage ?>"></div>
            </div>
            <span style="font-weight:600; font-size:.9rem;"><?= round($pourcentage) ?>% Complété</span>
            <span style="font-size:.85rem; color:var(--accent); font-weight:500;">(<?= $completed_lessons ?> / <?= $total_lessons ?> leçons)</span>
          </div>
        </div>
      </div>

      <!-- Courses & Lessons List -->
      <?php if (empty($courses_with_lessons)): ?>
        <div class="card">
          <div class="card-body text-center text-muted" style="padding:48px;">
            Aucun cours n'est programmé dans ce module pour le moment.
          </div>
        </div>
      <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:24px;">
          <?php foreach ($courses_with_lessons as $c): ?>
            <div class="card">
              <div class="card-header">
                <div>
                  <h3 style="margin-bottom: 4px;"><?= htmlspecialchars($c['titre']) ?></h3>
                  <span class="text-muted" style="font-size:.8rem;">Enseigné par : <strong><?= htmlspecialchars($c['prenom'] . ' ' . $c['nom']) ?></strong></span>
                </div>
              </div>
              <div class="card-body">
                <?php if ($c['description']): ?>
                  <p class="text-muted mb-2" style="font-size:.9rem;"><?= htmlspecialchars($c['description']) ?></p>
                <?php endif; ?>

                <?php if (empty($c['lessons'])): ?>
                  <p class="text-muted" style="font-size:.85rem; font-style:italic;">Aucune leçon disponible pour ce cours.</p>
                <?php else: ?>
                  <ul class="lesson-list">
                    <?php foreach ($c['lessons'] as $l): ?>
                      <li>
                        <a href="lesson.php?id=<?= $l['id'] ?>" class="lesson-item">
                          <div class="lesson-icon" style="color: var(--primary-light);">
                            <?php if ($l['type'] === 'video'): ?>
                              <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" style="display:block;"><path d="M23 7l-7 5 7 5V7z"></path><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>
                            <?php else: ?>
                              <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" style="display:block;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                            <?php endif; ?>
                          </div>
                          <div class="lesson-title">
                            <?= htmlspecialchars($l['titre']) ?>
                            <?php if ($l['quiz_id']): ?>
                              <span class="badge badge-amber" style="margin-left: 8px;">Quiz</span>
                            <?php endif; ?>
                          </div>
                          <div class="lesson-status">
                            <?php if ($l['completed']): ?>
                              <span class="badge badge-green">✓ Complété</span>
                            <?php else: ?>
                              <span class="badge badge-gray">À faire</span>
                            <?php endif; ?>
                          </div>
                        </a>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </main>
  </div>
</div>

<script src="../assets/js/app.js"></script>
</body>
</html>
