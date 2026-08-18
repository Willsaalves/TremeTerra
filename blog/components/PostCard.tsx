import Link from 'next/link';

const CATEGORY_LABELS: Record<string, string> = {
  locacao: 'Locação de equipamentos',
  corporativo: 'Eventos corporativos',
  social: 'Casamentos e formaturas',
  producao: 'Produção e shows',
};

export type PostListItem = {
  _id: string;
  title: string;
  slug: string;
  category?: string;
  seoDescription?: string;
  directAnswer?: string;
};

export default function PostCard({ post }: { post: PostListItem }) {
  return (
    <Link href={`/${post.slug}`} className="post-card">
      {post.category && <span className="category">{CATEGORY_LABELS[post.category] ?? post.category}</span>}
      <h2>{post.title}</h2>
      <p>{post.seoDescription ?? post.directAnswer}</p>
    </Link>
  );
}
