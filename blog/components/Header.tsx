const SITE_URL = 'https://www.tremeterraaudiovisual.com.br';

export default function Header() {
  return (
    <header className="site-header">
      <div className="container">
        <a href={SITE_URL} className="logo">
          treme·terra
        </a>
        <nav>
          <a href={SITE_URL}>Voltar ao site</a>
        </nav>
      </div>
    </header>
  );
}
