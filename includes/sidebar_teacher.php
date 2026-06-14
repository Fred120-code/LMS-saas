<?php
// afficher le nom et le prenom de l'utilisateur
$user = currentUser();
$initial = strtoupper(substr($user['prenom'], 0, 1) . substr($user['nom'], 0, 1));
?>

<aside class="sidebar">
  <div class="sidebar-brand">
    <span class="logo-sub">Espace Enseignant</span>
  </div>
  <div class="sidebar-user">
    <div class="sidebar-avatar"><?= $initial ?></div>
    <div class="sidebar-user-info">
      <div class="name"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></div>
      <div class="role">Enseignant</div>
    </div>
  </div>
  <nav class="sidebar-nav">
    <a class="nav-link" href="dashboard.php">
      <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
      Tableau de bord
    </a>
    <div class="nav-label">Contenu</div>
    <a class="nav-link" href="courses.php">
      <svg viewBox="0 0 24 24"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path></svg>
      Mes cours
    </a>
    <a class="nav-link" href="create_course.php">
      <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
      Créer un cours
    </a>
    <div class="nav-label">Suivi</div>
    <a class="nav-link" href="results.php">
      <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
      Résultats
    </a>
  </nav>
  <div class="sidebar-footer">
    <a href="../logout.php">
      <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
      Déconnexion
    </a>
  </div>
</aside>