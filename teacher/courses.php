<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// role de l'enseignant
requireRole('teacher');

$db = getDB();
$user = currentUser();
$teacher_id = $user['id'];

$msg = $err = '';

// Enregistrer les actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // 1. Supprimer un cours
    if ($action === 'delete_course') {
        $course_id = (int)($_POST['course_id'] ?? 0);
        if ($course_id) {
            $stmt = $db->prepare("DELETE FROM courses WHERE id = ? AND teacher_id = ?");
            $stmt->execute([$course_id, $teacher_id]);
            if ($stmt->rowCount() > 0) {
                $msg = 'Cours supprimé avec succès.';
            } else {
                $err = 'Impossible de supprimer ce cours.';
            }
        }
    }
    
    //ajouter une lecon
    elseif ($action === 'add_lesson') {
        $course_id = (int)($_POST['course_id'] ?? 0);
        $titre = trim($_POST['titre'] ?? '');
        $type = $_POST['type'] ?? '';
        $ordre = (int)($_POST['ordre'] ?? 1);
        
        // verifier que le cour appartient a l'enseignant
        $stmt_c = $db->prepare("SELECT id FROM courses WHERE id = ? AND teacher_id = ? LIMIT 1");
        $stmt_c->execute([$course_id, $teacher_id]);
        
        if ($stmt_c->fetch()) {
            if ($titre && ($type === 'pdf' || $type === 'video') && isset($_FILES['file'])) {
                $file = $_FILES['file'];
                
                if ($file['error'] === UPLOAD_ERR_OK) {
                    $filename = time() . '_' . basename($file['name']);
                    $dest_dir = ($type === 'pdf') ? UPLOAD_PDF : UPLOAD_VIDEO;
                    
                    //cree le dossier si il n'existe pas
                    if (!is_dir($dest_dir)) {
                        mkdir($dest_dir, 0777, true);
                    }
                    
                    if (move_uploaded_file($file['tmp_name'], $dest_dir . $filename)) {
                        $stmt_ins = $db->prepare("
                            INSERT INTO lessons (course_id, titre, type, fichier, ordre) 
                            VALUES (?, ?, ?, ?, ?)
                        ");

                        $stmt_ins->execute([$course_id, $titre, $type, $filename, $ordre]);
                        $msg = 'Leçon ajoutée avec succès.';
                    } else {
                        $err = "Erreur lors du déplacement du fichier vers le dossier cible ($dest_dir).";
                    }
                } else {
                    $err = 'Erreur lors du téléchargement du fichier (Code: ' . $file['error'] . ').';
                }
            } else {
                $err = 'Tous les champs et le fichier sont requis.';
            }
        } else {
            $err = 'Cours non valide.';
        }
    }
    
    //supprimer une lecon
    elseif ($action === 'delete_lesson') {
        $lesson_id = (int)($_POST['lesson_id'] ?? 0);
        if ($lesson_id) {
            $stmt = $db->prepare("
                DELETE l FROM lessons l 
                JOIN courses c ON c.id = l.course_id 
                WHERE l.id = ? AND c.teacher_id = ?
            ");
            $stmt->execute([$lesson_id, $teacher_id]);
            if ($stmt->rowCount() > 0) {
                $msg = 'Leçon supprimée avec succès.';
            } else {
                $err = 'Erreur ou non autorisé à supprimer cette leçon.';
            }
        }
    }
    
    //ajouter un quiz
    elseif ($action === 'add_quiz') {
        $lesson_id = (int)($_POST['lesson_id'] ?? 0);
        $titre = trim($_POST['titre'] ?? '');
        
        // verifier que le cour appartient a l'enseignant
        $stmt_l = $db->prepare("
            SELECT l.id FROM lessons l 
            JOIN courses c ON c.id = l.course_id 
            WHERE l.id = ? AND c.teacher_id = ? 
            LIMIT 1
        ");
        $stmt_l->execute([$lesson_id, $teacher_id]);
        
        if ($stmt_l->fetch()) {
            if ($titre) {
                $stmt_q_ins = $db->prepare("INSERT INTO quizzes (lesson_id, titre) VALUES (?, ?)");
                $stmt_q_ins->execute([$lesson_id, $titre]);
                $msg = 'Quiz créé avec succès. Vous pouvez maintenant ajouter des questions.';
            } else {
                $err = 'Le titre du quiz est requis.';
            }
        } else {
            $err = 'Leçon non valide.';
        }
    }
    
    //ajouter une question
    elseif ($action === 'add_question') {
        $quiz_id = (int)($_POST['quiz_id'] ?? 0);
        $question = trim($_POST['question'] ?? '');
        $opt_a = trim($_POST['option_a'] ?? '');
        $opt_b = trim($_POST['option_b'] ?? '');
        $opt_c = trim($_POST['option_c'] ?? '');
        $opt_d = trim($_POST['option_d'] ?? '');
        $correct = $_POST['bonne_reponse'] ?? '';
        
        //   verifier que le quiz appartient a l'enseignant
        $stmt_q = $db->prepare("
            SELECT q.id FROM quizzes q 
            JOIN lessons l ON l.id = q.lesson_id 
            JOIN courses c ON c.id = l.course_id 
            WHERE q.id = ? AND c.teacher_id = ? 
            LIMIT 1
        ");
        $stmt_q->execute([$quiz_id, $teacher_id]);
        
        if ($stmt_q->fetch()) {
            if ($question && $opt_a && $opt_b && $opt_c && $opt_d && in_array($correct, ['A', 'B', 'C', 'D'])) {
                $stmt_quest = $db->prepare("
                    INSERT INTO quiz_questions (quiz_id, question, option_a, option_b, option_c, option_d, bonne_reponse) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt_quest->execute([$quiz_id, $question, $opt_a, $opt_b, $opt_c, $opt_d, $correct]);
                $msg = 'Question ajoutée au quiz.';
            } else {
                $err = 'Tous les champs de la question sont requis et la bonne réponse doit être valide.';
            }
        } else {
            $err = 'Quiz non valide.';
        }
    }
}

// recuperer tout les cours de l'enseignant
$stmt_courses = $db->prepare("
    SELECT c.*, m.titre AS module_titre 
    FROM courses c 
    JOIN modules m ON m.id = c.module_id 
    WHERE c.teacher_id = ? 
    ORDER BY c.created_at DESC
");
$stmt_courses->execute([$teacher_id]);
$courses = $stmt_courses->fetchAll();

$courses_data = [];
foreach ($courses as $c) {
    //  recuperer les lecons de ce cours
    $stmt_lessons = $db->prepare("
        SELECT l.*, q.id AS quiz_id, q.titre AS quiz_titre,
               (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = q.id) AS nb_questions
        FROM lessons l
        LEFT JOIN quizzes q ON q.lesson_id = l.id
        WHERE l.course_id = ?
        ORDER BY l.ordre ASC, l.id ASC
    ");
    $stmt_lessons->execute([$c['id']]);
    $lessons = $stmt_lessons->fetchAll();
    
    $c['lessons'] = $lessons;
    $courses_data[] = $c;
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LMS — Gestion des Cours</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .course-card-header {
      cursor: pointer;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 20px 24px;
      background: var(--surface2);
      border-bottom: 1px solid var(--border);
    }
    .lesson-item-teacher {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px 18px;
      border-bottom: 1px solid var(--border);
    }
    .lesson-item-teacher:last-child {
      border-bottom: none;
    }
    .course-actions {
      display: flex;
      gap: 8px;
    }
  </style>
</head>
<body>
<div class="shell">
  <?php include '../includes/sidebar_teacher.php'; ?>
  <div class="main-content">
    <header class="topbar">
      <button class="menu-toggle">☰</button>
      <div class="topbar-right">
        <a href="create_course.php" class="btn btn-primary btn-sm">Créer un Cours</a>
      </div>
    </header>

    <main class="page-body">
      <?php if ($msg): ?><div class="alert alert-success">✓ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert alert-danger">⚠ <?= htmlspecialchars($err) ?></div><?php endif; ?>

      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
        <h2>Mes Cours (<?= count($courses_data) ?>)</h2>
        <input class="form-control" type="search" id="search-courses" placeholder="Rechercher un cours…" style="max-width:320px; margin-top:0;">
      </div>

      <?php if (empty($courses_data)): ?>
        <div class="card">
          <div class="card-body text-center text-muted" style="padding:48px;">
            Vous n'avez pas encore créé de cours. Cliquez sur "Créer un Cours" pour démarrer.
          </div>
        </div>

      <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:20px;" id="courses-list-container">
          <?php foreach ($courses_data as $c): ?>
            <div class="card course-card-item" data-title="<?= htmlspecialchars(strtolower($c['titre'])) ?>">
              <div class="course-card-header" onclick="toggleLessons(<?= $c['id'] ?>)">
                <div>
                  <h3 style="margin:0;"><?= htmlspecialchars($c['titre']) ?></h3>
                  <span class="text-muted" style="font-size:.8rem;">Module : <strong><?= htmlspecialchars($c['module_titre']) ?></strong> · <?= count($c['lessons']) ?> leçons</span>
                </div>
                <div class="course-actions" onclick="event.stopPropagation();">
                  <button class="btn btn-primary btn-sm" onclick="openAddLessonModal(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['titre'])) ?>')">Ajouter Leçon</button>
                  <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ce cours et toutes ses leçons ?')">
                    <input type="hidden" name="action" value="delete_course">
                    <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
                    <button class="btn btn-danger btn-sm" style="padding:6px 8px;" title="Supprimer le cours">
                      <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none" style="display:block;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    </button>
                  </form>
                </div>
              </div>
              <div class="card-body" id="lessons-list-<?= $c['id'] ?>" style="padding:0; display:none;">
                <?php if (empty($c['lessons'])): ?>
                  <div style="padding: 20px; text-align:center;" class="text-muted">Aucune leçon dans ce cours. Cliquez sur "Leçon" pour en ajouter une.</div>
                <?php else: ?>
                  <div style="display:flex; flex-direction:column;">
                    <?php foreach ($c['lessons'] as $l): ?>
                      <div class="lesson-item-teacher">
                        <div>
                          <strong>[<?= $l['ordre'] ?>] <?= htmlspecialchars($l['titre']) ?></strong>
                          <span style="font-size:.75rem; margin-left: 8px;" class="badge badge-gray"><?= $l['type'] === 'video' ? 'Vidéo' : 'PDF' ?></span>
                          <div style="font-size:.8rem; margin-top:2px;" class="text-muted">
                            Fichier : <a href="../uploads/<?= $l['type'] === 'pdf' ? 'pdfs' : 'videos' ?>/<?= htmlspecialchars($l['fichier']) ?>" target="_blank" style="color:var(--primary-light); text-decoration:underline;"><?= htmlspecialchars($l['fichier']) ?></a>
                          </div>
                          
                          <!-- Quiz status -->
                          <div style="margin-top:6px; font-size:.85rem;">
                            <?php if ($l['quiz_id']): ?>
                              <span class="badge badge-green">Quiz: <?= htmlspecialchars($l['quiz_titre']) ?></span>
                              <span class="text-muted" style="font-size:.78rem; margin-left:6px;">(<?= $l['nb_questions'] ?> question(s))</span>
                              <button class="btn btn-ghost btn-sm" style="padding: 2px 6px; font-size:.7rem; margin-left:8px;" onclick="openAddQuestionModal(<?= $l['quiz_id'] ?>, '<?= htmlspecialchars(addslashes($l['quiz_titre'])) ?>')">Ajouter Question</button>
                            <?php else: ?>
                              <button class="btn btn-accent btn-sm" style="padding: 2px 6px; font-size:.7rem;" onclick="openAddQuizModal(<?= $l['id'] ?>, '<?= htmlspecialchars(addslashes($l['titre'])) ?>')">Ajouter un Quiz</button>
                            <?php endif; ?>
                          </div>
                        </div>
                        <div>
                          <form method="POST" onsubmit="return confirm('Supprimer cette leçon ?')">
                            <input type="hidden" name="action" value="delete_lesson">
                            <input type="hidden" name="lesson_id" value="<?= $l['id'] ?>">
                            <button class="btn btn-danger btn-sm" style="padding:6px 8px;" title="Supprimer la leçon">
                              <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none" style="display:block;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                            </button>
                          </form>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </main>
  </div>
</div>

<!-- Modal Add Lesson -->
<div class="modal-overlay" id="modal-add-lesson">
  <div class="modal">
    <div class="modal-header">
      <h3>Ajouter une leçon à : <span id="add-lesson-course-name"></span></h3>
      <button class="modal-close">✕</button>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="add_lesson">
      <input type="hidden" name="course_id" id="add-lesson-course-id">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Titre de la leçon *</label>
          <input class="form-control" name="titre" required placeholder="Ex : Les bases du HTML">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Type de contenu *</label>
            <select class="form-control" name="type" required id="lesson-type-select" onchange="updateFileHint()">
              <option value="pdf">Document PDF</option>
              <option value="video">Vidéo MP4</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Ordre d'affichage *</label>
            <input class="form-control" type="number" name="ordre" value="1" required min="1">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Fichier *</label>
          <input class="form-control" type="file" name="file" required id="lesson-file-input">
          <span class="form-hint" id="file-format-hint">Veuillez uploader un fichier PDF (.pdf).</span>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost modal-close">Annuler</button>
        <button type="submit" class="btn btn-primary">Ajouter la leçon</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Add Quiz -->
<div class="modal-overlay" id="modal-add-quiz">
  <div class="modal">
    <div class="modal-header">
      <h3>Ajouter un Quiz à : <span id="add-quiz-lesson-name"></span></h3>
      <button class="modal-close">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add_quiz">
      <input type="hidden" name="lesson_id" id="add-quiz-lesson-id">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Titre du Quiz *</label>
          <input class="form-control" name="titre" required placeholder="Ex : Quiz d'évaluation HTML">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost modal-close">Annuler</button>
        <button type="submit" class="btn btn-primary">Créer le Quiz</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Add Question -->
<div class="modal-overlay" id="modal-add-question">
  <div class="modal" style="max-width: 600px;">
    <div class="modal-header">
      <h3>Nouvelle Question pour : <span id="add-question-quiz-name"></span></h3>
      <button class="modal-close">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add_question">
      <input type="hidden" name="quiz_id" id="add-question-quiz-id">
      <div class="modal-body" style="max-height: 450px; overflow-y: auto;">
        <div class="form-group">
          <label class="form-label">Question *</label>
          <textarea class="form-control" name="question" required rows="2" placeholder="Saisir l'énoncé de la question..."></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Option A *</label>
            <input class="form-control" name="option_a" required>
          </div>
          <div class="form-group">
            <label class="form-label">Option B *</label>
            <input class="form-control" name="option_b" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Option C *</label>
            <input class="form-control" name="option_c" required>
          </div>
          <div class="form-group">
            <label class="form-label">Option D *</label>
            <input class="form-control" name="option_d" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Bonne Réponse *</label>
          <select class="form-control" name="bonne_reponse" required>
            <option value="A">Option A</option>
            <option value="B">Option B</option>
            <option value="C">Option C</option>
            <option value="D">Option D</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost modal-close">Annuler</button>
        <button type="submit" class="btn btn-primary">Enregistrer la Question</button>
      </div>
    </form>
  </div>
</div>

<script src="../assets/js/app.js"></script>
<script>
function toggleLessons(courseId) {
  const container = document.getElementById('lessons-list-' + courseId);
  if (container) {
    if (container.style.display === 'none') {
      container.style.display = 'block';
    } else {
      container.style.display = 'none';
    }
  }
}

function openAddLessonModal(courseId, courseName) {
  document.getElementById('add-lesson-course-id').value = courseId;
  document.getElementById('add-lesson-course-name').textContent = courseName;
  openModal('modal-add-lesson');
  updateFileHint();
}

function updateFileHint() {
  const select = document.getElementById('lesson-type-select');
  const fileInput = document.getElementById('lesson-file-input');
  const hint = document.getElementById('file-format-hint');
  
  if (select.value === 'pdf') {
    fileInput.accept = '.pdf';
    hint.textContent = 'Veuillez uploader un fichier PDF (.pdf).';
  } else {
    fileInput.accept = '.mp4';
    hint.textContent = 'Veuillez uploader un fichier vidéo MP4 (.mp4).';
  }
}

function openAddQuizModal(lessonId, lessonName) {
  document.getElementById('add-quiz-lesson-id').value = lessonId;
  document.getElementById('add-quiz-lesson-name').textContent = lessonName;
  openModal('modal-add-quiz');
}

function openAddQuestionModal(quizId, quizName) {
  document.getElementById('add-question-quiz-id').value = quizId;
  document.getElementById('add-question-quiz-name').textContent = quizName;
  openModal('modal-add-question');
}

// Search filter
document.addEventListener('DOMContentLoaded', () => {
  const input = document.getElementById('search-courses');
  if (input) {
    input.addEventListener('input', () => {
      const q = input.value.toLowerCase();
      document.querySelectorAll('.course-card-item').forEach(item => {
        const title = item.dataset.title;
        if (title.includes(q)) {
          item.style.display = '';
        } else {
          item.style.display = 'none';
        }
      });
    });
  }
});
</script>
</body>
</html>
