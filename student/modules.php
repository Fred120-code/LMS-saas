<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

//role de l'étudiant
requireRole('student');

$db = getDB();
$user = currentUser();
$student_id = $user['id'];

// recuperer tous les modules et la progression
$stmt = $db->prepare("
    SELECT m.*, u.prenom, u.nom,
           COALESCE(p.pourcentage, 0.00) AS pourcentage, 
           COALESCE(p.lessons_done, 0) AS lessons_done,
           (SELECT COUNT(l.id) FROM lessons l JOIN courses c ON c.id = l.course_id WHERE c.module_id = m.id) AS total_lessons
    FROM modules m
    JOIN users u ON u.id = m.created_by
    LEFT JOIN progress p ON p.module_id = m.id AND p.student_id = ?
    ORDER BY m.created_at DESC
");
$stmt->execute([$student_id]);
$modules = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LMS — Liste des Modules</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="shell">
  <?php include '../includes/sidebar_student.php'; ?>
  <div class="main-content">
    <header class="topbar">
      <button class="menu-toggle">☰</button>
      <span class="topbar-title">Modules de formation</span>
    </header>

    <main class="page-body">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
        <h2>Mes Modules de formation</h2>
        <input class="form-control" type="search" id="search-modules" placeholder="Rechercher un module…" style="max-width:320px; margin-top:0;">
      </div>

      <?php if (empty($modules)): ?>
        <div class="card">
          <div class="card-body text-center text-muted" style="padding:48px;">
            Aucun module de formation n'est disponible pour le moment.
          </div>
        </div>
      <?php else: ?>
        <div class="grid-3" id="modules-grid">
          <?php foreach ($modules as $m): ?>
            <div class="module-card">
              <div class="module-card-header">
                <span class="module-card-title"><?= htmlspecialchars($m['titre']) ?></span>
              </div>
              <div class="module-card-body">
                <p class="module-card-meta" style="min-height: 50px;">
                  <?= htmlspecialchars(substr($m['description'], 0, 120)) ?><?= strlen($m['description']) > 120 ? '...' : '' ?>
                </p>
                
                <div style="font-size:.78rem; color:var(--text-muted); margin-bottom:4px; display:flex; justify-content:space-between;">
                  <span>Par <?= htmlspecialchars($m['prenom'] . ' ' . $m['nom']) ?></span>
                  <strong><?= $m['lessons_done'] ?> / <?= $m['total_lessons'] ?> leçons</strong>
                </div>

                <div class="module-card-progress" style="margin-bottom: 20px;">
                  <div style="display:flex; align-items:center; gap:8px;">
                    <div class="progress-bar-wrap" style="flex:1;">
                      <div class="progress-bar-fill" data-width="<?= $m['pourcentage'] ?>"></div>
                    </div>
                    <span class="pct" style="font-weight:600; font-size:.8rem; min-width:32px;"><?= round($m['pourcentage']) ?>%</span>
                  </div>
                </div>

                <a href="courses.php?module_id=<?= $m['id'] ?>" class="btn btn-primary w-full" style="justify-content:center;">
                  Accéder au module
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </main>
  </div>
</div>

<script src="../assets/js/app.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const input = document.getElementById('search-modules');
  if (input) {
    input.addEventListener('input', () => {
      const q = input.value.toLowerCase();
      document.querySelectorAll('.module-card').forEach(card => {
        const title = card.querySelector('.module-card-title').textContent.toLowerCase();
        const desc = card.querySelector('.module-card-meta').textContent.toLowerCase();
        if (title.includes(q) || desc.includes(q)) {
          card.style.display = '';
        } else {
          card.style.display = 'none';
        }
      });
    });
  }
});
</script>
</body>
</html>
