<?php
// afficher le nom et le prenom de l'utilisateur
$user = currentUser();
$initial = strtoupper(substr($user['prenom'], 0, 1) . substr($user['nom'], 0, 1));
?>
<aside class="sidebar">
  <div class="sidebar-brand">
    <span class="logo-sub">Espace Étudiant</span>
  </div>
  <div class="sidebar-user">
    <div class="sidebar-avatar"><?= $initial ?></div>
    <div class="sidebar-user-info">
      <div class="name"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></div>
      <div class="role">Étudiant</div>
    </div>
  </div>
  <nav class="sidebar-nav">
    <a class="nav-link" href="dashboard.php">
      <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
      Tableau de bord
    </a>
    <div class="nav-label">Apprentissage</div>
    <a class="nav-link" href="modules.php">
      <svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
      Modules
    </a>
    <a class="nav-link" href="my_results.php">
      <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
      Mes résultats
    </a>
    <a class="nav-link" href="certificates.php">
      <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="7"></circle><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline></svg>
      Certificats
    </a>
  </nav>
  <div class="sidebar-footer">
    <a href="../logout.php">
      <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
      Déconnexion
    </a>
  </div>
</aside>