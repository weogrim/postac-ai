FROM alpine:3.23.4

ARG WWWUSER=1000
ARG WWWGROUP=1000
RUN addgroup -g ${WWWGROUP} dev && adduser -D -u ${WWWUSER} -G dev dev

WORKDIR /var/www/html

# Install system librarys
RUN apk --no-cache add \
    libxml2-dev \
    bash \
    curl \
    nginx \
    nano \
    supervisor \
    nodejs \
    npm \
    poppler-utils \
    libzip-dev \
    postgresql16-client

#Install PHP 8.5 and its modules
RUN apk --no-cache add \
    php85 \
    php85-ctype \
    php85-dom \
    php85-fileinfo \
    php85-fpm \
    php85-gd \
    php85-intl \
    php85-mbstring \
    php85-pgsql \
    php85-openssl \
    php85-phar \
    php85-session \
    php85-tokenizer \
    php85-iconv \
    php85-xml \
    php85-xmlreader \
    php85-simplexml \
    php85-xmlwriter \
    php85-pdo \
    php85-pdo_pgsql \
    php85-exif \
    php85-pecl-redis \
    php85-zip \
    php85-curl \
    php85-bcmath \
    postgresql-client

# Create symlink so programs depending on `php` function
RUN ln -s /usr/bin/php85 /usr/bin/php

# Copy configuration files, that needs to be root
COPY Docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY Docker/nginx_default.conf /etc/nginx/http.d/default.conf
COPY Docker/php.ini /etc/php85/conf.d/30_custom_php.ini

# Make sure files/folders needed by the processes are accessable
RUN mkdir "/var/log/supervisor"
RUN chown -R dev:dev /var/www/html /run /var/lib/nginx /var/log/nginx /var/log/php85 /var/log/supervisor

# Install SuperCronic (cron: https://github.com/aptible/supercronic)
COPY --chmod=0755 Docker/supercronic /usr/bin/supercronic
RUN chmod 0755 /usr/bin/supercronic

# Copy composer from official image
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Add application
COPY ./ /var/www/html/
RUN chown -R dev:dev /var/www/html/

COPY Docker/entrypoint.sh /usr/local/bin/
RUN chmod 0755 /usr/local/bin/entrypoint.sh
RUN chown dev:dev /usr/local/bin/entrypoint.sh

# -------------------- FROM THIS POINT WE ARE THE DEV
USER dev

# Expose the port nginx is reachable on
EXPOSE 8080

# Let supervisord start nginx & php-fpm
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

# Composer
RUN composer install --optimize-autoloader --no-interaction --no-dev

# NPM
RUN npm install
RUN npm run build

# Migrations and laravel commands, are run from entrypoint.sh
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

# Configure a healthcheck to validate that everything is up&running
HEALTHCHECK --timeout=10s CMD curl --silent --fail http://127.0.0.1:8080/up || exit 1
