FROM php:8.1-cli

RUN apt-get update

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

# zip
RUN apt-get install -y libzip-dev zlib1g-dev zip \
  && docker-php-ext-install zip

RUN rm -rf /var/lib/apt/lists/*

# php.ini
ADD .docker/php/docker-php.ini /usr/local/etc/php/conf.d/docker-php-enable-jit.ini
ADD .docker/php/docker-php-disable-assertions.ini /usr/local/etc/php/conf.d/docker-php-disable-assertions.ini
ADD .docker/php/docker-php-enable-jit.ini /usr/local/etc/php/conf.d/docker-php-enable-jit.ini

RUN apt-get clean

COPY ./composer.json /app/
COPY ./metrics.json /app/
COPY ./index.php /app/
COPY ./src /app/src/
COPY ./packages /app/packages/
COPY ./public /app/public/

WORKDIR /app

RUN composer install --no-interaction

EXPOSE 8080

CMD [ "php", "./index.php" ]
