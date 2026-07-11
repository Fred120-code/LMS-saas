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
    <style>
      :root {
        --cert-bg: #ffffff;
        --cert-primary: #0A192F;
        /* Deep navy blue */
        --cert-gold: #C5A059;
        /* Elegant gold */
        --cert-text: #333333;
        --cert-muted: #666666;
        --cert-light: #F8F9FA;
      }

      * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
      }

      body {
        background-color: #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        padding: 40px 20px;
        font-family: 'Montserrat', sans-serif;
        color: var(--cert-text);
      }

      .print-btn-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1000;
      }

      .btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 24px;
        border-radius: 6px;
        font-size: 0.9rem;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
        font-family: 'Montserrat', sans-serif;
        text-decoration: none;
      }

      .btn-primary {
        background: var(--cert-primary);
        color: #fff;
        box-shadow: 0 4px 6px rgba(10, 25, 47, 0.2);
      }

      .btn-primary:hover {
        background: #0d213f;
        transform: translateY(-1px);
      }

      .btn-ghost {
        background: #fff;
        color: var(--cert-primary);
        border: 1px solid var(--cert-primary);
      }

      .btn-ghost:hover {
        background: var(--cert-light);
      }

      /* Certificate Layout */
      .cert-wrapper {
        background: var(--cert-bg);
        width: 100%;
        max-width: 1100px;
        aspect-ratio: 1.414 / 1;
        /* A4 landscape ratio */
        margin: 0 auto;
        position: relative;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        padding: 40px;
        overflow: hidden;
        border-radius: 4px;
      }

      /* Subtle watermark */
      .cert-wrapper::before {
        content: 'LMS ACADEMY';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(-30deg);
        font-family: 'Cinzel', serif;
        font-size: 120px;
        color: rgba(0, 0, 0, 0.02);
        white-space: nowrap;
        pointer-events: none;
        z-index: 0;
      }

      /* Inner borders */
      .cert-border-outer {
        position: relative;
        width: 100%;
        height: 100%;
        border: 2px solid var(--cert-gold);
        padding: 12px;
        z-index: 1;
      }

      .cert-border-inner {
        position: relative;
        width: 100%;
        height: 100%;
        border: 1px solid rgba(197, 160, 89, 0.5);
        padding: 48px 60px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        background: linear-gradient(135deg, rgba(255, 255, 255, 1) 0%, rgba(248, 249, 250, 0.4) 100%);
      }

      /* Corner decorations */
      .corner {
        position: absolute;
        width: 40px;
        height: 40px;
        border: 2px solid var(--cert-gold);
        z-index: 2;
      }

      .corner-tl {
        top: -2px;
        left: -2px;
        border-right: none;
        border-bottom: none;
      }

      .corner-tr {
        top: -2px;
        right: -2px;
        border-left: none;
        border-bottom: none;
      }

      .corner-bl {
        bottom: -2px;
        left: -2px;
        border-right: none;
        border-top: none;
      }

      .corner-br {
        bottom: -2px;
        right: -2px;
        border-left: none;
        border-top: none;
      }

      /* Header */
      .cert-header {
        text-align: center;
        margin-bottom: 20px;
      }

      .cert-logo {
        font-family: 'Cinzel', serif;
        font-size: 24px;
        color: var(--cert-primary);
        letter-spacing: 4px;
        text-transform: uppercase;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
      }

      .cert-logo::before,
      .cert-logo::after {
        content: '';
        display: block;
        width: 40px;
        height: 1px;
        background-color: var(--cert-gold);
      }

      /* Body */
      .cert-body {
        text-align: center;
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
      }

      .cert-title {
        font-family: 'Cinzel', serif;
        font-size: 48px;
        color: var(--cert-primary);
        margin-bottom: 24px;
        text-transform: uppercase;
        letter-spacing: 6px;
        font-weight: 700;
      }

      .cert-subtitle {
        font-size: 14px;
        color: var(--cert-muted);
        letter-spacing: 3px;
        text-transform: uppercase;
        margin-bottom: 30px;
      }

      .cert-name {
        font-family: 'Pinyon Script', cursive;
        font-size: 72px;
        color: var(--cert-primary);
        line-height: 1;
        margin-bottom: 30px;
        position: relative;
        display: inline-block;
        padding: 0 40px;
      }

      .cert-text {
        font-size: 14px;
        color: var(--cert-muted);
        letter-spacing: 2px;
        text-transform: uppercase;
        margin-bottom: 20px;
      }

      .cert-module {
        font-family: 'Cinzel', serif;
        font-size: 28px;
        font-weight: 700;
        color: var(--cert-gold);
        margin-bottom: 0;
        letter-spacing: 2px;
      }

      /* Footer */
      .cert-footer {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-top: 40px;
        padding: 0 40px;
      }

      .signature-block {
        text-align: center;
        width: 220px;
      }

      .signature-img {
        font-family: 'Pinyon Script', cursive;
        font-size: 32px;
        color: var(--cert-primary);
        margin-bottom: 5px;
        opacity: 0.8;
        height: 40px;
        display: flex;
        align-items: flex-end;
        justify-content: center;
      }

      .signature-line {
        border-bottom: 1px solid var(--cert-primary);
        height: 1px;
        width: 100%;
        margin-bottom: 8px;
      }

      .signature-label {
        font-size: 11px;
        color: var(--cert-muted);
        text-transform: uppercase;
        letter-spacing: 2px;
        font-weight: 600;
      }

      .date-value {
        font-family: 'Cinzel', serif;
        font-size: 18px;
        color: var(--cert-primary);
        font-weight: 600;
        margin-bottom: 10px;
        height: 35px;
        display: flex;
        align-items: flex-end;
        justify-content: center;
      }

      /* Seal / Badge */
      .cert-seal {
        position: relative;
        width: 130px;
        height: 130px;
        background: linear-gradient(135deg, #DFBD69, #926F34);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 10px 25px rgba(197, 160, 89, 0.4);
        z-index: 10;
      }

      .cert-seal::before {
        content: '';
        position: absolute;
        inset: 6px;
        border: 2px dashed rgba(255, 255, 255, 0.6);
        border-radius: 50%;
      }

      .cert-seal::after {
        content: '';
        position: absolute;
        inset: 12px;
        border: 1px solid rgba(255, 255, 255, 0.4);
        border-radius: 50%;
      }

      .seal-content {
        text-align: center;
        color: #fff;
        position: relative;
        z-index: 2;
      }

      .seal-text {
        display: block;
        font-family: 'Cinzel', serif;
        font-size: 14px;
        font-weight: 700;
        letter-spacing: 3px;
        margin-bottom: 2px;
      }

      .seal-year {
        display: block;
        font-size: 12px;
        letter-spacing: 2px;
        opacity: 0.9;
      }

      .seal-icon {
        font-size: 20px;
        margin-bottom: 4px;
      }

      /* Meta info */
      .cert-meta {
        position: absolute;
        bottom: 20px;
        left: 0;
        width: 100%;
        text-align: center;
        font-size: 10px;
        color: #9ca3af;
        font-family: monospace;
        letter-spacing: 1px;
      }

      @media print {
        @page {
          size: A4 landscape;
          margin: 0;
        }

        body {
          background: #fff;
          padding: 0;
          margin: 0;
          -webkit-print-color-adjust: exact !important;
          print-color-adjust: exact !important;
        }

        .print-btn-container {
          display: none;
        }

        .cert-wrapper {
          box-shadow: none;
          border-radius: 0;
          width: 100vw;
          height: 100vh;
          max-width: none;
          aspect-ratio: auto;
          padding: 12mm;
          /* Give print margins */
        }

        .cert-seal {
          box-shadow: none;
        }
      }

      @media (max-width: 900px) {
        .cert-wrapper {
          aspect-ratio: auto;
          padding: 20px;
          min-height: 800px;
        }

        .cert-border-inner {
          padding: 30px 20px;
        }

        .cert-title {
          font-size: 32px;
          letter-spacing: 4px;
        }

        .cert-name {
          font-size: 48px;
        }

        .cert-module {
          font-size: 20px;
        }

        .cert-footer {
          flex-direction: column;
          align-items: center;
          gap: 40px;
        }

        .cert-seal {
          order: -1;
          margin-bottom: 20px;
        }
      }
    </style>
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