import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { initSmoothScroll } from './js/lenis-setup.js';
import { initReveal } from './js/reveal.js';
import { initTilt } from './js/tilt.js';
import { initCounters } from './js/counter.js';
import { initHeader, initFooterYear } from './js/nav.js';
import { initContactWidget } from './js/contact-widget.js';
import { initPortfolioScroll } from './js/portfolio-scroll.js';
import { initHeroParticles } from './js/hero-particles.js';

gsap.registerPlugin(ScrollTrigger);
window.ScrollTrigger = ScrollTrigger;

document.addEventListener('DOMContentLoaded', () => {
  initSmoothScroll();
  initHeader();
  initFooterYear();
  initReveal();
  initTilt();
  initCounters();
  initContactWidget();
  initPortfolioScroll();
  initHeroParticles();
});
