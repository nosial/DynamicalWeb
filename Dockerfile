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

ARG PHP_VERSION=8.3

# Build WebsocketServer
FROM rust:latest AS wss-builder

WORKDIR /tmp/wss
RUN apt-get update && apt-get install -y --no-install-recommends git && rm -rf /var/lib/apt/lists/*
RUN git clone --recurse-submodules "https://github.com/nosial/WebsocketServer" . && \
    cargo build --release && cp target/release/websocket-server /usr/bin/wss


# Build DynamicalWeb NCC Package
FROM ghcr.io/nosial/ncc:latest AS dw-builder

WORKDIR /tmp/dw
COPY . .
RUN ncc build --configuration release

# Production Image
FROM ghcr.io/nosial/ncc:fpm

ARG PHP_VERSION
LABEL maintainer="Netkas <netkas@nosial.net>"
LABEL org.opencontainers.image.title="DynamicalWeb"
LABEL org.opencontainers.image.version="1.0.0"
LABEL org.opencontainers.image.description="Base image for PHP web applications with ncc, nginx, and WebsocketServer"
LABEL org.opencontainers.image.url="https://github.com/nosial/DynamicalWeb"
LABEL org.opencontainers.image.licenses="MIT"
LABEL ncc.package="net.nosial.dynamicalweb"

# Install runtime dependencies
RUN apt-get update && apt-get install -y --no-install-recommends nginx supervisor && rm -rf /var/lib/apt/lists/*

# Copy WebsocketServer binary
COPY --from=wss-builder /usr/bin/wss /usr/bin/wss

# Install DynamicalWeb ncc package
COPY --from=dw-builder /tmp/dw/target/release/net.nosial.dynamicalweb.ncc /tmp/dw.ncc
RUN ncc install --package="/tmp/dw.ncc" -y && rm /tmp/dw.ncc

# Create required directories
RUN mkdir -p /var/www/html /var/log/nginx /var/log/supervisor

# Remove default nginx site
RUN rm -f /etc/nginx/sites-enabled/default

# Copy configuration files
COPY nginx.conf /etc/nginx/conf.d/default.conf
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy default application files
COPY index.php /var/www/html/index.php
COPY websocket.php /var/www/html/websocket.php

# Copy and configure entrypoint
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

WORKDIR /var/www/html
EXPOSE 8080
ENTRYPOINT ["docker-entrypoint.sh"]
