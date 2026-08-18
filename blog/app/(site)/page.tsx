import type { Metadata } from 'next';
import { client } from '@/sanity/lib/client';
import { postsListQuery } from '@/sanity/lib/queries';
import PostCard, { type PostListItem } from '@/components/PostCard';

export const revalidate = 60;

export const metadata: Metadata = {
  title: 'Blog',
  description:
    'Guias sobre som, iluminação, painel de LED, DJ e produção de eventos em São Paulo — pela Treme Terra Audiovisual.',
  alternates: { canonical: '/' },
};

export default async function BlogIndexPage() {
  const posts = await client.fetch<PostListItem[]>(postsListQuery);

  return (
    <>
      <section className="blog-hero">
        <div className="container">
          <p className="eyebrow">Blog Treme Terra</p>
          <h1>Guias práticos de som, luz e produção de eventos</h1>
          <p>Conteúdo pra te ajudar a planejar seu evento em São Paulo, direto de quem monta e opera todo dia.</p>
        </div>
      </section>

      <div className="container post-list">
        {posts.length === 0 ? (
          <div className="empty-state">
            <strong>Em breve, novos posts por aqui.</strong>
            Estamos preparando conteúdo sobre locação de som, iluminação, painel de LED, DJ e produção de eventos.
          </div>
        ) : (
          posts.map((post) => <PostCard key={post._id} post={post} />)
        )}
      </div>
    </>
  );
}
