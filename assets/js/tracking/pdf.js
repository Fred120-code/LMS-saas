document.addEventListener('DOMContentLoaded', () => {
  if (window.IS_ALREADY_DONE || window.LESSON_TYPE !== 'pdf') return;

  let pdfCompleted = false;
  const iframe = document.getElementById('lesson-pdf');
  if (!iframe) return;

  const sentinel = document.createElement('div');
  sentinel.id = 'pdf-sentinel';
  sentinel.style.cssText = 'height:2px;width:100%;margin-top:-2px;';
  iframe.parentNode.insertBefore(sentinel, iframe.nextSibling);

  const MIN_READ_TIME_MS = 15000;
  let readTimerDone = false;
  setTimeout(() => { readTimerDone = true; }, MIN_READ_TIME_MS);

  const guardedObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting && !pdfCompleted) {
        if (!readTimerDone) {
          Toast.info('Continuez de lire le PDF — faites défiler jusqu\'à la fin.');
          return;
        }
        pdfCompleted = true;
        guardedObserver.disconnect();
        completeLessonAPI(window.LESSON_ID);
      }
    });
  }, { threshold: 0.9 });

  guardedObserver.observe(sentinel);
});
