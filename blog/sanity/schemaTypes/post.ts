import { defineField, defineType } from 'sanity';

export const post = defineType({
  name: 'post',
  title: 'Post do blog',
  type: 'document',
  fields: [
    defineField({
      name: 'title',
      title: 'Título (H1)',
      type: 'string',
      validation: (r) => r.required(),
    }),
    defineField({
      name: 'slug',
      title: 'URL (slug)',
      type: 'slug',
      options: { source: 'title', maxLength: 96 },
      validation: (r) => r.required(),
    }),
    defineField({
      name: 'category',
      title: 'Categoria',
      type: 'string',
      options: {
        list: [
          { title: 'Locação de equipamentos', value: 'locacao' },
          { title: 'Eventos corporativos', value: 'corporativo' },
          { title: 'Casamentos e formaturas', value: 'social' },
          { title: 'Produção e shows', value: 'producao' },
        ],
      },
    }),
    defineField({
      name: 'seoTitle',
      title: 'Title tag (SEO, até 60 caracteres)',
      type: 'string',
      validation: (r) => r.max(60).warning('Acima de 60 caracteres pode cortar no Google'),
    }),
    defineField({
      name: 'seoDescription',
      title: 'Meta description (SEO, até 160 caracteres)',
      type: 'text',
      rows: 2,
      validation: (r) => r.max(160).warning('Acima de 160 caracteres pode cortar no Google'),
    }),
    defineField({
      name: 'directAnswer',
      title: 'Resposta direta (primeiras linhas do post — regra GEO)',
      type: 'text',
      rows: 3,
      description: 'Resposta objetiva à pergunta principal do post, nas primeiras 30–50 palavras — ajuda buscas por IA (GEO) a citar o conteúdo direto.',
    }),
    defineField({
      name: 'coverImage',
      title: 'Imagem de capa',
      type: 'image',
      options: { hotspot: true },
      fields: [
        defineField({ name: 'alt', title: 'Texto alternativo (alt)', type: 'string', validation: (r) => r.required() }),
      ],
    }),
    defineField({
      name: 'body',
      title: 'Conteúdo do post',
      type: 'array',
      of: [
        { type: 'block' },
        {
          type: 'image',
          options: { hotspot: true },
          fields: [{ name: 'alt', title: 'Texto alternativo (alt)', type: 'string' }],
        },
      ],
    }),
    defineField({
      name: 'faqItems',
      title: 'FAQ (perguntas frequentes do post)',
      type: 'array',
      of: [{ type: 'faqItem' }],
      description: 'Vira Schema.org FAQPage automaticamente. Inclua ao menos uma pergunta de intenção negativa quando fizer sentido (regra GEO).',
    }),
    defineField({
      name: 'publishedAt',
      title: 'Data de publicação',
      type: 'datetime',
    }),
  ],
  preview: {
    select: { title: 'title', media: 'coverImage', subtitle: 'category' },
  },
});
