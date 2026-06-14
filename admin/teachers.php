<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireRole('admin');

$db  = getDB();
$msg = $err = '';

// ajouter un enseignant
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $nom    = trim($_POST['nom']    ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email  = trim($_POST['email']  ?? '');
    $pass   = trim($_POST['password'] ?? '');
    if ($nom && $prenom && $email && $pass) {
        $check = $db->prepare("SELECT id FROM users WHERE email=?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $err = 'Cet email existe déjà.';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $db->prepare("INSERT INTO users (nom, prenom, email, password, role) VALUES (?,?,?,?,'teacher')")
               ->execute([$nom, $prenom, $email, $hash]);
            $msg = 'Enseignant ajouté.';
        }
    } else { $err = 'Tous les champs sont requis.'; }
}

//supprimer un enseignant
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) { $db->prepare("DELETE FROM users WHERE id=? AND role='teacher'")->execute([$id]); $msg = 'Enseignant supprimé.'; }
}

//recuperer tous les enseignants 
$teachers = $db->query(
  "SELECT u.*, (SELECT COUNT(*) FROM courses WHERE teacher_id=u.id) AS nb_courses
   FROM users u WHERE u.role='teacher' ORDER BY u.created_at DESC"
)->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LMS — Enseignants</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="shell">
  <?php include '../includes/sidebar_admin.php'; ?>
  <div class="main-content">
    <header class="topbar">
      <button class="menu-toggle">☰</button>
      <span class="topbar-title">Enseignants</span>
    </header>
    <main class="page-body">
      <?php if ($msg): ?><div class="alert alert-success">✓ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert alert-danger">⚠ <?= htmlspecialchars($err) ?></div><?php endif; ?>
      <div class="card">
        <div class="card-header">
          <h2>Enseignants (<?= count($teachers) ?>)</h2>
          <button class="btn btn-primary" onclick="openModal('modal-add')">➕ Ajouter</button>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Nom</th><th>Email</th><th>Cours</th><th>Inscrit le</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($teachers as $t): ?>
              <tr>
                <td><strong><?= htmlspecialchars($t['prenom'] . ' ' . $t['nom']) ?></strong></td>
                <td><?= htmlspecialchars($t['email']) ?></td>
                <td><span class="badge badge-green"><?= $t['nb_courses'] ?></span></td>
                <td style="font-size:.8rem;"><?= date('d/m/Y', strtotime($t['created_at'])) ?></td>
                <td>
                  <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cet enseignant ?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                    <button class="btn btn-danger btn-sm">🗑</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>

<div class="modal-overlay" id="modal-add">
  <div class="modal">
    <div class="modal-header"><h3>Nouvel enseignant</h3><button class="modal-close">✕</button></div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group"><label class="form-label">Prénom</label><input class="form-control" name="prenom" required></div>
          <div class="form-group"><label class="form-label">Nom</label><input class="form-control" name="nom" required></div>
        </div>
        <div class="form-group"><label class="form-label">Email</label><input class="form-control" type="email" name="email" required></div>
        <div class="form-group"><label class="form-label">Mot de passe</label><input class="form-control" type="password" name="password" required></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost modal-close">Annuler</button>
        <button type="submit" class="btn btn-primary">Ajouter</button>
      </div>
    </form>
  </div>
</div>

<script src="../assets/js/app.js"></script>
</body>
</html>