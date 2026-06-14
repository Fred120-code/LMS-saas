<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// verifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    jsonResponse(['error' => 'Non autorisé. Veuillez vous connecter.'], 401);
}

$user = currentUser();
$student_id = $user['id'];

// recuperer les donnees 
$lesson_id = isset($_POST['lesson_id']) ? (int)$_POST['lesson_id'] : null;
$module_id = isset($_POST['module_id']) ? (int)$_POST['module_id'] : null;

$db = getDB();

try {
    // Si lesson_id est fourni, enregistre le comme terminé
    if ($lesson_id) {
        // trouver le module_id pour cette leçon si non fourni
        $stmt = $db->prepare("
            SELECT c.module_id 
            FROM lessons l 
            JOIN courses c ON c.id = l.course_id 
            WHERE l.id = ? 
            LIMIT 1
        ");
        $stmt->execute([$lesson_id]);
        $lesson_info = $stmt->fetch();
        
        if ($lesson_info) {
            $module_id = (int)$lesson_info['module_id'];
            
            // marquer la lecon comme terminée par cet etudiant
            $insert = $db->prepare("
                INSERT IGNORE INTO student_lessons (student_id, lesson_id) 
                VALUES (?, ?)
            ");
            $insert->execute([$student_id, $lesson_id]);
        } else {
            jsonResponse(['error' => 'Leçon introuvable.'], 404);
        }
    }

    if (!$module_id) {
        jsonResponse(['error' => 'Identifiant du module manquant.'], 400);
    }

    // compter le nombre total de lecon dans le module
    $stmt_total = $db->prepare("
        SELECT COUNT(l.id) 
        FROM lessons l 
        JOIN courses c ON c.id = l.course_id 
        WHERE c.module_id = ?
    ");
    $stmt_total->execute([$module_id]);
    $total_lessons = (int)$stmt_total->fetchColumn();

    // compter les lecon termine par cet etudiant
    $stmt_done = $db->prepare("
        SELECT COUNT(sl.lesson_id) 
        FROM student_lessons sl 
        JOIN lessons l ON l.id = sl.lesson_id 
        JOIN courses c ON c.id = l.course_id 
        WHERE sl.student_id = ? AND c.module_id = ?
    ");
    $stmt_done->execute([$student_id, $module_id]);
    $completed_lessons = (int)$stmt_done->fetchColumn();

    // calculer le pourcentage
    $pourcentage = 0.00;
    if ($total_lessons > 0) {
        $pourcentage = round(($completed_lessons / $total_lessons) * 100, 2);
    }

    // mettre a jour ou inserer dans la table progress
    $stmt_check = $db->prepare("
        SELECT id FROM progress 
        WHERE student_id = ? AND module_id = ? 
        LIMIT 1
    ");
    $stmt_check->execute([$student_id, $module_id]);
    $progress_row = $stmt_check->fetch();

    if ($progress_row) {
        $stmt_update = $db->prepare("
            UPDATE progress 
            SET lessons_done = ?, lessons_total = ?, pourcentage = ? 
            WHERE student_id = ? AND module_id = ?
        ");
        $stmt_update->execute([$completed_lessons, $total_lessons, $pourcentage, $student_id, $module_id]);
    } else {
        $stmt_insert = $db->prepare("
            INSERT INTO progress (student_id, module_id, lessons_done, lessons_total, pourcentage) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt_insert->execute([$student_id, $module_id, $completed_lessons, $total_lessons, $pourcentage]);
    }

    //retourner le nouveau pourcentage
    jsonResponse([
        'success' => true,
        'pourcentage' => $pourcentage,
        'lessons_done' => $completed_lessons,
        'lessons_total' => $total_lessons
    ]);

} catch (Exception $e) {
    jsonResponse(['error' => 'Erreur serveur : ' . $e->getMessage()], 500);
}
