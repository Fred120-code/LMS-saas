document.addEventListener('DOMContentLoaded', () => {
  if (window.IS_ALREADY_DONE || window.LESSON_TYPE !== 'video') return;

  const video = document.getElementById('lesson-video');
  const fill = document.getElementById('vid-track');
  const pctEl = document.getElementById('vid-pct');

  if (!video) return;

  let maxWatched = 0;
  let completed = false;

  video.addEventListener('timeupdate', () => {
    const current = video.currentTime;
    const duration = video.duration;
    if (!duration) return;

    if (current > maxWatched + 3) {
      video.currentTime = maxWatched;
      Toast.info('Veillez regarder la vidéo complètement pour valider.');
      return;
    }

    if (current > maxWatched) maxWatched = current;

    const progress = Math.min((maxWatched / duration) * 100, 100);
    if (fill) fill.style.width = progress.toFixed(1) + '%';
    if (pctEl) pctEl.textContent = Math.floor(progress) + '%';
  });

  video.addEventListener('ended', () => {
    if (!completed) {
      completed = true;
      completeLessonAPI(window.LESSON_ID);
    }
  });
});
