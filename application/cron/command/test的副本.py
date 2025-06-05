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

# 配置Chrome无头模式选项
chrome_options = Options()
chrome_options.add_argument('--headless')  # 启用无头模式
chrome_options.add_argument('--no-sandbox')  # 避免运行时权限问题
print('等待创建浏览器实例')
# 创建浏览器实例
driver = webdriver.Chrome()  # 替换为实际的驱动程序路径
#driver = webdriver.Chrome(options=chrome_options)#无桌面版
print('等待打开浏览器')
# 打开登录页面
driver.get('https://dsoft.desmotec.com/?login&uri=')

# 设置窗口大小
driver.set_window_size(1280, 800)  # 设置宽度为 1280px，高度为 800px

print('开始获取用户数据')
# 执行登录操作
username = driver.find_element(By.CSS_SELECTOR, '[placeholder="Username"]')  # 替换为实际的用户名输入框元素定位方式和值
password = driver.find_element(By.CSS_SELECTOR, '[placeholder="Password"]')  # 替换为实际的密码输入框元素定位方式和值
submit_btn = driver.find_element(By.CLASS_NAME, 'btn-default')  # 替换为实际的登录按钮元素定位方式和值

username.send_keys("physicalshanxi")  # 替换为实际的用户名
password.send_keys("shanxisports")  # 替换为实际的密码
submit_btn.click()

# 等待登录成功并获取页面内容
wait = WebDriverWait(driver, 10)
wait.until(EC.presence_of_element_located((By.CLASS_NAME, 'logout')))
#获取人员列表数据列表
user_lists = 'https://dsoft.desmotec.com/?master-data-users'
driver.get(user_lists)
usercount = 0
while usercount < 2:
# 获取分页数据的表格元素
    table = driver.find_element(By.CLASS_NAME, 'table-condensed')
    # 解析表格数据
    rows = table.find_elements(By.TAG_NAME, 'tr')  # 获取所有行
    user_tr_list = []
    for row in rows:
        cells = row.find_elements(By.TAG_NAME, 'td')  # 获取行中的所有单元格
        for cell in cells:
            link_elements = cell.find_elements(By.TAG_NAME, 'a')
            if len(link_elements) > 0:
                # 单元格包含<a>标签
                for link_element in link_elements:
                    # 获取<a>标签的href属性值
                    href = link_element.get_attribute('href')
                    user_tr_list.append(href)

    # 处理表格数据
    # ... 这里可以根据需要进行数据处理，例如提取表格中的数据并打印或保存

    # 定位并点击下一页按钮
    next_button = driver.find_element(By.XPATH, "//a[text()='Next']")
    next_button.click()

    # 等待页面加载完成
    time.sleep(0.5)
    usercount += 1


# 获取页面内容
for user_list in user_tr_list:
#user_list = 'https://dsoft.desmotec.com/?trainings/user:dddddddd/month:last'
    driver.get(user_list)
    #获取用户内容
    user_link_array = user_list.split("/user:")
    user_info = user_link_array[1]
    select_user = user_info.replace('/month:last', '')

    lists = []
    count = 0
    while count < 2:
        # 判断是否到达最后一页的条件，例如通过判断某个元素是否存在
    #     if not driver.find_elements(By.XPATH,  "//a[contains(@class, 'next') and contains(@class, 'disabled') and contains(@class, 'paginate_button')]"):
    #         print('循环失败')
    #         break

        # 获取分页数据的表格元素
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

                if len(tr_list)==7:
                    lists.append(tr_list)

        # 处理表格数据
        # ... 这里可以根据需要进行数据处理，例如提取表格中的数据并打印或保存

        # 定位并点击下一页按钮
        next_button = driver.find_element(By.XPATH, "//a[text()='Next']")
        next_button.click()

        # 等待页面加载完成
#         time.sleep(1)
        count += 1
    #环境参数
    env = 1
    #根据获取的列表数据进行业务处理
    #[['2023-10-13 08:50:46', 'V000402020', 'V12f', 'Lunge', '742', '742', 'https://dsoft.desmotec.com/?trainings/user:dddddddd/month:13/day:/session:355545']]
    if len(lists)>0:
        if env==1:
            # 连接到MySQL数据库--数据中心
            cnx1 = mysql.connector.connect(
                host='101.200.165.56',  # 数据库主机地址
                user='wwwweb',  # 数据库用户名
                password='Cpt2018@)!(',  # 数据库密码
                database='data_center'  # 数据库名称
            )

            # 连接到MySQL数据库--业务系统
            cnx2 = mysql.connector.connect(
                host='101.200.165.56',  # 数据库主机地址
                user='wwwweb',  # 数据库用户名
                password='Cpt2018@)!(',  # 数据库密码
                database='ydy3_sxtksdev'  # 数据库名称
            )
            business_name = 'sxkx'
        if env==2:
           # 连接到MySQL数据库--数据中心
           cnx1 = mysql.connector.connect(
               host='124.70.215.81',  # 数据库主机地址
               user='wwwweb',  # 数据库用户名
               password='Cpt2018@)!(',  # 数据库密码
               database='data_center'  # 数据库名称
           )

           # 连接到MySQL数据库--业务系统
           cnx2 = mysql.connector.connect(
               host='124.70.215.81',  # 数据库主机地址
               user='wwwweb',  # 数据库用户名
               password='Cpt2018@)!(',  # 数据库密码
               database='ydy3_sxtks'  # 数据库名称
           )
           business_name = 'sxkx_test'
        if env==3:
           # 连接到MySQL数据库--数据中心
           cnx1 = mysql.connector.connect(
               host='rds3nh30726o5tko5b9g502.mysql.rds.aliyuncs.com',  # 数据库主机地址
               user='rooot',  # 数据库用户名
               password='cpt2018@)!(',  # 数据库密码
               database='data_center'  # 数据库名称
           )

           # 连接到MySQL数据库--业务系统
           cnx2 = mysql.connector.connect(
               host='rds3nh30726o5tko5b9g502.mysql.rds.aliyuncs.com',  # 数据库主机地址
               user='rooot',  # 数据库用户名
               password='cpt2018@)!(',  # 数据库密码
               database='sxkx'  # 数据库名称
           )
           business_name = 'sxkx'
        # 创建游标对象
        datacenter = cnx1.cursor()
        business_sys = cnx2.cursor()
        for every_tr in lists:
            # 执行查询语句判断列表数据是否为重复数据
            record_time = every_tr[0]
            if every_tr[3]!='Balance':
                #去除列表中时间字段包含" T"的字符
                record_time = record_time.replace(' T', '')
                query = "SELECT id FROM ydy_equipment_result where record_time='"+record_time+"' and business= '"+business_name+"' limit 1"
                datacenter.execute(query)
                is_record = datacenter.fetchall()
                datacenter.nextset()

                business_sys.execute(query)
                is_record1 = business_sys.fetchall()
                business_sys.nextset()
                #组织insert插入语句
                # 执行插入语句
                insert_query = "INSERT INTO ydy_equipment_result (business, equipment_id, date, record_time, k1, k2, k3, k4, k5,k6) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s,%s)"
                date_format = "%Y-%m-%d %H:%M:%S"
                date_object = datetime.strptime(record_time, date_format)
                insert_date = date_object.strftime("%Y-%m-%d")
                #判断设备
                device_id = 65
                if every_tr[2] !='D11f':
                  device_id = 64

                insert_values = (business_name, device_id, insert_date, record_time, every_tr[1], every_tr[2], every_tr[3], every_tr[4], every_tr[5],select_user)
                if len(is_record)==0:
                    #数据中心主表插入数据
                    datacenter.execute(insert_query, insert_values)
                    # 获取插入的行的 ID
                    inserted_id = datacenter.lastrowid
                    datacenter.nextset()
                    #print(inserted_id)
                    #sys.exit()
                    if inserted_id:
                        detail_url = every_tr[6]
                        driver.get(detail_url)
                        # 获取内容div
                        all_content_div = driver.find_element(By.CLASS_NAME, 'wrapper-session')
                        # 解析表格数据
                        all_p_rows = all_content_div.find_elements(By.TAG_NAME, 'p')  # 获取所有行
                        p_elements = [element.text for element in all_p_rows]
    #                     print(p_elements)
                        pattern = re.compile(r'Pattern:\s*(\w+)')
                        model = re.compile(r'Mode: (\w+)')
                        method = re.compile(r'Method:\s*(\w+)')
                        time = re.compile(r'Time \(s\): (\d+)')
                        Repetitions = re.compile(r'Repetitions: \s*(\w+)')
                        target_power_pattern = re.compile(r'Target power \(W\): (\d+)Conc (\d+)Ecc')
                        target_power_pattern1 = re.compile(r'Target force \(kg\): (\d+)Conc (\d+)Ecc')
                        work_interval = re.compile(r'Work interval \(%\): (\d+)')
    #                     model = re.compile(r'Model:\s*(\w+)')

                        detail_data_every = {'Pattern':'','Model':'','Method':'','Action_name':'','Time':'','Conc':'',
                                             'Ecc':'','Work_interval':'','Total_Volume':'','Total_Time':'','Maximum_Concentric_Power_Peak':'',
                                             'Concentric_Average_Power':'','Concentric_Power_St_Deviation':'','Concentric_Time':'',
                                             'Maximum_Eccentric_Power_Peak':'','Eccentric_Concentric_Average_Power':'','Eccentric_Power_St_Deviation':'',
                                             'Eccentric_Power_St_Deviation':'','Eccentric_Time':'','Peaks_Within_Target_Range':''}

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
                                detail_data_every['Action_name'] = Action_name
                                detail_data_every['Pattern'] = Pattern

                            if matchstr2:
                                Model = matchstr2.group(1)
                                detail_data_every['Model'] = Model
                            if matchstr3:
                                Method = matchstr3.group(1)
                                detail_data_every['Method'] = Method
                            if matchstr4:
                                Time = matchstr4.group(1)
                                detail_data_every['Time'] = Time
                            if matchstr41:
                                Time = matchstr41.group(1)
                                detail_data_every['Time'] = Time
                            if matchstr5:
                                Conc = matchstr5.group(1)
                                Ecc = matchstr5.group(2)
                                detail_data_every['Conc'] = Conc
                                detail_data_every['Ecc'] = Ecc
                            if matchstr51:
                                Conc = matchstr51.group(1)
                                Ecc = matchstr51.group(2)
                                detail_data_every['Conc'] = Conc
                                detail_data_every['Ecc'] = Ecc
                            if matchstr6:
                                Work_interval = matchstr6.group(1)
                                detail_data_every['Work_interval'] = Work_interval

                        # 解析表格数据
                        analysis = driver.find_element(By.ID, 'stats')
                        td_rows = analysis.find_elements(By.TAG_NAME, 'td')  # 获取所有行
                        detail_td_list = []
                        for td_row in td_rows:
                            cell_text = td_row.text  # 获取单元格的文本内容
                            cell_text = cell_text.replace('Kg', '')
                            cell_text = cell_text.replace('W', '')
                            cell_text = cell_text.replace('kJ', '')
                            cell_text = cell_text.replace('s', '')
                            detail_td_list.append(cell_text)

                        detail_data_every['Total_Volume'] = detail_td_list[0]
                        detail_data_every['Total_Time'] = detail_td_list[1]
                        detail_data_every['Maximum_Concentric_Power_Peak'] = detail_td_list[2]
                        detail_data_every['Concentric_Average_Power'] = detail_td_list[3]
                        detail_data_every['Concentric_Power_St_Deviation'] = detail_td_list[4]
                        detail_data_every['Concentric_Time'] = detail_td_list[5]
                        detail_data_every['Maximum_Eccentric_Power_Peak'] = detail_td_list[6]
                        detail_data_every['Eccentric_Concentric_Average_Power'] = detail_td_list[7]
                        detail_data_every['Eccentric_Power_St_Deviation'] = detail_td_list[8]
                        detail_data_every['Eccentric_Time'] = detail_td_list[9]
                        detail_data_every['Peaks_Within_Target_Range'] = detail_td_list[10].replace('%', '')


                        insert_detail_query = "INSERT INTO ydy_equipment_process (equipment_result_id, k1, k2, k3, k4, k5, k6, k7, k8, k9, k10, k11, k12, k13, k14, k15, k16, k17, k18, k19,k20)VALUES(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)"
                        insert_detail_values = (inserted_id, detail_data_every['Pattern'], detail_data_every['Model'], detail_data_every['Method'],
                                                detail_data_every['Action_name'], detail_data_every['Time'], detail_data_every['Conc'],
                                                detail_data_every['Ecc'], detail_data_every['Work_interval'],detail_data_every['Total_Volume'],
                                                detail_data_every['Total_Time'], detail_data_every['Maximum_Concentric_Power_Peak'], detail_data_every['Concentric_Average_Power'],
                                                detail_data_every['Concentric_Power_St_Deviation'], detail_data_every['Concentric_Time'],
                                                detail_data_every['Maximum_Eccentric_Power_Peak'], detail_data_every['Eccentric_Concentric_Average_Power'], detail_data_every['Eccentric_Power_St_Deviation'],
                                                detail_data_every['Eccentric_Time'], detail_data_every['Peaks_Within_Target_Range'], 1)
                        datacenter.execute(insert_detail_query, insert_detail_values)
                        inserted_id2 = datacenter.lastrowid
                        datacenter.nextset()
                        print("数据中心添加成功")

                        #根据详情页class获取总共有几组，循环写入过程表
                        # 获取内容div
                        all_content_div = driver.find_elements(By.CLASS_NAME, 'grafico-session')
    #                     print(all_content_div)
    #                     sys.exit()
                        #定义有多set
                        detail_set_list = []
                        set_content_index = 0
                        for set_content in all_content_div:
                            body_id = 'stats'+str(set_content_index)
                            analysis = driver.find_element(By.ID, body_id)
                            set_content_index += 1
                            # 解析表格数据
                            td_rows = analysis.find_elements(By.TAG_NAME, 'td')  # 获取所有行
                            detail_td_list = []
                            for td_row in td_rows:
                                cell_text = td_row.text  # 获取单元格的文本内容
                                cell_text = cell_text.replace('Kg', '')
                                cell_text = cell_text.replace('W', '')
                                cell_text = cell_text.replace('kJ', '')
                                cell_text = cell_text.replace('s', '')
                                detail_td_list.append(cell_text)

                            detail_data_every['Total_Volume'] = detail_td_list[0]
                            detail_data_every['Total_Time'] = detail_td_list[1]
                            detail_data_every['Maximum_Concentric_Power_Peak'] = detail_td_list[2]
                            detail_data_every['Concentric_Average_Power'] = detail_td_list[3]
                            detail_data_every['Concentric_Power_St_Deviation'] = detail_td_list[4]
                            detail_data_every['Concentric_Time'] = detail_td_list[5]
                            detail_data_every['Maximum_Eccentric_Power_Peak'] = detail_td_list[6]
                            detail_data_every['Eccentric_Concentric_Average_Power'] = detail_td_list[7]
                            detail_data_every['Eccentric_Power_St_Deviation'] = detail_td_list[8]
                            detail_data_every['Eccentric_Time'] = detail_td_list[9]
                            detail_data_every['Peaks_Within_Target_Range'] = detail_td_list[10].replace('%', '')


                            insert_detail_query = "INSERT INTO ydy_equipment_process (equipment_result_id, k1, k2, k3, k4, k5, k6, k7, k8, k9, k10, k11, k12, k13, k14, k15, k16, k17, k18, k19, k20)VALUES(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)"
                            insert_detail_values = (inserted_id, detail_data_every['Pattern'], detail_data_every['Model'], detail_data_every['Method'],
                                                    detail_data_every['Action_name'], detail_data_every['Time'], detail_data_every['Conc'],
                                                    detail_data_every['Ecc'], detail_data_every['Work_interval'],detail_data_every['Total_Volume'],
                                                    detail_data_every['Total_Time'], detail_data_every['Maximum_Concentric_Power_Peak'], detail_data_every['Concentric_Average_Power'],
                                                    detail_data_every['Concentric_Power_St_Deviation'], detail_data_every['Concentric_Time'],
                                                    detail_data_every['Maximum_Eccentric_Power_Peak'], detail_data_every['Eccentric_Concentric_Average_Power'], detail_data_every['Eccentric_Power_St_Deviation'],
                                                    detail_data_every['Eccentric_Time'], detail_data_every['Peaks_Within_Target_Range'], 2)
                            print(insert_detail_values)

                            datacenter.execute(insert_detail_query, insert_detail_values)
                            inserted_id2 = datacenter.lastrowid
                            datacenter.nextset()
                    else:
                        print('数据中心添加失败')
                else:
                    #根据返回值的id,循环详情页，挨个判断详情是否存在，无则添加，有跳过
                    result_id = is_record[0][0]
    #                 print(result_id)
                    query = "SELECT id FROM ydy_equipment_process where equipment_result_id="+str(result_id)+" limit 1"
                    datacenter.execute(query)
                    process_result = datacenter.fetchall()
                    datacenter.nextset()
                    if len(process_result) ==0:
                        # k1=Pattern=动作模式  k2=Model=训练强度单位 k3=Method=训练量单位   k4=Action_name=动作名称
                        # k5=Time=时间  k6=目标功率-向心=Conc k7=目标功率-离心=Ecc k8=训练间隔时间百分比=Work_interval
                        # k9=总做功=Total_Volume   k10=总时间=Total_Time k11=向心峰值功率=Maximum_Concentric_Power_Peak
                        # k12=向心平均功率=Concentric_Average_Power k13=向心功率标准差=Concentric_Power_St_Deviation
                        # k14=向心时间=Concentric_Time k15=离心峰值功率=Maximum_Eccentric_Power_Peak  k16=离心平均功率=Eccentric_Concentric_Average_Power
                        # k17=离心功率标准差=Eccentric_Power_St_Deviation k18=离心功时间=Eccentric_Time k19=目标范围峰值百分比=Peaks_Within_Target_Range
                        detail_url = every_tr[6]
                        driver.get(detail_url)
                        # 获取内容div
                        all_content_div = driver.find_element(By.CLASS_NAME, 'wrapper-session')
                        # 解析表格数据
                        all_p_rows = all_content_div.find_elements(By.TAG_NAME, 'p')  # 获取所有行
                        p_elements = [element.text for element in all_p_rows]
    #                     print(p_elements)
                        pattern = re.compile(r'Pattern:\s*(\w+)')
                        model = re.compile(r'Mode: (\w+)')
                        method = re.compile(r'Method:\s*(\w+)')
                        time = re.compile(r'Time \(s\): (\d+)')
                        Repetitions = re.compile(r'Repetitions: \s*(\w+)')
                        target_power_pattern = re.compile(r'Target power \(W\): (\d+)Conc (\d+)Ecc')
                        target_power_pattern1 = re.compile(r'Target force \(kg\): (\d+)Conc (\d+)Ecc')
                        work_interval = re.compile(r'Work interval \(%\): (\d+)')
    #                     model = re.compile(r'Model:\s*(\w+)')

                        detail_data_every = {'Pattern':'','Model':'','Method':'','Action_name':'','Time':'','Conc':'',
                                             'Ecc':'','Work_interval':'','Total_Volume':'','Total_Time':'','Maximum_Concentric_Power_Peak':'',
                                             'Concentric_Average_Power':'','Concentric_Power_St_Deviation':'','Concentric_Time':'',
                                             'Maximum_Eccentric_Power_Peak':'','Eccentric_Concentric_Average_Power':'','Eccentric_Power_St_Deviation':'',
                                             'Eccentric_Power_St_Deviation':'','Eccentric_Time':'','Peaks_Within_Target_Range':''}

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
                                detail_data_every['Action_name'] = Action_name
                                detail_data_every['Pattern'] = Pattern

                            if matchstr2:
                                Model = matchstr2.group(1)
                                detail_data_every['Model'] = Model
                            if matchstr3:
                                Method = matchstr3.group(1)
                                detail_data_every['Method'] = Method
                            if matchstr4:
                                Time = matchstr4.group(1)
                                detail_data_every['Time'] = Time
                            if matchstr41:
                                Time = matchstr41.group(1)
                                detail_data_every['Time'] = Time
                            if matchstr5:
                                Conc = matchstr5.group(1)
                                Ecc = matchstr5.group(2)
                                detail_data_every['Conc'] = Conc
                                detail_data_every['Ecc'] = Ecc
                            if matchstr51:
                                Conc = matchstr51.group(1)
                                Ecc = matchstr51.group(2)
                                detail_data_every['Conc'] = Conc
                                detail_data_every['Ecc'] = Ecc
                            if matchstr6:
                                Work_interval = matchstr6.group(1)
                                detail_data_every['Work_interval'] = Work_interval

                        # 解析表格数据
                        analysis = driver.find_element(By.ID, 'stats')
                        td_rows = analysis.find_elements(By.TAG_NAME, 'td')  # 获取所有行
                        detail_td_list = []
                        for td_row in td_rows:
                            cell_text = td_row.text  # 获取单元格的文本内容
                            cell_text = cell_text.replace('Kg', '')
                            cell_text = cell_text.replace('W', '')
                            cell_text = cell_text.replace('kJ', '')
                            cell_text = cell_text.replace('s', '')
                            detail_td_list.append(cell_text)

                        detail_data_every['Total_Volume'] = detail_td_list[0]
                        detail_data_every['Total_Time'] = detail_td_list[1]
                        detail_data_every['Maximum_Concentric_Power_Peak'] = detail_td_list[2]
                        detail_data_every['Concentric_Average_Power'] = detail_td_list[3]
                        detail_data_every['Concentric_Power_St_Deviation'] = detail_td_list[4]
                        detail_data_every['Concentric_Time'] = detail_td_list[5]
                        detail_data_every['Maximum_Eccentric_Power_Peak'] = detail_td_list[6]
                        detail_data_every['Eccentric_Concentric_Average_Power'] = detail_td_list[7]
                        detail_data_every['Eccentric_Power_St_Deviation'] = detail_td_list[8]
                        detail_data_every['Eccentric_Time'] = detail_td_list[9]
                        detail_data_every['Peaks_Within_Target_Range'] = detail_td_list[10].replace('%', '')


                        insert_detail_query = "INSERT INTO ydy_equipment_process (equipment_result_id, k1, k2, k3, k4, k5, k6, k7, k8, k9, k10, k11, k12, k13, k14, k15, k16, k17, k18, k19, k20)VALUES(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)"
                        insert_detail_values = (result_id, detail_data_every['Pattern'], detail_data_every['Model'], detail_data_every['Method'],
                                                detail_data_every['Action_name'], detail_data_every['Time'], detail_data_every['Conc'],
                                                detail_data_every['Ecc'], detail_data_every['Work_interval'],detail_data_every['Total_Volume'],
                                                detail_data_every['Total_Time'], detail_data_every['Maximum_Concentric_Power_Peak'], detail_data_every['Concentric_Average_Power'],
                                                detail_data_every['Concentric_Power_St_Deviation'], detail_data_every['Concentric_Time'],
                                                detail_data_every['Maximum_Eccentric_Power_Peak'], detail_data_every['Eccentric_Concentric_Average_Power'], detail_data_every['Eccentric_Power_St_Deviation'],
                                                detail_data_every['Eccentric_Time'], detail_data_every['Peaks_Within_Target_Range'], 1)
                        datacenter.execute(insert_detail_query, insert_detail_values)
                        inserted_id2 = datacenter.lastrowid
                        datacenter.nextset()
                        #根据详情页class获取总共有几组，循环写入过程表
                        # 获取内容div
                        all_content_div = driver.find_elements(By.CLASS_NAME, 'grafico-session')
    #                     print(all_content_div)
    #                     sys.exit()
                        #定义有多set
                        detail_set_list = []
                        set_content_index = 0
                        for set_content in all_content_div:
                            body_id = 'stats'+str(set_content_index)
                            analysis = driver.find_element(By.ID, body_id)
                            set_content_index += 1
                            # 解析表格数据
                            td_rows = analysis.find_elements(By.TAG_NAME, 'td')  # 获取所有行
                            detail_td_list = []
                            for td_row in td_rows:
                                cell_text = td_row.text  # 获取单元格的文本内容
                                cell_text = cell_text.replace('Kg', '')
                                cell_text = cell_text.replace('W', '')
                                cell_text = cell_text.replace('kJ', '')
                                cell_text = cell_text.replace('s', '')
                                detail_td_list.append(cell_text)

                            detail_data_every['Total_Volume'] = detail_td_list[0]
                            detail_data_every['Total_Time'] = detail_td_list[1]
                            detail_data_every['Maximum_Concentric_Power_Peak'] = detail_td_list[2]
                            detail_data_every['Concentric_Average_Power'] = detail_td_list[3]
                            detail_data_every['Concentric_Power_St_Deviation'] = detail_td_list[4]
                            detail_data_every['Concentric_Time'] = detail_td_list[5]
                            detail_data_every['Maximum_Eccentric_Power_Peak'] = detail_td_list[6]
                            detail_data_every['Eccentric_Concentric_Average_Power'] = detail_td_list[7]
                            detail_data_every['Eccentric_Power_St_Deviation'] = detail_td_list[8]
                            detail_data_every['Eccentric_Time'] = detail_td_list[9]
                            detail_data_every['Peaks_Within_Target_Range'] = detail_td_list[10].replace('%', '')


                            insert_detail_query = "INSERT INTO ydy_equipment_process (equipment_result_id, k1, k2, k3, k4, k5, k6, k7, k8, k9, k10, k11, k12, k13, k14, k15, k16, k17, k18, k19, k20)VALUES(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)"
                            insert_detail_values = (result_id, detail_data_every['Pattern'], detail_data_every['Model'], detail_data_every['Method'],
                                                    detail_data_every['Action_name'], detail_data_every['Time'], detail_data_every['Conc'],
                                                    detail_data_every['Ecc'], detail_data_every['Work_interval'],detail_data_every['Total_Volume'],
                                                    detail_data_every['Total_Time'], detail_data_every['Maximum_Concentric_Power_Peak'], detail_data_every['Concentric_Average_Power'],
                                                    detail_data_every['Concentric_Power_St_Deviation'], detail_data_every['Concentric_Time'],
                                                    detail_data_every['Maximum_Eccentric_Power_Peak'], detail_data_every['Eccentric_Concentric_Average_Power'], detail_data_every['Eccentric_Power_St_Deviation'],
                                                    detail_data_every['Eccentric_Time'], detail_data_every['Peaks_Within_Target_Range'], 2)
                            datacenter.execute(insert_detail_query, insert_detail_values)
                            inserted_id2 = datacenter.lastrowid
                            datacenter.nextset()

                #业务系统数据处理
                if len(is_record1)==0:
                    #业务主表插入数据
                    business_sys.execute(insert_query, insert_values)
                    # 获取插入的行的 ID
                    inserted_id1 = business_sys.lastrowid
                    business_sys.nextset()
#                     print(inserted_id1)
                    #sys.exit()
                    if inserted_id1:
                        detail_url = every_tr[6]
                        driver.get(detail_url)
                        # 获取内容div
                        all_content_div = driver.find_element(By.CLASS_NAME, 'wrapper-session')
                        # 解析表格数据
                        all_p_rows = all_content_div.find_elements(By.TAG_NAME, 'p')  # 获取所有行
                        p_elements = [element.text for element in all_p_rows]
    #                     print(p_elements)
                        pattern = re.compile(r'Pattern:\s*(\w+)')
                        model = re.compile(r'Mode: (\w+)')
                        method = re.compile(r'Method:\s*(\w+)')
                        time = re.compile(r'Time \(s\): (\d+)')
                        Repetitions = re.compile(r'Repetitions: \s*(\w+)')
                        target_power_pattern = re.compile(r'Target power \(W\): (\d+)Conc (\d+)Ecc')
                        target_power_pattern1 = re.compile(r'Target force \(kg\): (\d+)Conc (\d+)Ecc')
                        work_interval = re.compile(r'Work interval \(%\): (\d+)')
    #                     model = re.compile(r'Model:\s*(\w+)')

                        detail_data_every = {'Pattern':'','Model':'','Method':'','Action_name':'','Time':'','Conc':'',
                                             'Ecc':'','Work_interval':'','Total_Volume':'','Total_Time':'','Maximum_Concentric_Power_Peak':'',
                                             'Concentric_Average_Power':'','Concentric_Power_St_Deviation':'','Concentric_Time':'',
                                             'Maximum_Eccentric_Power_Peak':'','Eccentric_Concentric_Average_Power':'','Eccentric_Power_St_Deviation':'',
                                             'Eccentric_Power_St_Deviation':'','Eccentric_Time':'','Peaks_Within_Target_Range':''}

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
                                detail_data_every['Action_name'] = Action_name
                                detail_data_every['Pattern'] = Pattern

                            if matchstr2:
                                Model = matchstr2.group(1)
                                detail_data_every['Model'] = Model
                            if matchstr3:
                                Method = matchstr3.group(1)
                                detail_data_every['Method'] = Method
                            if matchstr4:
                                Time = matchstr4.group(1)
                                detail_data_every['Time'] = Time
                            if matchstr41:
                                Time = matchstr41.group(1)
                                detail_data_every['Time'] = Time
                            if matchstr5:
                                Conc = matchstr5.group(1)
                                Ecc = matchstr5.group(2)
                                detail_data_every['Conc'] = Conc
                                detail_data_every['Ecc'] = Ecc
                            if matchstr51:
                                Conc = matchstr51.group(1)
                                Ecc = matchstr51.group(2)
                                detail_data_every['Conc'] = Conc
                                detail_data_every['Ecc'] = Ecc
                            if matchstr6:
                                Work_interval = matchstr6.group(1)
                                detail_data_every['Work_interval'] = Work_interval

                        # 解析表格数据
                        analysis = driver.find_element(By.ID, 'stats')
                        td_rows = analysis.find_elements(By.TAG_NAME, 'td')  # 获取所有行
                        detail_td_list = []
                        for td_row in td_rows:
                            cell_text = td_row.text  # 获取单元格的文本内容
                            cell_text = cell_text.replace('Kg', '')
                            cell_text = cell_text.replace('W', '')
                            cell_text = cell_text.replace('kJ', '')
                            cell_text = cell_text.replace('s', '')
                            detail_td_list.append(cell_text)

                        detail_data_every['Total_Volume'] = detail_td_list[0]
                        detail_data_every['Total_Time'] = detail_td_list[1]
                        detail_data_every['Maximum_Concentric_Power_Peak'] = detail_td_list[2]
                        detail_data_every['Concentric_Average_Power'] = detail_td_list[3]
                        detail_data_every['Concentric_Power_St_Deviation'] = detail_td_list[4]
                        detail_data_every['Concentric_Time'] = detail_td_list[5]
                        detail_data_every['Maximum_Eccentric_Power_Peak'] = detail_td_list[6]
                        detail_data_every['Eccentric_Concentric_Average_Power'] = detail_td_list[7]
                        detail_data_every['Eccentric_Power_St_Deviation'] = detail_td_list[8]
                        detail_data_every['Eccentric_Time'] = detail_td_list[9]
                        detail_data_every['Peaks_Within_Target_Range'] = detail_td_list[10].replace('%', '')


                        insert_detail_query = "INSERT INTO ydy_equipment_process (equipment_result_id, k1, k2, k3, k4, k5, k6, k7, k8, k9, k10, k11, k12, k13, k14, k15, k16, k17, k18, k19, k20)VALUES(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)"
                        insert_detail_values = (inserted_id1, detail_data_every['Pattern'], detail_data_every['Model'], detail_data_every['Method'],
                                                detail_data_every['Action_name'], detail_data_every['Time'], detail_data_every['Conc'],
                                                detail_data_every['Ecc'], detail_data_every['Work_interval'],detail_data_every['Total_Volume'],
                                                detail_data_every['Total_Time'], detail_data_every['Maximum_Concentric_Power_Peak'], detail_data_every['Concentric_Average_Power'],
                                                detail_data_every['Concentric_Power_St_Deviation'], detail_data_every['Concentric_Time'],
                                                detail_data_every['Maximum_Eccentric_Power_Peak'], detail_data_every['Eccentric_Concentric_Average_Power'], detail_data_every['Eccentric_Power_St_Deviation'],
                                                detail_data_every['Eccentric_Time'], detail_data_every['Peaks_Within_Target_Range'], 1)
                        business_sys.execute(insert_detail_query, insert_detail_values)
                        inserted_id2 = business_sys.lastrowid
                        business_sys.nextset()
                        #根据详情页class获取总共有几组，循环写入过程表
                        # 获取内容div
                        all_content_div = driver.find_elements(By.CLASS_NAME, 'grafico-session')
    #                     print(all_content_div)
    #                     sys.exit()
                        #定义有多set
                        detail_set_list = []
                        set_content_index = 0
                        for set_content in all_content_div:
                            body_id = 'stats'+str(set_content_index)
                            analysis = driver.find_element(By.ID, body_id)
                            set_content_index += 1
                            # 解析表格数据
                            td_rows = analysis.find_elements(By.TAG_NAME, 'td')  # 获取所有行
                            detail_td_list = []
                            for td_row in td_rows:
                                cell_text = td_row.text  # 获取单元格的文本内容
                                cell_text = cell_text.replace('Kg', '')
                                cell_text = cell_text.replace('W', '')
                                cell_text = cell_text.replace('kJ', '')
                                cell_text = cell_text.replace('s', '')
                                detail_td_list.append(cell_text)

                            detail_data_every['Total_Volume'] = detail_td_list[0]
                            detail_data_every['Total_Time'] = detail_td_list[1]
                            detail_data_every['Maximum_Concentric_Power_Peak'] = detail_td_list[2]
                            detail_data_every['Concentric_Average_Power'] = detail_td_list[3]
                            detail_data_every['Concentric_Power_St_Deviation'] = detail_td_list[4]
                            detail_data_every['Concentric_Time'] = detail_td_list[5]
                            detail_data_every['Maximum_Eccentric_Power_Peak'] = detail_td_list[6]
                            detail_data_every['Eccentric_Concentric_Average_Power'] = detail_td_list[7]
                            detail_data_every['Eccentric_Power_St_Deviation'] = detail_td_list[8]
                            detail_data_every['Eccentric_Time'] = detail_td_list[9]
                            detail_data_every['Peaks_Within_Target_Range'] = detail_td_list[10].replace('%', '')


                            insert_detail_query = "INSERT INTO ydy_equipment_process (equipment_result_id, k1, k2, k3, k4, k5, k6, k7, k8, k9, k10, k11, k12, k13, k14, k15, k16, k17, k18, k19, k20)VALUES(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)"
                            insert_detail_values = (inserted_id1, detail_data_every['Pattern'], detail_data_every['Model'], detail_data_every['Method'],
                                                    detail_data_every['Action_name'], detail_data_every['Time'], detail_data_every['Conc'],
                                                    detail_data_every['Ecc'], detail_data_every['Work_interval'],detail_data_every['Total_Volume'],
                                                    detail_data_every['Total_Time'], detail_data_every['Maximum_Concentric_Power_Peak'], detail_data_every['Concentric_Average_Power'],
                                                    detail_data_every['Concentric_Power_St_Deviation'], detail_data_every['Concentric_Time'],
                                                    detail_data_every['Maximum_Eccentric_Power_Peak'], detail_data_every['Eccentric_Concentric_Average_Power'], detail_data_every['Eccentric_Power_St_Deviation'],
                                                    detail_data_every['Eccentric_Time'], detail_data_every['Peaks_Within_Target_Range'], 2)
                            business_sys.execute(insert_detail_query, insert_detail_values)
                            inserted_id2 = business_sys.lastrowid
                            business_sys.nextset()
                    else:
                        print('业务系统添加失败')
                else:
                    #根据返回值的id判断详情页是否存入数据
                    #根据返回值的id,循环详情页，挨个判断详情是否存在，无则添加，有跳过
                    result_id = is_record1[0][0]
    #                 print(result_id)
                    query = "SELECT id FROM ydy_equipment_process where equipment_result_id="+str(result_id)+" limit 1"
                    business_sys.execute(query)
                    process_result = business_sys.fetchall()
                    business_sys.nextset()
                    if len(process_result) ==0:
                        detail_url = every_tr[6]
                        driver.get(detail_url)
                        # 获取内容div
                        all_content_div = driver.find_element(By.CLASS_NAME, 'wrapper-session')
                        # 解析表格数据
                        all_p_rows = all_content_div.find_elements(By.TAG_NAME, 'p')  # 获取所有行
                        p_elements = [element.text for element in all_p_rows]
    #                     print(p_elements)
                        pattern = re.compile(r'Pattern:\s*(\w+)')
                        model = re.compile(r'Mode: (\w+)')
                        method = re.compile(r'Method:\s*(\w+)')
                        time = re.compile(r'Time \(s\): (\d+)')
                        Repetitions = re.compile(r'Repetitions: \s*(\w+)')
                        target_power_pattern = re.compile(r'Target power \(W\): (\d+)Conc (\d+)Ecc')
                        target_power_pattern1 = re.compile(r'Target force \(kg\): (\d+)Conc (\d+)Ecc')
                        work_interval = re.compile(r'Work interval \(%\): (\d+)')
    #                     model = re.compile(r'Model:\s*(\w+)')

                        detail_data_every = {'Pattern':'','Model':'','Method':'','Action_name':'','Time':'','Conc':'',
                                             'Ecc':'','Work_interval':'','Total_Volume':'','Total_Time':'','Maximum_Concentric_Power_Peak':'',
                                             'Concentric_Average_Power':'','Concentric_Power_St_Deviation':'','Concentric_Time':'',
                                             'Maximum_Eccentric_Power_Peak':'','Eccentric_Concentric_Average_Power':'','Eccentric_Power_St_Deviation':'',
                                             'Eccentric_Power_St_Deviation':'','Eccentric_Time':'','Peaks_Within_Target_Range':''}

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
                                detail_data_every['Action_name'] = Action_name
                                detail_data_every['Pattern'] = Pattern

                            if matchstr2:
                                Model = matchstr2.group(1)
                                detail_data_every['Model'] = Model
                            if matchstr3:
                                Method = matchstr3.group(1)
                                detail_data_every['Method'] = Method
                            if matchstr4:
                                Time = matchstr4.group(1)
                                detail_data_every['Time'] = Time
                            if matchstr41:
                                Time = matchstr41.group(1)
                                detail_data_every['Time'] = Time
                            if matchstr5:
                                Conc = matchstr5.group(1)
                                Ecc = matchstr5.group(2)
                                detail_data_every['Conc'] = Conc
                                detail_data_every['Ecc'] = Ecc
                            if matchstr51:
                                Conc = matchstr51.group(1)
                                Ecc = matchstr51.group(2)
                                detail_data_every['Conc'] = Conc
                                detail_data_every['Ecc'] = Ecc
                            if matchstr6:
                                Work_interval = matchstr6.group(1)
                                detail_data_every['Work_interval'] = Work_interval

                        # 解析表格数据
                        analysis = driver.find_element(By.ID, 'stats')
                        td_rows = analysis.find_elements(By.TAG_NAME, 'td')  # 获取所有行
                        detail_td_list = []
                        for td_row in td_rows:
                            cell_text = td_row.text  # 获取单元格的文本内容
                            cell_text = cell_text.replace('Kg', '')
                            cell_text = cell_text.replace('W', '')
                            cell_text = cell_text.replace('kJ', '')
                            cell_text = cell_text.replace('s', '')
                            detail_td_list.append(cell_text)

                        detail_data_every['Total_Volume'] = detail_td_list[0]
                        detail_data_every['Total_Time'] = detail_td_list[1]
                        detail_data_every['Maximum_Concentric_Power_Peak'] = detail_td_list[2]
                        detail_data_every['Concentric_Average_Power'] = detail_td_list[3]
                        detail_data_every['Concentric_Power_St_Deviation'] = detail_td_list[4]
                        detail_data_every['Concentric_Time'] = detail_td_list[5]
                        detail_data_every['Maximum_Eccentric_Power_Peak'] = detail_td_list[6]
                        detail_data_every['Eccentric_Concentric_Average_Power'] = detail_td_list[7]
                        detail_data_every['Eccentric_Power_St_Deviation'] = detail_td_list[8]
                        detail_data_every['Eccentric_Time'] = detail_td_list[9]
                        detail_data_every['Peaks_Within_Target_Range'] = detail_td_list[10].replace('%', '')


                        insert_detail_query = "INSERT INTO ydy_equipment_process (equipment_result_id, k1, k2, k3, k4, k5, k6, k7, k8, k9, k10, k11, k12, k13, k14, k15, k16, k17, k18, k19, k20)VALUES(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)"
                        insert_detail_values = (result_id, detail_data_every['Pattern'], detail_data_every['Model'], detail_data_every['Method'],
                                                detail_data_every['Action_name'], detail_data_every['Time'], detail_data_every['Conc'],
                                                detail_data_every['Ecc'], detail_data_every['Work_interval'],detail_data_every['Total_Volume'],
                                                detail_data_every['Total_Time'], detail_data_every['Maximum_Concentric_Power_Peak'], detail_data_every['Concentric_Average_Power'],
                                                detail_data_every['Concentric_Power_St_Deviation'], detail_data_every['Concentric_Time'],
                                                detail_data_every['Maximum_Eccentric_Power_Peak'], detail_data_every['Eccentric_Concentric_Average_Power'], detail_data_every['Eccentric_Power_St_Deviation'],
                                                detail_data_every['Eccentric_Time'], detail_data_every['Peaks_Within_Target_Range'], 1)
                        business_sys.execute(insert_detail_query, insert_detail_values)
                        inserted_id2 = business_sys.lastrowid
                        business_sys.nextset()

                        #根据详情页class获取总共有几组，循环写入过程表
                        # 获取内容div
                        all_content_div = driver.find_elements(By.CLASS_NAME, 'grafico-session')
    #                     print(all_content_div)
    #                     sys.exit()
                        #定义有多set
                        detail_set_list = []
                        set_content_index = 0
                        for set_content in all_content_div:
                            body_id = 'stats'+str(set_content_index)
                            analysis = driver.find_element(By.ID, body_id)
                            set_content_index += 1
                            # 解析表格数据
                            td_rows = analysis.find_elements(By.TAG_NAME, 'td')  # 获取所有行
                            detail_td_list = []
                            for td_row in td_rows:
                                cell_text = td_row.text  # 获取单元格的文本内容
                                cell_text = cell_text.replace('Kg', '')
                                cell_text = cell_text.replace('W', '')
                                cell_text = cell_text.replace('kJ', '')
                                cell_text = cell_text.replace('s', '')
                                detail_td_list.append(cell_text)

                            detail_data_every['Total_Volume'] = detail_td_list[0]
                            detail_data_every['Total_Time'] = detail_td_list[1]
                            detail_data_every['Maximum_Concentric_Power_Peak'] = detail_td_list[2]
                            detail_data_every['Concentric_Average_Power'] = detail_td_list[3]
                            detail_data_every['Concentric_Power_St_Deviation'] = detail_td_list[4]
                            detail_data_every['Concentric_Time'] = detail_td_list[5]
                            detail_data_every['Maximum_Eccentric_Power_Peak'] = detail_td_list[6]
                            detail_data_every['Eccentric_Concentric_Average_Power'] = detail_td_list[7]
                            detail_data_every['Eccentric_Power_St_Deviation'] = detail_td_list[8]
                            detail_data_every['Eccentric_Time'] = detail_td_list[9]
                            detail_data_every['Peaks_Within_Target_Range'] = detail_td_list[10].replace('%', '')


                            insert_detail_query = "INSERT INTO ydy_equipment_process (equipment_result_id, k1, k2, k3, k4, k5, k6, k7, k8, k9, k10, k11, k12, k13, k14, k15, k16, k17, k18, k19, k20)VALUES(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)"
                            insert_detail_values = (result_id, detail_data_every['Pattern'], detail_data_every['Model'], detail_data_every['Method'],
                                                    detail_data_every['Action_name'], detail_data_every['Time'], detail_data_every['Conc'],
                                                    detail_data_every['Ecc'], detail_data_every['Work_interval'],detail_data_every['Total_Volume'],
                                                    detail_data_every['Total_Time'], detail_data_every['Maximum_Concentric_Power_Peak'], detail_data_every['Concentric_Average_Power'],
                                                    detail_data_every['Concentric_Power_St_Deviation'], detail_data_every['Concentric_Time'],
                                                    detail_data_every['Maximum_Eccentric_Power_Peak'], detail_data_every['Eccentric_Concentric_Average_Power'], detail_data_every['Eccentric_Power_St_Deviation'],
                                                    detail_data_every['Eccentric_Time'], detail_data_every['Peaks_Within_Target_Range'], 2)
                            business_sys.execute(insert_detail_query, insert_detail_values)
                            inserted_id2 = business_sys.lastrowid
                            business_sys.nextset()
        # 提交事务
        cnx1.commit()
        cnx2.commit()

        # 关闭游标和数据库连接
        datacenter.close()
        cnx1.close()
        business_sys.close()
        cnx2.close()

print('数据收集完成')
sys.exit()
