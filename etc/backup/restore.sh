#!/bin/bash
# local restore backup script for track7 content in mysql and non-git files

# this script is meant to run on the test site webserver, or the live site
# webserver in case of data loss.
# see t7mysql.template.sh for setup instructions.

source ~/backup/.t7mysql.sh

bzcat $BACKUP_DIR/track7content.sql.bz2 | mysql -h $MYSQL_HOST -u $MYSQL_USER -p$MYSQL_PASS $MYSQL_NAME

cd $DOCUMENT_ROOT
rm album/photos/*
rm art/img/*
rm code/calc/files/*
rm code/games/files/*
rm code/vs/files/*
rm code/web/files/*
rm lego/data/*
rm user/avatar/*
tar xf $BACKUP_DIR/track7content.tar
