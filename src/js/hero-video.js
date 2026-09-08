export function initHeroVideo() {
  const video = document.querySelector('.hero-video-solo-media');
  if (!(video instanceof HTMLVideoElement)) return;

  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    video.removeAttribute('autoplay');
    video.pause();
    return;
  }

  // iOS/Safari só libera autoplay com muted + playsInline de verdade
  // (atributo HTML às vezes não basta se o JS não reforçar as propriedades).
  video.muted = true;
  video.defaultMuted = true;
  video.playsInline = true;
  video.setAttribute('playsinline', '');
  video.setAttribute('webkit-playsinline', 'true');

  const tryPlay = () => {
    const playPromise = video.play();
    if (playPromise && typeof playPromise.catch === 'function') {
      playPromise.catch(() => {});
    }
  };

  if (video.readyState >= 2) tryPlay();
  else video.addEventListener('canplay', tryPlay, { once: true });

  document.addEventListener('touchstart', tryPlay, { once: true, passive: true });
  document.addEventListener('click', tryPlay, { once: true });
  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible' && video.paused) tryPlay();
  });
}
