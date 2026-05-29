FROM php:8.2-apache-bookworm

ENV ACCEPT_EULA=Y
ENV PORT=10000
ENV UPLOAD_DIR=/var/www/storage/uploads
ENV LOG_DIR=/var/www/storage/logs

# Dependências básicas
RUN apt-get update && apt-get install -y --no-install-recommends \
    apt-transport-https \
    ca-certificates \
    curl \
    gnupg \
    unixodbc \
    unixodbc-dev \
    libgssapi-krb5-2 \
    $PHPIZE_DEPS

# Repositório Microsoft SQL Server ODBC
RUN curl -fsSL https://packages.microsoft.com/keys/microsoft.asc \
    | gpg --dearmor -o /usr/share/keyrings/microsoft-prod.gpg

RUN echo "deb [arch=amd64 signed-by=/usr/share/keyrings/microsoft-prod.gpg] https://packages.microsoft.com/debian/12/prod bookworm main" \
    > /etc/apt/sources.list.d/mssql-release.list

# Driver ODBC SQL Server
RUN apt-get update && ACCEPT_EULA=Y apt-get install -y --no-install-recommends msodbcsql18

# Extensões PHP SQL Server
RUN pecl install sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv

# Apache
RUN a2enmod rewrite

WORKDIR /var/www/html

COPY . /var/www/html/
COPY apache/vhost.conf /etc/apache2/sites-available/000-default.conf

RUN mkdir -p /var/www/storage/uploads /var/www/storage/logs /var/www/html/uploads /var/www/html/logs \
    && sed -i 's/\r$//' /var/www/html/docker-entrypoint.sh \
    && chmod +x /var/www/html/docker-entrypoint.sh \
    && chown -R www-data:www-data /var/www/html /var/www/storage

RUN rm -rf /var/lib/apt/lists/* /tmp/pear

EXPOSE 10000

CMD ["/var/www/html/docker-entrypoint.sh"]