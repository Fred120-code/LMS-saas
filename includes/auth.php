<?php
if (session_status() === PHP_SESSION_NONE) session_start();

//fonction pour vérifier si l'utilisateur est connecté
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

//fonction pour rediriger l'utilisateur vers la page de connexion s'il n'est pas connecté
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'index.php');
        exit;
    }
}

//fonction pour vérifier si l'utilisateur a le bon role
function requireRole(string $role): void {
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        header('Location: ' . BASE_URL . '/index.php?error=access');
        exit;
    }
}

//fonction pour récupérer les informations de l'utilisateur connecté
function currentUser(): array {
    return [
        'id'     => $_SESSION['user_id']  ?? null,
        'nom'    => $_SESSION['nom']      ?? '',
        'prenom' => $_SESSION['prenom']   ?? '',
        'email'  => $_SESSION['email']    ?? '',
        'role'   => $_SESSION['role']     ?? '',
    ];
}

//fonction pour renvoyer une réponse JSON
function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

//fonction pour nettoyer une chaîne de caractères
function sanitize(string $str): string {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}