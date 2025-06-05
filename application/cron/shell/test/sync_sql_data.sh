#!/bin/bash
#按照时间生成日志文件
#日期
date=$(date +%Y%m%d)
#用户标识
business=$1
#操作项
op=$2
#日志文件
logfile=/work_cpt/log/datacenter/cron/sync_sql_data_${date}_${op}.log
#判断文件是否存在，不存在创建文件
if [ ! -f "$logfile" ]; then
    touch $logfile
fi
chown -R www:www /work_cpt/log
#执行脚本
/www/server/php/74/bin/php /workspace/developSite/datacenter/think SyncSqlData ${business} ${op} >> $logfile 2>&1