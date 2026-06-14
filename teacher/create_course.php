<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

//role de l'enseignant
requireRole('teacher');

$db = getDB();
$user = currentUser();
$teacher_id = $user['id'];

$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre     = trim($_POST['titre'] ?? '');
    $desc      = trim($_POST['description'] ?? '');
    $module_id = (int)($_POST['module_id'] ?? 0);
    
    if ($titre && $module_id) {
        try {
            //verify que le module existe
            $stmt_mod = $db->prepare("SELECT id FROM modules WHERE id = ? LIMIT 1");
            $stmt_mod->execute([$module_id]);
            
            if ($stmt_mod->fetch()) {
                $stmt_ins = $db->prepare("
                    INSERT INTO courses (module_id, teacher_id, titre, description) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt_ins->execute([$module_id, $teacher_id, $titre, $desc]);
                
                header('Location: courses.php?msg=' . urlencode('Cours créé avec succès.'));
                exit;
            } else {
                $err = 'Module de formation non valide.';
            }
        } catch (Exception $e) {
            $err = 'Erreur lors de la création du cours : ' . $e->getMessage();
        }
    } else {
        $err = 'Le titre du cours et le module sont requis.';
    }
}

  // recuperer tout les modules disponible pour le menu deroulant
$modules = $db->query("SELECT id, titre FROM modules ORDER BY titre ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LMS — Nouveau Cours</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="shell">
  <?php include '../includes/sidebar_teacher.php'; ?>
  <div class="main-content">
    <header class="topbar">
      <button class="menu-toggle">☰</button>
      <span class="topbar-title">Nouveau Cours</span>
      <div class="topbar-right">
        <a href="courses.php" class="btn btn-ghost btn-sm">➔ Retour aux cours</a>
      </div>
    </header>

    <main class="page-body" style="max-width: 680px; margin: 0 auto;">
      <div class="card">
        <div class="card-header">
          <h2>Créer un nouveau cours</h2>
        </div>
        <div class="card-body">
          <?php if ($err): ?><div class="alert alert-danger">⚠ <?= htmlspecialchars($err) ?></div><?php endif; ?>

          <form method="POST">
            <div class="form-group">
              <label class="form-label" for="titre">Titre du cours *</label>
              <input class="form-control" type="text" id="titre" name="titre" required 
                     placeholder="Ex : Structure fondamentale d'un document HTML">
            </div>

            <div class="form-group">
              <label class="form-label" for="module_id">Module de rattachement *</label>
              <select class="form-control" id="module_id" name="module_id" required>
                <option value="">Sélectionner un module</option>
                <?php foreach ($modules as $m): ?>
                  <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['titre']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label" for="description">Description ou objectifs du cours</label>
              <textarea class="form-control" id="description" name="description" rows="5" 
                        placeholder="Qu'est-ce que les étudiants vont apprendre dans ce cours ?..."></textarea>
            </div>

            <div style="display:flex; gap:12px; justify-content:flex-end; margin-top:32px;">
              <a href="courses.php" class="btn btn-ghost">Annuler</a>
              <button type="submit" class="btn btn-primary">Créer le cours</button>
            </div>
          </form>
        </div>
      </div>
    </main>
  </div>
</div>

<script src="../assets/js/app.js"></script>
</body>
</html>
