# syntax=docker/dockerfile:1
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

# Build extensions with the existing ncc FPM image and export the runtime.
ARG NCC_FPM_IMAGE=ghcr.io/nosial/ncc:fpm
ARG DEBIAN_VERSION=trixie
FROM ${NCC_FPM_IMAGE} AS extensions
RUN install-php-extensions apcu sockets memcached && \
    echo "apc.enable_cli=1" > /usr/local/etc/php/conf.d/dynamicalweb-apcu.ini && \
    ncc-runtime-dependencies > /tmp/runtime-packages.txt

FROM extensions AS downloads
RUN mkdir -p /runtime-bin && \
    curl --fail --show-error --location --retry 3 \
        "https://github.com/nosial/WebsocketServer/releases/latest/download/websocket-server-linux-x86_64" -o /runtime-bin/wss && \
    curl --fail --show-error --location --retry 3 \
        "https://github.com/nosial/LogLib2Server/releases/latest/download/LogLib2Server-linux-x86_64" -o /runtime-bin/ll2s && \
    chmod +x /runtime-bin/wss /runtime-bin/ll2s && \
    ncc-runtime-dependencies /runtime-bin >> /tmp/runtime-packages.txt

# Keep the compiler toolchain out of the final image without another registry tag.
FROM extensions AS runtime-files
RUN mkdir -p /opt/php && cp -a /usr/local/. /opt/php/ && \
    rm -rf /opt/php/include /opt/php/lib/php/build && \
    rm -f /opt/php/bin/phpize /opt/php/bin/pear /opt/php/bin/peardev \
        /opt/php/bin/pecl /opt/php/bin/install-php-extensions \
        /opt/php/bin/docker-php-ext-* /opt/php/bin/docker-php-source

# Match the Debian release used by the ncc FPM image.
FROM debian:${DEBIAN_VERSION}-slim AS production
ENV PHP_INI_DIR=/usr/local/etc/php
COPY --from=runtime-files /opt/php/ /usr/local/
COPY --from=extensions /etc/apt/preferences.d/no-debian-php /etc/apt/preferences.d/no-debian-php

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

RUN --mount=type=bind,from=downloads,source=/tmp/runtime-packages.txt,target=/tmp/runtime-packages.txt \
    apt-get update && apt-get install -y --no-install-recommends \
        nginx supervisor memcached ca-certificates curl wget \
        $(cat /tmp/runtime-packages.txt) \
    && rm -f /etc/nginx/sites-enabled/default \
    && mkdir -p /var/www/html /var/log/nginx /var/log/supervisor \
    && chown www-data:www-data /var/www/html && chmod 1777 /var/www/html \
    && rm -rf /var/lib/apt/lists/*

COPY --from=downloads /runtime-bin/ /usr/bin/

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