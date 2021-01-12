#!/bin/bash

set -m

echo "MYSQL_HOSTNAME=$MYSQL_HOSTNAME" > /var/www/html/.env
echo "MYSQL_USERNAME=$MYSQL_USERNAME" >> /var/www/html/.env
echo "MYSQL_PASSWORD=$MYSQL_PASSWORD" >> /var/www/html/.env
echo "MYSQL_DATABASE=$MYSQL_DATABASE" >> /var/www/html/.env
echo "REDIS_HOST=$REDIS_HOST" >> /var/www/html/.env
echo "REDIS_PORT=$REDIS_PORT" >> /var/www/html/.env
echo "REDIS_AUTH=$REDIS_AUTH" >> /var/www/html/.env
echo "POSTGRES_URI=$POSTGRES_URI" >> /var/www/html/.env

mv /etc/redis/redis.conf.orig /etc/redis/redis.conf

cp /etc/redis/redis.conf /etc/redis/redis.conf.orig

echo "requirepass $REDIS_AUTH" >> /etc/redis/redis.conf

service mysql start
service redis-server start

cp /var/www/html/themes/crisp/theme.json.orig /var/www/html/themes/crisp/theme.json
cp /var/www/html/themes/crisp/theme.json /var/www/html/themes/crisp/theme.json.orig
sed 's/https\:\/\/cdn\.tosdr\.org/$CDN_URL/' /var/www/html/themes/crisp/theme.json.orig > /var/www/html/themes/crisp/theme.json
sed 's/https\:\/\/shields\.tosdr\.org/$SHIELD_URL/' /var/www/html/themes/crisp/theme.json.orig > /var/www/html/themes/crisp/theme.json

/usr/bin/php7.4 /var/www/html/bin/cli.php migrate
/usr/bin/php7.4 /var/www/html/bin/cli.php theme install crisp
/usr/bin/php7.4 /var/www/html/bin/cli.php plugin install core
/usr/bin/php7.4 /var/www/html/bin/cli.php plugin install admin

/usr/bin/php7.4 /var/www/html/bin/cli.php theme storage refresh crisp
/usr/bin/php7.4 /var/www/html/bin/cli.php plugin storage refresh core
/usr/bin/php7.4 /var/www/html/bin/cli.php plugin storage refresh admin

apachectl -D FOREGROUND