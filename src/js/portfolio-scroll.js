export function initPortfolioScroll() {
  const track = document.getElementById('portfolio-track');
  const prev = document.getElementById('portfolio-prev');
  const next = document.getElementById('portfolio-next');
  if (!track || !prev || !next) return;

  const scrollByCard = (direction) => {
    const card = track.querySelector('.portfolio-card');
    const amount = card ? card.getBoundingClientRect().width + 24 : 320;
    track.scrollBy({ left: direction * amount, behavior: 'smooth' });
  };

  prev.addEventListener('click', () => scrollByCard(-1));
  next.addEventListener('click', () => scrollByCard(1));
}
