# ---- build: compila os assets com Vite e gera dist/index.php ----
FROM node:20-slim AS build
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build:php

# ---- runtime: serve dist/ com o servidor embutido do PHP ----
FROM php:8.3-cli
RUN apt-get update \
    && apt-get install -y --no-install-recommends libcurl4-openssl-dev libsqlite3-dev pkg-config \
    && docker-php-ext-install curl pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /app
COPY --from=build /app/dist ./dist
EXPOSE 10000
# Config do PHP em produção:
# - display_errors=0 + log_errors=1: NUNCA mostrar avisos/erros do PHP na
#   página (em produção). Warnings impressos vazam pro usuário e ainda
#   quebram os header()/session_start() ("headers already sent").
# - upload_max_filesize/post_max_size com folga: o app é a fonte de verdade
#   do tamanho (5 MB por imagem); o post_max_size grande evita estourar o
#   POST quando o corpo tem várias imagens/URLs.
CMD php dist/seed-blog-posts.php && php \
    -d display_errors=0 -d log_errors=1 \
    -d upload_max_filesize=15M -d post_max_size=25M \
    -S 0.0.0.0:${PORT:-10000} -t dist dist/router.php
