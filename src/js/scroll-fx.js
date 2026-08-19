import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

export function initScrollFx() {
  const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  if (prefersReduced) {
    // Sem parallax/scrub/autoplay no modo reduzido — o filme vira um
    // vídeo comum, parado no poster até o usuário decidir tocar.
    const filmVideo = document.querySelector('.film-video');
    if (filmVideo) filmVideo.setAttribute('controls', '');
    const showcaseVideo = document.querySelector('.showcase-visual-video');
    if (showcaseVideo) {
      showcaseVideo.removeAttribute('autoplay');
      showcaseVideo.pause();
    }
    return;
  }

  const hero = document.querySelector('.hero');
  if (hero) {
    const g1 = hero.querySelector('.hero-glow .g1');
    const g2 = hero.querySelector('.hero-glow .g2');
    if (g1) {
      gsap.to(g1, {
        yPercent: 28,
        xPercent: -8,
        ease: 'none',
        scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom top', scrub: 0.6 },
      });
    }
    if (g2) {
      gsap.to(g2, {
        yPercent: -18,
        xPercent: 12,
        ease: 'none',
        scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom top', scrub: 0.6 },
      });
    }
  }

  const showcase = document.querySelector('.showcase');
  const visual = document.querySelector('.showcase-visual');
  if (showcase && visual) {
    gsap.fromTo(
      visual,
      { yPercent: 6, rotate: -1.2 },
      {
        yPercent: -6,
        rotate: 1.2,
        ease: 'none',
        scrollTrigger: { trigger: showcase, start: 'top bottom', end: 'bottom top', scrub: 0.8 },
      }
    );
  }

  const film = document.querySelector('.film');
  const filmVideo = document.querySelector('.film-video');
  if (film && filmVideo) {
    const isReady = () => Number.isFinite(filmVideo.duration) && filmVideo.duration > 0;
    // O vídeo (preload="auto") pode já ter carregado os metadados antes
    // deste módulo rodar — checa o estado atual primeiro, e só espera o
    // evento se ainda não estiver pronto (senão o evento já passou e o
    // scrub nunca liga).
    let seekable = isReady();
    if (!seekable) {
      filmVideo.addEventListener('loadedmetadata', () => { seekable = isReady(); }, { once: true });
    }

    // Sem play/pause normal aqui de propósito: o tempo do vídeo é
    // amarrado 1:1 ao progresso do scroll dentro da seção — desce o
    // scroll, o vídeo avança; sobe o scroll, o vídeo volta.
    ScrollTrigger.create({
      trigger: film,
      start: 'top top',
      end: 'bottom bottom',
      scrub: true,
      onUpdate: (self) => {
        if (!seekable) return;
        filmVideo.currentTime = self.progress * filmVideo.duration;
      },
    });
  }

  const ctaFinal = document.querySelector('.cta-final');
  if (ctaFinal) {
    const g1 = ctaFinal.querySelector('.cta-glow .g1');
    const g2 = ctaFinal.querySelector('.cta-glow .g2');
    if (g1) {
      gsap.to(g1, {
        yPercent: -22,
        ease: 'none',
        scrollTrigger: { trigger: ctaFinal, start: 'top bottom', end: 'bottom top', scrub: 0.6 },
      });
    }
    if (g2) {
      gsap.to(g2, {
        yPercent: 22,
        ease: 'none',
        scrollTrigger: { trigger: ctaFinal, start: 'top bottom', end: 'bottom top', scrub: 0.6 },
      });
    }
  }

  // Os ScrollTriggers acima (principalmente o do .film, com scrub 1:1 do
  // vídeo) são criados aqui perto do DOMContentLoaded, medindo start/end
  // em pixels com o layout de *naquele momento*. A fonte do Google Fonts
  // carrega de propósito depois do primeiro paint (truque
  // media="print"/onload em partials/seo-meta.php) e troca a fonte de
  // fallback pela definitiva — isso quase sempre reflowa o texto e move a
  // posição de tudo abaixo, inclusive o .film. Sem recalcular depois desse
  // reflow, o scroll "termina" a seção num ponto que não bate mais com
  // onde ela realmente termina na tela.
  if (document.fonts && document.fonts.ready) {
    document.fonts.ready.then(() => ScrollTrigger.refresh());
  }
  window.addEventListener('load', () => ScrollTrigger.refresh());
}
