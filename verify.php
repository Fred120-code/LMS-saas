<?php
require_once 'includes/config.php';
$db = getDB();

$code = $_GET['code'] ?? '';
$cert = null;
$error = null;

if ($code) {
    $stmt = $db->prepare("
        SELECT c.*, m.titre AS module_titre, u.nom, u.prenom 
        FROM certificates c
        JOIN modules m ON c.module_id = m.id
        JOIN users u ON c.student_id = u.id
        WHERE c.verification_code = ?
    ");
    $stmt->execute([$code]);
    $cert = $stmt->fetch();
    if (!$cert) {
        $error = "Certificat introuvable ou code invalide.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification de Certificat</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700&family=Montserrat:wght@300;400;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/css/verify.css">
</head>

<body>
    <div class="verify-page">
        <div class="verify-box">
            <div class="verify-subtitle">Portail de vérification des diplômes</div>

            <?php if ($cert): ?>
                <!-- Valid Certificate -->
                <div class="verify-icon success">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <h2 style="color: var(--success); margin-bottom: 8px;">Certificat Authentique</h2>
                <p class="text-muted">Ce certificat est valide</p>

                <div class="cert-details">
                    <div class="detail-row">
                        <span class="detail-label">Décerné à</span>
                        <span class="detail-value"><?= htmlspecialchars($cert['prenom'] . ' ' . $cert['nom']) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Formation Complétée</span>
                        <span class="detail-value"><?= htmlspecialchars($cert['module_titre']) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Date de Délivrance</span>
                        <span class="detail-value"><?= date('d/m/Y', strtotime($cert['delivered_at'])) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">ID de Vérification</span>
                        <span class="detail-value"
                            style="font-family: monospace; color: var(--primary);"><?= htmlspecialchars($cert['verification_code']) ?></span>
                    </div>
                </div>

                <a href="verify.php" class="btn btn-ghost mt-4 w-full">Vérifier un autre certificat</a>

            <?php else: ?>
                <!-- Verification Form or Error -->
                <?php if ($error): ?>
                    <div class="verify-icon error">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                            stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="15" y1="9" x2="9" y2="15"></line>
                            <line x1="9" y1="9" x2="15" y2="15"></line>
                        </svg>
                    </div>
                    <h2 style="color: var(--danger); margin-bottom: 8px;">Certificat Invalide</h2>
                    <p class="text-muted mb-4"><?= htmlspecialchars($error) ?></p>
                <?php else: ?>
                    <div class="verify-icon" style="background: var(--primary-light); color: var(--primary);">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                            stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                    </div>
                    <h2 style="margin-bottom: 8px;">Vérifier un certificat</h2>
                    <p class="text-muted mb-4">Saisissez l'ID unique de vérification figurant sur le document.</p>
                <?php endif; ?>

                <form action="verify.php" method="GET">
                    <div class="form-group text-left" style="margin-bottom: 16px;">
                        <input type="text" name="code" class="form-control" placeholder="Ex: b3f8x2ac412"
                            value="<?= htmlspecialchars($code) ?>" required
                            style="text-align: center; font-size: 1.1rem; letter-spacing: 1px; font-family: monospace;">
                    </div>
                    <button type="submit" class="btn btn-primary w-full"
                        style="padding: 14px; font-size: 1rem;">Vérifier</button>
                </form>

                <?php if ($error): ?>
                    <a href="verify.php" class="btn btn-ghost mt-3 w-full">Réessayer</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>