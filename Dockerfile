#
# DynamicalWeb Base Docker Image
#
# Provides a complete PHP web application environment with:
#   - ncc package manager (with install-php-extensions)
#   - PHP-FPM
#   - Nginx with WebSocket reverse proxy
#   - WebsocketServer (Rust) for WebSocket-to-TCP bridge
#   - Supervisor for process management
#   - DynamicalWeb pre-installed as an ncc package
#
# Usage:
#   FROM ghcr.io/nosial/dynamicalweb:master AS base
#   RUN install-php-extensions your-extension
#   COPY your-package .
#   RUN ncc build --configuration release
#   RUN ncc install --package="target/release/your-package.ncc" -y
#   COPY index.php /var/www/html/index.php
#

# Production Image
FROM ghcr.io/nosial/ncc:fpm

ARG PHP_VERSION=8.5

LABEL maintainer="Netkas <netkas@nosial.net>" \
      org.opencontainers.image.title="DynamicalWeb" \
      org.opencontainers.image.version="1.0.0" \
      org.opencontainers.image.description="Base image for PHP web applications with ncc, nginx, memcached, and WebsocketServer" \
      org.opencontainers.image.url="https://github.com/nosial/DynamicalWeb" \
      org.opencontainers.image.licenses="MIT" \
      ncc.package="net.nosial.dynamicalweb"

ENV LOGLIB_CONSOLE_ENABLED=false \
    LOGLIB_UDP_ENABLED=true \
    LOGLIB_UDP_HOST=127.0.0.1 \
    LOGLIB_UDP_PORT=9003 \
    LOGLIB_UDP_TRACE_FORMAT=full \
    MEMCACHED_ENABLED=1 \
    MEMCACHED_HOST=127.0.0.1 \
    MEMCACHED_PORT=11211 \
    MEMCACHED_SESSION_TTL=3600

RUN apt update && apt install -y --no-install-recommends \
        nginx supervisor memcached ca-certificates curl nodejs npm \
    && rm -f /etc/nginx/sites-enabled/default \
    && mkdir -p /var/www/html /var/log/nginx /var/log/supervisor \
    && rm -rf /var/lib/apt/lists/*

RUN install-php-extensions apcu sockets memcached \
    && echo "apc.enable_cli=1" >> /usr/local/etc/php/conf.d/docker-php-ext-apcu.ini

RUN curl -sL "https://github.com/nosial/WebsocketServer/releases/latest/download/websocket-server-linux-x86_64" -o /usr/bin/wss \
    && curl -sL "https://github.com/nosial/LogLib2Server/releases/latest/download/LogLib2Server-linux-x86_64" -o /usr/bin/ll2s \
    && chmod +x /usr/bin/wss /usr/bin/ll2s \
    && apt-get purge -y curl && apt-get autoremove -y && rm -rf /var/lib/apt/lists/*

COPY nginx.conf /etc/nginx/conf.d/default.conf
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY index.php /var/www/html/index.php
COPY docker-entrypoint.sh /usr/local/bin/

RUN chmod +x /usr/local/bin/docker-entrypoint.sh

WORKDIR /var/www/html
EXPOSE 8080
ENTRYPOINT ["docker-entrypoint.sh"]

HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=3 \
    CMD curl -f http://localhost:8080/dynaweb/health || exit 1