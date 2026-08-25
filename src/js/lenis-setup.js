// Scroll suave nativo (sem Lenis) — o scroll com inércia customizada era
// um "nice to have" que puxava mais uma lib pro bundle principal. O
// scrollTo nativo com behavior:'smooth' já dá uma rolagem suave o
// suficiente, e ainda respeita prefers-reduced-motion automaticamente.
export function initSmoothScroll() {
  const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  document.querySelectorAll('a[href^="#"]:not([data-open-contact])').forEach((anchor) => {
    anchor.addEventListener('click', (e) => {
      const id = anchor.getAttribute('href');
      if (!id || id === '#') return;
      const target = document.querySelector(id);
      if (!target) return;
      e.preventDefault();
      const top = target.getBoundingClientRect().top + window.scrollY - 80;
      window.scrollTo({ top, behavior: prefersReduced ? 'auto' : 'smooth' });
    });
  });
}
