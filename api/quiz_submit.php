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
$quiz_id   = isset($_POST['quiz_id']) ? (int)$_POST['quiz_id'] : null;
$lesson_id = isset($_POST['lesson_id']) ? (int)$_POST['lesson_id'] : null;
$raw_answers = isset($_POST['answers']) ? $_POST['answers'] : '';

if (!$quiz_id || !$lesson_id || !$raw_answers) {
    jsonResponse(['error' => 'Données de soumission invalides.'], 400);
}

$answers = json_decode($raw_answers, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    jsonResponse(['error' => 'Format de réponses invalide.'], 400);
}

$db = getDB();

try {
    // verifier que le quiz et la lecon existent et sont lies
    $stmt_quiz = $db->prepare("SELECT id, titre FROM quizzes WHERE id = ? AND lesson_id = ? LIMIT 1");
    $stmt_quiz->execute([$quiz_id, $lesson_id]);
    $quiz = $stmt_quiz->fetch();
    
    if (!$quiz) {
        jsonResponse(['error' => 'Quiz ou leçon introuvable.'], 404);
    }

    // recuperer toutes les questions pour ce quiz
    $stmt_questions = $db->prepare("SELECT id, bonne_reponse FROM quiz_questions WHERE quiz_id = ?");
    $stmt_questions->execute([$quiz_id]);
    $questions = $stmt_questions->fetchAll();

    if (empty($questions)) {
        jsonResponse(['error' => 'Ce quiz ne contient aucune question.'], 500);
    }

    // noter les reponses
    $score = 0;
    $total = 0;
    $corrections = [];

    foreach ($questions as $q) {
        $q_id = (int)$q['id'];
        $correct_ans = $q['bonne_reponse'];
        
        $corrections[$q_id] = $correct_ans;
        $total++;

        // recuperer la reponse de l'etudiant
        $student_ans = isset($answers[$q_id]) ? trim($answers[$q_id]) : '';

        if (strcasecmp($student_ans, $correct_ans) === 0) {
            $score++;
        }
    }

    $pourcentage = $total > 0 ? round(($score / $total) * 100, 2) : 0.00;
    
    // La note de passage est fixée à 50% ou plus
    $passed = $pourcentage >= 50.00 ? 1 : 0;

    $db->beginTransaction();

    // sauvegarder/mettre a jour le resultat du quiz dans la base de donnees
    $stmt_check_res = $db->prepare("SELECT id FROM results WHERE student_id = ? AND quiz_id = ? LIMIT 1");
    $stmt_check_res->execute([$student_id, $quiz_id]);
    $existing_result = $stmt_check_res->fetch();

    if ($existing_result) {
        $stmt_update_res = $db->prepare("
            UPDATE results 
            SET score = ?, total = ?, pourcentage = ?, passed = ? 
            WHERE student_id = ? AND quiz_id = ?
        ");
        $stmt_update_res->execute([$score, $total, $pourcentage, $passed, $student_id, $quiz_id]);
    } else {
        $stmt_insert_res = $db->prepare("
            INSERT INTO results (student_id, quiz_id, lesson_id, score, total, pourcentage, passed) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt_insert_res->execute([$student_id, $quiz_id, $lesson_id, $score, $total, $pourcentage, $passed]);
    }

    // marquer la lecon comme terminee
    $stmt_sl = $db->prepare("INSERT IGNORE INTO student_lessons (student_id, lesson_id) VALUES (?, ?)");
    $stmt_sl->execute([$student_id, $lesson_id]);

    // trouver l'identifiant du module
    $stmt_mod = $db->prepare("
        SELECT c.module_id 
        FROM lessons l 
        JOIN courses c ON c.id = l.course_id 
        WHERE l.id = ? 
        LIMIT 1
    ");
    $stmt_mod->execute([$lesson_id]);
    $module_info = $stmt_mod->fetch();
    $module_id = $module_info ? (int)$module_info['module_id'] : 0;

    $has_new_certificate = false;

    if ($module_id > 0) {
        // recalculer la progression
        $stmt_total_l = $db->prepare("
            SELECT COUNT(l.id) 
            FROM lessons l 
            JOIN courses c ON c.id = l.course_id 
            WHERE c.module_id = ?
        ");
        $stmt_total_l->execute([$module_id]);
        $total_lessons = (int)$stmt_total_l->fetchColumn();

        $stmt_done_l = $db->prepare("
            SELECT COUNT(sl.lesson_id) 
            FROM student_lessons sl 
            JOIN lessons l ON l.id = sl.lesson_id 
            JOIN courses c ON c.id = l.course_id 
            WHERE sl.student_id = ? AND c.module_id = ?
        ");
        $stmt_done_l->execute([$student_id, $module_id]);
        $completed_lessons = (int)$stmt_done_l->fetchColumn();

        $progression_pct = 0.00;
        if ($total_lessons > 0) {
            $progression_pct = round(($completed_lessons / $total_lessons) * 100, 2);
        }

        // mettre a jour la table progress
        $stmt_check_prog = $db->prepare("SELECT id FROM progress WHERE student_id = ? AND module_id = ? LIMIT 1");
        $stmt_check_prog->execute([$student_id, $module_id]);
        if ($stmt_check_prog->fetch()) {
            $db->prepare("
                UPDATE progress 
                SET lessons_done = ?, lessons_total = ?, pourcentage = ? 
                WHERE student_id = ? AND module_id = ?
            ")->execute([$completed_lessons, $total_lessons, $progression_pct, $student_id, $module_id]);
        } else {
            $db->prepare("
                INSERT INTO progress (student_id, module_id, lessons_done, lessons_total, pourcentage) 
                VALUES (?, ?, ?, ?, ?)
            ")->execute([$student_id, $module_id, $completed_lessons, $total_lessons, $progression_pct]);
        }

        // verifier si l'etudiant a termine le module pour decerner le certificat
        if ($progression_pct >= 100.00) {
            $stmt_cert_check = $db->prepare("SELECT id FROM certificates WHERE student_id = ? AND module_id = ? LIMIT 1");
            $stmt_cert_check->execute([$student_id, $module_id]);
            
            if (!$stmt_cert_check->fetch()) {
                // generer une nouvelle entree de certificat
                $stmt_cert_gen = $db->prepare("
                    INSERT INTO certificates (student_id, module_id, fichier) 
                    VALUES (?, ?, NULL)
                ");
                $stmt_cert_gen->execute([$student_id, $module_id]);
                $has_new_certificate = true;
            }
        }
    }

    $db->commit();

    // Determine certificate status to return
    $has_any_certificate = false;
    if ($module_id > 0) {
        $stmt_c = $db->prepare("SELECT id FROM certificates WHERE student_id = ? AND module_id = ? LIMIT 1");
        $stmt_c->execute([$student_id, $module_id]);
        $has_any_certificate = (bool)$stmt_c->fetch();
    }

    jsonResponse([
        'success'         => true,
        'score'           => $score,
        'total'           => $total,
        'pourcentage'     => $pourcentage,
        'passed'          => (bool)$passed,
        'corrections'     => $corrections,
        'module_id'       => $module_id,
        'certificate'     => $has_any_certificate,
        'certificate_url' => 'certificates.php'
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    jsonResponse(['error' => 'Erreur serveur : ' . $e->getMessage()], 500);
}
