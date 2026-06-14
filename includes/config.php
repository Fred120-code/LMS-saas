<?php
//  Configuration de la base de données (Support des variables d'environnement pour le déploiement)

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_NAME', getenv('DB_NAME') ?: 'lms_db');
define('BASE_URL', getenv('BASE_URL') ?: 'http://localhost/lms');
define('UPLOAD_PDF',   __DIR__ . '/../uploads/pdfs/');
define('UPLOAD_VIDEO', __DIR__ . '/../uploads/videos/');
define('UPLOAD_CERT',  __DIR__ . '/../uploads/certificates/');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Connexion impossible : ' . $e->getMessage()]));
        }
    }
    return $pdo;
}