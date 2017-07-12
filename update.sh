#!/bin/sh 
echo "start pulling..."
git pull
echo "pull done"
chown www-data:www-data etc/var/routers.data.php
echo "change router permission done"
chown -R www-data:www-data assets
chown -R www-data:www-data usr/
echo "change assets permission done"
