export function initFilm() {
  const video = document.querySelector('.film-video');
  const toggle = document.querySelector('.film-sound-toggle');
  if (!video || !toggle) return;

  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    video.removeAttribute('autoplay');
    video.loop = false;
    video.pause();
    video.setAttribute('controls', '');
  }

  toggle.addEventListener('click', () => {
    video.muted = !video.muted;
    toggle.setAttribute('aria-pressed', String(!video.muted));
    toggle.setAttribute('aria-label', video.muted ? 'Ativar som do vídeo' : 'Silenciar vídeo');
  });
}
