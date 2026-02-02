FROM php:8.2-cli

LABEL authors="andrejessy"

# Instala dependências do sistema e extensões do PHP necessárias para Laravel
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    curl \
    && docker-php-ext-install pdo pdo_mysql zip gd mbstring bcmath

# Instala Composer globalmente
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Define diretório de trabalho
WORKDIR /var/www

# Copia apenas composer.json/lock primeiro para aproveitar cache
COPY composer.json composer.lock ./

# Instala dependências PHP sem rodar scripts do Laravel
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Agora copia o restante do projeto
COPY . .

# Roda os scripts do Laravel (package:discover etc.)
RUN composer run-script post-autoload-dump

# Garante permissões corretas
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Expõe a porta padrão do PHP Artisan serve
EXPOSE 8000

# Comando para subir o Laravel
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
