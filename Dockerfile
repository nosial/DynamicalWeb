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
LABEL maintainer="Netkas <netkas@nosial.net>"
LABEL org.opencontainers.image.title="DynamicalWeb"
LABEL org.opencontainers.image.version="1.0.0"
LABEL org.opencontainers.image.description="Base image for PHP web applications with ncc, nginx, and WebsocketServer"
LABEL org.opencontainers.image.url="https://github.com/nosial/DynamicalWeb"
LABEL org.opencontainers.image.licenses="MIT"
LABEL ncc.package="net.nosial.dynamicalweb"
ENV LOGLIB_CONSOLE_ENABLED=false
ENV LOGLIB_UDP_ENABLED=true
ENV LOGLIB_UDP_HOST=127.0.0.1
ENV LOGLIB_UDP_PORT=9003
ENV LOGLIB_UDP_TRACE_FORMAT=full

# Install runtime dependencies and extensions
RUN apt update && apt install -y --no-install-recommends nginx supervisor ca-certificates curl && rm -rf /var/lib/apt/lists/*
RUN install-php-extensions apcu sockets \
    && echo "apc.enable_cli=1" >> /usr/local/etc/php/conf.d/docker-php-ext-apcu.ini

# download pre-built WebsocketServer binary & logger
RUN curl -sL "https://github.com/nosial/WebsocketServer/releases/latest/download/websocket-server-linux-x86_64" -o /usr/bin/wss && \
    curl -sL "https://github.com/nosial/LogLib2Server/releases/latest/download/LogLib2Server-linux-x86_64" -o /usr/bin/ll2s && \
    chmod +x /usr/bin/wss && chmod +x /usr/bin/ll2s  && apt purge -y --auto-remove curl


# Create required directories
RUN mkdir -p /var/www/html /var/log/nginx /var/log/supervisor

# Remove default nginx site
RUN rm -f /etc/nginx/sites-enabled/default

# Copy configuration files
COPY nginx.conf /etc/nginx/conf.d/default.conf
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy default application files
COPY index.php /var/www/html/index.php

# Copy and configure entrypoint
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

WORKDIR /var/www/html
EXPOSE 8080
ENTRYPOINT ["docker-entrypoint.sh"]
