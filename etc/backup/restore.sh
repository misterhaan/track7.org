#!/bin/bash
# local restore backup script for track7 content in mysql and non-git files

# this script is meant to run on the test site webserver, or the live site
# webserver in case of data loss.
# see t7mysql.template.sh for setup instructions.

source ~/backup/.t7mysql.sh

bzcat $BACKUP_DIR/track7content.sql.bz2 | mysql -h $MYSQL_HOST -u $MYSQL_USER -p$MYSQL_PASS $MYSQL_NAME

cd $DOCUMENT_ROOT
rm -f album/photos/*
rm -f art/img/*
rm -f code/calc/files/*
rm -f code/games/files/*
rm -f code/vs/files/*
rm -f code/web/files/*
rm -f lego/data/*
rm -f user/avatar/*
tar xf $BACKUP_DIR/track7content.tar
