<?php
// afficher le nom et le prenom de l'utilisateur
$user = currentUser();
$initial = strtoupper(substr($user['prenom'], 0, 1) . substr($user['nom'], 0, 1));
?>

<aside class="sidebar">
  <div class="sidebar-brand">
    <span class="logo-sub">Administration</span>
  </div>
  <div class="sidebar-user">
    <div class="sidebar-avatar"><?= $initial ?></div>
    <div class="sidebar-user-info">
      <div class="name"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></div>
      <div class="role">Promoteur</div>
    </div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-label">Général</div>
    <a class="nav-link" href="dashboard.php">
      <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
      Tableau de bord
    </a>
    <div class="nav-label">Gestion</div>
    <a class="nav-link" href="modules.php">
      <svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
      Modules
    </a>
    <a class="nav-link" href="teachers.php">
      <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
      Enseignants
    </a>
    <a class="nav-link" href="students.php">
      <svg viewBox="0 0 24 24"><path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"></path></svg>
      Étudiants
    </a>
  </nav>
  <div class="sidebar-footer">
    <a href="../logout.php">
      <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
      Déconnexion
    </a>
  </div>
</aside>