export function initContactWidget() {
  const widget = document.getElementById('contact-widget');
  if (!widget) return;

  const toggle = document.getElementById('contact-widget-toggle');
  const panel = widget.querySelector('.contact-widget-panel');
  const form = document.getElementById('contact-form');

  const openWidget = () => {
    widget.classList.add('is-open');
    toggle.setAttribute('aria-expanded', 'true');
    panel.setAttribute('aria-hidden', 'false');
  };

  const closeWidget = () => {
    widget.classList.remove('is-open');
    toggle.setAttribute('aria-expanded', 'false');
    panel.setAttribute('aria-hidden', 'true');
  };

  toggle.addEventListener('click', () => {
    widget.classList.contains('is-open') ? closeWidget() : openWidget();
  });

  document.addEventListener('click', (e) => {
    if (!widget.contains(e.target) && widget.classList.contains('is-open')) {
      closeWidget();
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && widget.classList.contains('is-open')) {
      closeWidget();
      toggle.focus();
    }
  });

  document.querySelectorAll('[data-open-contact]').forEach((btn) => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      openWidget();
    });
  });

  if (form) {
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      widget.classList.add('is-success');
      window.setTimeout(() => {
        form.reset();
        widget.classList.remove('is-success');
      }, 4000);
    });
  }
}
