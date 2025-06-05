#!/bin/bash
#按照时间生成日志文件
#日期
date=$(date +%Y%m%d)
#用户标识
business=$1
#操作项
op=$2
#日志文件
logfile=/workspace/wwwroot/log/datacenter/cron/sync_sql_data_${date}_${op}.log
#判断文件是否存在，不存在创建文件
if [ ! -f "$logfile" ]; then
    touch $logfile
fi
#执行脚本
#/usr/local/php7.3/bin/php /workspace/wwwroot/datacenter/project/think SyncSqlData ${business} ${op} >> $logfile 2>&1
step=30 #间隔的秒数，不能大于60
for (( i = 0; i < 60; i=(i+step) )); do
$(/usr/local/php7.3/bin/php /workspace/wwwroot/datacenter/project/think SyncSqlData ${business} ${op} >> $logfile 2>&1)
sleep $step
done
exit 0