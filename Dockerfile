FROM php:8.3.23-apache AS web

WORKDIR /var/www/html

ARG UNAME=www-hosted
ARG UID=1010
ARG GID=1010

RUN groupadd -g $GID -o $UNAME && \
    useradd -m -u $UID -g $GID -o -s /bin/bash $UNAME

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libsodium-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    libzip-dev \
    nodejs \
    npm \
    libpq-dev \
    build-essential \
    bash \
    wget


RUN apt-get clean && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite ssl && \
    docker-php-ext-install zip pdo pdo_pgsql bcmath sodium && \
    docker-php-ext-configure intl && \
    docker-php-ext-install intl

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf && \
    sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN echo "finished preparing the web php-8.3 image"

FROM web AS appsrc


WORKDIR /var/www/html

# Copy the source code
COPY . /var/www/html


RUN mkdir -p /var/www/html/bootstrap/cache && \
    chown -R $UID:$GID /var/www/html/ && \
    rm -rf /var/www/html/vendor && rm -f /var/www/html/composer.lock

USER $UNAME

# Run composer install
RUN composer install --no-dev --prefer-dist --optimize-autoloader

FROM appsrc AS deployment

#RUN npm i html-truncate && npm install && npm run build

EXPOSE 80 443

CMD ["apache2-foreground"]
