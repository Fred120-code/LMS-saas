<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

//role etudiant
requireRole('student');

$db = getDB();
$user = currentUser();
$student_id = $user['id'];

// si print_id est defini, afficher le certificat
if (isset($_GET['print_id'])) {
    // recuperer le certificat
    $cert_id = (int)$_GET['print_id'];
    $stmt = $db->prepare("
        SELECT c.*, m.titre AS module_titre 
        FROM certificates c 
        JOIN modules m ON m.id = c.module_id 
        WHERE c.id = ? AND c.student_id = ? 
        LIMIT 1
    ");
    $stmt->execute([$cert_id, $student_id]);
    $certificate = $stmt->fetch();
    
    if (!$certificate) {
        // si le certificat n'est pas trouvé ou non autorisé  
        die("Certificat introuvable ou non autorisé.");
    }
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>LMS — Certificat #<?= $certificate['id'] ?></title>
      <link rel="stylesheet" href="../assets/css/style.css">
      <style>
        body {
          background: #f0f0f5;
          display: flex;
          align-items: center;
          justify-content: center;
          min-height: 100vh;
          padding: 20px;
        }
        .print-btn-container {
          position: fixed;
          top: 20px;
          right: 20px;
          z-index: 1000;
        }
        @media print {
          body {
            background: #fff;
            padding: 0;
            margin: 0;
          }
          .print-btn-container {
            display: none;
          }
          .cert-card {
            box-shadow: none;
            border: 2px solid var(--accent);
            margin: 0;
            width: 100%;
            max-width: 100%;
          }
        }
      </style>
    </head>
    <body>
      <div class="print-btn-container">
        <button onclick="window.print()" class="btn btn-primary">🖨 Imprimer / Enregistrer en PDF</button>
        <button onclick="window.close()" class="btn btn-ghost">Fermer</button>
      </div>

      <div class="cert-card">
        <div class="cert-stamp">🎓</div>
        <div class="cert-title">Certificat de Réussite</div>
        <div class="cert-sub">Ce document atteste que</div>
        <div class="cert-name" style="font-size: 2.2rem; margin: 16px 0;"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></div>
        <div class="cert-sub">a complété avec succès et à 100% le module de formation</div>
        <div class="cert-module" style="font-size: 1.5rem; font-weight: 700; color: var(--primary); margin: 20px 0; font-family:'Playfair Display', serif;">
          <?= htmlspecialchars($certificate['module_titre']) ?>
        </div>
        <div class="cert-sub" style="margin-top: 32px;">Délivré avec succès le <?= date('d/m/Y', strtotime($certificate['delivered_at'])) ?></div>
        <p class="text-muted" style="font-size: .75rem; margin-top: 48px; border-top: 1px solid var(--border); padding-top: 12px;">
          Code de vérification : LMS-CERT-<?= str_pad($certificate['id'], 6, '0', STR_PAD_LEFT) ?> · EduLearn LMS
        </p>
      </div>
    </body>
    </html>
    <?php
    exit;
}

// sinon afficher la liste des certificats
$stmt = $db->prepare("      
    SELECT c.*, m.titre AS module_titre 
    FROM certificates c 
    JOIN modules m ON m.id = c.module_id 
    WHERE c.student_id = ? 
    ORDER BY c.delivered_at DESC
");
$stmt->execute([$student_id]);
$certificates = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LMS — Mes Certificats</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="shell">
  <?php include '../includes/sidebar_student.php'; ?>
  <div class="main-content">
    <header class="topbar">
      <button class="menu-toggle">☰</button>
      <span class="topbar-title">Mes Certificats</span>
    </header>

    <main class="page-body">
      <h2>Mes Certificats de Réussite</h2>
      <p class="text-muted mb-3">Obtenez un certificat de réussite pour chaque module de formation complété à 100%.</p>

      <div class="card">
        <div class="card-header">
          <h3>Certificats obtenus (<?= count($certificates) ?>)</h3>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Module de formation</th>
                <th>Obtenu le</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($certificates)): ?>
                <tr>
                  <td colspan="4" class="text-center text-muted" style="padding:48px;">
                    Vous n'avez pas encore obtenu de certificat. Terminez des modules pour en générer un.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($certificates as $index => $c): ?>
                  <tr>
                    <td><?= $index + 1 ?></td>
                    <td><strong><?= htmlspecialchars($c['module_titre']) ?></strong></td>
                    <td class="text-muted"><?= date('d/m/Y', strtotime($c['delivered_at'])) ?></td>
                    <td>
                      <a href="certificates.php?print_id=<?= $c['id'] ?>" target="_blank" class="btn btn-accent btn-sm">
                        📜 Voir / Imprimer
                      </a>
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
</body>
</html>
