#!/usr/bin/env bash

if [ ! -d /.composer ]; then
    mkdir /.composer
fi

chmod -R ugo+rw /.composer

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
