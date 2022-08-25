FROM debian:bullseye

ENV LANG C.UTF-8
ENV DEBIAN_FRONTEND noninteractive

# Basics
RUN apt-get update && apt-get install -y \
    composer \
    php-zip \
    rsync \
    sudo \
    wget \
    curl \
    lsb-release \
    && rm -rf /var/lib/apt/lists/*

# PHP Repo
RUN wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
RUN echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list

# PHP 7.3
RUN apt-get update && apt-get install -y \
    php7.3 \
    php7.3-curl \
    php7.3-soap \
    php7.3-xml \
    php7.3-ldap \
    php7.3-zip \
    php7.3-gmp \
    php7.3-xdebug \
    php7.3-gd \
    php7.3-mbstring \
    php7.3-intl \
    && rm -rf /var/lib/apt/lists/*
COPY ./php-ini-overrides.ini /etc/php/7.3/cli/conf.d/

# PHP 7.4
RUN apt-get update && apt-get install -y \
    php7.4 \
    php7.4-curl \
    php7.4-soap \
    php7.4-xml \
    php7.4-ldap \
    php7.4-zip \
    php7.4-gmp \
    php7.4-xdebug \
    php7.4-gd \
    php7.4-mbstring \
    php7.4-intl \
    && rm -rf /var/lib/apt/lists/*
COPY ./php-ini-overrides.ini /etc/php/7.4/cli/conf.d/

# PHP 8.0
RUN apt-get update && apt-get install -y \
    php8.0 \
    php8.0-curl \
    php8.0-soap \
    php8.0-xml \
    php8.0-ldap \
    php8.0-zip \
    php8.0-gmp \
    php8.0-xdebug \
    php8.0-gd \
    php8.0-mbstring \
    php8.0-intl \
    && rm -rf /var/lib/apt/lists/*
COPY ./php-ini-overrides.ini /etc/php/8.0/cli/conf.d/

# PHP 8.1
RUN apt-get update && apt-get install -y \
    php8.1 \
    php8.1-curl \
    php8.1-soap \
    php8.1-xml \
    php8.1-ldap \
    php8.1-zip \
    php8.1-gmp \
    php8.1-xdebug \
    php8.1-gd \
    php8.1-mbstring \
    php8.1-intl \
    && rm -rf /var/lib/apt/lists/*
COPY ./php-ini-overrides.ini /etc/php/8.1/cli/conf.d/

# Default to PHP 7.3
RUN sudo update-alternatives --set php /usr/bin/php7.3
RUN sudo update-alternatives --set phar /usr/bin/phar7.3
RUN sudo update-alternatives --set phar.phar /usr/bin/phar.phar7.3

# Add a normal user and enable sudo
RUN useradd -u 1000 -ms /bin/bash user
RUN echo 'user ALL=(ALL) NOPASSWD:ALL' >> /etc/sudoers
USER user

# Install a newer composer
RUN mkdir -p /home/user/.local/bin
RUN curl -L https://github.com/composer/getcomposer.org/raw/main/web/download/2.3.7/composer.phar -o /home/user/.local/bin/composer
RUN chmod a+x /home/user/.local/bin/composer
ENV PATH "/home/user/.local/bin:${PATH}"

WORKDIR /home/user