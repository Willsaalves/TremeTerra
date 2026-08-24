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
# upload_max_filesize/post_max_size acima do limite do app (5 MB) pra o
# próprio app ser a fonte de verdade do tamanho e devolver uma mensagem
# amigável — o padrão do PHP (2 MB) barrava imagens antes disso com erro cru.
CMD php dist/seed-blog-posts.php && php -d upload_max_filesize=8M -d post_max_size=12M -S 0.0.0.0:${PORT:-10000} -t dist dist/router.php
