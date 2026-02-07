# -----------------------------
# Composerバイナリ取得用ステージ
# -----------------------------
FROM composer:2.8 AS composer_bin

# -----------------------------
# Node.js + pnpm取得用ステージ
# -----------------------------
FROM node:22-bookworm-slim AS node_bin

# pnpmをグローバルに有効化
# corepackを使うことでpnpmのバージョン管理をNodeに任せる
RUN corepack enable

# -----------------------------
# PHP-FPM 本体
# web系は基本fpmを使う
# bookwormはdebian系になる
# -----------------------------
FROM php:8.3-fpm-bookworm

# -----------------------------
# 環境変数
# rootでComposerを使っても警告を出さない
# Composerのキャッシュ場所を固定して権限問題を回避
# -----------------------------
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/composer

WORKDIR /var/www/html

# -----------------------------
# パッケージ & PHP拡張 インストール
# -----------------------------
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
    git \
    unzip \
    ca-certificates \
    procps \
    libzip-dev \
    libicu-dev \
    ; \
    \
    docker-php-ext-install -j"$(nproc)" \
    intl \
    pdo_mysql \
    bcmath \
    pcntl \
    zip \
    ; \
    \
    apt-get clean; \
    rm -rf /var/lib/apt/lists/*

# -----------------------------
# Composer / Node / pnpm 配置
# -----------------------------
# Composer実行ファイルのみコピー
COPY --from=composer_bin /usr/bin/composer /usr/local/bin/composer

# Node.js 一式（node / npm / corepack）を丸ごとコピー
COPY --from=node_bin /usr/local/ /usr/local/

# pnpm を有効化（必要ならバージョン固定も）
RUN set -eux; \
    corepack enable; \
    corepack prepare pnpm@9.15.1 --activate

# -----------------------------
# 非rootユーザー作成（開発用）
# -----------------------------
# ホストのUID/GIDに合わせられるよう引数化
ARG UID=1000
ARG GID=1000

# - セキュリティ向上
# - ボリュームマウント時の権限トラブル防止
RUN set -eux; \
    groupadd -g "${GID}" app; \
    useradd -m -u "${UID}" -g "${GID}" -s /bin/sh app; \
    \
    mkdir -p /composer; \
    chown -R app:app /var/www/html /composer

# 以降は非rootユーザーで実行
USER app

