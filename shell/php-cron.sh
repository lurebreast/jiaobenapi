#!/bin/bash
while [ true ]; do
    /bin/sleep 3
    php /home/wwwroot/default/apps/cron/queue.php > /dev/null &
done
