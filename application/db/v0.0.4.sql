insert into `ydy_equipment_relation` ( `type`, `k`, `v`, `note`, `note_en`, `unit`, `upper_limit`, `lower_limit`, `is_del`, `is_norm`, `create_time`, `update_time`, `create_by`) values ( '47', 'currentFeet', 'k1', '', 'currentFeet', '', null, null, '0', '0', '2024-06-12 14:53:50', '2024-06-12 15:00:32', 'liuhongbo');
insert into `ydy_equipment_relation` ( `type`, `k`, `v`, `note`, `note_en`, `unit`, `upper_limit`, `lower_limit`, `is_del`, `is_norm`, `create_time`, `update_time`, `create_by`) values ( '47', 'longRange', 'k2', '', 'longRange', '', null, null, '0', '0', '2024-06-12 14:55:49', '2024-06-12 14:57:29', 'liuhongbo');
insert into `ydy_equipment_relation` ( `type`, `k`, `v`, `note`, `note_en`, `unit`, `upper_limit`, `lower_limit`, `is_del`, `is_norm`, `create_time`, `update_time`, `create_by`) values ( '47', 'feetMinute', 'k3', '', 'feetMinute', '', null, null, '0', '0', '2024-06-12 15:00:26', '2024-06-12 15:00:26', 'liuhogngbo');
insert into `ydy_equipment_relation` ( `type`, `k`, `v`, `note`, `note_en`, `unit`, `upper_limit`, `lower_limit`, `is_del`, `is_norm`, `create_time`, `update_time`, `create_by`) values ( '47', 'averageFeet', 'k4', '', 'averageFeet', '', null, null, '0', '0', '2024-06-12 15:01:52', '2024-06-12 15:39:45', 'liuhogngbo');
insert into `ydy_equipment_relation` ( `type`, `k`, `v`, `note`, `note_en`, `unit`, `upper_limit`, `lower_limit`, `is_del`, `is_norm`, `create_time`, `update_time`, `create_by`) values ( '47', 'heartRate', 'k5', '', 'heartRate', '', null, null, '0', '0', '2024-06-12 15:13:13', '2024-06-12 15:39:46', 'liuhogngbo');
insert into `ydy_equipment_relation` ( `type`, `k`, `v`, `note`, `note_en`, `unit`, `upper_limit`, `lower_limit`, `is_del`, `is_norm`, `create_time`, `update_time`, `create_by`) values ( '47', 'energy', 'k6', '', 'energy', '', null, null, '0', '0', '2024-06-12 15:14:05', '2024-06-12 15:39:48', 'liuhogngbo');
insert into `ydy_equipment_relation` ( `type`, `k`, `v`, `note`, `note_en`, `unit`, `upper_limit`, `lower_limit`, `is_del`, `is_norm`, `create_time`, `update_time`, `create_by`) values ( '47', 'power', 'k7', '', 'power', '', null, null, '0', '0', '2024-06-12 15:14:44', '2024-06-12 15:39:50', 'liuhogngbo');
insert into `ydy_equipment_relation` ( `type`, `k`, `v`, `note`, `note_en`, `unit`, `upper_limit`, `lower_limit`, `is_del`, `is_norm`, `create_time`, `update_time`, `create_by`) values ( '47', 'terminalMac', 'k8', '', 'terminalMac', '', null, null, '0', '0', '2024-06-12 15:14:44', '2024-06-12 15:39:50', 'liuhogngbo');
insert into `ydy_equipment_relation` ( `type`, `k`, `v`, `note`, `note_en`, `unit`, `upper_limit`, `lower_limit`, `is_del`, `is_norm`, `create_time`, `update_time`, `create_by`) values ( '47', 'elapsedTime', 'k9', '', 'elapsedTime', '', null, null, '0', '0', '2024-06-12 15:14:44', '2024-06-12 15:39:50', 'liuhogngbo');




CREATE TABLE `ydy_table_versa_climber` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增ID',
          `cid` varchar(64) DEFAULT NULL,
          `time` datetime DEFAULT NULL,
          `customer` varchar(128) DEFAULT NULL,
          `staff_uuid` varchar(64) DEFAULT NULL,
          `terminalMac` varchar(128) DEFAULT NULL,
          `deviceName` varchar(256) DEFAULT NULL,
          `device_id` varchar(64) DEFAULT NULL,
          `elapsedTime` varchar(64) DEFAULT NULL,
          `productKey` varchar(64) DEFAULT NULL,
          `currentFeet` varchar(64) DEFAULT NULL,
          `longRange` varchar(64) DEFAULT NULL,
          `feetMinute` varchar(64) DEFAULT NULL,
          `averageFeet` varchar(64) DEFAULT NULL,
          `meheartRatet` varchar(64) DEFAULT NULL,
          `heartRate` varchar(64) DEFAULT NULL,
          `energy` varchar(64) DEFAULT NULL,
          `power` varchar(64) DEFAULT NULL,
          `date` datetime DEFAULT NULL,
          PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='攀爬机阿里云tableStore格式化数据表'