FROM php:7.3

# Install OS dependencies
RUN apt-get update -y \
    && apt-get install -y libmcrypt-dev zlib1g-dev zip

# Install grpc PHP extension
RUN pecl install grpc
RUN echo "extension=grpc.so" >> /usr/local/etc/php/conf.d/docker-php-ext-grpc.ini

# Install mcrypt PHP extension
RUN pecl install mcrypt-1.0.2 grpc

# Install other PHP extensions
RUN docker-php-ext-install bcmath

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && mkdir /usr/src/sample

WORKDIR /usr/src/sample

COPY ./composer.* ./

# Install app dependencies
RUN composer install

COPY . .

ENTRYPOINT ["php"]

CMD ["-S", "0.0.0.0:3000", "-t", "public"]
