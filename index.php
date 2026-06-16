<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $role = $_SESSION['role'];
    if ($role === 'admin')   header('Location: ' . BASE_URL . '/admin/dashboard.php');
    elseif ($role === 'teacher') header('Location: ' . BASE_URL . '/teacher/dashboard.php');
    else                     header('Location: ' . BASE_URL . '/student/dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email && $password) {
        $db   = getDB();
        $stmt = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nom']     = $user['nom'];
            $_SESSION['prenom']  = $user['prenom'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];

            if ($user['role'] === 'admin')        header('Location: ' . BASE_URL . '/admin/dashboard.php');
            elseif ($user['role'] === 'teacher')  header('Location: ' . BASE_URL . '/teacher/dashboard.php');
            else                                  header('Location: ' . BASE_URL . '/student/dashboard.php');
            exit;
        } else {
            $error = 'Email ou mot de passe incorrect.';
        }
    } else {
        $error = 'Veuillez remplir tous les champs.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LMS — Connexion</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="login-page">
  <div class="login-box">

    <h2>Connexion</h2>
    <p>Entrez vos identifiants pour accéder à la plateforme.</p>

    <?php if ($error): ?>
      <div class="alert alert-danger">⚠ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
      <div class="alert alert-danger">⚠ Accès non autorisé.</div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label class="form-label" for="email">Adresse e-mail</label>
        <input class="form-control" type="email" id="email" name="email"
               placeholder="vous@exemple.com" required
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label" for="password">Mot de passe</label>
        <input class="form-control" type="password" id="password" name="password"
               placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn btn-primary w-full" style="justify-content:center;padding:12px;">
        Se connecter
      </button>
    </form>

    <div class="text-center mt-2" style="font-size:.85rem;">
      Pas encore de compte ? <a href="register.php" style="color:var(--primary-light);font-weight:600;text-decoration:underline;">S'inscrire</a>
    </div>

    <p class="text-muted text-center mt-3" style="font-size:.78rem;">
      Comptes de démo · Mot de passe : <strong>Password123!</strong><br>
      admin@lms.com · sophie@lms.com · julien@lms.com
    </p>
  </div>
</div>
</body>
</html>