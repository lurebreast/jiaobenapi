#!/bin/bash
while [ true ]; do
    /bin/sleep 1
    php /home/wwwroot/default/apps/cron/queue.php > /dev/null &
done
