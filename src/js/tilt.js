const lerp = (a, b, n) => a + (b - a) * n;

export function initTilt() {
  const els = document.querySelectorAll('[data-tilt]');
  if (!els.length) return;

  const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (prefersReduced) return;
  if (window.matchMedia('(hover: none)').matches) return;

  els.forEach((el) => {
    const strength = Number(el.dataset.tiltStrength) || 10;
    let targetX = 0;
    let targetY = 0;
    let currentX = 0;
    let currentY = 0;
    let raf = null;
    let settling = false;

    const tick = () => {
      currentX = lerp(currentX, targetX, 0.12);
      currentY = lerp(currentY, targetY, 0.12);

      const shadowX = (-currentY / strength) * 16;
      const shadowY = 20 + (currentX / strength) * 16;

      el.style.transform = `perspective(800px) rotateX(${currentX.toFixed(2)}deg) rotateY(${currentY.toFixed(2)}deg)`;
      el.style.boxShadow = `0 1px 0 hsl(var(--color-ink) / 0.05), ${shadowX.toFixed(1)}px ${shadowY.toFixed(1)}px 40px -28px hsl(var(--color-ink) / 0.3)`;

      const settled = Math.abs(currentX - targetX) < 0.01 && Math.abs(currentY - targetY) < 0.01;
      if (!settled || settling) {
        raf = requestAnimationFrame(tick);
      } else {
        raf = null;
      }
    };

    const start = () => {
      if (!raf) raf = requestAnimationFrame(tick);
    };

    el.addEventListener('mousemove', (e) => {
      settling = false;
      const rect = el.getBoundingClientRect();
      const x = (e.clientX - rect.left) / rect.width - 0.5;
      const y = (e.clientY - rect.top) / rect.height - 0.5;
      targetX = -y * strength;
      targetY = x * strength;
      start();
    });

    el.addEventListener('mouseleave', () => {
      settling = true;
      targetX = 0;
      targetY = 0;
      start();
      window.setTimeout(() => { settling = false; }, 400);
    });
  });
}
