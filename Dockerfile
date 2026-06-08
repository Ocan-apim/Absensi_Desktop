FROM php:8.2-apache

# 1. Install ekstensi database
RUN docker-php-ext-install mysqli pdo pdo_mysql

# 2. Copy semua file projek ke folder server Apache
COPY . /var/www/html/

# 3. FIX ERROR MPM: Matikan mpm_event, nyalakan mpm_prefork
RUN a2dismod mpm_event || true && a2enmod mpm_prefork

# 4. Ubah port Apache dari 80 ke 8080 (sesuai standar Railway)
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
    && sed -i 's/:80/:8080/g' /etc/apache2/sites-available/000-default.conf

EXPOSE 8080

# 5. Jalankan Apache
CMD ["apache2-foreground"]