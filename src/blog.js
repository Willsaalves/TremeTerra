// JS estático do blog — copiado direto pro dist/ (não passa pelo Vite),
// mesmo padrão de src/styles/blog.css. Cobre: filtro por categoria e
// reveal-on-scroll dos cards de post.

function initFilters() {
  const filters = document.querySelectorAll('.filter-chip');
  const cards = document.querySelectorAll('.post-card');
  if (!filters.length || !cards.length) return;

  filters.forEach((chip) => {
    chip.addEventListener('click', () => {
      const target = chip.dataset.filter;

      filters.forEach((c) => c.classList.toggle('is-active', c === chip));

      cards.forEach((card) => {
        const matches = target === 'all' || card.dataset.category === target;
        card.hidden = !matches;
      });
    });
  });
}

function initReveal() {
  const cards = document.querySelectorAll('.post-card');
  if (!cards.length) return;

  if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    cards.forEach((card) => card.classList.add('is-revealed'));
    return;
  }

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-revealed');
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.15, rootMargin: '0px 0px -40px 0px' }
  );

  cards.forEach((card, i) => {
    card.style.transitionDelay = `${Math.min(i, 6) * 60}ms`;
    observer.observe(card);
  });
}

document.addEventListener('DOMContentLoaded', () => {
  initFilters();
  initReveal();
});
