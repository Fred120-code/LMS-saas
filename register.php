<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Redirection si déjà connecté
if (isLoggedIn()) {
    $role = $_SESSION['role'];
    if ($role === 'admin')   header('Location: ' . BASE_URL . '/admin/dashboard.php');
    elseif ($role === 'teacher') header('Location: ' . BASE_URL . '/teacher/dashboard.php');
    else                     header('Location: ' . BASE_URL . '/student/dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom              = trim($_POST['nom'] ?? '');
    $prenom           = trim($_POST['prenom'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $role             = trim($_POST['role'] ?? 'student');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($nom && $prenom && $email && $role && $password && $confirm_password) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "L'adresse e-mail n'est pas valide.";
        } elseif ($password !== $confirm_password) {
            $error = "Les mots de passe ne correspondent pas.";
        } elseif (strlen($password) < 6) {
            $error = "Le mot de passe doit contenir au moins 6 caractères.";
        } elseif (!in_array($role, ['student', 'teacher'])) {
            $error = "Rôle invalide sélectionné.";
        } else {
            $db = getDB();
            // Vérifier si l'email existe déjà
            $stmt = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "Cette adresse e-mail est déjà utilisée.";
            } else {
                // Insérer le nouvel utilisateur
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $insert = $db->prepare('INSERT INTO users (nom, prenom, email, password, role) VALUES (?, ?, ?, ?, ?)');
                if ($insert->execute([$nom, $prenom, $email, $hashed_password, $role])) {
                    // Récupérer l'ID généré
                    $userId = $db->lastInsertId();

                    // Connecter l'utilisateur automatiquement
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['nom']     = $nom;
                    $_SESSION['prenom']  = $prenom;
                    $_SESSION['email']   = $email;
                    $_SESSION['role']    = $role;

                    // Redirection selon le rôle
                    if ($role === 'teacher') {
                        header('Location: ' . BASE_URL . '/teacher/dashboard.php');
                    } else {
                        header('Location: ' . BASE_URL . '/student/dashboard.php');
                    }
                    exit;
                } else {
                    $error = "Une erreur est survenue lors de la création du compte. Veuillez réessayer.";
                }
            }
        }
    } else {
        $error = "Veuillez remplir tous les champs.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LMS — Inscription</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="login-page">
  <div class="login-box" style="max-width: 500px;">
    <h2>Inscription</h2>
    <p>Créez votre compte pour accéder à la plateforme.</p>

    <?php if ($error): ?>
      <div class="alert alert-danger">⚠ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="prenom">Prénom</label>
          <input class="form-control" type="text" id="prenom" name="prenom" required
                 value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label" for="nom">Nom</label>
          <input class="form-control" type="text" id="nom" name="nom" required
                 value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="email">Adresse e-mail</label>
        <input class="form-control" type="email" id="email" name="email" required
               placeholder="vous@exemple.com"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label class="form-label" for="role">Je suis un...</label>
        <select class="form-control" id="role" name="role" required>
          <option value="student" <?= (isset($_POST['role']) && $_POST['role'] === 'student') ? 'selected' : '' ?>>Étudiant</option>
          <option value="teacher" <?= (isset($_POST['role']) && $_POST['role'] === 'teacher') ? 'selected' : '' ?>>Enseignant</option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Mot de passe</label>
        <input class="form-control" type="password" id="password" name="password" required
               placeholder="Min. 6 caractères">
      </div>

      <div class="form-group">
        <label class="form-label" for="confirm_password">Confirmer le mot de passe</label>
        <input class="form-control" type="password" id="confirm_password" name="confirm_password" required
               placeholder="••••••••">
      </div>

      <button type="submit" class="btn btn-primary w-full" style="justify-content:center;padding:12px;">
        S'inscrire
      </button>
    </form>

    <div class="text-center mt-2" style="font-size:.85rem;">
      Déjà inscrit ? <a href="index.php" style="color:var(--primary-light);font-weight:600;text-decoration:underline;">Se connecter</a>
    </div>
  </div>
</div>
</body>
</html>
