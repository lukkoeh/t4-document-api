FROM php:8.2-cli
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions mysqli sockets zip
RUN apt update && apt install unzip -y
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
RUN chmod +x /usr/local/bin/composer
COPY . /app
WORKDIR /app
RUN useradd -ms /bin/bash socketrunner
RUN chown -R socketrunner:socketrunner /app
USER socketrunner
RUN /usr/local/bin/composer install --no-interaction --no-progress --optimize-autoloader
EXPOSE 8082
CMD ["php", "/app/standalone-server.php"]
