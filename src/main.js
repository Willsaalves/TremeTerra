import { initSmoothScroll } from './js/lenis-setup.js';
import { initReveal } from './js/reveal.js';
import { initTilt } from './js/tilt.js';
import { initCounters } from './js/counter.js';
import { initHeader, initFooterYear } from './js/nav.js';
import { initContactWidget } from './js/contact-widget.js';
import { initButtonGlow } from './js/button-glow.js';

document.addEventListener('DOMContentLoaded', () => {
  initSmoothScroll();
  initHeader();
  initFooterYear();
  initReveal();
  initTilt();
  initCounters();
  initContactWidget();
  initButtonGlow();

  // Code-splitting: esses módulos só existem/fazem algo em páginas com os
  // elementos correspondentes (Home, principalmente) — import() dinâmico
  // vira chunk próprio no build, então páginas sem esses elementos não
  // baixam mais esse código (antes era tudo no bundle único main-*.js,
  // compartilhado por todas as 15 páginas).
  // Carrossel 3D (three.js, ~127KB gzip): nem o Portfólio nem o roster
  // de DJs ficam acima da dobra, então o import() + a inicialização
  // pesada (WebGLRenderer, construção de texturas/vídeos) só acontecem
  // quando a seção se aproxima da tela — mesma técnica de
  // IntersectionObserver já usada em scroll-fx.js pro "aquecimento" do
  // vídeo de Bastidores. Sem isso, esse trabalho competia com o
  // carregamento inicial da página e derrubava LCP/INP.
  const carouselTarget = document.getElementById('portfolio-track') || document.querySelector('.dj-roster-grid');
  if (carouselTarget) {
    if ('IntersectionObserver' in window) {
      const carouselObserver = new IntersectionObserver(
        (entries) => {
          if (entries.some((entry) => entry.isIntersecting)) {
            import('./js/carousel3d.js').then((m) => m.initCarousel3D());
            carouselObserver.disconnect();
          }
        },
        { rootMargin: '800px 0px' }
      );
      carouselObserver.observe(carouselTarget);
    } else {
      import('./js/carousel3d.js').then((m) => m.initCarousel3D());
    }
  }
  if (document.getElementById('hero-particles')) {
    import('./js/hero-particles.js').then((m) => m.initHeroParticles());
  }
  if (document.querySelector('.film-video, .showcase-visual-video, .hero-glow')) {
    import('./js/scroll-fx.js').then((m) => m.initScrollFx());
  }
});
