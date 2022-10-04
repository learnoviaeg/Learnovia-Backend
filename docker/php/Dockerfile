FROM dmzci/php

MAINTAINER Learnovia

ARG UID=1000
ARG UNAME=learnovia
ARG LARAVEL_ENV
ENV LARAVEL_ENV=$LARAVEL_ENV

RUN useradd --create-home --shell /bin/bash --uid $UID --user-group $UNAME \
    && usermod --lock $UNAME

RUN if [ "$LARAVEL_ENV" = "PRODUCTION" ]; then \
       mv $PHP_INI_DIR/php.ini-production $PHP_INI_DIR/php.ini \
       ; \
    else \
       mv $PHP_INI_DIR/php.ini-development $PHP_INI_DIR/php.ini \
       && docker-php-ext-enable xdebug \
       && echo "xdebug.mode=off" >> $PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini \
       && echo "xdebug.cli_color=2" >> $PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini \
       ; \
    fi

WORKDIR /app

RUN chown -R $UNAME:$UNAME /app

USER $UNAME

COPY --chown=$UNAME:$UNAME ["package.json", "./"]

RUN if [ "$LARAVEL_ENV" != "PRODUCTION" ] ; then yarn install ; fi

COPY --chown=$UNAME:$UNAME ["composer.json", "composer.lock", "./"]

RUN composer install --no-autoloader --no-cache --no-scripts --prefer-dist --quiet $([ "$LARAVEL_ENV" = "PRODUCTION" ] && echo "--no-dev")

COPY --chown=$UNAME:$UNAME [".", "."]

ENTRYPOINT ["tini", "--", "entrypoint"]

CMD ["php-fpm"]
