#!/usr/bin/env sh

echo ${APP_UID}:${APP_GID}
usermod -u ${APP_UID} www-data
groupmod -g ${APP_GID} www-data

echo "Fixing execution permissions"
find /var/www/html -iname "*.php" -exec chmod 777 {} \;

echo "Launch application"
exec "$@"
