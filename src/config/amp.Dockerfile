FROM ubuntu:24.10

RUN ln -snf /usr/share/zoneinfo/GMT /etc/localtime && echo GMT > /etc/timezone

RUN echo "" > '/var/log/xdebug.log'; chmod 777 '/var/log/xdebug.log';

RUN apt-get clean && apt-get update

RUN apt-get install -y php php-fpm php-bcmath php-cli php-oauth php-common \
    php-ssh2 php-curl php-gd php-intl \
    php-mbstring php-mysql php-opcache php-soap \
    php-sqlite3 php-xml  php-xmlrpc php-xsl \
    php-zip php-dev php-imagick libapache2-mod-php

RUN apt-get install -y apache2

RUN a2enmod php8.3; a2enmod rewrite

RUN pecl install -f xdebug

RUN ln -s /etc/php/8.3/mods-available/xdebug.ini /etc/php/8.3/cli/conf.d/11-xdebug.ini \
    && ln -s /etc/php/8.3/mods-available/xdebug.ini /etc/php/8.3/apache2/conf.d/11-xdebug.ini;

RUN php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --2 --filename=composer

RUN apt-get install -y mysql-server;

RUN echo "#!/bin/bash" >  /usr/local/bin/start-services.sh
RUN echo "" >>  /usr/local/bin/start-services.sh
RUN echo "echo \"Starting mysql server\"" >>  /usr/local/bin/start-services.sh
RUN echo "service mysql start" >>  /usr/local/bin/start-services.sh
RUN echo "echo \"Starting apache2 server\"" >>  /usr/local/bin/start-services.sh
RUN echo "service apache2 start" >>  /usr/local/bin/start-services.sh
RUN echo "tail -f /var/log/apache2/error.log" >>  /usr/local/bin/start-services.sh

RUN usermod -d /var/lib/mysql mysql

RUN echo service mysql start
RUN echo "CREATE DATABASE developer" | mysql
RUN echo "CREATE USER 'developer'@'localhost' IDENTIFIED BY 'developer'" | mysql
RUN echo "GRANT ALL PRIVILEGES ON developer.* TO 'developer'@'localhost'" | mysql
RUN echo service mysql stop

RUN chmod +x /usr/local/bin/start-services.sh

EXPOSE 80 3306 9000 9003

CMD ["/usr/local/bin/start-services.sh"]