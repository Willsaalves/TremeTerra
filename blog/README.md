# Blog Treme Terra Audiovisual

Blog em Next.js (App Router) com [Sanity](https://www.sanity.io/) como CMS —
o admin publica posts pelo painel em `/studio`, sem precisar mexer em código
nem pedir deploy pra cada post novo.

Este projeto é **separado** do site principal (`tremeterraaudiovisual.com.br`,
que continua rodando em PHP/Docker no Render). O blog vive num subdomínio
próprio (`blog.tremeterraaudiovisual.com.br`) hospedado na
[Vercel](https://vercel.com/).

## Por que essa arquitetura

- **Vercel é feito pra Next.js** — deploy automático a cada push, preview de
  cada PR, CDN global, zero configuração de servidor.
- **Sanity Studio embutido** — o painel de administração roda dentro do
  próprio Next.js (`/studio`), então não existe um segundo serviço pra
  hospedar ou manter no ar. O conteúdo em si fica no banco gerenciado do
  Sanity (plano gratuito cobre bem o volume de um blog institucional).
- **Subdomínio em vez de `/blog`** — colocar o blog sob
  `tremeterraaudiovisual.com.br/blog` exigiria um proxy na frente do Render
  (que serve o resto do site) roteando só `/blog` pra Vercel. Isso é possível
  (ex: com um Cloudflare Worker), mas adiciona uma peça de infra a mais pra
  manter. Um subdomínio (`blog.tremeterraaudiovisual.com.br`) aponta direto
  pra Vercel via DNS, sem depender do Render pra nada — SEO fica quase tão
  bom quanto um path, com uma pequena diferença de autoridade de domínio que
  não costuma ser decisiva pra um blog de apoio.

## Passo a passo pra colocar no ar

### 1. Criar o projeto no Sanity (uma vez só)

```bash
cd blog
npx sanity@latest init
```

- Escolha "Create new project", dê um nome (ex: "Treme Terra Blog").
- Dataset: aceite o padrão `production`.
- Quando perguntar sobre template/schema, escolha "Clean project" — o schema
  já está pronto em `sanity/schemaTypes/`.
- Ao final, o comando imprime o **Project ID** — copie, você vai precisar
  dele no passo 3.

### 2. Rodar localmente (opcional, pra conferir antes de publicar)

```bash
cp .env.example .env.local
# preencha NEXT_PUBLIC_SANITY_PROJECT_ID com o ID do passo 1
npm install
npm run dev
```

- Blog: http://localhost:3000
- Painel do admin: http://localhost:3000/studio (primeiro acesso pede login
  com a conta Sanity/Google usada no `sanity init`)

### 3. Deploy na Vercel

1. Entre em [vercel.com](https://vercel.com/) e conecte a conta GitHub que
   tem acesso a este repositório (`Willsaalves/TremeTerra`).
2. "Add New… → Project" → selecione o repositório `TremeTerra`.
3. Em **Root Directory**, aponte para `blog` (importante — o repositório tem
   o site principal na raiz e o blog nesta subpasta).
4. Framework Preset: a Vercel detecta "Next.js" automaticamente.
5. Em **Environment Variables**, adicione (mesmos nomes do `.env.example`):
   - `NEXT_PUBLIC_SANITY_PROJECT_ID` → o ID copiado no passo 1
   - `NEXT_PUBLIC_SANITY_DATASET` → `production`
   - `NEXT_PUBLIC_SITE_URL` → `https://blog.tremeterraaudiovisual.com.br`
6. Clique em **Deploy**. A Vercel builda e publica numa URL temporária tipo
   `tremeterra-blog.vercel.app` — confirme que abre antes de seguir.

### 4. Apontar o subdomínio

1. No projeto da Vercel: **Settings → Domains → Add** →
   `blog.tremeterraaudiovisual.com.br`.
2. A Vercel mostra um registro DNS tipo:
   ```
   CNAME  blog  cname.vercel-dns.com.
   ```
3. No painel de DNS onde o domínio `tremeterraaudiovisual.com.br` está
   registrado (Registro.br, Cloudflare, etc. — quem administra hoje o DNS do
   domínio principal), crie esse registro CNAME.
4. Depois da propagação (minutos a poucas horas), a Vercel emite o
   certificado HTTPS automaticamente e o subdomínio fica no ar.

### 5. Publicar o primeiro post

- Acesse `https://blog.tremeterraaudiovisual.com.br/studio`.
- Clique em "Post do blog → Create new".
- Preencha título, categoria, meta description, resposta direta (opcional,
  ajuda em buscas por IA), imagem de capa, conteúdo e FAQ (opcional).
- Clique em **Publish** (não só "Save" — no Sanity, "Publish" é o que torna o
  conteúdo visível no site).
- O post aparece no blog em até 60 segundos (o cache de página é revalidado
  automaticamente).

## Estrutura do projeto

```
blog/
├─ app/
│  ├─ layout.tsx              ← shell HTML raiz (fontes, metadata base)
│  ├─ (site)/                 ← grupo de rotas do blog público
│  │  ├─ layout.tsx           ← header/footer do blog
│  │  ├─ page.tsx             ← listagem de posts
│  │  └─ [slug]/page.tsx      ← página de um post (SEO + schema.org)
│  └─ studio/[[...tool]]/     ← painel de admin (Sanity Studio embutido)
├─ sanity/
│  ├─ schemaTypes/            ← modelo de dados (post, FAQ)
│  └─ lib/                    ← client, queries GROQ, helper de imagem
├─ components/                ← Header, Footer, PostCard
└─ sanity.config.ts           ← configuração do Studio
```

## Regras de SEO/GEO já aplicadas no schema

Seguindo as regras globais do documento de conteúdo do cliente:

- 1 H1 por página (`title` do post).
- Title ≤60 e meta description ≤160 caracteres, com aviso no Studio se
  passar do limite.
- Canonical automático por post (`/{slug}`).
- Breadcrumb visual + `BreadcrumbList` schema (`Home > Blog > Post`).
- `BlogPosting` + `FAQPage` (quando o post tem perguntas) em JSON-LD.
- Imagens com `alt` obrigatório.
- Campo "Resposta direta" — atende a regra GEO de resposta objetiva nas
  primeiras linhas, pra IA de busca conseguir citar o conteúdo direto.

## O que falta pra ter conteúdo

Este projeto sobe **sem nenhum post** — a página inicial mostra um estado
"em breve" até o primeiro post ser publicado pelo Studio. Os 10 temas de
guia/GEO mapeados no documento de conteúdo do cliente ainda não têm texto
definido; quando os temas forem definidos, o admin cria os posts direto pelo
`/studio`, sem precisar de deploy novo.
