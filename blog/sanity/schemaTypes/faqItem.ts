import { defineField, defineType } from 'sanity';

export const faqItem = defineType({
  name: 'faqItem',
  title: 'Pergunta frequente',
  type: 'object',
  fields: [
    defineField({ name: 'question', title: 'Pergunta', type: 'string', validation: (r) => r.required() }),
    defineField({ name: 'answer', title: 'Resposta', type: 'text', rows: 3, validation: (r) => r.required() }),
  ],
  preview: {
    select: { title: 'question' },
  },
});
