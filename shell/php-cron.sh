#!/bin/bash
while [ true ]; do
    /bin/sleep 1
    php /home/wwwroot/default/apps/cron/dataadd.php > /dev/null &
    php /home/wwwroot/default/apps/cron/dataExport.php  > /dev/null &
done
