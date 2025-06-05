CREATE TABLE `ydy_equipment_result` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`business` varchar(64) NOT NULL DEFAULT '' COMMENT '商户唯一标识',
`equipment_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '设备id',
`department_uuid` char(36) NOT NULL DEFAULT '' COMMENT '部门uuid',
`staff_uuid` char(36) NOT NULL DEFAULT '' COMMENT '人员uuid',
`date` date NOT NULL DEFAULT '2000-01-01' COMMENT '训练/测试日期',
`record_time` datetime NOT NULL DEFAULT '2000-01-01 00:00:00' COMMENT '训练/测试时间',
`reference` varchar(36) NOT NULL DEFAULT '' COMMENT '外部唯一标识',
`k1` varchar(64) NOT NULL DEFAULT '',
`k2` varchar(64) NOT NULL DEFAULT '',
`k3` varchar(64) NOT NULL DEFAULT '',
`k4` varchar(64) NOT NULL DEFAULT '',
`k5` varchar(64) NOT NULL DEFAULT '',
`k6` varchar(64) NOT NULL DEFAULT '',
`k7` varchar(64) NOT NULL DEFAULT '',
`k8` varchar(64) NOT NULL DEFAULT '',
`k9` varchar(64) NOT NULL DEFAULT '',
`k10` varchar(64) NOT NULL DEFAULT '',
`k11` varchar(64) NOT NULL DEFAULT '',
`k12` varchar(64) NOT NULL DEFAULT '',
`k13` varchar(64) NOT NULL DEFAULT '',
`k14` varchar(64) NOT NULL DEFAULT '',
`k15` varchar(64) NOT NULL DEFAULT '',
`k16` varchar(64) NOT NULL DEFAULT '',
`k17` varchar(64) NOT NULL DEFAULT '',
`k18` varchar(64) NOT NULL DEFAULT '',
`k19` varchar(64) NOT NULL DEFAULT '',
`k20` varchar(64) NOT NULL DEFAULT '',
`is_del` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '是否删除1是0否',
`create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
`update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
`create_by` varchar(36) NOT NULL DEFAULT '' COMMENT '创建人',
PRIMARY KEY (`id`),
KEY `ber_index` (`business`,`equipment_id`,`reference`),
KEY `staff_uuid` (`staff_uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='设备数据主表';

CREATE TABLE `ydy_equipment_result_extend` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`equipment_result_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '设备数据主表id',
`k21` varchar(64) NOT NULL DEFAULT '',
`k22` varchar(64) NOT NULL DEFAULT '',
`k23` varchar(64) NOT NULL DEFAULT '',
`k24` varchar(64) NOT NULL DEFAULT '',
`k25` varchar(64) NOT NULL DEFAULT '',
`k26` varchar(64) NOT NULL DEFAULT '',
`k27` varchar(64) NOT NULL DEFAULT '',
`k28` varchar(64) NOT NULL DEFAULT '',
`k29` varchar(64) NOT NULL DEFAULT '',
`k30` varchar(64) NOT NULL DEFAULT '',
`k31` varchar(64) NOT NULL DEFAULT '',
`k32` varchar(64) NOT NULL DEFAULT '',
`k33` varchar(64) NOT NULL DEFAULT '',
`k34` varchar(64) NOT NULL DEFAULT '',
`k35` varchar(64) NOT NULL DEFAULT '',
`k36` varchar(64) NOT NULL DEFAULT '',
`k37` varchar(64) NOT NULL DEFAULT '',
`k38` varchar(64) NOT NULL DEFAULT '',
`k39` varchar(64) NOT NULL DEFAULT '',
`k40` varchar(64) NOT NULL DEFAULT '',
`is_del` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '是否删除1是0否',
`create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
`update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
`create_by` varchar(36) NOT NULL DEFAULT '' COMMENT '创建人',
PRIMARY KEY (`id`),
KEY `equipment_result_id` (`equipment_result_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='设备数据主表扩展表';

CREATE TABLE `ydy_equipment_process` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`equipment_result_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '设备数据主表id',
`reference` varchar(36) NOT NULL DEFAULT '' COMMENT '外部唯一标识',
`k1` varchar(64) NOT NULL DEFAULT '',
`k2` varchar(64) NOT NULL DEFAULT '',
`k3` varchar(64) NOT NULL DEFAULT '',
`k4` varchar(64) NOT NULL DEFAULT '',
`k5` varchar(64) NOT NULL DEFAULT '',
`k6` varchar(64) NOT NULL DEFAULT '',
`k7` varchar(64) NOT NULL DEFAULT '',
`k8` varchar(64) NOT NULL DEFAULT '',
`k9` varchar(64) NOT NULL DEFAULT '',
`k10` varchar(64) NOT NULL DEFAULT '',
`k11` varchar(64) NOT NULL DEFAULT '',
`k12` varchar(64) NOT NULL DEFAULT '',
`k13` varchar(64) NOT NULL DEFAULT '',
`k14` varchar(64) NOT NULL DEFAULT '',
`k15` varchar(64) NOT NULL DEFAULT '',
`k16` varchar(64) NOT NULL DEFAULT '',
`k17` varchar(64) NOT NULL DEFAULT '',
`k18` varchar(64) NOT NULL DEFAULT '',
`k19` varchar(64) NOT NULL DEFAULT '',
`k20` varchar(64) NOT NULL DEFAULT '',
`is_del` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '是否删除1是0否',
`create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
`update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
`create_by` varchar(36) NOT NULL DEFAULT '' COMMENT '创建人',
PRIMARY KEY (`id`),
KEY `equipment_result_id` (`equipment_result_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='设备数据详细/过程表';

CREATE TABLE `ydy_equipment_process_extend` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`equipment_process_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '设备过程数据id',
`k21` varchar(64) NOT NULL DEFAULT '',
`k22` varchar(64) NOT NULL DEFAULT '',
`k23` varchar(64) NOT NULL DEFAULT '',
`k24` varchar(64) NOT NULL DEFAULT '',
`k25` varchar(64) NOT NULL DEFAULT '',
`k26` varchar(64) NOT NULL DEFAULT '',
`k27` varchar(64) NOT NULL DEFAULT '',
`k28` varchar(64) NOT NULL DEFAULT '',
`k29` varchar(64) NOT NULL DEFAULT '',
`k30` varchar(64) NOT NULL DEFAULT '',
`is_del` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '是否删除1是0否',
`create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
`update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
`create_by` varchar(36) NOT NULL DEFAULT '' COMMENT '创建人',
PRIMARY KEY (`id`),
KEY `equipment_process_id` (`equipment_process_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='设备数据详细/过程扩展表';

CREATE TABLE `ydy_equipment_relation` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`type` int(6) unsigned NOT NULL DEFAULT '1000' COMMENT '类型1000：force步态分析仪',
`k` varchar(32) NOT NULL DEFAULT '' COMMENT '设备参数真实列名',
`v` varchar(8) NOT NULL DEFAULT '' COMMENT '设备扩展表中的列名',
`is_del` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '是否删除1是0否',
`create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
`update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
`create_by` varchar(36) NOT NULL DEFAULT '' COMMENT '创建人',
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='设备测试参数与扩展表对应关系';

CREATE TABLE `ydy_equipment_relation_dict` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`type` int(6) unsigned NOT NULL DEFAULT '1000' COMMENT '类型1000：force步态分析仪',
`note` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
`is_del` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '是否删除1是0否',
`create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
`update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
`create_by` varchar(36) NOT NULL DEFAULT '' COMMENT '创建人',
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='设备数据关系字典';

ALTER TABLE `ydy_equipment_result_extend`
ADD COLUMN `k41` varchar(64) NOT NULL DEFAULT '' AFTER `k40`;

ALTER TABLE `ydy_equipment_result_extend`
ADD COLUMN `k42` varchar(64) NOT NULL DEFAULT '' AFTER `k41`,
ADD COLUMN `k43` varchar(64) NOT NULL DEFAULT '' AFTER `k42`,
ADD COLUMN `k44` varchar(64) NOT NULL DEFAULT '' AFTER `k43`,
ADD COLUMN `k45` varchar(64) NOT NULL DEFAULT '' AFTER `k44`,
ADD COLUMN `k46` varchar(64) NOT NULL DEFAULT '' AFTER `k45`,
ADD COLUMN `k47` varchar(64) NOT NULL DEFAULT '' AFTER `k46`,
ADD COLUMN `k48` varchar(64) NOT NULL DEFAULT '' AFTER `k47`,
ADD COLUMN `k49` varchar(64) NOT NULL DEFAULT '' AFTER `k48`,
ADD COLUMN `k50` varchar(64) NOT NULL DEFAULT '' AFTER `k49`,
ADD COLUMN `k51` varchar(64) NOT NULL DEFAULT '' AFTER `k50`,
ADD COLUMN `k52` varchar(64) NOT NULL DEFAULT '' AFTER `k51`,
ADD COLUMN `k53` varchar(64) NOT NULL DEFAULT '' AFTER `k52`,
ADD COLUMN `k54` varchar(64) NOT NULL DEFAULT '' AFTER `k53`,
ADD COLUMN `k55` varchar(64) NOT NULL DEFAULT '' AFTER `k54`,
ADD COLUMN `k56` varchar(64) NOT NULL DEFAULT '' AFTER `k55`,
ADD COLUMN `k57` varchar(64) NOT NULL DEFAULT '' AFTER `k56`,
ADD COLUMN `k58` varchar(64) NOT NULL DEFAULT '' AFTER `k57`,
ADD COLUMN `k59` varchar(64) NOT NULL DEFAULT '' AFTER `k58`,
ADD COLUMN `k60` varchar(64) NOT NULL DEFAULT '' AFTER `k59`,
ADD COLUMN `k61` varchar(64) NOT NULL DEFAULT '' AFTER `k60`,
ADD COLUMN `k62` varchar(64) NOT NULL DEFAULT '' AFTER `k61`,
ADD COLUMN `k63` varchar(64) NOT NULL DEFAULT '' AFTER `k62`,
ADD COLUMN `k64` varchar(64) NOT NULL DEFAULT '' AFTER `k63`,
ADD COLUMN `k65` varchar(64) NOT NULL DEFAULT '' AFTER `k64`;

ALTER TABLE `ydy_equipment_result_extend`
ADD COLUMN `other_info` text NULL COMMENT '其他长字段' AFTER `k230`;
ALTER TABLE `ydy_equipment_relation`
MODIFY COLUMN `v` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '设备扩展表中的列名' AFTER `k`;

ALTER TABLE `ydy_equipment_result_extend`
ADD COLUMN `k66` varchar(64) NOT NULL DEFAULT '' AFTER `k65`,
ADD COLUMN `k67` varchar(64) NOT NULL DEFAULT '' AFTER `k66`,
ADD COLUMN `k68` varchar(64) NOT NULL DEFAULT '' AFTER `k67`,
ADD COLUMN `k69` varchar(64) NOT NULL DEFAULT '' AFTER `k68`,
ADD COLUMN `k70` varchar(64) NOT NULL DEFAULT '' AFTER `k69`,
ADD COLUMN `k71` varchar(64) NOT NULL DEFAULT '' AFTER `k70`,
ADD COLUMN `k72` varchar(64) NOT NULL DEFAULT '' AFTER `k71`,
ADD COLUMN `k73` varchar(64) NOT NULL DEFAULT '' AFTER `k72`,
ADD COLUMN `k74` varchar(64) NOT NULL DEFAULT '' AFTER `k73`,
ADD COLUMN `k75` varchar(64) NOT NULL DEFAULT '' AFTER `k74`,
ADD COLUMN `k76` varchar(64) NOT NULL DEFAULT '' AFTER `k75`,
ADD COLUMN `k77` varchar(64) NOT NULL DEFAULT '' AFTER `k76`,
ADD COLUMN `k78` varchar(64) NOT NULL DEFAULT '' AFTER `k77`,
ADD COLUMN `k79` varchar(64) NOT NULL DEFAULT '' AFTER `k78`,
ADD COLUMN `k80` varchar(64) NOT NULL DEFAULT '' AFTER `k79`,
ADD COLUMN `k81` varchar(64) NOT NULL DEFAULT '' AFTER `k80`,
ADD COLUMN `k82` varchar(64) NOT NULL DEFAULT '' AFTER `k81`,
ADD COLUMN `k83` varchar(64) NOT NULL DEFAULT '' AFTER `k82`,
ADD COLUMN `k84` varchar(64) NOT NULL DEFAULT '' AFTER `k83`,
ADD COLUMN `k85` varchar(64) NOT NULL DEFAULT '' AFTER `k84`,
ADD COLUMN `k86` varchar(64) NOT NULL DEFAULT '' AFTER `k85`,
ADD COLUMN `k87` varchar(64) NOT NULL DEFAULT '' AFTER `k86`,
ADD COLUMN `k88` varchar(64) NOT NULL DEFAULT '' AFTER `k87`,
ADD COLUMN `k89` varchar(64) NOT NULL DEFAULT '' AFTER `k88`,
ADD COLUMN `k90` varchar(64) NOT NULL DEFAULT '' AFTER `k89`;
--wiscourper 2023-12-23 体能大比武测力台
-- 数据中心数据库添加
insert into ydy_equipment_relation_dict(type,note,is_del,create_time,update_time,create_by) values
(18,'体能大比武测力台CMJ',0,now(),now(),'兰海军');
insert into ydy_equipment_relation_dict(type,note,is_del,create_time,update_time,create_by) values
(19,'体能大比武测力台IMPT',0,now(),now(),'兰海军');
alter table ydy_equipment_relation add column `note` varchar(128) not null default '' comment '备注' after `v`;
alter table ydy_equipment_relation add column `unit` varchar(24) not null default '' comment '单位' after `note`;
insert into ydy_equipment_relation(type,k,v,note,unit,create_by)
values 
(18,'PeopleNum','k1','外部人员编号','','lanhaijun'),
(18,'PeopleName','k2','人员姓名','','lanhaijun'),
(18,'Weight','k3','体重','kg','lanhaijun'),
(18,'TestTime','k4','测试时间','','lanhaijun'),
(18,'TestModule','k5','测试模式:CMJ下蹲跳,IMPT最大自主收缩','','lanhaijun'),
(18,'最大相对力值','k6','','BW','lanhaijun'),
(18,'合力最大力值','k7','','N','lanhaijun'),
(18,'缓冲最小相对力值','k8','','BW','lanhaijun'),
(18,'平均功率','k8','','W','lanhaijun'),
(18,'峰值功率','k9','','W','lanhaijun'),
(18,'相对平均功率','k10','','W/kg','lanhaijun'),
(18,'相对峰值功率','k11','','W/kg','lanhaijun'),
(18,'峰值功率发生时刻','k12','','s','lanhaijun'),
(18,'峰值功率发生时速度','k13','','m/s','lanhaijun'),
(18,'向心阶段冲量','k14','','Ns','lanhaijun'),
(18,'向心最大速度','k15','','m/s','lanhaijun'),
(18,'起跳速度','k16','','m/s','lanhaijun'),
(18,'腾空时间','k17','','s','lanhaijun'),
(18,'腾空高度v','k18','','m','lanhaijun'),
(18,'腾空高度t','k19','','m','lanhaijun'),
(18,'下蹲时间','k20','','s','lanhaijun'),
(18,'制动时间','k21','','s','lanhaijun'),
(18,'蹬伸时间','k22','','s','lanhaijun'),
(18,'触地时间','k23','','s','lanhaijun'),
(18,'蹬伸与制动时间比T','k24','','%','lanhaijun'),
(18,'峰值力发力率','k25','','N/s','lanhaijun'),
(18,'反应力量指数RSI','k26','','','lanhaijun'),
(18,'反应力量指数RSImod','k27','','','lanhaijun'),
(18,'蹬伸最大功率%','k28','','%','lanhaijun'),
(18,'左侧最大力值','k29','','N','lanhaijun'),
(18,'右侧最大力值','k30','','N','lanhaijun'),
(18,'蹬伸最大力值%','k31','','%','lanhaijun'),
(18,'落地最大力值%','k32','','%','lanhaijun');

insert into ydy_equipment_relation(type,k,v,note,unit,create_by)
values 
(19,'PeopleNum','k1','外部人员编号','','lanhaijun'),
(19,'PeopleName','k2','人员姓名','','lanhaijun'),
(19,'Weight','k3','体重','kg','lanhaijun'),
(19,'TestTime','k4','测试时间','','lanhaijun'),
(19,'TestModule','k5','测试模式:CMJ下蹲跳,IMPT最大自主收缩','','lanhaijun'),
(19,'合力最大力值','k6','','N','lanhaijun'),
(19,'相对最大力值','k7','','BW','lanhaijun'),
(19,'到达最大力值时间','k8','','s','lanhaijun'),
(19,'左侧最大力值','k9','','N','lanhaijun'),
(19,'右侧最大力值','k10','','N','lanhaijun'),
(19,'左侧到达最大力值时间','k11','','s','lanhaijun'),
(19,'右侧到达最大力值时间','k12','','s','lanhaijun'),
(19,'单位体重最大力值','k13','','N/kg','lanhaijun'),
(19,'50ms冲量','k14','','Ns','lanhaijun'),
(19,'50ms RFD','k15','','N/s','lanhaijun'),
(19,'100ms冲量','k16','','Ns','lanhaijun'),
(19,'100ms RFD','k17','','N/s','lanhaijun'),
(19,'150ms冲量','k18','','Ns','lanhaijun'),
(19,'150ms RFD','k19','','N/s','lanhaijun'),
(19,'200ms冲量','k20','','Ns','lanhaijun'),
(19,'200ms RFD','k21','','N/s','lanhaijun'),
(19,'250ms冲量','k22','','Ns','lanhaijun'),
(19,'250ms RFD','k23','','N/s','lanhaijun'),
(19,'300ms冲量','k24','','Ns','lanhaijun'),
(19,'300ms RFD','k25','','N/s','lanhaijun'),
(19,'最大合力时刻分量%','k26','','','lanhaijun'),
(19,'最大力值%','k27','','','lanhaijun'),
(19,'最大力值时间%','k28','','','lanhaijun'),
(19,'合力最大力值DSI','k29','','','lanhaijun'),
(19,'左侧最大力值DSI','k30','','','lanhaijun'),
(19,'右侧最大力值DSI','k31','','','lanhaijun');

-- 2023-12-24wiscourper 体能大比武测力台CMJ过程数据 体能大比武测力台IMPT过程数据
insert into ydy_equipment_relation_dict(type,note,is_del,create_time,update_time,create_by) values
(20,'体能大比武测力台CMJ和IMPT过程数据',0,now(),now(),'兰海军');

ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k31` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k30`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k32` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k31`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k33` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k32`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k34` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k33`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k35` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k34`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k36` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k35`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k37` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k36`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k38` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k37`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k39` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k38`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k40` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k39`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k41` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k40`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k42` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k41`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k43` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k42`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k44` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k43`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k45` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k44`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k46` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k45`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k47` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k46`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k48` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k47`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k49` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k48`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k50` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k49`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k51` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k50`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k52` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k51`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k53` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k52`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k54` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k53`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k55` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k54`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k56` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k55`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k57` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k56`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k58` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k57`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k59` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k58`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k60` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k59`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k61` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k60`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k62` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k61`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k63` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k62`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k64` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k63`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k65` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k64`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k66` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k65`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k67` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k66`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k68` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k67`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k69` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k68`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k70` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k69`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k71` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k70`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k72` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k71`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k73` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k72`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k74` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k73`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k75` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k74`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k76` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k75`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k77` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k76`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k78` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k77`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k79` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k78`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `k80` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k79`;
ALTER TABLE `ydy_equipment_process_extend` ADD COLUMN `other_info` text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '其他信息' AFTER `k80`;

ALTER TABLE `ydy_equipment_relation` ADD COLUMN `note_en` varchar(128) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '英文备注' AFTER `note`;

ALTER TABLE `ydy_equipment_result` ADD COLUMN `equipment_mark` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '设备标志' AFTER `equipment_id`;
ALTER TABLE `ydy_equipment_result` ADD COLUMN `datetimes` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '测试时间' AFTER `record_time`;
ALTER TABLE `ydy_equipment_result` ADD COLUMN `filename` varchar(256) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '原始数据文件' AFTER `reference`;
ALTER TABLE `ydy_equipment_result` ADD COLUMN `filemtime` varchar(24) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '原始数据文件修改时间' AFTER `filename`;
ALTER TABLE `ydy_equipment_result` ADD COLUMN `report_url` varchar(256) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '报告地址' AFTER `k20`;

ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k91` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k90`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k92` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k91`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k93` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k92`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k94` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k93`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k95` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k94`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k96` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k95`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k97` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k96`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k98` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k97`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k99` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k98`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k100` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k99`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k101` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k100`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k102` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k101`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k103` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k102`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k104` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k103`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k105` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k104`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k106` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k105`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k107` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k106`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k108` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k107`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k109` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k108`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k110` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k109`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k111` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k110`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k112` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k111`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k113` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k112`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k114` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k113`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k115` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k114`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k116` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k115`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k117` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k116`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k118` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k117`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k119` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k118`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k120` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k119`
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k121` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k120`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k122` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k121`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k123` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k122`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k124` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k123`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k125` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k124`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k126` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k125`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k127` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k126`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k128` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k127`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k129` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k128`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k130` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k129`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k131` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k130`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k132` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k131`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k133` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k132`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k134` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k133`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k135` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k134`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k136` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k135`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k137` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k136`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k138` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k137`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k139` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k138`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k140` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k139`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k141` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k140`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k142` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k141`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k143` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k142`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k144` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k143`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k145` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k144`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k146` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k145`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k147` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k146`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k148` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k147`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k149` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k148`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k150` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k149`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k151` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k150`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k152` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k151`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k153` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k152`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k154` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k153`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k155` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k154`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k156` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k155`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k157` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k156`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k158` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k157`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k159` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k158`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k160` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k159`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k161` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k160`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k162` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k161`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k163` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k162`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k164` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k163`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k165` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k164`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k166` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k165`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k167` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k166`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k168` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k167`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k169` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k168`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k170` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k169`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k171` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k170`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k172` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k171`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k173` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k172`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k174` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k173`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k175` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k174`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k176` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k175`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k177` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k176`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k178` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k177`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k179` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k178`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k180` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k179`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k181` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k180`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k182` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k181`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k183` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k182`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k184` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k183`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k185` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k184`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k186` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k185`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k187` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k186`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k188` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k187`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k189` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k188`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k190` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k189`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k191` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k190`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k192` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k191`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k193` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k192`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k194` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k193`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k195` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k194`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k196` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k195`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k197` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k196`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k198` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k197`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k199` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k198`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k200` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k199`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k201` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k200`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k202` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k201`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k203` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k202`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k204` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k203`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k205` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k204`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k206` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k205`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k207` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k206`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k208` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k207`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k209` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k208`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k210` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k209`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k211` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k210`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k212` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k211`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k213` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k212`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k214` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k213`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k215` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k214`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k216` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k215`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k217` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k216`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k218` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k217`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k219` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k218`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k220` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k219`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k221` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k220`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k222` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k221`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k223` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k222`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k224` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k223`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k225` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k224`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k226` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k225`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k227` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k226`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k228` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k227`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k229` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k228`;
ALTER TABLE `ydy_equipment_result_extend` ADD COLUMN `k230` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `k229`;


-- 2024-11-27 wiscourper
insert into ydy_equipment_relation_dict(type,note,is_del,create_time,update_time,create_by) values
(57,'polar总结数据',0,now(),now(),'兰海军');
insert into ydy_equipment_relation_dict(type,note,is_del,create_time,update_time,create_by) values
(58,'polar过程数据',0,now(),now(),'兰海军');

INSERT INTO `ydy_equipment_relation` (type,k,v,note,note_en,unit,is_norm) values
('57','player_session_id','k1','球员训练课程 ID','player_session_id','','0'),
('57','created','k2','','created','','0'),
('57','modified','k3','','modified','','0'),
('57','trimmed_start_time','k4','球队训练开始时间','trimmed_start_time','','1'),
('57','duration_ms','k5','团队训练课程的持续时间','duration_ms','ms','1'),
('57','distance_meters','k6','训练距离','distance_meters','m','1'),
('57','kilo_calories','k7','卡路里消耗量','kilo_calories','','1'),
('57','heart_rate_max','k8','最大心率','heart_rate_max','','1'),
('57','heart_rate_avg','k9','平均心率','heart_rate_avg','','1'),
('57','heart_rate_min','k10','最低心率','heart_rate_min','','1'),
('57','heart_rate_max_percent','k11','最大心率百分比','heart_rate_max_percent','%','1'),
('57','heart_rate_avg_percent','k12','平均心率百分比','heart_rate_avg_percent','%','1'),
('57','heart_rate_min_percent','k13','最低心率百分比','heart_rate_min_percent','%','1'),
('57','sprint_counter','k14','冲刺次数','sprint_counter','','1'),
('57','speed_avg_kmh','k15','平均速度','speed_avg_kmh','','1'),
('57','speed_max_kmh','k16','最大速度','speed_max_kmh','','1'),
('57','cadence_avg','k17','平均节奏','cadence_avg','','1'),
('57','cadence_max','k18','最大节奏','cadence_max','','1'),
('57','training_load','k19','训练负荷','training_load','','1'),
('57','cardio_load','k20','心肺负荷','cardio_load','','1'),
('57','muscle_load','k21','肌肉负荷','muscle_load','','1');

INSERT INTO `ydy_equipment_relation` (type,k,v,note,note_en,unit,is_norm) values
('58','time','k1','','time','','1'),
('58','distance','k2','','distance','','1'),
('58','hr','k3','','hr','','1'),
('58','speed','k4','','speed','','1'),
('58','cadence','k5','','cadence','','1'),
('58','lat','k6','','lat','','1'),
('58','lon','k7','','lon','','1'),
('58','altitude','k8','','altitude','','1'),
('58','forward_acceleration','k9','','forward_acceleration','','1'),
('58','power','k10','','power','','1');