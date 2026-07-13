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
  $cert_id = (int) $_GET['print_id'];
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
    <link
      href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700&family=Montserrat:wght@300;400;600&family=Pinyon+Script&display=swap"
      rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/certificate.css">
  </head>

  <body>
    <div class="print-btn-container">
      <button onclick="window.print()" class="btn btn-primary">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
          stroke-linecap="round" stroke-linejoin="round">
          <polyline points="6 9 6 2 18 2 18 9"></polyline>
          <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
          <rect x="6" y="14" width="12" height="8"></rect>
        </svg>
        Imprimer / PDF
      </button>
      <button onclick="window.close()" class="btn btn-ghost">Fermer</button>
    </div>

    <div class="cert-wrapper">
      <div class="cert-border-outer">
        <div class="corner corner-tl"></div>
        <div class="corner corner-tr"></div>
        <div class="corner corner-bl"></div>
        <div class="corner corner-br"></div>

        <div class="cert-border-inner">
          <div class="cert-header">
            <div class="cert-logo">LMS Academy</div>
          </div>

          <div class="cert-body">
            <div class="cert-title">Certificat de Réussite</div>
            <div class="cert-subtitle">Ce document atteste que</div>

            <div class="cert-name"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></div>

            <div class="cert-text">a complété avec succès le module de formation</div>
            <div class="cert-module"><?= htmlspecialchars($certificate['module_titre']) ?></div>
          </div>

          <div class="cert-footer">
            <div class="signature-block">
              <div class="signature-img">DR messi</div>
              <div class="signature-line"></div>
              <div class="signature-label">Directeur de Formation</div>
            </div>


            <div class="signature-block">
              <div class="date-value"><?= date('d/m/Y', strtotime($certificate['delivered_at'])) ?></div>
              <div class="signature-line"></div>
              <div class="signature-label">Date de délivrance</div>
            </div>
          </div>

          <div class="cert-meta" style="display:flex; justify-content:center; align-items:flex-end; gap: 16px;">
            <?php
            $verify_url = "http://" . $_SERVER['HTTP_HOST'] . "/lms/verify.php?code=" . urlencode($certificate['verification_code']);
            $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=" . urlencode($verify_url);
            ?>
            <div style="text-align: right;">
              <div
                style="font-size: 9px; color: #9ca3af; font-family: monospace; letter-spacing: 1px; margin-bottom: 4px;">
                SCANNEZ POUR VÉRIFIER</div>
              <div style="font-size: 11px; color: var(--cert-primary); font-family: monospace; letter-spacing: 1px;">ID:
                <?= htmlspecialchars($certificate['verification_code']) ?></div>
            </div>
            <img src="<?= $qr_url ?>" alt="QR Code" width="50" height="50"
              style="border: 2px solid var(--cert-gold); padding: 2px; border-radius: 4px; background: #fff;">
          </div>
        </div>
      </div>
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
        <p class="text-muted mb-3">Obtenez un certificat de réussite pour chaque module de formation complété à 100%.
        </p>

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
                          Voir / Imprimer
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