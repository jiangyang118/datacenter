#!/bin/bash
#按照时间生成日志文件
#日期
date=$(date +%Y%m%d)
#用户标识
business=$1
#操作项
op=$2

#日志文件
logfile=/workspace/wwwroot/log/${business}/sync_table_data_${op}_${date}.log
#判断文件是否存在，不存在创建文件
if [ ! -f "$logfile" ]; then
    touch $logfile
fi

#执行脚本
step=10 #间隔的秒数，不能大于60
for (( i = 0; i < 60; i=(i+step) )); do
$(/usr/local/php/bin/php /workspace/wwwroot/datacenter/project/think SyncTableData ${business} ${op} >> $logfile 2>&1)
sleep $step
done
exit 0

#执行脚本
#/usr/local/php/bin/php /workspace/wwwroot/ahtksdps/think SyncTableData ${op} >> $logfile 2>&1