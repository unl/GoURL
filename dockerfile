# [Choice] PHP version (use -bullseye variants on local arm64/Apple Silicon): 8, 8.1, 8.0, 7, 7.4, 7.3, 8-bullseye, 8.1-bullseye, 8.0-bullseye, 7-bullseye, 7.4-bullseye, 7.3-bullseye, 8-buster, 8.1-buster, 8.0-buster, 7-buster, 7.4-buster
ARG VARIANT="7.4"
FROM php:${VARIANT}-apache-bullseye

# Install MariaDB client
RUN apt-get update && export DEBIAN_FRONTEND=noninteractive \
    && apt-get install -y mariadb-client \ 
    && apt-get clean -y && rm -rf /var/lib/apt/lists/*

RUN apt-get update && apt-get install -y libz-dev libmemcached-dev && \
    pecl install memcached && \
    docker-php-ext-enable memcached

# install prerequisites
RUN apt-get update && apt-get install -y git zip unzip libpng-dev


# Install php-mysql driver
RUN docker-php-ext-install mysqli pdo pdo_mysql gd


# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# removes error "Invalid command 'RewriteEngine'"
RUN a2enmod rewrite && service apache2 restart

# changes the docroot so the domain points to the correct file
ARG DIRROOT="/var/www/html"
ARG DOCROOT="/www"
ENV APACHE_DOCUMENT_ROOT=${DIRROOT}${DOCROOT}
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR ${DIRROOT}

# can't do this since mounting the volume in the docker compose will remove the files made here
# COPY ./composer.json ${DIRROOT}
# RUN composer install


COPY . ${DIRROOT}

# [Optional] Uncomment this section to install additional OS packages.
# RUN apt-get update && export DEBIAN_FRONTEND=noninteractive \
#     && apt-get -y install --no-install-recommends <your-package-list-here>

# [Optional] Uncomment this line to install global node packages.
# RUN su vscode -c "source /usr/local/share/nvm/nvm.sh && npm install -g <your-package-here>" 2>&1

