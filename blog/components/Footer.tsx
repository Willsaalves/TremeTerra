const SITE_URL = 'https://www.tremeterraaudiovisual.com.br';

export default function Footer() {
  return (
    <footer className="site-footer">
      <div className="container">
        <p>
          © {new Date().getFullYear()} Treme Terra Audiovisual — Grupo All Party ·{' '}
          <a href={`${SITE_URL}/#contato`}>Solicitar orçamento</a>
        </p>
      </div>
    </footer>
  );
}
