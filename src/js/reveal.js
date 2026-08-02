function wrapWord(word, index) {
  const wrap = document.createElement('span');
  wrap.className = 'word';
  const inner = document.createElement('span');
  inner.textContent = word;
  inner.style.transitionDelay = `${Math.min(index * 45, 480)}ms`;
  wrap.appendChild(inner);
  return wrap;
}

function splitNode(node, target, counter) {
  node.childNodes.forEach((child) => {
    if (child.nodeType === Node.TEXT_NODE) {
      const words = child.textContent.split(/(\s+)/).filter(Boolean);
      words.forEach((word) => {
        if (/^\s+$/.test(word)) {
          target.appendChild(document.createTextNode(word));
        } else {
          target.appendChild(wrapWord(word, counter.count++));
        }
      });
    } else if (child.tagName === 'BR') {
      target.appendChild(document.createElement('br'));
    } else {
      const clone = child.cloneNode(false);
      splitNode(child, clone, counter);
      target.appendChild(clone);
    }
  });
}

function splitWords(el) {
  const original = el.cloneNode(true);
  el.innerHTML = '';
  splitNode(original, el, { count: 0 });
}

export function initReveal() {
  const revealEls = document.querySelectorAll('[data-reveal], [data-split-reveal]');
  if (!revealEls.length) return;

  const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  document.querySelectorAll('[data-split-reveal]').forEach((el) => {
    if (!prefersReduced) splitWords(el);
  });

  if (prefersReduced) {
    revealEls.forEach((el) => el.classList.add('is-revealed'));
    return;
  }

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry, i) => {
        if (entry.isIntersecting) {
          const el = entry.target;
          const delay = el.closest('.tile-grid, .showcase-features')
            ? Array.from(el.parentElement.children).indexOf(el) * 90
            : 0;
          window.setTimeout(() => el.classList.add('is-revealed'), delay);
          observer.unobserve(el);
        }
      });
    },
    { threshold: 0.15, rootMargin: '0px 0px -8% 0px' }
  );

  revealEls.forEach((el) => observer.observe(el));
}
