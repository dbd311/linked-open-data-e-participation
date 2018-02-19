#!/bin/bash

INSTDIR=/applications/lod/users/lod/www/lodepart

if cd $INSTDIR ; then
    echo "Creating directories into $INSTDIR"
else
    echo "Cannot cd to $INSTDIR, please adapt this script to your environment"
    exit 5
fi


rm -fr storage/ bootstrap/cache/
mkdir -p storage/app storage/logs storage/framework/views storage/framework/sessions storage/framework/cache bootstrap/cache/

# change owner and permissions
chown -R lod:apache storage bootstrap/cache/ public/images/avatars
chmod -R 2775 storage bootstrap/cache/
chmod -R g+w public/images/avatars

# show details
ls -ld storage/app storage/logs storage/framework/views storage/framework/sessions storage/framework/cache bootstrap/cache/ public/images/avatars