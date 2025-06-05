import sys
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
from bs4 import BeautifulSoup
import requests
import mysql.connector
import re
import time
from datetime import datetime
import json
from mysql.connector import Error

# 配置Chrome无头模式选项
chrome_options = Options()
chrome_options.add_argument('--headless')  # 启用无头模式
chrome_options.add_argument('--no-sandbox')  # 避免运行时权限问题
print('等待创建浏览器实例')
# 创建浏览器实例
driver = webdriver.Chrome()  # 替换为实际的驱动程序路径
# driver = webdriver.Chrome(options=chrome_options)#无桌面版
print('等待打开浏览器')
# 打开登录页面
driver.get('https://dsoft.desmotec.com/?login&uri=')

print('开始获取用户数据')
# 执行登录操作
username = driver.find_element(By.CSS_SELECTOR, '[placeholder="Username"]')  # 替换为实际的用户名输入框元素定位方式和值
password = driver.find_element(By.CSS_SELECTOR, '[placeholder="Password"]')  # 替换为实际的密码输入框元素定位方式和值
submit_btn = driver.find_element(By.CLASS_NAME, 'btn-default')  # 替换为实际的登录按钮元素定位方式和值

# 登录的账号密码
# username.send_keys("physicalshanxi")  # 替换为实际的用户名
# password.send_keys("shanxisports")  # 替换为实际的密码
username.send_keys("gdtkscenter")  # 替换为实际的用户名
password.send_keys("beijingyandinghsd")  # 替换为实际的密码
submit_btn.click()

# 等待登录成功并获取页面内容
wait = WebDriverWait(driver, 10)
wait.until(EC.presence_of_element_located((By.CLASS_NAME, 'logout')))
# 获取人员列表
user_lists = 'https://dsoft.desmotec.com/?master-data-users'
driver.get(user_lists)
next_button = driver.find_element(By.ID, "DataTables_Table_0_next")
user_max_page = int(next_button.get_attribute('data-dt-idx'))

user_tr_list = []  # 用户session列表
user_current_page = 1
while user_current_page < user_max_page:
    # 获取分页数据的表格元素
    table = driver.find_element(By.CLASS_NAME, 'table-condensed')
    tbody = table.find_element(By.TAG_NAME, 'tbody')
    # 解析表格数据
    rows = tbody.find_elements(By.TAG_NAME, 'tr')  # 获取所有行
    for row in rows:
        cells = row.find_elements(By.TAG_NAME, 'td')  # 获取行中的所有单元格
        for cell in cells:
            link_elements = cell.find_elements(By.TAG_NAME, 'a')
            if(len(link_elements) <= 0):
                continue
            # 单元格包含<a>标签
            for link_element in link_elements:
                if(re.search(r'\bbtn-success\b', link_element.get_attribute('class'))):
                    # 获取<a>标签的href属性值
                    href = link_element.get_attribute('href')
                    user_tr_list.append(href)
    # 等待页面加载完成
    time.sleep(0.5)
    # 页码
    user_current_page += 1
    # 定位并点击下一页按钮
    next_button.click()

# 环境参数
env = 3
cnx1 = ''
cnx2 = ''
business_name = ''
if env == 1:
    # 连接到MySQL数据库--数据中心
    cnx1 = mysql.connector.connect(
        host='101.200.165.56',  # 数据库主机地址
        user='wwwweb',  # 数据库用户名
        password='Cpt2018@)!(',  # 数据库密码
        database='data_center',  # 数据库名称
        # autocommit=False  # 禁用自动提交
    )

    # 连接到MySQL数据库--业务系统
    cnx2 = mysql.connector.connect(
        host='101.200.165.56',  # 数据库主机地址
        user='wwwweb',  # 数据库用户名
        password='Cpt2018@)!(',  # 数据库密码
        database='ydy3_gdtksdev',  # 数据库名称
        # autocommit=False  # 禁用自动提交
    )
    business_name = 'gdtkskx'
if env == 2:
    # 连接到MySQL数据库--数据中心
    cnx1 = mysql.connector.connect(
        host='59.110.32.205',  # 数据库主机地址
        user='wwwweb',  # 数据库用户名
        password='Cpt2018@)!(',  # 数据库密码
        database='data_center',  # 数据库名称
        # autocommit=False  # 禁用自动提交
    )

    # 连接到MySQL数据库--业务系统
    cnx2 = mysql.connector.connect(
        host='59.110.32.205',  # 数据库主机地址
        user='wwwweb',  # 数据库用户名
        password='Cpt2018@)!(',  # 数据库密码
        database='ydy3_gdtkstest',  # 数据库名称
        # autocommit=False  # 禁用自动提交
    )
    business_name = 'gdtkskx'
if env == 3:
    # 连接到MySQL数据库--数据中心
    cnx1 = mysql.connector.connect(
        # host='rds3nh30726o5tko5b9g502.mysql.rds.aliyuncs.com',  # 内网数据库主机地址
        host='rds3nh30726o5tko5b9gro.mysql.rds.aliyuncs.com',
        user='rooot',  # 数据库用户名
        password='cpt2018@)!(',  # 数据库密码
        database='data_center',  # 数据库名称
        # autocommit=False  # 禁用自动提交
    )

    # 连接到MySQL数据库--业务系统
    cnx2 = mysql.connector.connect(
        host='39.106.45.95',  # 数据库主机地址
        user='wwwweb',  # 数据库用户名
        password='cpt2018@)!(',  # 数据库密码
        database='ydy3_gdtksdc',  # 数据库名称
        # autocommit=False  # 禁用自动提交
    )
    business_name = 'gdtkskx'

# 创建游标对象
datacenter = cnx1.cursor()
business_sys = cnx2.cursor()

# 根据system_number获取设备id
query = "SELECT id FROM ydy_equipment where system_number='4' limit 1"
business_sys.execute(query)
is_sz = business_sys.fetchall()     # 上肢
business_sys.nextset()
query = "SELECT id FROM ydy_equipment where system_number='5' limit 1"
business_sys.execute(query)
is_xz = business_sys.fetchall()     # 下肢
business_sys.nextset()
is_sz_id = ''
is_xz_id = ''
if len(is_sz) != 0:
    is_sz_id = is_sz[0][0]
if len(is_xz) != 0:
    is_xz_id = is_xz[0][0]

# 设备指标映射关系
relation_type = 7
create_by = 'system'
create_time = datetime.now().strftime("%Y-%m-%d %H:%M:%S")

# 获取用户session训练内容
for user_list in user_tr_list:
    # user_list = 'https://dsoft.desmotec.com/?trainings/user:dddddddd/month:last'
    driver.get(user_list)
    # 获取用户姓名
    user_link_array = user_list.split("/user:")
    user_info = user_link_array[1]
    select_user = user_info.replace('/month:last', '')
    # 获取人员uuid
    query = "SELECT id,uuid,name FROM ydy_staff where name='" + select_user + "' and is_show=1 limit 1"
    business_sys.execute(query)
    business_staff = business_sys.fetchall()
    business_sys.nextset()
    staff_uuid = ''
    department_uuid = ''
    if len(business_staff) != 0:
        staff_uuid = business_staff[0][1]
        query = ("SELECT b.uuid,b.name as department_uuid FROM ydy_staff_department as a left join ydy_department "
                 "as b on a.department_uuid=b.uuid where a.staff_uuid='") + staff_uuid + ("' and b.is_show=1 and "
                                                                                          "b.status=1 limit 1")
        business_sys.execute(query)
        business_department = business_sys.fetchall()
        business_sys.nextset()
        if len(business_department) != 0:
            department_uuid = business_department[0][0]
    else:
        msg_query = ("REPLACE INTO ydy_equipment_sync (name, equipment, msg, create_time, create_by) VALUES (%s, %s, %s, %s, %s)")
        msg = "受测人员【" + select_user + "】在数据中心不存在，请先在数据中心创建"
        msg_values = (select_user, '离心训练机', msg, create_time, create_by)
        if not cnx2.in_transaction:
            cnx2.start_transaction()
        business_sys.execute(msg_query, msg_values)
        cnx2.commit()
        continue
    # print(staff_uuid)
    # print(department_uuid)
    # sys.exit()

    # 用户训练数据列表
    lists = []
    view_next_button = driver.find_element(By.ID, "DataTables_Table_0_next")
    view_max_page = int(view_next_button.get_attribute('data-dt-idx'))
    # print(view_max_page)
    # sys.exit()
    view_current_page = 1
    # 这里需要优化，应该是记录已操作的页码，然后本次处理截止页码到最新页码（默认时间倒序）
    while view_current_page < 2:
        # 获取分页数据的表格元素  DataTables_Table_0
        table = driver.find_element(By.CLASS_NAME, 'table-condensed')
        # 解析表格数据
        rows = table.find_elements(By.TAG_NAME, 'tr')  # 获取所有行
        for row in rows:
            tr_list = []
            cells = row.find_elements(By.TAG_NAME, 'td')  # 获取行中的所有单元格
            for cell in cells:
                cell_text = cell.text  # 获取单元格的文本内容
                link_elements = cell.find_elements(By.TAG_NAME, 'a')
                if len(link_elements) > 0:
                    # 单元格包含<a>标签
                    for link_element in link_elements:
                        # 获取<a>标签的href属性值
                        href = link_element.get_attribute('href')
                        tr_list.append(href)
                else:
                    tr_list.append(cell_text)

                if len(tr_list) == 7:
                    lists.append(tr_list)

        # 等待页面加载完成
        time.sleep(2)
        view_current_page += 1
        # 定位并点击下一页按钮
        view_next_button.click()
    for every_tr in lists:
        # 执行查询语句判断列表数据是否为重复数据
        record_time = every_tr[0]
        if every_tr[3] == 'Balance':
            continue
        # 去除列表中时间字段包含" T"的字符
        record_time = record_time.replace(' T', '')
        query = "SELECT id FROM ydy_equipment_result where record_time='" + record_time + "' limit 1"
        business_sys.execute(query)
        is_record = business_sys.fetchall()
        business_sys.nextset()

        date_format = "%Y-%m-%d %H:%M:%S"
        date_object = datetime.strptime(record_time, date_format)
        insert_date = date_object.strftime("%Y-%m-%d")

        device_id = is_xz_id
        device_name = '离心训练机-下肢'
        if every_tr[2] != 'D11f':
            device_id = is_sz_id
            device_name = '离心训练机-上肢'

        # 定义一个字段存放变量
        result_arr = {
            'business': business_name, 'equipment_id': device_id, 'equipment_mark': device_name,
            'relation_type': relation_type,
            'department_uuid': department_uuid, 'staff_uuid': staff_uuid, 'date': insert_date,
            'record_time': record_time,
            'device': every_tr[1], 'model': every_tr[2], 'activity_type': every_tr[3], 'watt_desmo': every_tr[4],
            'watt': every_tr[5],
            'name': select_user, 'surname': '', 'create_by': create_by,
            'Pattern': '', 'Mode': '', 'Method': '', 'Action_name': '', 'Time': '', 'Conc': '',
            'Ecc': '', 'Work_interval': '', 'Total_Volume': '', 'Total_Time': '',
            'Maximum_Concentric_Power_Peak': '', 'Concentric_Average_Power': '',
            'Concentric_Power_St_Deviation': '', 'Concentric_Time': '', 'Maximum_Eccentric_Power_Peak': '',
            'Eccentric_Concentric_Average_Power': '',
            'Eccentric_Power_St_Deviation': '', 'Eccentric_Time': '', 'Peaks_Within_Target_Range': ''
        }

        # 业务系统没有
        if len(is_record) == 0:
            detail_url = every_tr[6]
            driver.get(detail_url)
            # 获取内容div
            all_content_div = driver.find_element(By.CLASS_NAME, 'wrapper-session')
            # 解析表格数据
            all_p_rows = all_content_div.find_elements(By.TAG_NAME, 'p')  # 获取所有行
            p_elements = [element.text for element in all_p_rows]
            # print(p_elements)

            pattern = re.compile(r'Pattern:\s*(\w+)')
            model = re.compile(r'Mode: (\w+)')
            method = re.compile(r'Method:\s*(\w+)')
            time = re.compile(r'Time \(s\): (\d+)')
            Repetitions = re.compile(r'Repetitions: \s*(\w+)')
            target_power_pattern = re.compile(r'Target power \(W\): (\d+)Conc (\d+)Ecc')
            target_power_pattern1 = re.compile(r'Target force \(kg\): (\d+)Conc (\d+)Ecc')
            work_interval = re.compile(r'Work interval \(%\): (\d+)')
            for item in p_elements:
                matchstr1 = pattern.search(item)
                matchstr2 = model.search(item)
                matchstr3 = method.search(item)
                matchstr4 = time.search(item)
                matchstr41 = Repetitions.search(item)
                matchstr5 = target_power_pattern.search(item)
                matchstr51 = target_power_pattern1.search(item)
                matchstr6 = work_interval.search(item)

                if matchstr1:
                    Action_name = matchstr1.group(1).upper()
                    Pattern = matchstr1.group(1)
                    result_arr['Action_name'] = Action_name
                    result_arr['Pattern'] = Pattern

                if matchstr2:
                    Mode = matchstr2.group(1)
                    result_arr['Mode'] = Mode
                if matchstr3:
                    Method = matchstr3.group(1)
                    result_arr['Method'] = Method
                if matchstr4:
                    Time = matchstr4.group(1)
                    result_arr['Time'] = Time
                if matchstr41:
                    Time = matchstr41.group(1)
                    result_arr['Time'] = Time
                if matchstr5:
                    Conc = matchstr5.group(1)
                    Ecc = matchstr5.group(2)
                    result_arr['Conc'] = Conc
                    result_arr['Ecc'] = Ecc
                if matchstr51:
                    Conc = matchstr51.group(1)
                    Ecc = matchstr51.group(2)
                    result_arr['Conc'] = Conc
                    result_arr['Ecc'] = Ecc
                if matchstr6:
                    Work_interval = matchstr6.group(1)
                    result_arr['Work_interval'] = Work_interval

            # 解析表格数据，获取多组

            number_lists = ['']
            sets = driver.find_elements(By.CLASS_NAME, 'serie')
            for set_number in sets:
                number_lists.append(int(set_number.text) - 1)
            # 循环获取多组数据
            for number_list in number_lists:
                analysis = driver.find_element(By.ID, "stats" + str(number_list))
                #                           analysis = driver.find_element(By.ID, 'stats')
                td_rows = analysis.find_elements(By.TAG_NAME, 'td')  # 获取所有行
                detail_td_list = []
                for td_row in td_rows:
                    cell_text = td_row.text  # 获取单元格的文本内容
                    cell_text = cell_text.replace('Kg', '')
                    cell_text = cell_text.replace('W', '')
                    cell_text = cell_text.replace('kJ', '')
                    cell_text = cell_text.replace('s', '')
                    detail_td_list.append(cell_text)

                result_arr['Total_Volume'] = detail_td_list[0]
                result_arr['Total_Time'] = detail_td_list[1]
                result_arr['Maximum_Concentric_Power_Peak'] = detail_td_list[2]
                result_arr['Concentric_Average_Power'] = detail_td_list[3]
                result_arr['Concentric_Power_St_Deviation'] = detail_td_list[4]
                result_arr['Concentric_Time'] = detail_td_list[5]
                result_arr['Maximum_Eccentric_Power_Peak'] = detail_td_list[6]
                result_arr['Eccentric_Concentric_Average_Power'] = detail_td_list[7]
                result_arr['Eccentric_Power_St_Deviation'] = detail_td_list[8]
                result_arr['Eccentric_Time'] = detail_td_list[9]
                result_arr['Peaks_Within_Target_Range'] = detail_td_list[10].replace('%', '')

            result_query = ("INSERT INTO ydy_equipment_result (business, equipment_id, equipment_mark, relation_type, "
                            "department_uuid, staff_uuid, date, record_time, create_by, k1, k2, k3, k4, k5, k6,k7,k8,"
                            "k9,k10,k11, k12,k13,k14,k15,k16,k17,k18,k19,k20) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, "
                            "%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)")
            result_extend_query = ("INSERT INTO ydy_equipment_result_extend (equipment_result_id, create_by, k21, "
                                   "k22, k23, k24, k25, k26) VALUES (%s, %s, %s, %s, %s, %s, %s, %s)")

            result_values = (result_arr['business'], result_arr['equipment_id'], result_arr['equipment_mark'], result_arr['relation_type'],
                             result_arr['department_uuid'], result_arr['staff_uuid'], result_arr['date'], result_arr['record_time'], result_arr['create_by'],
                             result_arr['device'],
                             result_arr['model'],
                             result_arr['activity_type'],
                             result_arr['watt_desmo'],
                             result_arr['watt'],
                             result_arr['name'],
                             result_arr['surname'],
                             result_arr['Pattern'],
                             result_arr['Mode'],
                             result_arr['Method'],
                             result_arr['Action_name'],
                             result_arr['Time'],
                             result_arr['Conc'],
                             result_arr['Ecc'],
                             result_arr['Work_interval'],
                             result_arr['Total_Volume'],
                             result_arr['Total_Time'],
                             result_arr['Maximum_Concentric_Power_Peak'],
                             result_arr['Concentric_Average_Power'],
                             result_arr['Concentric_Power_St_Deviation'])
            result_id = ''
            try:
                if cnx1.in_transaction:
                    cnx1.start_transaction()

                # 数据中心主表插入数据
                datacenter.execute(result_query, result_values)
                # 获取插入的行的 ID
                result_id = datacenter.lastrowid
                result_extend_values = (result_id, result_arr['create_by'],
                                        result_arr['Concentric_Time'],
                                        result_arr['Maximum_Eccentric_Power_Peak'],
                                        result_arr['Eccentric_Concentric_Average_Power'],
                                        result_arr['Eccentric_Power_St_Deviation'],
                                        result_arr['Eccentric_Time'],
                                        result_arr['Peaks_Within_Target_Range'])
                datacenter.execute(result_extend_query, result_extend_values)

                cnx1.commit()
            except Exception as e:
                cnx1.rollback()
                print(e.msg)
                break

            # 业务系统
            if result_id != '':
                business_result_query = (
                    "INSERT INTO ydy_equipment_result (business, equipment_id, equipment_mark, relation_type, "
                    "department_uuid, staff_uuid, date, record_time, create_by, reference, k1, k2, k3, k4, k5, k6,k7,"
                    "k8,k9,k10,k11, k12,k13,k14,k15,k16,k17,k18,k19,k20) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, "
                    "%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)")

                business_result_extend_query = ("INSERT INTO ydy_equipment_result_extend (equipment_result_id, "
                                                "create_by, k21, k22, k23, k24, k25, k26) VALUES (%s, %s, %s, %s, %s,"
                                                " %s, %s, %s)")

                business_result_values = (result_arr['business'], result_arr['equipment_id'], result_arr['equipment_mark'],
                                 result_arr['relation_type'],
                                 result_arr['department_uuid'], result_arr['staff_uuid'], result_arr['date'],
                                 result_arr['record_time'], result_arr['create_by'], result_id,
                                 result_arr['device'],
                                 result_arr['model'],
                                 result_arr['activity_type'],
                                 result_arr['watt_desmo'],
                                 result_arr['watt'],
                                 result_arr['name'],
                                 result_arr['surname'],
                                 result_arr['Pattern'],
                                 result_arr['Mode'],
                                 result_arr['Method'],
                                 result_arr['Action_name'],
                                 result_arr['Time'],
                                 result_arr['Conc'],
                                 result_arr['Ecc'],
                                 result_arr['Work_interval'],
                                 result_arr['Total_Volume'],
                                 result_arr['Total_Time'],
                                 result_arr['Maximum_Concentric_Power_Peak'],
                                 result_arr['Concentric_Average_Power'],
                                 result_arr['Concentric_Power_St_Deviation'])

                try:
                    if not cnx2.in_transaction:
                        cnx2.start_transaction()

                    business_sys.execute(business_result_query, business_result_values)
                    business_result_id = business_sys.lastrowid
                    business_result_extend_values = (business_result_id, result_arr['create_by'],
                                            result_arr['Concentric_Time'],
                                            result_arr['Maximum_Eccentric_Power_Peak'],
                                            result_arr['Eccentric_Concentric_Average_Power'],
                                            result_arr['Eccentric_Power_St_Deviation'],
                                            result_arr['Eccentric_Time'],
                                            result_arr['Peaks_Within_Target_Range'])

                    business_sys.execute(business_result_extend_query, business_result_extend_values)

                    cnx2.commit()
                except Exception as e:
                    cnx2.rollback()
                    print(e.msg)
                    break


cnx1.close()
datacenter.close()
cnx2.close()
business_sys.close()

print('数据收集完成')
sys.exit()


