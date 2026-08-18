import type { Metadata } from 'next';
import { notFound } from 'next/navigation';
import Image from 'next/image';
import { PortableText, type PortableTextBlock } from '@portabletext/react';
import { client } from '@/sanity/lib/client';
import { postBySlugQuery, postSlugsQuery } from '@/sanity/lib/queries';
import { urlForImage } from '@/sanity/lib/image';

export const revalidate = 60;

const SITE_URL = process.env.NEXT_PUBLIC_SITE_URL ?? 'https://blog.tremeterraaudiovisual.com.br';

type SanityImage = { asset?: { _ref: string }; alt?: string };

type Post = {
  _id: string;
  title: string;
  slug: string;
  category?: string;
  seoTitle?: string;
  seoDescription?: string;
  directAnswer?: string;
  coverImage?: SanityImage;
  body?: PortableTextBlock[];
  faqItems?: { question: string; answer: string }[];
  publishedAt?: string;
};

async function getPost(slug: string): Promise<Post | null> {
  return client.fetch<Post | null>(postBySlugQuery, { slug });
}

export async function generateStaticParams() {
  const slugs = await client.fetch<string[]>(postSlugsQuery);
  return slugs.map((slug) => ({ slug }));
}

export async function generateMetadata({ params }: { params: { slug: string } }): Promise<Metadata> {
  const post = await getPost(params.slug);
  if (!post) return {};

  const title = post.seoTitle ?? post.title;
  const description = post.seoDescription ?? post.directAnswer ?? '';
  const url = `${SITE_URL}/${post.slug}`;

  return {
    title,
    description,
    alternates: { canonical: url },
    openGraph: { title, description, url, type: 'article' },
    twitter: { card: 'summary_large_image', title, description },
  };
}

export default async function PostPage({ params }: { params: { slug: string } }) {
  const post = await getPost(params.slug);
  if (!post) notFound();

  const url = `${SITE_URL}/${post.slug}`;

  const breadcrumbSchema = {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: [
      { '@type': 'ListItem', position: 1, name: 'Home', item: 'https://www.tremeterraaudiovisual.com.br/' },
      { '@type': 'ListItem', position: 2, name: 'Blog', item: SITE_URL },
      { '@type': 'ListItem', position: 3, name: post.title, item: url },
    ],
  };

  const articleSchema = {
    '@context': 'https://schema.org',
    '@type': 'BlogPosting',
    headline: post.title,
    description: post.seoDescription ?? post.directAnswer,
    datePublished: post.publishedAt,
    author: { '@type': 'Organization', name: 'Treme Terra Audiovisual' },
    publisher: { '@type': 'Organization', name: 'Treme Terra Audiovisual' },
    mainEntityOfPage: url,
  };

  const faqSchema = post.faqItems?.length
    ? {
        '@context': 'https://schema.org',
        '@type': 'FAQPage',
        mainEntity: post.faqItems.map((item) => ({
          '@type': 'Question',
          name: item.question,
          acceptedAnswer: { '@type': 'Answer', text: item.answer },
        })),
      }
    : null;

  return (
    <article className="post">
      <div className="container">
        <nav className="breadcrumb" aria-label="Breadcrumb">
          <a href="https://www.tremeterraaudiovisual.com.br/">Home</a> / <a href="/">Blog</a> / {post.title}
        </nav>

        <p className="eyebrow">Blog Treme Terra</p>
        <h1>{post.title}</h1>

        {post.directAnswer && <p className="direct-answer">{post.directAnswer}</p>}

        {post.coverImage?.asset && (
          <div className="cover">
            <Image
              src={urlForImage(post.coverImage as any).width(1200).height(675).url()}
              alt={post.coverImage.alt ?? post.title}
              width={1200}
              height={675}
            />
          </div>
        )}

        <div className="prose">{post.body && <PortableText value={post.body} />}</div>

        {post.faqItems && post.faqItems.length > 0 && (
          <section className="faq">
            <h2>Perguntas frequentes</h2>
            {post.faqItems.map((item, i) => (
              <details className="faq-item" key={i}>
                <summary>{item.question}</summary>
                <p>{item.answer}</p>
              </details>
            ))}
          </section>
        )}
      </div>

      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbSchema) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(articleSchema) }} />
      {faqSchema && (
        <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqSchema) }} />
      )}
    </article>
  );
}
