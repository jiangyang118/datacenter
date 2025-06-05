
#!/bin/bash
#按照时间生成日志文件
#日期
date=$(date +%Y%m%d)
#用户标识
business=$1
#操作项
#日志文件
logfile=/workspace/wwwroot/log/datacenter/cron/sync_cosmedk5_data_${date}.log
#判断文件是否存在，不存在创建文件
if [ ! -f "$logfile" ]; then
    touch $logfile
fi
#执行脚本
/usr/local/php/bin/php /workspace/wwwroot/datacenter/project/think SyncK5Data ${business} >> $logfile 2>&1