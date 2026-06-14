<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

//role de l'etudiant
requireRole('student');

$db = getDB();
$user = currentUser();
$student_id = $user['id'];

// recuperer les resultats de l'etudiant
$stmt = $db->prepare("
    SELECT r.*, q.titre AS quiz_titre, l.titre AS lesson_titre, m.titre AS module_titre, l.id AS lesson_id
    FROM results r
    JOIN quizzes q ON q.id = r.quiz_id
    JOIN lessons l ON l.id = r.lesson_id
    JOIN courses c ON c.id = l.course_id
    JOIN modules m ON m.id = c.module_id
    WHERE r.student_id = ?
    ORDER BY r.taken_at DESC
");
$stmt->execute([$student_id]);
$results = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LMS — Mes Résultats</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="shell">
  <?php include '../includes/sidebar_student.php'; ?>
  <div class="main-content">
    <header class="topbar">
      <button class="menu-toggle">☰</button>
      <span class="topbar-title">Mes Résultats</span>
    </header>

    <main class="page-body">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
        <h2>Historique de mes évaluations</h2>
        <input class="form-control" type="search" id="search-results" placeholder="Rechercher une évaluation…" style="max-width:320px; margin-top:0;">
      </div>

      <div class="card">
        <div class="card-header">
          <h3>Évaluations passées (<?= count($results) ?>)</h3>
        </div>
        <div class="table-wrap">
          <table id="results-table">
            <thead>
              <tr>
                <th>Quiz</th>
                <th>Module / Leçon</th>
                <th>Score</th>
                <th>Pourcentage</th>
                <th>Statut</th>
                <th>Date de passage</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($results)): ?>
                <tr>
                  <td colspan="7" class="text-center text-muted" style="padding:48px;">
                    Vous n'avez pas encore passé d'évaluation.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($results as $r): ?>
                  <tr>
                    <td><strong><?= htmlspecialchars($r['quiz_titre']) ?></strong></td>
                    <td style="font-size:.85rem;">
                      <span class="text-muted"><?= htmlspecialchars($r['module_titre']) ?></span><br>
                      <?= htmlspecialchars($r['lesson_titre']) ?>
                    </td>
                    <td><?= $r['score'] ?> / <?= $r['total'] ?></td>
                    <td><strong><?= round($r['pourcentage']) ?>%</strong></td>
                    <td>
                      <?php if ($r['passed']): ?>
                        <span class="badge badge-green">Réussi</span>
                      <?php else: ?>
                        <span class="badge badge-red">Échoué</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-muted" style="font-size:.8rem;"><?= date('d/m/Y à H:i', strtotime($r['taken_at'])) ?></td>
                    <td>
                      <a href="lesson.php?id=<?= $r['lesson_id'] ?>" class="btn btn-ghost btn-sm">Revoir</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>

<script src="../assets/js/app.js"></script>
<script>
tableSearch('search-results', 'results-table');
</script>
</body>
</html>
