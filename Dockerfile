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
CMD php dist/seed-blog-posts.php && php -S 0.0.0.0:${PORT:-10000} -t dist dist/router.php
