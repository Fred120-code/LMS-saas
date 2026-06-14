<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

//role de l'étudiant
requireRole('student');

$db = getDB();
$user = currentUser();
$student_id = $user['id'];

// recuperer les stats
$stats = [
    'started_modules' => $db->query("SELECT COUNT(*) FROM progress WHERE student_id = {$student_id} AND pourcentage > 0")->fetchColumn(),
    'certificates'    => $db->query("SELECT COUNT(*) FROM certificates WHERE student_id = {$student_id}")->fetchColumn(),
    'avg_score'       => round((float)$db->query("SELECT AVG(pourcentage) FROM results WHERE student_id = {$student_id}")->fetchColumn(), 1)
];

// recuperer les modules et la progression
$stmt_modules = $db->prepare("
    SELECT m.*, 
           COALESCE(p.pourcentage, 0.00) AS pourcentage, 
           COALESCE(p.lessons_done, 0) AS lessons_done,
           (SELECT COUNT(l.id) FROM lessons l JOIN courses c ON c.id = l.course_id WHERE c.module_id = m.id) AS total_lessons
    FROM modules m
    LEFT JOIN progress p ON p.module_id = m.id AND p.student_id = ?
    ORDER BY m.created_at DESC
    LIMIT 6
");
$stmt_modules->execute([$student_id]);
$modules = $stmt_modules->fetchAll();

// recuperer les dernieres evaluations
$stmt_results = $db->prepare("
    SELECT r.*, q.titre AS quiz_titre, l.titre AS lesson_titre
    FROM results r
    JOIN quizzes q ON q.id = r.quiz_id
    JOIN lessons l ON l.id = r.lesson_id
    WHERE r.student_id = ?
    ORDER BY r.taken_at DESC
    LIMIT 5
");
$stmt_results->execute([$student_id]);
$recentResults = $stmt_results->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LMS — Tableau de bord Étudiant</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="shell">
  <?php include '../includes/sidebar_student.php'; ?>
  <div class="main-content">
    <header class="topbar">
      <button class="menu-toggle">☰</button>
      <span class="topbar-title">Tableau de bord</span>
      <div class="topbar-right">
        <span class="topbar-greeting">Bonjour, <?= htmlspecialchars($user['prenom']) ?></span>
      </div>
    </header>

    <main class="page-body">
      <!-- Stats Grid -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon">
            <svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
          </div>
          <div class="stat-value"><?= $stats['started_modules'] ?></div>
          <div class="stat-label">Modules commencés</div>
        </div>
        <div class="stat-card accent-green">
          <div class="stat-icon">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="7"></circle><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline></svg>
          </div>
          <div class="stat-value"><?= $stats['certificates'] ?></div>
          <div class="stat-label">Certificats obtenus</div>
        </div>
        <div class="stat-card accent-amber">
          <div class="stat-icon">
            <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
          </div>
          <div class="stat-value"><?= $stats['avg_score'] ?>%</div>
          <div class="stat-label">Score moyen aux évaluations</div>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;flex-wrap:wrap;" class="dashboard-sections">
        
        <!-- Modules en cours -->
        <div class="card">
          <div class="card-header">
            <h3>Mes Modules</h3>
            <a href="modules.php" class="btn btn-ghost btn-sm">Voir tout</a>
          </div>
          <div class="card-body">
            <?php if (empty($modules)): ?>
              <p class="text-muted">Aucun module disponible pour le moment.</p>
            <?php else: ?>
              <div style="display:flex;flex-direction:column;gap:18px;">
                <?php foreach ($modules as $m): ?>
                  <div style="border-bottom: 1px solid var(--border); padding-bottom: 12px; last-child: border-bottom-none;">
                    <div style="display:flex; justify-content:space-between; margin-bottom: 6px;">
                      <strong><?= htmlspecialchars($m['titre']) ?></strong>
                      <span class="text-muted" style="font-size:.8rem;"><?= $m['lessons_done'] ?> / <?= $m['total_lessons'] ?> leçons</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:12px;">
                      <div class="progress-bar-wrap" style="flex:1;">
                        <div class="progress-bar-fill" data-width="<?= $m['pourcentage'] ?>"></div>
                      </div>
                      <span style="font-size:.8rem; font-weight:600; width: 40px; text-align:right;"><?= round($m['pourcentage']) ?>%</span>
                      <a href="courses.php?module_id=<?= $m['id'] ?>" class="btn btn-primary btn-sm btn-icon" style="padding:4px 8px;">➔</a>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Dernières Évaluations -->
        <div class="card">
          <div class="card-header">
            <h3>Dernières Évaluations</h3>
            <a href="my_results.php" class="btn btn-ghost btn-sm">Voir tout</a>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Quiz</th>
                  <th>Score</th>
                  <th>Statut</th>
                  <th>Date</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($recentResults)): ?>
                  <tr>
                    <td colspan="4" class="text-center text-muted" style="padding:24px;">Aucun quiz passé pour le moment.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($recentResults as $r): ?>
                    <tr>
                      <td>
                        <strong><?= htmlspecialchars($r['quiz_titre']) ?></strong><br>
                        <span class="text-muted" style="font-size:.75rem;"><?= htmlspecialchars($r['lesson_titre']) ?></span>
                      </td>
                      <td><?= $r['score'] ?> / <?= $r['total'] ?></td>
                      <td>
                        <?php if ($r['passed']): ?>
                          <span class="badge badge-green">Réussi</span>
                        <?php else: ?>
                          <span class="badge badge-red">Échoué</span>
                        <?php endif; ?>
                      </td>
                      <td class="text-muted" style="font-size:.8rem;"><?= date('d/m/Y', strtotime($r['taken_at'])) ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </main>
  </div>
</div>

<script src="../assets/js/app.js"></script>
</body>
</html>
