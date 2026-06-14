<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireRole('admin');

$db = getDB();
$msg = $err = '';

//creer un module
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $titre = trim($_POST['titre'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    if ($titre) {
        $db->prepare("INSERT INTO modules (titre, description, created_by) VALUES (?,?,?)")
           ->execute([$titre, $desc, $_SESSION['user_id']]);
        $msg = 'Module créé avec succès.';
    } else { $err = 'Le titre est requis.'; }
}

// modifier un module
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $id    = (int)($_POST['id'] ?? 0);
    $titre = trim($_POST['titre'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    if ($id && $titre) {
        $db->prepare("UPDATE modules SET titre=?, description=? WHERE id=?")->execute([$titre, $desc, $id]);
        $msg = 'Module modifié.';
    }
}

//supprimer un module
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) { $db->prepare("DELETE FROM modules WHERE id=?")->execute([$id]); $msg = 'Module supprimé.'; }
}

//recuperer tous les modules
$modules = $db->query(
  "SELECT m.*, u.prenom, u.nom,
          (SELECT COUNT(*) FROM courses WHERE module_id=m.id) AS nb_courses
   FROM modules m JOIN users u ON u.id=m.created_by ORDER BY m.created_at DESC"
)->fetchAll();
?>


<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LMS — Modules</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="shell">
  <?php include '../includes/sidebar_admin.php'; ?>
  <div class="main-content">
    <header class="topbar">
      <button class="menu-toggle">☰</button>
      <span class="topbar-title">Gestion des modules</span>
    </header>
    <main class="page-body">
      <?php if ($msg): ?><div class="alert alert-success">✓ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert alert-danger">⚠ <?= htmlspecialchars($err) ?></div><?php endif; ?>

      <div class="card">
        <div class="card-header">
          <h2>Modules (<?= count($modules) ?>)</h2>
          <button class="btn btn-primary" onclick="openModal('modal-create')">➕ Nouveau module</button>
        </div>
        <div class="card-body" style="padding-top:12px;">
          <input class="form-control mb-2" type="search" id="search-mod" placeholder="Rechercher…" style="max-width:320px;">
        </div>
        <div class="table-wrap">
          <table id="mod-table">
            <thead><tr><th>#</th><th>Titre</th><th>Description</th><th>Cours</th><th>Créé par</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($modules as $m): ?>
              <tr>
                <td><?= $m['id'] ?></td>
                <td><strong><?= htmlspecialchars($m['titre']) ?></strong></td>
                <td style="font-size:.85rem;color:var(--text-muted);max-width:220px;"><?= htmlspecialchars(substr($m['description'], 0, 80)) ?>…</td>
                <td><span class="badge badge-blue"><?= $m['nb_courses'] ?></span></td>
                <td><?= htmlspecialchars($m['prenom'] . ' ' . $m['nom']) ?></td>
                <td style="font-size:.8rem;"><?= date('d/m/Y', strtotime($m['created_at'])) ?></td>
                <td>
                  <button class="btn btn-ghost btn-sm"
                          onclick="editModule(<?= $m['id'] ?>, '<?= htmlspecialchars(addslashes($m['titre'])) ?>', '<?= htmlspecialchars(addslashes($m['description'])) ?>')">✏</button>
                  <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ce module ?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $m['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm">🗑</button>
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

<!-- Modal Create -->
<div class="modal-overlay" id="modal-create">
  <div class="modal">
    <div class="modal-header">
      <h3>Nouveau module</h3>
      <button class="modal-close">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="create">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Titre *</label>
          <input class="form-control" name="titre" required placeholder="Ex : Développement Web">
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea class="form-control" name="description" rows="3" placeholder="Description du module…"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost modal-close">Annuler</button>
        <button type="submit" class="btn btn-primary">Créer</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Edit -->
<div class="modal-overlay" id="modal-edit">
  <div class="modal">
    <div class="modal-header">
      <h3>Modifier le module</h3>
      <button class="modal-close">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="edit-id">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Titre *</label>
          <input class="form-control" name="titre" id="edit-titre" required>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea class="form-control" name="description" id="edit-desc" rows="3"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost modal-close">Annuler</button>
        <button type="submit" class="btn btn-primary">Enregistrer</button>
      </div>
    </form>
  </div>
</div>

<script src="../assets/js/app.js"></script>
<script>
function editModule(id, titre, desc) {
  document.getElementById('edit-id').value    = id;
  document.getElementById('edit-titre').value = titre;
  document.getElementById('edit-desc').value  = desc;
  openModal('modal-edit');
}
tableSearch('search-mod', 'mod-table');
</script>
</body>
</html>