FROM node:20-slim 

# corepackでpnpm有効化（Node20では公式推奨）
RUN corepack enable

# pnpm を有効化（必要ならバージョン固定も）
RUN set -eux; \
    corepack enable; \
    corepack prepare pnpm@9.15.1 --activate

WORKDIR /app