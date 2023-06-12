#!/usr/bin/env sh

usermod -u ${UID} www-data
groupmod -g ${GID} www-data

echo "Fixing execution permissions"
find /var/www/src -iname "*.php" | xargs chmod 777

echo "Launch application"
exec "$@"
