#!/bin/bash

set -m

apt-get update
apt-get install -y software-properties-common ca-certificates apt-transport-https wget gnupg2
wget -q https://packages.sury.org/php/apt.gpg -O- | apt-key add -
echo "deb https://packages.sury.org/php/ buster main" | tee /etc/apt/sources.list.d/php.list
apt-get update

apt-get install -y git-core libpq-dev autoconf gcc libc6-dev make apache2 php7.4 php7.4-bcmath php7.4-bz2 php7.4-intl php7.4-gd php7.4-mbstring php7.4-mysql php7.4-zip php7.4-pdo php7.4-redis libapache2-mod-php7.4 php7.4-pgsql composer php7.4-dom mariadb-server redis-server
a2enmod rewrite

rm -R /var/www/html
cp -rf /tmp/crisp /var/www/html
cd /var/www/html
rm /tmp/crisp -R
composer install --no-dev

service mysql start

mysql -e "CREATE DATABASE $MYSQL_DATABASE"
mysql -e "CREATE USER '$MYSQL_USERNAME'@'localhost' IDENTIFIED BY '$MYSQL_PASSWORD'"
mysql -e "GRANT ALL PRIVILEGES ON $MYSQL_DATABASE.* TO '$MYSQL_USERNAME'@'localhost'"
mysql -e "FLUSH PRIVILEGES"

chown www-data:www-data /var/www/html -R
ln -sf /dev/stderr /var/log/apache2/error.log