#!/bin/bash
# local backup script for track7 content in mysql and non-git files

# this script is meant to run on the live site webserver, called over ssh by a
# copy of remote.sh running on the machine that will store the backup.
# see t7mysql.template.sh for setup instructions.

source ~/backup/.t7mysql.sh

if mysqlshow -h $MYSQL_HOST --user=$MYSQL_USER --password=$MYSQL_PASS | grep -q $MYSQL_NAME; then
	mysqldump -h $MYSQL_HOST -u $MYSQL_USER --password="$MYSQL_PASS" --databases $MYSQL_NAME --add-drop-database | bzip2 -c > $BACKUP_DIR/track7content.sql.bz2
fi

cd $DOCUMENT_ROOT
tar cf $BACKUP_DIR/track7content.tar album/photos/
tar rf $BACKUP_DIR/track7content.tar art/img/
tar rf $BACKUP_DIR/track7content.tar code/calc/files/
tar rf $BACKUP_DIR/track7content.tar code/games/files/
tar rf $BACKUP_DIR/track7content.tar code/vs/files/
tar rf $BACKUP_DIR/track7content.tar code/web/files/
tar rf $BACKUP_DIR/track7content.tar lego/data/
tar rf $BACKUP_DIR/track7content.tar user/avatar/
