<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireRole('admin');

$db = getDB();

//recupere les statistiques globales
$stats = [
  'modules'  => $db->query("SELECT COUNT(*) FROM modules")->fetchColumn(),
  'courses'  => $db->query("SELECT COUNT(*) FROM courses")->fetchColumn(),
  'teachers' => $db->query("SELECT COUNT(*) FROM users WHERE role='teacher'")->fetchColumn(),
  'students' => $db->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn(),
  'lessons'  => $db->query("SELECT COUNT(*) FROM lessons")->fetchColumn(),
  'results'  => $db->query("SELECT COUNT(*) FROM results")->fetchColumn(),
];

//recupere les derniers étudiants
$recentStudents = $db->query(
  "SELECT nom, prenom, email, created_at FROM users WHERE role='student' ORDER BY created_at DESC LIMIT 6"
)->fetchAll();

//recupere les derniers modules
$recentModules = $db->query(
  "SELECT m.titre, m.created_at, u.prenom, u.nom,
          (SELECT COUNT(*) FROM courses WHERE module_id=m.id) AS nb_courses
   FROM modules m JOIN users u ON u.id=m.created_by ORDER BY m.created_at DESC LIMIT 5"
)->fetchAll();
?>


<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LMS — Dashboard Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="shell">
  <?php include '../includes/sidebar_admin.php'; ?>
  <div class="main-content">

    <header class="topbar">
      <button class="menu-toggle">☰</button>
      <span class="topbar-title">Tableau de bord</span>
      <div class="topbar-right">
        <span class="topbar-greeting">Bonjour, <?= htmlspecialchars($_SESSION['prenom']) ?></span>
      </div>
    </header>

    <main class="page-body">

      <!-- Stats -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon">
            <svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
          </div>
          <div class="stat-value"><?= $stats['modules'] ?></div>
          <div class="stat-label">Modules</div>
        </div>
        <div class="stat-card accent-green">
          <div class="stat-icon">
            <svg viewBox="0 0 24 24"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
          </div>
          <div class="stat-value"><?= $stats['courses'] ?></div>
          <div class="stat-label">Cours</div>
        </div>
        <div class="stat-card accent-amber">
          <div class="stat-icon">
            <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
          </div>
          <div class="stat-value"><?= $stats['teachers'] ?></div>
          <div class="stat-label">Enseignants</div>
        </div>
        <div class="stat-card accent-red">
          <div class="stat-icon">
            <svg viewBox="0 0 24 24"><path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"></path></svg>
          </div>
          <div class="stat-value"><?= $stats['students'] ?></div>
          <div class="stat-label">Étudiants</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">
            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
          </div>
          <div class="stat-value"><?= $stats['lessons'] ?></div>
          <div class="stat-label">Leçons</div>
        </div>
        <div class="stat-card accent-green">
          <div class="stat-icon">
            <svg viewBox="0 0 24 24"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
          </div>
          <div class="stat-value"><?= $stats['results'] ?></div>
          <div class="stat-label">Évaluations passées</div>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;flex-wrap:wrap;">

        <!-- modules recents -->
        <div class="card">
          <div class="card-header">
            <h3>Derniers modules</h3>
            <a href="modules.php" class="btn btn-ghost btn-sm">Voir tout</a>
          </div>
          <div class="table-wrap">
            <table>
              <thead><tr><th>Titre</th><th>Cours</th><th>Date</th></tr></thead>
              <tbody>
              <?php foreach ($recentModules as $m): ?>
                <tr>
                  <td><?= htmlspecialchars($m['titre']) ?></td>
                  <td><span class="badge badge-blue"><?= $m['nb_courses'] ?></span></td>
                  <td class="text-muted" style="font-size:.8rem;"><?= date('d/m/Y', strtotime($m['created_at'])) ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- etudiant recents  -->
        <div class="card">
          <div class="card-header">
            <h3>Derniers étudiants</h3>
            <a href="students.php" class="btn btn-ghost btn-sm">Voir tout</a>
          </div>
          <div class="table-wrap">
            <table>
              <thead><tr><th>Nom</th><th>Email</th><th>Date</th></tr></thead>
              <tbody>
              <?php foreach ($recentStudents as $s): ?>
                <tr>
                  <td><?= htmlspecialchars($s['prenom'] . ' ' . $s['nom']) ?></td>
                  <td style="font-size:.8rem;"><?= htmlspecialchars($s['email']) ?></td>
                  <td class="text-muted" style="font-size:.8rem;"><?= date('d/m/Y', strtotime($s['created_at'])) ?></td>
                </tr>
              <?php endforeach; ?>
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