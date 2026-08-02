export function initButtonGlow() {
  const buttons = document.querySelectorAll('.btn-primary');
  if (!buttons.length) return;
  if (window.matchMedia('(hover: none)').matches) return;

  buttons.forEach((btn) => {
    btn.addEventListener('mousemove', (e) => {
      const rect = btn.getBoundingClientRect();
      btn.style.setProperty('--mx', `${e.clientX - rect.left}px`);
      btn.style.setProperty('--my', `${e.clientY - rect.top}px`);
    });
  });
}
