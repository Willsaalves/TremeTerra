// Parallax/scrub sem GSAP/ScrollTrigger — a lib inteira (registrada global
// só pra uns poucos gsap.to()/ScrollTrigger.create()) pesava boa parte do
// bundle principal (a maior fatia do "JS não usado" do Lighthouse, já que
// só uma fração do código da lib roda de verdade). Progresso de scroll
// recalculado ao vivo via getBoundingClientRect a cada frame — isso também
// elimina a necessidade de recalcular medições depois do reflow de fonte
// (o problema que o ScrollTrigger.refresh() resolvia antes): não tem nada
// "cacheado" pra ficar desatualizado.

function clamp01(v) {
  return Math.max(0, Math.min(1, v));
}

// Replica os pares start/end do ScrollTrigger que este arquivo usava.
function computeProgress(el, start, end) {
  const rect = el.getBoundingClientRect();
  const vh = window.innerHeight;

  if (start === 'top top' && end === 'bottom top') {
    return clamp01(-rect.top / rect.height);
  }
  if (start === 'top top' && end === 'bottom bottom') {
    return clamp01(-rect.top / (rect.height - vh));
  }
  if (start === 'top bottom' && end === 'bottom top') {
    return clamp01((vh - rect.top) / (vh + rect.height));
  }
  return 0;
}

export function initScrollFx() {
  const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  if (prefersReduced) {
    // Sem parallax/scrub/autoplay no modo reduzido — o filme e o vídeo
    // do Innova Show ficam parados no poster até o usuário decidir tocar.
    const filmVideo = document.querySelector('.film-video');
    if (filmVideo) filmVideo.setAttribute('controls', '');
    return;
  }

  // Vídeo do Innova Show: toca assim que a página carrega, sem esperar o
  // usuário rolar até a seção — prioridade em UX sobre economia de rede.
  const showcaseVideo = document.querySelector('.showcase-visual-video');
  if (showcaseVideo) showcaseVideo.play().catch(() => {});

  // Cada efeito é { el, start, end, apply(progress) }. Um único loop de
  // rAF, ligado só enquanto o usuário rola, computa e aplica tudo — evita
  // vários listeners de scroll concorrentes e agrupa leitura (rect) e
  // escrita (style) num único frame, o que é exatamente a recomendação
  // padrão pra evitar "reflow forçado". Aplicação direta (sem lerp): os
  // próprios eventos de scroll já disparam com frequência alta o
  // suficiente pra parecer fluido, sem precisar de um loop de rAF
  // rodando o tempo todo em segundo plano.
  const effects = [];
  const addParallax = (el, { start, end, apply }) => {
    if (!el) return;
    effects.push({ el, start, end, apply });
  };

  const hero = document.querySelector('.hero');
  if (hero) {
    const g1 = hero.querySelector('.hero-glow .g1');
    const g2 = hero.querySelector('.hero-glow .g2');
    addParallax(g1, {
      start: 'top top',
      end: 'bottom top',
      apply: (p, el) => { el.style.transform = `translate3d(${-8 * p}%, ${28 * p}%, 0)`; },
    });
    addParallax(g2, {
      start: 'top top',
      end: 'bottom top',
      apply: (p, el) => { el.style.transform = `translate3d(${12 * p}%, ${-18 * p}%, 0)`; },
    });
  }

  const showcase = document.querySelector('.showcase');
  const visual = document.querySelector('.showcase-visual');
  if (showcase && visual) {
    addParallax(visual, {
      start: 'top bottom',
      end: 'bottom top',
      apply: (p, el) => {
        const y = 6 - p * 12;
        const rot = -1.2 + p * 2.4;
        el.style.transform = `translate3d(0, ${y}%, 0) rotate(${rot}deg)`;
      },
    });
  }

  const ctaFinal = document.querySelector('.cta-final');
  if (ctaFinal) {
    const g1 = ctaFinal.querySelector('.cta-glow .g1');
    const g2 = ctaFinal.querySelector('.cta-glow .g2');
    addParallax(g1, {
      start: 'top bottom',
      end: 'bottom top',
      apply: (p, el) => { el.style.transform = `translate3d(0, ${-22 * p}%, 0)`; },
    });
    addParallax(g2, {
      start: 'top bottom',
      end: 'bottom top',
      apply: (p, el) => { el.style.transform = `translate3d(0, ${22 * p}%, 0)`; },
    });
  }

  // Vídeo de Bastidores: no desktop, tempo amarrado 1:1 ao progresso do
  // scroll dentro da seção (pin + scrub cinematográfico) — desce o
  // scroll, o vídeo avança; sobe, o vídeo volta. No mobile, a seção não
  // é mais fixada em tela cheia (ver film.css): o vídeo toca sozinho em
  // loop normal, igual o do Innova Show, evitando o efeito de "área
  // escura enorme" que o pin+scrub causava numa tela bem mais alta que
  // larga.
  const film = document.querySelector('.film');
  const filmVideo = document.querySelector('.film-video');
  const isWideScreen = window.matchMedia('(min-width: 641px)').matches;

  if (film && filmVideo && isWideScreen) {
    const isReady = () => Number.isFinite(filmVideo.duration) && filmVideo.duration > 0;
    let seekable = isReady();
    if (!seekable) {
      filmVideo.addEventListener('loadedmetadata', () => { seekable = isReady(); }, { once: true });
    }

    // "Aquece" o carregamento: o scrub amarra `currentTime` ao scroll
    // pixel a pixel — se o navegador só tiver bufferizado um trecho
    // pequeno à frente da posição atual, rolar até perto do fim da
    // seção pode pedir um trecho de bytes que ainda não chegou, e o
    // quadro fica "travado" escuro até o fetch terminar (lido como "o
    // vídeo não chega a terminar, sobra scroll com tela preta"). Como o
    // arquivo já foi reduzido pra poucos MB, um fetch() explícito do
    // arquivo inteiro garante que ele fique no cache HTTP do navegador
    // (Cache-Control: public, max-age=2592000 na resposta 200) antes do
    // usuário rolar pela seção inteira — daí em diante toda mudança de
    // currentTime é decodificada a partir do cache local.
    //
    // IMPORTANTE: isso precisa disparar sempre, independente de
    // `seekable` já estar `true` — com preload="auto" o navegador às
    // vezes já sabe a duração (metadata carregada) bem antes de ter
    // baixado o arquivo inteiro, e `seekable` vira `true` cedo demais;
    // gatear esse fetch por `!seekable` (como antes) fazia o
    // pré-carregamento nunca disparar nesses casos, deixando o vídeo
    // sem o arquivo completo em cache mesmo assim.
    let prefetched = false;
    const warmUpVideo = () => {
      if (prefetched) return;
      prefetched = true;
      if (!seekable) filmVideo.load();
      const source = filmVideo.querySelector('source[type="video/mp4"]');
      if (source) fetch(source.src).catch(() => {});
    };

    // Só dispara isso quando a seção estiver perto de aparecer (não no
    // carregamento da página) — .film fica bem abaixo da dobra, e forçar
    // o carregamento do vídeo assim que a Home abre pesaria no payload
    // de rede de quem nunca rola até lá.
    if ('IntersectionObserver' in window) {
      const filmObserver = new IntersectionObserver(
        (entries) => {
          if (entries.some((entry) => entry.isIntersecting)) {
            warmUpVideo();
            filmObserver.disconnect();
          }
        },
        { rootMargin: '600px 0px' }
      );
      filmObserver.observe(film);
    } else {
      warmUpVideo();
    }

    effects.push({
      el: film,
      start: 'top top',
      end: 'bottom bottom',
      apply: (p) => {
        if (!seekable) return;
        filmVideo.currentTime = p * filmVideo.duration;
      },
    });
  } else if (filmVideo) {
    // Mobile: bloco normal (não fixado em tela cheia), mas ainda assim
    // vídeo pesado (2,7-4MB) — só toca quando a seção chega perto da
    // tela, evitando competir por banda com o resto da página logo no
    // carregamento (diferente do Innova Show, que toca imediatamente
    // por pedido explícito).
    if ('IntersectionObserver' in window) {
      const filmObserver = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              filmVideo.play().catch(() => {});
              filmObserver.unobserve(entry.target);
            }
          });
        },
        { rootMargin: '400px 0px' }
      );
      filmObserver.observe(filmVideo);
    } else {
      filmVideo.play().catch(() => {});
    }
  }

  if (!effects.length) return;

  let ticking = false;
  const tick = () => {
    effects.forEach(({ el, start, end, apply }) => {
      apply(computeProgress(el, start, end), el);
    });
    ticking = false;
  };

  const requestTick = () => {
    if (!ticking) {
      ticking = true;
      requestAnimationFrame(tick);
    }
  };

  tick();
  window.addEventListener('scroll', requestTick, { passive: true });
  window.addEventListener('resize', requestTick);
}
