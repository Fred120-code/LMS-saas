<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

requireRole('teacher');

$db = getDB();
$user = currentUser();
$teacher_id = $user['id'];

//recuperer les resultats des etudiants aux quiz crees par l'enseignant
$stmt = $db->prepare("
    SELECT r.*, u.prenom AS student_prenom, u.nom AS student_nom, u.email AS student_email,
           q.titre AS quiz_titre, l.titre AS lesson_titre, c.titre AS course_titre
    FROM results r
    JOIN users u ON u.id = r.student_id
    JOIN quizzes q ON q.id = r.quiz_id
    JOIN lessons l ON l.id = r.lesson_id
    JOIN courses c ON c.id = l.course_id
    WHERE c.teacher_id = ?
    ORDER BY r.taken_at DESC
");
$stmt->execute([$teacher_id]);
$results = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LMS — Résultats des Étudiants</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="shell">
  <?php include '../includes/sidebar_teacher.php'; ?>
  <div class="main-content">
    <header class="topbar">
      <button class="menu-toggle">☰</button>
      <span class="topbar-title">Suivi des Résultats</span>
    </header>

    <main class="page-body">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
        <h2>Résultats des étudiants à vos quiz</h2>
        <input class="form-control" type="search" id="search-student-results" placeholder="Rechercher un étudiant ou un cours…" style="max-width:320px; margin-top:0;">
      </div>

      <div class="card">
        <div class="card-header">
          <h3>Résultats enregistrés (<?= count($results) ?>)</h3>
        </div>
        <div class="table-wrap">
          <table id="student-results-table">
            <thead>
              <tr>
                <th>Étudiant</th>
                <th>Cours / Leçon / Quiz</th>
                <th>Score</th>
                <th>Pourcentage</th>
                <th>Statut</th>
                <th>Date de passage</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($results)): ?>
                <tr>
                  <td colspan="6" class="text-center text-muted" style="padding:48px;">
                    Aucun étudiant n'a encore passé d'évaluation pour vos cours.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($results as $r): ?>
                  <tr>
                    <td>
                      <strong><?= htmlspecialchars($r['student_prenom'] . ' ' . $r['student_nom']) ?></strong><br>
                      <span class="text-muted" style="font-size:.78rem;"><?= htmlspecialchars($r['student_email']) ?></span>
                    </td>
                    <td>
                      <span class="text-muted" style="font-size:.8rem;"><?= htmlspecialchars($r['course_titre']) ?></span><br>
                      <strong><?= htmlspecialchars($r['lesson_titre']) ?></strong> · <span style="font-style:italic; font-size:.85rem;"><?= htmlspecialchars($r['quiz_titre']) ?></span>
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
tableSearch('search-student-results', 'student-results-table');
</script>
</body>
</html>
