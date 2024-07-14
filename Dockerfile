FROM php:8.1-cli

RUN apt-get update

# zip
#RUN apt-get install -y libzip-dev zlib1g-dev zip \
#  && docker-php-ext-install zip

# git
RUN apt-get install -y git

# composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer \
  && chmod 755 /usr/bin/composer

# pcntl
RUN docker-php-ext-configure pcntl --enable-pcntl \
  && docker-php-ext-install pcntl

# pecl/ev
RUN pecl install -o -f ev \
  && docker-php-ext-enable ev

# redis
#RUN pecl install -o -f redis \
#    &&  rm -rf /tmp/pear \
#    &&  docker-php-ext-enable redis

# mysqli
#RUN docker-php-ext-install mysqli pdo pdo_mysql \
#	&& docker-php-ext-enable mysqli pdo_mysql

# Install "curl", "libmemcached-dev", "libpq-dev", "libjpeg-dev", "libpng-dev", "libfreetype6-dev", "libssl-dev", "libmcrypt-dev"
RUN set -eux; \
    apt-get update; \
    apt-get upgrade -y; \
    apt-get install -y --no-install-recommends \
            curl \
            libmemcached-dev \
            libz-dev \
            libpq-dev \
            libjpeg-dev \
            libpng-dev \
            libfreetype6-dev \
            libssl-dev \
            libwebp-dev \
            libxpm-dev \
            libmcrypt-dev \
            libonig-dev; \
    rm -rf /var/lib/apt/lists/*

# Install the PHP gd library
#RUN set -eux; \
#    docker-php-ext-configure gd \
#            --prefix=/usr \
#            --with-jpeg \
#            --with-webp \
#            --with-xpm \
#            --with-freetype; \
#    docker-php-ext-install gd; \
#    php -r 'var_dump(gd_info());'

# php.ini
ADD .docker/php/docker-php.ini /usr/local/etc/php/conf.d/docker-php-enable-jit.ini
ADD .docker/php/docker-php-disable-assertions.ini /usr/local/etc/php/conf.d/docker-php-disable-assertions.ini
ADD .docker/php/docker-php-enable-jit.ini /usr/local/etc/php/conf.d/docker-php-enable-jit.ini

RUN apt-get clean

COPY ./composer.json /app/
COPY ./index.php /app/
COPY ./src /app/src/

WORKDIR /app

RUN composer install --no-interaction

EXPOSE 8080

CMD [ "php", "./index.php" ]
