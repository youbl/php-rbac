/**
 * b_开头的4个表为基础表;
 * map开头的4个表为关系映射表.
 * user可以映射到多个role和多个group，并拥有对应的role以及group的全部权限;
 * 
 * 注1： b_开头的表都有app字段，可以让本套系统在多个产品或公司间共用而不冲突，如果只有一个产品，默认空即可
 * 注2： b_permissions表的p_val表示各产品的权限值，可以是
 *         url：表示对指定的url是否有访问权限，（url是否带query，由各产品自行决定）
 *         button id：表示对指定的按钮是否有点击权限
 *         img id：表示对指定的图片是否有显示或查看权限
 *       诸如此类，这些各产品方自行发挥
 * Author:  youbeiliang01
 * Created: 2017-9-7
 */

CREATE TABLE `b_users` (
  `u_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `app` varchar(10) NOT NULL DEFAULT '' COMMENT '所属产品',
  `account` varchar(50) NOT NULL DEFAULT '' COMMENT '登录账号',
  `u_name` varchar(50) NOT NULL DEFAULT '' COMMENT '用户名称',
  `u_type` tinyint(4) NOT NULL DEFAULT '0' COMMENT '用户类型',
  `u_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态，0:正常 1:禁用',
  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `upd_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `lastip` varchar(50) NOT NULL DEFAULT '' COMMENT '最后操作IP',
  PRIMARY KEY (`u_id`),
  KEY `idx_name` (`u_name`),
  KEY `idx_account` (`account`),
  UNIQUE KEY `unq_account` (`app`, `account`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户账号表';

CREATE TABLE `b_groups` (
  `g_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `app` varchar(10) NOT NULL DEFAULT '' COMMENT '所属产品',
  `g_name` varchar(20) NOT NULL DEFAULT '' COMMENT '组名',
  `g_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态，0正常，1禁用',
  `g_parentid` bigint(20) NOT NULL DEFAULT '0' COMMENT '父组id',
  `g_sort` tinyint(4) NOT NULL DEFAULT '100' COMMENT '排序',
  `add_time` int(11) NOT NULL COMMENT '添加时间',
  `upd_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `lastip` varchar(50) NOT NULL DEFAULT '' COMMENT '最后操作IP',
  PRIMARY KEY (`g_id`),
  UNIQUE KEY `unq_name` (`app`, `g_name`),
  KEY `idx_name` (`g_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户分组表';

CREATE TABLE `b_roles` (
  `r_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '角色id',
  `app` varchar(10) NOT NULL DEFAULT '' COMMENT '所属产品',
  `r_name` varchar(100) NOT NULL DEFAULT '' COMMENT '角色名称',
  `r_desc` varchar(500) NOT NULL DEFAULT '' COMMENT '角色说明',
  `r_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态，0:正常 1:禁用',
  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `upd_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `lastip` varchar(50) NOT NULL DEFAULT '' COMMENT '最后操作IP',
  PRIMARY KEY (`r_id`),
  UNIQUE KEY `unq_name` (`app`, `r_name`),
  KEY `idx_name` (`r_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='角色定义清单';

CREATE TABLE `b_permissions` (
  `p_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '权限id',
  `app` varchar(10) NOT NULL DEFAULT '' COMMENT '所属产品',
  `p_val` varchar(100) NOT NULL DEFAULT '' COMMENT '权限内容,如url',
  `p_desc` varchar(100) NOT NULL DEFAULT '' COMMENT '权限说明',
  `p_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态，0:正常 1:禁用',
  `p_parentid` int(11) NOT NULL DEFAULT '0' COMMENT '父节点id',
  `p_sort` int(10) unsigned NOT NULL DEFAULT '100' COMMENT '排序',
  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `upd_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `lastip` varchar(50) NOT NULL DEFAULT '' COMMENT '最后操作IP',
  PRIMARY KEY (`p_id`),
  UNIQUE KEY `unq_val` (`app`, `p_val`),
  KEY `idx_val` (`p_val`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='可用权限清单';

CREATE TABLE `map_user_group` (
  `u_id` bigint(20) NOT NULL COMMENT '用户id',
  `g_id` int(11) NOT NULL COMMENT '分组id',
  `map_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态,0有效,1禁用',
  `add_time` int(11) NOT NULL COMMENT '创建时间',
  `lastip` varchar(50) NOT NULL DEFAULT '' COMMENT '最后操作IP',
  PRIMARY KEY (`u_id`,`g_id`),
  KEY `idx_group` (`g_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户与分组映射表';

CREATE TABLE `map_user_role` (
  `u_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '用户id',
  `r_id` int(11) NOT NULL COMMENT '角色id',
  `map_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态,0有效,1禁用',
  `add_time` int(11) NOT NULL COMMENT '创建时间',
  `lastip` varchar(50) NOT NULL DEFAULT '' COMMENT '最后操作IP',
  PRIMARY KEY (`u_id`,`r_id`),
  KEY `idx_role` (`r_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户与角色映射表';

CREATE TABLE `map_group_role` (
  `g_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '用户组id',
  `r_id` int(11) NOT NULL COMMENT '角色id',
  `map_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态,0有效,1禁用',
  `add_time` int(11) NOT NULL COMMENT '创建时间',
  `lastip` varchar(50) NOT NULL DEFAULT '' COMMENT '最后操作IP',
  PRIMARY KEY (`g_id`,`r_id`),
  KEY `idx_role` (`r_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户组与角色映射表';

CREATE TABLE `map_role_perm` (
  `p_id` bigint(20) NOT NULL COMMENT '权限id',
  `r_id` int(11) NOT NULL COMMENT '角色id',
  `map_status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态,0正常,1禁用',
  `add_time` int(11) NOT NULL COMMENT '创建时间',
  `lastip` varchar(50) NOT NULL DEFAULT '' COMMENT '最后操作IP',
  PRIMARY KEY (`p_id`,`r_id`),
  KEY `idx_role` (`r_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='角色与权限映射表';
