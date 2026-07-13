function completeLessonAPI(lessonId) {
  ajax('../api/progress_update.php', { lesson_id: lessonId })
    .then(res => {
      if (res.error) {
        console.warn('Completion error:', res.error);
        return;
      }
      showCompletedUI();
    })
    .catch(err => console.error('Network error:', err));
}

function showCompletedUI() {
  const gate = document.getElementById('lesson-gate');
  const slot = document.getElementById('quiz-btn-slot');
  const track = document.getElementById('vid-track');
  const pct = document.getElementById('vid-pct');

  if (gate) {
    gate.classList.remove('state-locked');
    gate.classList.add('state-done');
    const icon = gate.querySelector('.gate-icon');
    if (icon) {
      icon.innerHTML = `
        <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M20 6L9 17l-5-5"></path>
        </svg>`;
    }
    const title = gate.querySelector('.gate-title');
    if (title) title.textContent = 'Leçon terminée';
    const sub = gate.querySelector('.gate-sub');
    if (sub) sub.textContent = 'Vous avez complété cette leçon avec succès.';
  }

  if (track) { track.style.width = '100%'; }
  if (pct) { pct.textContent = '100%'; }

  if (window.HAS_QUIZ && slot) {
    slot.style.display = 'block';
    slot.innerHTML = `<a href="${window.QUIZ_URL}" class="btn btn-primary">Faire le quiz </a>`;
  }

  Toast.success('Leçon validée ! Vous pouvez maintenant passer au quiz.');
}
