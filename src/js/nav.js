export function initHeader() {
  const header = document.getElementById('site-header');
  if (!header) return;

  const setState = () => {
    header.classList.toggle('is-scrolled', window.scrollY > 24);
  };

  setState();
  window.addEventListener('scroll', setState, { passive: true });

  const toggle = document.querySelector('.nav-toggle');
  const nav = document.querySelector('.main-nav ul');
  if (!toggle || !nav) return;

  toggle.addEventListener('click', () => {
    const isOpen = nav.classList.toggle('is-open');
    toggle.setAttribute('aria-expanded', String(isOpen));
  });

  nav.addEventListener('click', (e) => {
    if (e.target.closest('a')) {
      nav.classList.remove('is-open');
      toggle.setAttribute('aria-expanded', 'false');
    }
  });
}

export function initFooterYear() {
  const el = document.getElementById('footer-year');
  if (!el) return;
  el.textContent = String(new Date().getFullYear());
}
