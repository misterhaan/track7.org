#!/bin/bash
# remote backup script for track7 content in mysql and non-git files

# copy to a secure location on the server that will hold the backups, fill in
# the values in the next few lines, chmod 700, set up ssh with an rsa key for
# passwordless connections, and schedule it via cron if you like.

USER=""
HOST=""
DOCUMENT_ROOT=""
LOCAL_DIR=""

ssh $USER@$HOST "$DOCUMENT_ROOT/etc/backup/local.sh"
scp $USER@$HOST:~/backup/track7content.* $LOCAL_DIR
