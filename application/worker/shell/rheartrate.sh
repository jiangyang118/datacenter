#!/bin/bash
# 添加执行权限，用www用户启动
sudo -u www  chmod -R 777  /workspace/developSite/datacenter/vendor/workerman/
sudo -u www  chmod -R 777  /workspace/wwwroot/datacenter/project/vendor/workerman/
sudo -u www  chmod -R 777  /workspace/wwwroot/datacenter/project/vendor/workerman/
chmod +x "$0"

# 使用 `sudo` 执行命令
echo "Killing processes listening on port 2361..."
sudo -u www fuser -k 2361/tcp
sudo -u www  /www/server/php/73/bin/php /workspace/developSite/datacenter/RHeartRate.php stop
sudo -u www  /usr/local/php7.3/bin/php /workspace/wwwroot/datacenter/project/RHeartRate.php stop

# 检查第一行命令是否成功
if [ $? -eq 0 ]; then
    echo "Port 2361 cleared."
else
    echo "Failed to clear port 2361."
fi

# 使用 `sudo` 执行命令
echo "Restarting PHP script..."
sudo -u www  /www/server/php/73/bin/php /workspace/developSite/datacenter/RHeartRate.php start
sudo -u www /usr/local/php7.3/bin/php /workspace/wwwroot/datacenter/project/RHeartRate.php start
# 检查第二行命令是否成功
if [ $? -eq 0 ]; then
    echo "PHP script restarted successfully."
else
    echo "Failed to restart PHP script."
fi