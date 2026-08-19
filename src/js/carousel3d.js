// Carrossel 3D (three.js) — usado no Portfólio da Home (#portfolio-track)
// e no roster de DJs (.dj-roster-grid). Progressive enhancement: se WebGL
// não estiver disponível ou o usuário preferir menos movimento, a seção
// continua no formato original (grid/scroll-track plano), sem 3D. O
// conteúdo original permanece no DOM (só fica visualmente oculto via
// .visually-hidden, não display:none) pra continuar acessível a leitor de
// tela e a crawlers — a versão 3D é puramente decorativa (aria-hidden).
import * as THREE from 'three';

const FONT_HEADING = "'Montserrat', ui-sans-serif, system-ui, -apple-system, sans-serif";

function prefersReducedMotion() {
  return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

function supportsWebGL() {
  try {
    const canvas = document.createElement('canvas');
    return !!(window.WebGLRenderingContext && (canvas.getContext('webgl') || canvas.getContext('experimental-webgl')));
  } catch {
    return false;
  }
}

function roundedRectPath(ctx, x, y, w, h, r) {
  ctx.beginPath();
  ctx.moveTo(x + r, y);
  ctx.arcTo(x + w, y, x + w, y + h, r);
  ctx.arcTo(x + w, y + h, x, y + h, r);
  ctx.arcTo(x, y + h, x, y, r);
  ctx.arcTo(x, y, x + w, y, r);
  ctx.closePath();
}

// Só calcula a quebra de linha (não desenha) — precisamos saber quantas
// linhas a legenda vai ocupar ANTES de decidir onde colocar o eyebrow em
// cima dela, senão legendas longas (3 linhas) invadem o texto de cima.
function measureWrap(ctx, text, maxWidth) {
  const words = text.split(' ');
  const lines = [];
  let line = '';
  for (const word of words) {
    const test = `${line}${word} `;
    if (ctx.measureText(test).width > maxWidth && line) {
      lines.push(line.trim());
      line = `${word} `;
    } else {
      line = test;
    }
  }
  lines.push(line.trim());
  return lines;
}

function drawLines(ctx, lines, x, bottomY, lineHeight) {
  const startY = bottomY - (lines.length - 1) * lineHeight;
  lines.forEach((l, i) => ctx.fillText(l, x, startY + i * lineHeight));
}

// Compõe imagem + gradiente + texto num canvas (mesmo visual do
// .portfolio-card original) pra usar como textura do plane 3D.
async function buildImageTexture(item, w, h) {
  const img = new Image();
  await new Promise((resolve, reject) => {
    img.onload = resolve;
    img.onerror = reject;
    img.src = item.src;
  });

  const canvas = document.createElement('canvas');
  canvas.width = w;
  canvas.height = h;
  const ctx = canvas.getContext('2d');

  roundedRectPath(ctx, 0, 0, w, h, 36);
  ctx.clip();

  const scale = Math.max(w / img.naturalWidth, h / img.naturalHeight);
  const dw = img.naturalWidth * scale;
  const dh = img.naturalHeight * scale;
  ctx.drawImage(img, (w - dw) / 2, (h - dh) / 2, dw, dh);

  if (item.eyebrow || item.caption) {
    const pad = w * 0.09;
    const captionFont = `800 ${Math.round(w * 0.075)}px ${FONT_HEADING}`;
    const eyebrowFont = `700 ${Math.round(w * 0.052)}px ${FONT_HEADING}`;
    const captionLineHeight = w * 0.09;
    const eyebrowGap = h * 0.05;

    // Mede a legenda ANTES de desenhar qualquer coisa, pra saber a altura
    // real do bloco de texto (1 a 3 linhas) e posicionar o eyebrow sempre
    // acima dele, sem sobrepor.
    ctx.font = captionFont;
    const captionLines = item.caption ? measureWrap(ctx, item.caption, w - pad * 2) : [];
    const captionBottomY = h - h * 0.06;
    const captionTopY = captionBottomY - (captionLines.length - 1) * captionLineHeight;
    const eyebrowY = item.caption ? captionTopY - eyebrowGap : h - h * 0.1;
    const gradientTop = Math.min(h * 0.4, eyebrowY - h * 0.12);

    const grad = ctx.createLinearGradient(0, h, 0, gradientTop);
    grad.addColorStop(0, 'hsla(222, 47%, 6%, 0.82)');
    grad.addColorStop(1, 'hsla(222, 47%, 6%, 0.05)');
    ctx.fillStyle = grad;
    ctx.fillRect(0, gradientTop, w, h - gradientTop);

    if (item.eyebrow) {
      ctx.fillStyle = 'hsla(220, 30%, 97%, 0.85)';
      ctx.font = eyebrowFont;
      ctx.fillText(item.eyebrow.toUpperCase(), pad, eyebrowY);
    }
    if (item.caption) {
      ctx.fillStyle = 'hsl(220, 30%, 97%)';
      ctx.font = captionFont;
      drawLines(ctx, captionLines, pad, captionBottomY, captionLineHeight);
    }
  }

  const texture = new THREE.CanvasTexture(canvas);
  texture.colorSpace = THREE.SRGBColorSpace;
  texture.anisotropy = 4;
  return texture;
}

function buildVideoTexture(webmSrc, mp4Src) {
  const video = document.createElement('video');
  // Duas fontes (webm/VP9 primeiro, mp4/H.264 como fallback) — mesmo
  // padrão já usado no vídeo de Bastidores (bastidores.webm/.mp4), o
  // navegador escolhe a primeira que conseguir decodificar.
  const sourceWebm = document.createElement('source');
  sourceWebm.src = webmSrc;
  sourceWebm.type = 'video/webm';
  const sourceMp4 = document.createElement('source');
  sourceMp4.src = mp4Src;
  sourceMp4.type = 'video/mp4';
  video.appendChild(sourceWebm);
  video.appendChild(sourceMp4);
  video.loop = true;
  video.muted = true;
  video.playsInline = true;
  video.autoplay = true;
  video.preload = 'auto';
  // Precisa estar no DOM (mesmo fora da tela) — a maioria dos navegadores
  // não decodifica frame nenhum de um <video> nunca anexado ao document,
  // mesmo chamando play() nele, então a textura three.js ficaria sempre
  // em branco. `.visually-hidden` (base.css) tira da tela sem impedir a
  // decodificação, igual já é usado pro grid original ficar acessível.
  video.className = 'visually-hidden';
  document.body.appendChild(video);
  video.play().catch(() => {});
  const texture = new THREE.VideoTexture(video);
  texture.colorSpace = THREE.SRGBColorSpace;
  return { texture, video };
}

// Monta o carrossel: N planes num círculo, o grupo inteiro gira no eixo Y
// (arrastar/botões mudam o ângulo alvo), cada card já nasce virado pra fora
// do centro — visual clássico de carrossel giratório em 3D.
async function createCarousel(container, items, { aspect, videoTexture = false, autoRotateSpeed = 0.12 } = {}) {
  const width = container.clientWidth;
  const height = container.clientHeight;

  const scene = new THREE.Scene();
  const camera = new THREE.PerspectiveCamera(38, width / Math.max(height, 1), 0.1, 200);

  const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
  renderer.setSize(width, height);
  renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
  container.appendChild(renderer.domElement);

  const group = new THREE.Group();
  scene.add(group);

  const n = items.length;
  const cardHeight = 4.4;
  const cardWidth = cardHeight * aspect;
  const textureSize = 780;

  // Raio do círculo dimensionado pra caber todos os cards lado a lado sem
  // sobrepor (perímetro necessário ÷ 2π), com uma folga de 25%. A câmera
  // fica sempre a uma distância fixa ALÉM do raio, então o enquadramento
  // funciona igual não importa quantos itens o carrossel tiver (5 DJs ou
  // 20 cases de portfólio).
  const radius = Math.max(4, (n * cardWidth * 1.25) / (2 * Math.PI));
  camera.position.set(0, 0, radius + 7.5);

  const videos = [];
  const meshes = await Promise.all(
    items.map(async (item, i) => {
      const angle = (i / n) * Math.PI * 2;
      const geometry = new THREE.PlaneGeometry(cardWidth, cardHeight);
      let texture;
      if (videoTexture) {
        const vt = buildVideoTexture(item.webmSrc, item.mp4Src);
        texture = vt.texture;
        videos.push(vt.video);
      } else {
        texture = await buildImageTexture(item, textureSize, Math.round(textureSize / aspect));
      }
      const material = new THREE.MeshBasicMaterial({ map: texture, transparent: true, toneMapped: false });
      const mesh = new THREE.Mesh(geometry, material);
      mesh.position.set(Math.sin(angle) * radius, 0, Math.cos(angle) * radius);
      mesh.rotation.y = angle;
      group.add(mesh);
      return mesh;
    })
  );

  const step = (Math.PI * 2) / n;
  let targetRotation = 0;
  let currentRotation = 0;
  let isDragging = false;
  let lastX = 0;
  let autoRotate = true;

  const getX = (e) => (e.touches ? e.touches[0].clientX : e.clientX);

  const onDown = (e) => {
    isDragging = true;
    autoRotate = false;
    lastX = getX(e);
    renderer.domElement.style.cursor = 'grabbing';
  };
  const onMove = (e) => {
    if (!isDragging) return;
    const x = getX(e);
    targetRotation += (x - lastX) * 0.006;
    lastX = x;
  };
  const onUp = () => {
    if (!isDragging) return;
    isDragging = false;
    renderer.domElement.style.cursor = 'grab';
    targetRotation = Math.round(targetRotation / step) * step;
  };

  renderer.domElement.style.touchAction = 'pan-y';
  renderer.domElement.style.cursor = 'grab';
  renderer.domElement.addEventListener('pointerdown', onDown);
  renderer.domElement.addEventListener('pointermove', onMove);
  window.addEventListener('pointerup', onUp);

  const next = () => {
    autoRotate = false;
    targetRotation += step;
  };
  const prev = () => {
    autoRotate = false;
    targetRotation -= step;
  };

  let rafId;
  let lastTime = performance.now();
  const animate = (time) => {
    const dt = Math.min((time - lastTime) / 1000, 0.05);
    lastTime = time;
    if (autoRotate) targetRotation -= dt * autoRotateSpeed;
    currentRotation += (targetRotation - currentRotation) * 0.08;
    group.rotation.y = currentRotation;

    meshes.forEach((mesh) => {
      const worldAngle = ((mesh.rotation.y + currentRotation) % (Math.PI * 2) + Math.PI * 2) % (Math.PI * 2);
      const facing = Math.cos(worldAngle);
      mesh.material.opacity = THREE.MathUtils.clamp(0.35 + facing * 0.65, 0.15, 1);
    });

    renderer.render(scene, camera);
    rafId = requestAnimationFrame(animate);
  };
  rafId = requestAnimationFrame(animate);

  const resize = () => {
    const w = container.clientWidth;
    const h = container.clientHeight;
    if (!w || !h) return;
    camera.aspect = w / h;
    camera.updateProjectionMatrix();
    renderer.setSize(w, h);
  };
  const resizeObserver = new ResizeObserver(resize);
  resizeObserver.observe(container);

  return {
    next,
    prev,
    destroy() {
      cancelAnimationFrame(rafId);
      resizeObserver.disconnect();
      window.removeEventListener('pointerup', onUp);
      renderer.dispose();
      videos.forEach((v) => v.pause());
      container.removeChild(renderer.domElement);
    },
  };
}

function extractPortfolioItems(track) {
  return [...track.querySelectorAll('.portfolio-card')].map((card) => {
    const img = card.querySelector('img');
    return {
      src: img.currentSrc || img.src,
      eyebrow: card.querySelector('.eyebrow')?.textContent ?? '',
      caption: card.querySelector('.portfolio-card-label strong')?.textContent ?? '',
    };
  });
}

function extractVideoItems(grid) {
  return [...grid.querySelectorAll('img.dj-roster-card')].map((img) => ({
    webmSrc: img.src.replace(/\.gif$/, '.webm'),
    mp4Src: img.src.replace(/\.gif$/, '.mp4'),
  }));
}

async function setupPortfolio(track) {
  const items = extractPortfolioItems(track);
  if (!items.length) return;

  const viewport = document.createElement('div');
  viewport.className = 'carousel-3d-viewport';
  viewport.setAttribute('aria-hidden', 'true');
  track.insertAdjacentElement('afterend', viewport);
  track.classList.add('visually-hidden');

  const carousel = await createCarousel(viewport, items, { aspect: 3 / 4 });

  document.getElementById('portfolio-prev')?.addEventListener('click', () => carousel.prev());
  document.getElementById('portfolio-next')?.addEventListener('click', () => carousel.next());
}

async function setupDjRoster(grid) {
  const items = extractVideoItems(grid);
  if (!items.length) return;

  const viewport = document.createElement('div');
  viewport.className = 'carousel-3d-viewport carousel-3d-viewport--wide';
  viewport.setAttribute('aria-hidden', 'true');
  grid.insertAdjacentElement('afterend', viewport);
  grid.classList.add('visually-hidden');

  const controls = document.createElement('div');
  controls.className = 'carousel-3d-controls';
  controls.innerHTML = `
    <button type="button" class="carousel-3d-btn" data-dir="prev" aria-label="DJ anterior">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="m15 18-6-6 6-6"/></svg>
    </button>
    <button type="button" class="carousel-3d-btn" data-dir="next" aria-label="Próximo DJ">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="m9 18 6-6-6-6"/></svg>
    </button>
  `;
  viewport.insertAdjacentElement('afterend', controls);

  const carousel = await createCarousel(viewport, items, { aspect: 15 / 7, videoTexture: true, autoRotateSpeed: 0.18 });

  controls.querySelector('[data-dir="prev"]').addEventListener('click', () => carousel.prev());
  controls.querySelector('[data-dir="next"]').addEventListener('click', () => carousel.next());
}

export async function initCarousel3D() {
  const portfolioTrack = document.getElementById('portfolio-track');
  const djGrid = document.querySelector('.dj-roster-grid');

  if (prefersReducedMotion() || !supportsWebGL()) {
    // Sem 3D: mantém o comportamento plano original (scroll-track com
    // botões prev/next) já implementado em portfolio-scroll.js. O grid de
    // DJs já funciona sozinho como grid estático, sem JS extra necessário.
    if (portfolioTrack) {
      import('./portfolio-scroll.js').then((m) => m.initPortfolioScroll());
    }
    return;
  }

  if (portfolioTrack) await setupPortfolio(portfolioTrack);
  if (djGrid) await setupDjRoster(djGrid);
}
