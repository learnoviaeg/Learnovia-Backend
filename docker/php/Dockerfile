FROM php:7.4-fpm-bullseye

ARG UID=1000
ARG UNAME=learnovia
ARG LARAVEL_ENV

RUN useradd --create-home --shell /bin/bash --uid $UID --user-group $UNAME && \
    usermod --lock $UNAME

RUN apt-get update -qq \
    && apt-get install -qqy --no-install-recommends tini default-mysql-client \
    && curl -sSLf \
        -o /usr/local/bin/install-php-extensions \
        https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions \
    && chmod +x /usr/local/bin/install-php-extensions  \
    && install-php-extensions bcmath bz2 calendar exif ffi gettext gd intl mysqli opcache pcntl \
                           pdo_mysql shmop sockets sysvmsg sysvsem sysvshm xsl zip mbstring \
    && php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/bin --filename=composer --version=1.10.1
RUN cd /usr/local/etc/php/conf.d/ && \
  echo 'memory_limit = -1' >> /usr/local/etc/php/conf.d/docker-php-ram-limit.ini
COPY ["docker/php/entrypoint", "/usr/local/bin"]


RUN chmod +x /usr/local/bin/entrypoint

WORKDIR /app

RUN chown -R $UNAME:$UNAME /app

USER $UNAME

COPY --chown=$UNAME:$UNAME ["composer.json", "composer.lock", "./"]

RUN composer install --no-autoloader --no-cache --no-scripts --prefer-dist --quiet $([ "$LARAVEL_ENV" = "PRODUCTION" ] && echo "--no-dev")

COPY --chown=$UNAME:$UNAME [".", "."]

ENTRYPOINT ["tini", "--", "entrypoint"]

CMD ["php-fpm"]
