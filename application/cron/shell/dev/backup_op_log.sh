#!/bin/bash

business=$1

# 数据库连接信息
DB_HOST="your_db_host"
DB_USER="your_username"
DB_PASS="your_password"
DB_NAME="your_database_name"
TABLE_NAME="your_table_name"
DATA_COLUMN="your_table_name"
case ${business} in
"1") # 安徽体能项目
DB_HOST="101.200.165.56"
DB_USER="wwwweb"
DB_PASS="Cpt2018@)!("
DB_NAME="tnxl_dev"
TABLE_NAME="tn_operation_log"
DATA_COLUMN="date"
;;
"2") # 其他项目
DB_HOST="your_db_host"
DB_USER="your_username"
DB_PASS="your_password"
DB_NAME="your_database_name"
TABLE_NAME="your_table_name"
DATA_COLUMN="your_table_name"
;;
esac

# 日志文件路径
#日期
date=$(date +%Y%m%d)
#日志文件
LOG_FILE=/work_cpt/log/datacenter/cron/back_op_log_${date}.log

# 错误处理函数
handle_error() {
    echo "备份过程中发生错误，请查看日志文件：$LOG_FILE"
    exit 1
}
# 日志记录函数
log_message() {
    local timestamp=$(date +"%Y-%m-%d %H:%M:%S")
    echo "[$timestamp] $1" >> "$LOG_FILE"
}

# 备份文件路径和名称
BACKUP_DIR="/work_cpt/backup"
BACKUP_FILE="${BACKUP_DIR}/$(date +'%Y%m%d')_${DB_NAME}_${TABLE_NAME}_backup.sql"

# 计算一个月前的日期
ONE_MONTH_AGO=$(date -d "1 month ago" +%Y-%m-%d)

# 备份表数据
mysqldump -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME $TABLE_NAME > $BACKUP_FILE
if [ $? -eq 0 ]; then
    log_message "数据库表${DB_NAME}_${TABLE_NAME}已备份到${BACKUP_FILE}"
else
    log_message "数据库表${DB_NAME}_${TABLE_NAME}备份失败"
    handle_error
fi

# 删除一个月前的数据
mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME -e "DELETE FROM $TABLE_NAME WHERE $DATA_COLUMN < '$ONE_MONTH_AGO';"
if [ $? -eq 0 ]; then
    log_message "数据库表${DB_NAME}_${TABLE_NAME}中${ONE_MONTH_AGO}前的数据删除成功"
else
    log_message "数据库表${DB_NAME}_${TABLE_NAME}中${ONE_MONTH_AGO}前的数据删除失败"
    handle_error
fi