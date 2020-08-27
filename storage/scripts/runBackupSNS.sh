#!/bin/sh

DATE=`date +%Y%m%d`
DIR="/home/erebolledo/workspace/container-leads/leads"

cp $DIR/public/js/sns.json $DIR/storage/backups/sns$DATE.json
cat /dev/null > $DIR/public/js/sns.json

echo /prueba/sns${DATE}.json
