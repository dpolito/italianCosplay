FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpng-dev \
    libicu-dev \
    libonig-dev \
    unzip \
    git && \
    docker-php-ext-install pdo_mysql mysqli zip gd intl opcache && \
    a2enmod rewrite headers && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

RUN apt-get update && apt-get install -y \
    libjpeg62-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libfreetype6-dev

RUN docker-php-ext-configure gd \
    --with-jpeg \
    --with-webp \
    --with-freetype

RUN docker-php-ext-install gd

# Copia configurazione personalizzata di Apache
COPY apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php-uploads.ini /usr/local/etc/php/conf.d/zz-italian-cosplay-uploads.ini

# Imposta il proprietario della directory di lavoro per Apache (facoltativo ma buona pratica)
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

WORKDIR /var/www/html

EXPOSE 80
