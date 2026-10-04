FROM php:8.3-apache

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install required PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

# Copy backend files to Apache document root
COPY . /var/www/html/

# Update Apache DocumentRoot to point to public/
RUN sed -i -e 's/html/html\/public/g' /etc/apache2/sites-available/000-default.conf

# Set permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
