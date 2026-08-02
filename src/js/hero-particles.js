export function initHeroParticles() {
  const canvas = document.getElementById('hero-particles');
  if (!canvas) return;

  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    canvas.remove();
    return;
  }

  const ctx = canvas.getContext('2d');
  let width, height, bars;
  const hue = 207;

  const makeBars = () => {
    const barWidth = 4 * devicePixelRatio;
    const gap = 9 * devicePixelRatio;
    const count = Math.max(1, Math.floor(width / (barWidth + gap)));
    bars = Array.from({ length: count }, (_, i) => ({
      x: i * (barWidth + gap),
      phase: Math.random() * Math.PI * 2,
      speed: 0.008 + Math.random() * 0.016,
      amp: 0.2 + Math.random() * 0.6,
      alpha: 0.06 + Math.random() * 0.2,
    }));
  };

  const resize = () => {
    width = canvas.width = canvas.offsetWidth * devicePixelRatio;
    height = canvas.height = canvas.offsetHeight * devicePixelRatio;
    makeBars();
  };

  let t = 0;
  const barWidth = () => 4 * devicePixelRatio;

  const draw = () => {
    ctx.clearRect(0, 0, width, height);
    const baseY = height * 0.86;
    const bw = barWidth();
    bars.forEach((b) => {
      const wobble = Math.sin(t * b.speed + b.phase) * 0.5 + 0.5;
      const h = wobble * b.amp * height * 0.42;
      ctx.fillStyle = `hsla(${hue}, 82%, 64%, ${b.alpha})`;
      ctx.fillRect(b.x, baseY - h, bw, h);
    });
    t += 1;
    requestAnimationFrame(draw);
  };

  resize();
  requestAnimationFrame(draw);

  window.addEventListener('resize', resize);
}
