<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';


requireRole('teacher');

$db = getDB();
$user = currentUser();
$teacher_id = $user['id'];

// recuperer les stats
$stmt_nb_courses = $db->prepare("SELECT COUNT(*) FROM courses WHERE teacher_id = ?");
$stmt_nb_courses->execute([$teacher_id]);
$nb_courses = $stmt_nb_courses->fetchColumn();

// recuperer les lecons
$stmt_nb_lessons = $db->prepare("
    SELECT COUNT(l.id) 
    FROM lessons l 
    JOIN courses c ON c.id = l.course_id 
    WHERE c.teacher_id = ?
");
$stmt_nb_lessons->execute([$teacher_id]);
$nb_lessons = $stmt_nb_lessons->fetchColumn();

//recuperer les resultats
$stmt_nb_results = $db->prepare("
    SELECT COUNT(r.id) 
    FROM results r 
    JOIN lessons l ON l.id = r.lesson_id 
    JOIN courses c ON c.id = l.course_id 
    WHERE c.teacher_id = ?
");

$stmt_nb_results->execute([$teacher_id]);
$nb_results = $stmt_nb_results->fetchColumn();

$stats = [
    'courses' => $nb_courses,
    'lessons' => $nb_lessons,
    'results' => $nb_results
];

// recuperer les cours recents
$stmt_courses = $db->prepare("
    SELECT c.*, m.titre AS module_titre, 
           (SELECT COUNT(*) FROM lessons WHERE course_id = c.id) AS nb_lessons 
    FROM courses c 
    JOIN modules m ON m.id = c.module_id 
    WHERE c.teacher_id = ? 
    ORDER BY c.created_at DESC 
    LIMIT 5
");
$stmt_courses->execute([$teacher_id]);
$courses = $stmt_courses->fetchAll();

// recuperer les resultats recents des etudiants
$stmt_results = $db->prepare("
    SELECT r.*, u.prenom AS student_prenom, u.nom AS student_nom, 
           q.titre AS quiz_titre, c.titre AS course_titre 
    FROM results r 
    JOIN users u ON u.id = r.student_id 
    JOIN quizzes q ON q.id = r.quiz_id 
    JOIN lessons l ON l.id = r.lesson_id 
    JOIN courses c ON c.id = l.course_id 
    WHERE c.teacher_id = ? 
    ORDER BY r.taken_at DESC 
    LIMIT 5
");
$stmt_results->execute([$teacher_id]);
$recentResults = $stmt_results->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LMS — Tableau de bord Enseignant</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="shell">
  <?php include '../includes/sidebar_teacher.php'; ?>
  <div class="main-content">
    <header class="topbar">
      <button class="menu-toggle">☰</button>
      <span class="topbar-title">Tableau de bord Enseignant</span>
      <div class="topbar-right">
        <span class="topbar-greeting">Bonjour, M/Mme. <?= htmlspecialchars($user['prenom']) ?></span>
      </div>
    </header>

    <main class="page-body">
      <!-- Stats Grid -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon">
            <svg viewBox="0 0 24 24"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
          </div>
          <div class="stat-value"><?= $stats['courses'] ?></div>
          <div class="stat-label">Mes Cours</div>
        </div>
        <div class="stat-card accent-green">
          <div class="stat-icon">
            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
          </div>
          <div class="stat-value"><?= $stats['lessons'] ?></div>
          <div class="stat-label">Leçons créées</div>
        </div>
        <div class="stat-card accent-amber">
          <div class="stat-icon">
            <svg viewBox="0 0 24 24"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
          </div>
          <div class="stat-value"><?= $stats['results'] ?></div>
          <div class="stat-label">Évaluations passées par les élèves</div>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;flex-wrap:wrap;">
        
        <!-- Mes cours récents -->
        <div class="card">
          <div class="card-header">
            <h3>Mes Cours récents</h3>
            <a href="courses.php" class="btn btn-ghost btn-sm">Gérer tout</a>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Titre</th>
                  <th>Module</th>
                  <th>Leçons</th>
                  <th>Créé le</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($courses)): ?>
                  <tr>
                    <td colspan="4" class="text-center text-muted" style="padding:24px;">Aucun cours créé pour le moment.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($courses as $c): ?>
                    <tr>
                      <td><strong><?= htmlspecialchars($c['titre']) ?></strong></td>
                      <td><?= htmlspecialchars($c['module_titre']) ?></td>
                      <td><span class="badge badge-blue"><?= $c['nb_lessons'] ?> leçons</span></td>
                      <td class="text-muted" style="font-size:.8rem;"><?= date('d/m/Y', strtotime($c['created_at'])) ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Derniers résultats des étudiants -->
        <div class="card">
          <div class="card-header">
            <h3>Résultats élèves récents</h3>
            <a href="results.php" class="btn btn-ghost btn-sm">Voir tout</a>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Étudiant</th>
                  <th>Quiz / Cours</th>
                  <th>Score</th>
                  <th>Statut</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($recentResults)): ?>
                  <tr>
                    <td colspan="4" class="text-center text-muted" style="padding:24px;">Aucun élève n'a encore passé d'évaluation.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($recentResults as $r): ?>
                    <tr>
                      <td><strong><?= htmlspecialchars($r['student_prenom'] . ' ' . $r['student_nom']) ?></strong></td>
                      <td style="font-size:.8rem;">
                        <strong><?= htmlspecialchars($r['quiz_titre']) ?></strong><br>
                        <span class="text-muted"><?= htmlspecialchars($r['course_titre']) ?></span>
                      </td>
                      <td><?= $r['score'] ?> / <?= $r['total'] ?> (<?= round($r['pourcentage']) ?>%)</td>
                      <td>
                        <?php if ($r['passed']): ?>
                          <span class="badge badge-green">Réussi</span>
                        <?php else: ?>
                          <span class="badge badge-red">Échoué</span>
                        <?php endif; ?>
                      </td>
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
