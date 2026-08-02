import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

export function initScrollFx() {
  const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (prefersReduced) return;

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
}
