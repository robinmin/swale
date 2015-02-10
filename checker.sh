#!/bin/bash

## Add the following line into crontab
## */1 * * * * /data/script/check_server.sh

checker_log='/var/log/server_restart.log'
checker_script='/data/script/server.php'
checker_port='2048'

count=`netstat -lnp|grep "LISTEN"|grep ":$checker_port"|wc -l`

echo $count
if [ $count -lt 1 ]; then
    ps -eaf |grep "server.php" | grep -v "grep"| awk '{print $2}'|xargs kill -9
    sleep 2
    ulimit -c unlimited
    php $checker_script
    echo "restarting......";

    if [ ! -e "$checker_log" ] ; then
        touch $checker_log
    fi
    echo $(date +%Y-%m-%d_%H:%M:%S) >>$checker_log
fi
