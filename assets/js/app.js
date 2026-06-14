
// notifications
const Toast = (() => {
  let container = null;

  function getContainer() {
    if (!container) {
      container = document.createElement('div');
      container.className = 'toast-container';
      document.body.appendChild(container);
    }
    return container;
  }

  function show(msg, type = '', dur = 3500) {
    const t = document.createElement('div');
    t.className = 'toast ' + type;
    t.innerHTML = `<span>${type === 'success' ? '✓' : type === 'error' ? '✕' : 'ℹ'}</span><span>${msg}</span>`;
    getContainer().appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity .4s'; setTimeout(() => t.remove(), 400); }, dur);
  }

  return { success: m => show(m, 'success'), error: m => show(m, 'error'), info: m => show(m, '') };
})();

// fonction ajax pour envoyer des donnees au serveur  
function ajax(url, data = {}, method = 'POST') {
  return new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest();
    xhr.open(method, url, true);

    let body;
    if (data instanceof FormData) {
      body = data;
    } else {
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      body = Object.entries(data).map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`).join('&');
    }

    xhr.onload = () => {
      try { resolve(JSON.parse(xhr.responseText)); }
      catch { reject({ error: 'Réponse invalide du serveur' }); }
    };
    xhr.onerror = () => reject({ error: 'Erreur réseau' });
    xhr.send(body);

  });
}

//helper pour les modales
function openModal(id) {
  const m = document.getElementById(id);
  if (m) m.classList.add('open');
}

//fermer la modale 
function closeModal(id) {
  const m = document.getElementById(id);
  if (m) m.classList.remove('open');
}

//fermer la modale quand on clique sur l'overlay ou le bouton de fermeture
document.addEventListener('click', e => {
  if (e.target.classList.contains('modal-overlay')) e.target.classList.remove('open');
  if (e.target.classList.contains('modal-close')) {
    const m = e.target.closest('.modal-overlay');
    if (m) m.classList.remove('open');
  }
});

//sidebar mobile 
document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.querySelector('.menu-toggle');
  const sidebar = document.querySelector('.sidebar');

  if (toggle && sidebar) {
    toggle.addEventListener('click', () => sidebar.classList.toggle('open'));
  }

  //mise a jour du lien actif 
  const path = window.location.pathname;
  document.querySelectorAll('.nav-link').forEach(link => {
    if (link.getAttribute('href') && path.endsWith(link.getAttribute('href').split('/').pop())) {
      link.classList.add('active');
    }
  });
});


//confirmation de suppression
function confirmDelete(msg, callback) {
  if (confirm(msg || 'Confirmer la suppression ?')) callback();
}

// animation de la barre de progression 
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.progress-bar-fill').forEach(bar => {
    const target = bar.dataset.width || '0';
    bar.style.width = '0';
    setTimeout(() => bar.style.width = target + '%', 150);
  });
});

//envoi des reponses du quiz
function submitQuiz(quizId, lessonId) {
  const form = document.getElementById('quiz-form');
  if (!form) return;

  const questions = form.querySelectorAll('.quiz-question');
  const answers = {};

  let allAnswered = true;
  questions.forEach(q => {
    const id = q.dataset.questionId;
    const sel = q.querySelector(`input[name="q_${id}"]:checked`);
    if (!sel) { allAnswered = false; } else { answers[id] = sel.value; }
  });

  if (!allAnswered) { Toast.error('Répondez à toutes les questions.'); return; }

  const btn = document.getElementById('quiz-submit-btn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner" style="width:18px;height:18px;border-width:2px"></span> Correction…';

  const payload = { quiz_id: quizId, lesson_id: lessonId, answers: JSON.stringify(answers) };
  ajax('../api/quiz_submit.php', payload)
    .then(res => {
      if (res.error) { Toast.error(res.error); btn.disabled = false; btn.textContent = 'Soumettre'; return; }
      // affichage des résultats 
      showQuizResult(res, questions);
      // mise a jour de la progression
      updateProgress(res.module_id);
    })
    .catch(() => { Toast.error('Erreur réseau.'); btn.disabled = false; });
}


//affichage des résultat du quiz
function showQuizResult(res, questions) {
  const resultBox = document.getElementById('quiz-result');
  if (resultBox) {
    resultBox.classList.remove('hidden');
    resultBox.innerHTML = `
      <div class="score-card">
        <div class="score-circle">${res.pourcentage}%</div>
        <h2>${res.passed ? ' Réussi !' : ' A améliorer'}</h2>
        <p>Vous avez obtenu <strong>${res.score} / ${res.total}</strong> bonnes réponses.</p>
        ${res.certificate ? `<p class="mt-2"><a href="${res.certificate_url}" class="btn btn-accent mt-2" target="_blank"> Télécharger le certificat</a></p>` : ''}
        <a href="dashboard.php" class="btn btn-ghost mt-3">Retour au tableau de bord</a>
      </div>`;
  }

  // mise en evidence des reponses  
  if (res.corrections) {
    questions.forEach(q => {
      const id = q.dataset.questionId;
      const correct = res.corrections[id];
      q.querySelectorAll('.quiz-option').forEach(opt => {
        const val = opt.querySelector('input').value;
        opt.style.pointerEvents = 'none';
        if (val === correct) opt.classList.add('correct');
      });

      const selected = q.querySelector('input:checked');
      if (selected && selected.value !== correct) selected.closest('.quiz-option').classList.add('wrong');
    });
  }

  document.getElementById('quiz-submit-btn').classList.add('hidden');
}

//mise a jour de la progression
function updateProgress(moduleId) {
  if (!moduleId) return;
  ajax('../api/progress_update.php', { module_id: moduleId })
    .then(res => {
      const bar = document.getElementById('module-progress-' + moduleId);
      if (bar) { bar.style.width = res.pourcentage + '%'; }
    });
}

//recherche dynamique dans les tableau
function tableSearch(inputId, tableId) {
  const input = document.getElementById(inputId);
  const table = document.getElementById(tableId);

  if (!input || !table) return;
  input.addEventListener('input', () => {
    const q = input.value.toLowerCase();
    table.querySelectorAll('tbody tr').forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
}

// Confirmation de déconnexion premium
document.addEventListener('DOMContentLoaded', () => {
  const logoutLinks = document.querySelectorAll('a[href*="logout.php"]');
  logoutLinks.forEach(link => {
    link.addEventListener('click', e => {
      e.preventDefault();
      const href = link.getAttribute('href');
      
      let modal = document.getElementById('logout-confirm-modal');
      if (!modal) {
        modal = document.createElement('div');
        modal.id = 'logout-confirm-modal';
        modal.className = 'modal-overlay';
        modal.innerHTML = `
          <div class="modal" style="max-width: 400px; overflow: hidden; border: 1px solid var(--border);">
            <div class="modal-header" style="background: var(--surface2); padding: 18px 24px;">
              <h3 style="font-family: 'Inter', system-ui, sans-serif; font-weight: 600; font-size: 1.1rem; display: flex; align-items: center; gap: 10px; color: var(--text);">
                <svg viewBox="0 0 24 24" width="20" height="20" stroke="var(--danger)" fill="none" stroke-width="2.5" style="flex-shrink: 0;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                Déconnexion
              </h3>
              <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body" style="padding: 24px; color: var(--text-muted); font-size: 0.95rem; line-height: 1.5;">
              Êtes-vous sûr de vouloir vous déconnecter de votre compte EduLearn ? Toutes les modifications non enregistrées seront perdues.
            </div>
            <div class="modal-footer" style="padding: 16px 24px; background: var(--surface2); gap: 12px;">
              <button class="btn btn-ghost modal-close-btn" style="border: 1px solid var(--border); color: var(--text-muted); background: var(--surface); font-size: 0.85rem; padding: 8px 16px; cursor: pointer;">
                Annuler
              </button>
              <a href="${href}" class="btn btn-danger" style="font-size: 0.85rem; padding: 8px 16px; display: inline-flex; align-items: center; gap: 8px; font-weight: 600; text-decoration: none;">
                <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" fill="none" stroke-width="2.5"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                Se déconnecter
              </a>
            </div>
          </div>
        `;
        document.body.appendChild(modal);
        
        const close = () => modal.classList.remove('open');
        modal.querySelector('.modal-close').addEventListener('click', close);
        modal.querySelector('.modal-close-btn').addEventListener('click', close);
        modal.addEventListener('click', evt => {
          if (evt.target === modal) close();
        });
      } else {
        modal.querySelector('.btn-danger').setAttribute('href', href);
      }
      
      setTimeout(() => modal.classList.add('open'), 10);
    });
  });
});