#! /bin/sh
ps -fe|grep php-cron.sh |grep -v grep
if [ $? -ne 0 ]
then
    echo "start process....."
    /bin/sh /home/wwwroot/default/shell/php-cron.sh &
else
    echo "runing....."
fi
