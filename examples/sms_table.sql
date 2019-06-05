CREATE TABLE IF NOT EXISTS `{$prefix}sms_template` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sms_type` varchar(20) NOT NULL DEFAULT 'local' COMMENT '短信类型 local | qcloudsms | dysms | clsms | htsms',
  `type` varchar(50) NOT NULL DEFAULT '' COMMENT '业务类型(如register | find_password)',
  `title` varchar(50) NOT NULL DEFAULT '' COMMENT '短信标题',
  `template_id` varchar(50) NOT NULL DEFAULT '' COMMENT '短信模板ID',
  `params` varchar(500) NOT NULL DEFAULT '' COMMENT '短信参数（''code'', ''time'' | {1}, {2}）',
  `template` varchar(500) NOT NULL DEFAULT '' COMMENT '短信模板内容',
  PRIMARY KEY (`id`),
  KEY `sms_type` (`sms_type`),
  KEY `type` (`type`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='短信模板';

CREATE TABLE `{$prefix}sms_log` (
	`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`sms_type` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '短信供应商 如 dysms',
	`type` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '短信类型 如 register',
	`mobile` CHAR(11) NOT NULL DEFAULT '' COMMENT '手机号码',
	`params` VARCHAR(3000) NOT NULL DEFAULT '' COMMENT '参数(json)',
	`out_id` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '返回的第三方id',
	`create_time` INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '创建时间',
	PRIMARY KEY (`id`),
	INDEX `mobile` (`mobile`),
	INDEX `type` (`type`)
)
COMMENT='短信日志表' COLLATE='utf8_general_ci' ENGINE=InnoDBAUTO_INCREMENT=10;

-- 示例数据
-- INSERT INTO `tp51_sms_template` (`sms_type`, `type`, `title`, `template_id`, `params`, `template`) VALUES ('local', 'register', '用户注册', 'local_register', 'code', '您好，欢迎注册超级商城，您的手机验证码是：${code}，若非本人操作，请忽略！');
-- INSERT INTO `tp51_sms_template` (`sms_type`, `type`, `title`, `template_id`, `params`, `template`) VALUES ('dysms', 'register', '用户注册', 'SMS_13092xxxx', 'code', '您好，欢迎注册超级商城，您的手机验证码是：${code}，若非本人操作，请忽略！');
-- INSERT INTO `tp51_sms_template` (`sms_type`, `type`, `title`, `template_id`, `params`, `template`) VALUES ('qcloudsms', 'register', '用户注册', '158xxx', '{1}', '您好，欢迎注册超级商城，您的手机验证码是：{1}，若非本人操作，请忽略！');