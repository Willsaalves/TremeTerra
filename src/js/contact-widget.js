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
    if (widget.contains(e.target) || e.target.closest('[data-open-contact]')) return;
    if (widget.classList.contains('is-open')) closeWidget();
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
    const submitBtn = form.querySelector('button[type="submit"]');
    const errorEl = form.querySelector('.contact-form-error');
    const submitLabel = submitBtn ? submitBtn.textContent : 'Enviar';

    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      if (errorEl) {
        errorEl.hidden = true;
        errorEl.textContent = '';
      }
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Enviando…';
      }

      try {
        const response = await fetch(form.action, {
          method: 'POST',
          body: new FormData(form),
          headers: { Accept: 'application/json' },
        });
        const data = await response.json().catch(() => null);

        if (!response.ok || !data || data.success !== true) {
          throw new Error(data?.message || 'Não conseguimos enviar agora. Tente de novo em instantes.');
        }

        widget.classList.add('is-success');
        window.setTimeout(() => {
          form.reset();
          widget.classList.remove('is-success');
        }, 5000);
      } catch (err) {
        if (errorEl) {
          errorEl.textContent = err instanceof Error ? err.message : 'Não conseguimos enviar agora. Tente de novo.';
          errorEl.hidden = false;
        }
      } finally {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.textContent = submitLabel;
        }
      }
    });
  }
}
