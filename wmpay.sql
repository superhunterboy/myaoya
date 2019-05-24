/*
 Navicat MySQL Data Transfer

 Source Server         : preOnline
 Source Server Type    : MySQL
 Source Server Version : 100307
 Source Host           : 10.88.12.23:3306
 Source Schema         : ay_wmpay

 Target Server Type    : MySQL
 Target Server Version : 100307
 File Encoding         : 65001

 Date: 24/05/2019 16:09:42
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for affiche
-- ----------------------------
DROP TABLE IF EXISTS `affiche`;
CREATE TABLE `affiche`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '标题',
  `content` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL COMMENT '内容',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态 0不发布 1发布',
  `created_at` timestamp(0) NOT NULL COMMENT '创建时间',
  `updated_at` timestamp(0) NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for bank_cards
-- ----------------------------
DROP TABLE IF EXISTS `bank_cards`;
CREATE TABLE `bank_cards`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `bank_name` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '所属银行',
  `user_name` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '银行卡户名',
  `bank_number` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '银行卡卡号',
  `address` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '开户行地址',
  `type` enum('1','2') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '1' COMMENT '银行类型 1:支付宝 2:线下',
  `level_ids` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL COMMENT '层级id',
  `count` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '收款次数',
  `money` decimal(20, 2) NOT NULL DEFAULT 0.00 COMMENT '收款总额',
  `status` enum('0','1') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '0' COMMENT '使用状态',
  `created_at` timestamp(0) NOT NULL COMMENT '创建时间',
  `updated_at` timestamp(0) NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 36 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci COMMENT = '银行卡管理' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for channels
-- ----------------------------
DROP TABLE IF EXISTS `channels`;
CREATE TABLE `channels`  (
  `id` tinyint(3) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '支付通道',
  `tag` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '通道标识',
  `position` tinyint(1) NOT NULL DEFAULT 1 COMMENT '运用场景 1,PC端  2,移动端',
  `status` tinyint(1) NULL DEFAULT 0 COMMENT '开启状态',
  `sequence` tinyint(3) NULL DEFAULT 0 COMMENT '排序',
  `created_at` datetime(0) NULL DEFAULT NULL,
  `updated_at` datetime(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `tag`(`tag`) USING BTREE,
  INDEX `status`(`status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 16 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for codes
-- ----------------------------
DROP TABLE IF EXISTS `codes`;
CREATE TABLE `codes`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `mobile` char(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '手机号',
  `code` char(8) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '验证码',
  `send_id` int(10) UNSIGNED NOT NULL COMMENT '发送ID，接口返回的数据',
  `status` int(10) UNSIGNED NOT NULL COMMENT '接口返回状态code',
  `ok` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否已验证OK，0为没验证OK，1为验证OK',
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1303 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for company
-- ----------------------------
DROP TABLE IF EXISTS `company`;
CREATE TABLE `company`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `no` char(5) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `name` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `url` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `wechat_vendor_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `wap_wechat_vendor_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '微信wap',
  `alipay_vendor_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `wap_alipay_vendor_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付宝wap',
  `netbank_vendor_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `qq_vendor_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `wap_qq_vendor_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'QQwap',
  `jd_vendor_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `wap_jd_vendor_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '京东wap',
  `baidu_vendor_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `wap_baidu_vendor_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '百度wap',
  `union_vendor_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `wap_union_vendor_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '银联wap',
  `autorecharge_url` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `is_autorecharge` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `is_5qrcode` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime(0) NOT NULL,
  `updated_at` datetime(0) NOT NULL,
  `yun_vendor_id` int(10) NOT NULL COMMENT '云闪付',
  `wap_yun_vendor_id` int(10) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `c_no`(`no`) USING BTREE,
  UNIQUE INDEX `c_name`(`name`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 7 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci COMMENT = '业务平台' ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for level
-- ----------------------------
DROP TABLE IF EXISTS `level`;
CREATE TABLE `level`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '层级名字',
  `status` enum('0','1') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '0' COMMENT '层级银行卡占用状态:0为占用 1已占用',
  `remark` mediumtext CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL COMMENT '爬虫提交原始数据',
  `created_at` datetime(0) NOT NULL,
  `updated_at` datetime(0) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 15874 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for locks
-- ----------------------------
DROP TABLE IF EXISTS `locks`;
CREATE TABLE `locks`  (
  `id` int(7) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_no` char(25) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `order_no`(`order_no`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 6445 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for member
-- ----------------------------
DROP TABLE IF EXISTS `member`;
CREATE TABLE `member`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` bigint(20) UNSIGNED NOT NULL COMMENT '会员id',
  `account` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '会员账号',
  `level_id` int(11) NOT NULL DEFAULT 0 COMMENT '会员层级id',
  `register_time` datetime(0) NOT NULL COMMENT '注册时间',
  `status` set('启用','停用','冻结','停权') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '状态，1 启用、2 停用、3 冻结、4 停权',
  `remark` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '备注',
  `created_at` datetime(0) NULL DEFAULT NULL,
  `updated_at` datetime(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uid_account`(`uid`, `account`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 136688 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for mer_qrcode_pay
-- ----------------------------
DROP TABLE IF EXISTS `mer_qrcode_pay`;
CREATE TABLE `mer_qrcode_pay`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `member` varchar(16) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '会员账户名',
  `recharge_money` double(10, 2) NOT NULL DEFAULT 0.00 COMMENT '交易金额',
  `order` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '交易单号',
  `merchant_id` int(10) NOT NULL DEFAULT 0 COMMENT '商户id',
  `type` enum('1','2') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '1' COMMENT '商户类型1微信2支付宝',
  `original_money` double(10, 2) NOT NULL DEFAULT 0.00 COMMENT '原始金额',
  `discount` double(10, 2) NOT NULL DEFAULT 0.00 COMMENT '优惠金额',
  `money` double(10, 2) NOT NULL DEFAULT 0.00 COMMENT '实际交易金额',
  `hand_charge` double(10, 2) NOT NULL DEFAULT 0.00 COMMENT '手续费',
  `status` enum('0','1','2','3') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '0' COMMENT '交易状态0:未支付 1:已支付 2:取消支付 3:其他',
  `user_id` tinyint(2) NOT NULL DEFAULT 0 COMMENT '操作用户id',
  `msg` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '备注；主要用于备注其他支付状态',
  `pay_time` int(10) NOT NULL DEFAULT 0 COMMENT '支付时间戳',
  `created_at` timestamp(0) NOT NULL COMMENT '记录创建时间',
  `updated_at` timestamp(0) NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci COMMENT = '商家二维码支付记录' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for merchant
-- ----------------------------
DROP TABLE IF EXISTS `merchant`;
CREATE TABLE `merchant`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `open_id` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '机构编号',
  `open_key` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '机构公钥',
  `shop_no` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '门店编号',
  `merchant_name` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '商户名称',
  `signboard_name` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '招牌名字',
  `address` varchar(256) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '所在地址',
  `status` enum('0','1') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '0' COMMENT '使用状态0、不使用 1、使用(锁定状态)',
  `type` set('1','2') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '1,2' COMMENT '类型1、微信 2、支付宝',
  `key` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT 'json格式密钥',
  `created_at` timestamp(0) NOT NULL COMMENT '创建时间',
  `updated_at` timestamp(0) NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 139 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci COMMENT = '商户二维码管理(微信、支付宝)' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for offline_pay
-- ----------------------------
DROP TABLE IF EXISTS `offline_pay`;
CREATE TABLE `offline_pay`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_no` char(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '单号',
  `account` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '会员账号',
  `amount` decimal(32, 2) UNSIGNED NOT NULL COMMENT '转款金额',
  `depositor` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '转款人(支付宝时为支付宝账户名字)',
  `bank_name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '转入银行名称',
  `bank_card_no` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '转入银行卡号',
  `type` enum('1','2') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1' COMMENT '转账类型1、银行卡线下转账 2、支付宝转账到银行卡',
  `card_id` int(10) NOT NULL DEFAULT 0 COMMENT '银行卡id',
  `card_user` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '转入银行卡持有人',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态 0 未处理 1 已入款 2 忽略',
  `remark` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '备注',
  `user_id` int(11) NOT NULL DEFAULT 0 COMMENT '操作人',
  `created_at` datetime(0) NOT NULL,
  `updated_at` datetime(0) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 21311 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for pay
-- ----------------------------
DROP TABLE IF EXISTS `pay`;
CREATE TABLE `pay`  (
  `id` mediumint(7) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pay_type` tinyint(1) UNSIGNED NOT NULL COMMENT '支付方式，3支付宝，2微信',
  `pay_code` char(12) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '支付编码',
  `user` char(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '会员',
  `device` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '充值终端',
  `order_no` char(25) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '订单号',
  `vendor_order_no` char(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '支付流水号',
  `money` decimal(10, 2) NOT NULL COMMENT '充值金额',
  `rk_user_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '入款人id',
  `rk_user` char(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '入款人',
  `rk_status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否入款，1为已入款',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '订单状态',
  `recharge_status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '充值状态',
  `recharge_count` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '充值次数',
  `recharge_msg` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '充值消息',
  `queue_job_id` char(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '队列任务id',
  `company_id` int(10) UNSIGNED NOT NULL COMMENT '业务平台id',
  `vendor_id` int(10) UNSIGNED NOT NULL COMMENT '支付平台id',
  `vendor_type` int(10) UNSIGNED NOT NULL COMMENT '支付平台类型id',
  `pay_datetime` datetime(0) NULL DEFAULT NULL COMMENT '支付时间',
  `version` varchar(3) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT '1.0' COMMENT '版本：旧版1.0 新版2.0',
  `remark` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '明细',
  `created_at` datetime(0) NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime(0) NULL DEFAULT NULL COMMENT '更新时间',
  `notify_url` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '直接支付回调地址',
  `notify_msg` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL COMMENT '直连通知bbin返回结果',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `order_no`(`order_no`) USING BTREE,
  INDEX `status`(`status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 541800 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for pay_out
-- ----------------------------
DROP TABLE IF EXISTS `pay_out`;
CREATE TABLE `pay_out`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `wid` int(10) UNSIGNED NOT NULL COMMENT 'BBIN后台出款记录ID',
  `account` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '会员账号',
  `realname` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '真实姓名',
  `mobile` char(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '会员手机',
  `bank_card` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '银行卡',
  `bank_name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '开户行',
  `amount` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '出款金额',
  `discount` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '优惠',
  `service_charge` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '手续费',
  `cash_info` decimal(10, 2) NOT NULL DEFAULT 0.00 COMMENT '出款资讯',
  `discount_deduction` char(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '优惠扣除',
  `pay_out_status` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '出款状况',
  `platform_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '出款平台id',
  `platform_type` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '出款平台类型，1 天付宝、2 雅付、3 金海哲',
  `order_no` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '出款单号',
  `platform_order_no` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '出款平台单号',
  `platform_status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '出款平台返回状态，0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态，0未处理 1确定 2取消 3拒绝 4已锁定',
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '操作人ID',
  `user` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '操作人',
  `remark` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '备注',
  `platform_attach` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '出款平台返回原始数据',
  `crawl_attach` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '爬虫原始数据',
  `pay_out_time` timestamp(0) NULL DEFAULT NULL COMMENT '出款时间',
  `pay_out_lastime` timestamp(0) NULL DEFAULT NULL COMMENT '异动时间',
  `job_id` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '队列任务ID',
  `company_id` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT '业务平台id',
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `wid`(`wid`) USING BTREE,
  UNIQUE INDEX `order_no`(`order_no`) USING BTREE,
  INDEX ```platform_status```(`platform_status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 333099 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for pay_out_limit
-- ----------------------------
DROP TABLE IF EXISTS `pay_out_limit`;
CREATE TABLE `pay_out_limit`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `level_ids` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '会员层级',
  `count` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '出款次数限制,0为限制出款',
  `created_at` datetime(0) NULL DEFAULT NULL,
  `updated_at` datetime(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 10 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for pay_qrcode
-- ----------------------------
DROP TABLE IF EXISTS `pay_qrcode`;
CREATE TABLE `pay_qrcode`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sender` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '付款人',
  `money` decimal(20, 2) NOT NULL COMMENT '金额',
  `createTime` char(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '产生时间',
  `code` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '收款码',
  `recType` tinyint(1) NOT NULL COMMENT '收款类型：0 红包, 1 转账',
  `remark` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '备注',
  `qrcode_id` int(10) UNSIGNED NOT NULL COMMENT '二维码id',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '入款状态，0 未处理、1 入款中、 2 已入款、3 入款失败',
  `result` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '入款失败原因',
  `rk_user_id` int(10) UNSIGNED NULL DEFAULT 0 COMMENT '入款人id',
  `rk_user` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '入款人',
  `queue_job_id` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '入款队列id',
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 81 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for payment_channels
-- ----------------------------
DROP TABLE IF EXISTS `payment_channels`;
CREATE TABLE `payment_channels`  (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `platform` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '支付平台',
  `platform_identifer` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '平台识别符',
  `channel` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '支付方式',
  `paycode` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '支付识别码',
  `merchant_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '商户号',
  `key` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '商户Key',
  `display_name` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '前台显示名称',
  `position` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1：线上， 2：线下',
  `offline_category` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT 'scanCode 扫码, addFriend 加好友, transfer 转账',
  `deposit_range` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '充值金额范围',
  `callback_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '' COMMENT '异步通知地址',
  `notify_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '同步通知地址',
  `status` tinyint(1) NULL DEFAULT 0 COMMENT '显示状态',
  `remark` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT '渠道描述',
  `sequence` tinyint(3) NULL DEFAULT 0 COMMENT '排序',
  `created_at` datetime(0) NULL DEFAULT NULL,
  `updated_at` datetime(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `status`(`status`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 28 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for paystatus
-- ----------------------------
DROP TABLE IF EXISTS `paystatus`;
CREATE TABLE `paystatus`  (
  `id` int(10) NOT NULL,
  `keys` char(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `payname` char(20) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `status` tinyint(1) NULL DEFAULT NULL,
  `updated_at` datetime(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for permission
-- ----------------------------
DROP TABLE IF EXISTS `permission`;
CREATE TABLE `permission`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '权限名称',
  `method` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '请求方法',
  `route` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '路由',
  `action` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '控制器方法',
  `category` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '类别',
  `require` tinyint(1) NOT NULL DEFAULT 0 COMMENT '必须的权限',
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 43 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for pictures
-- ----------------------------
DROP TABLE IF EXISTS `pictures`;
CREATE TABLE `pictures`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `picture` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `enabled` tinyint(1) UNSIGNED NULL DEFAULT 0,
  `remark` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `type` tinyint(1) UNSIGNED NULL DEFAULT 1,
  `company_id` int(10) UNSIGNED NULL DEFAULT NULL,
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 17 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for platform
-- ----------------------------
DROP TABLE IF EXISTS `platform`;
CREATE TABLE `platform`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `pay_out_type` int(10) UNSIGNED NOT NULL,
  `no` char(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `key` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `callback_url` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notify_url` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_amount_limit` decimal(32, 2) UNSIGNED NOT NULL DEFAULT 0.00,
  `end_amount_limit` decimal(32, 2) UNSIGNED NOT NULL DEFAULT 0.00,
  `balance` decimal(32, 2) UNSIGNED NOT NULL DEFAULT 0.00,
  `enabled` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `type` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 为会员出款 1 为公司出款',
  `created_at` datetime(0) NOT NULL,
  `updated_at` datetime(0) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `v_no`(`no`) USING BTREE,
  INDEX `enabled`(`enabled`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 65 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = '出款平台' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for pri_qrcode_pay
-- ----------------------------
DROP TABLE IF EXISTS `pri_qrcode_pay`;
CREATE TABLE `pri_qrcode_pay`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `member` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '会员账号',
  `money` double(10, 2) NOT NULL DEFAULT 0.00 COMMENT '金额',
  `drawee` varchar(16) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '付款人(支付宝或者微信呢称)',
  `qrcode_id` int(10) NOT NULL DEFAULT 0 COMMENT '二维码id',
  `status` enum('0','1','2') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '0' COMMENT '支付状态 0:未支付 1:已支付 2:取消支付',
  `user_id` tinyint(2) NOT NULL DEFAULT 0 COMMENT '操作用户id',
  `type` enum('1','2','3','4') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '1' COMMENT '二维码类型',
  `msg` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '备注',
  `created_at` timestamp(0) NOT NULL COMMENT '创建时间',
  `updated_at` timestamp(0) NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 208992 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci COMMENT = '个人二维码支付记录' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for private_qrcode
-- ----------------------------
DROP TABLE IF EXISTS `private_qrcode`;
CREATE TABLE `private_qrcode`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID(二维码编号)',
  `qrcode_name` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '微信号/支付宝账号(收款人)',
  `url` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '二维码地址',
  `money` double(10, 2) NOT NULL DEFAULT 0.00 COMMENT '收款总额',
  `count` tinyint(8) NOT NULL DEFAULT 0 COMMENT '支付次数',
  `type` enum('1','2','3','4') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '1' COMMENT '二维码类型1、微信 2、支付宝 3、QQ 4、云闪付',
  `status` enum('0','1','2') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '0' COMMENT '使用状态0、不使用 1、使用',
  `msg` longtext CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL COMMENT '备注',
  `created_at` timestamp(0) NOT NULL COMMENT '创建时间',
  `updated_at` timestamp(0) NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 475 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci COMMENT = '个人二维码管理(微信、支付宝)' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for qrcode
-- ----------------------------
DROP TABLE IF EXISTS `qrcode`;
CREATE TABLE `qrcode`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `wechat_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '微信号',
  `url` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '二维码图片地址',
  `limit` decimal(20, 2) UNSIGNED NOT NULL COMMENT '限额',
  `money` decimal(40, 2) UNSIGNED NOT NULL COMMENT '总金额',
  `day_money` decimal(40, 2) UNSIGNED NOT NULL COMMENT '每天收款金额',
  `count` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付次数',
  `type` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '类型，1个人二维码、2商户二维码',
  `company_id` int(11) NOT NULL DEFAULT 1 COMMENT '所属业务平台',
  `disable` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态，0启用，1禁用',
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 67 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for recharge
-- ----------------------------
DROP TABLE IF EXISTS `recharge`;
CREATE TABLE `recharge`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` int(10) UNSIGNED NOT NULL,
  `platform_id` int(10) UNSIGNED NOT NULL,
  `pay_out_type` int(10) UNSIGNED NOT NULL,
  `pay_code` char(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(32, 2) NOT NULL DEFAULT 0.00,
  `order_no` char(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `platform_order_no` char(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `remark` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `recharge_link_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime(0) NULL DEFAULT NULL,
  `updated_at` datetime(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 52424 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for recharge_link
-- ----------------------------
DROP TABLE IF EXISTS `recharge_link`;
CREATE TABLE `recharge_link`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `platform_id` int(10) UNSIGNED NOT NULL COMMENT '出款平台ID',
  `token` char(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '令牌标识',
  `remark` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '备注',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '0启用、1禁用',
  `created_at` datetime(0) NULL DEFAULT NULL,
  `updated_at` datetime(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 34 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for report
-- ----------------------------
DROP TABLE IF EXISTS `report`;
CREATE TABLE `report`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '名称',
  `time` date NOT NULL COMMENT '哪一天',
  `flag` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '类型标识：pay_company 公司入款、pay_online 线上支付、artificial_Deposit 人工存入',
  `total` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '收入金额',
  `created_at` datetime(0) NULL DEFAULT NULL,
  `updated_at` datetime(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1471 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for report_artificial_deposit
-- ----------------------------
DROP TABLE IF EXISTS `report_artificial_deposit`;
CREATE TABLE `report_artificial_deposit`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `report_l1_id` bigint(20) UNSIGNED NOT NULL,
  `account` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(60, 2) NOT NULL,
  `order_no` char(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remark` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `time` datetime(0) NOT NULL,
  `created_at` datetime(0) NULL DEFAULT NULL,
  `updated_at` datetime(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `order_no`(`order_no`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 229910 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for report_l1
-- ----------------------------
DROP TABLE IF EXISTS `report_l1`;
CREATE TABLE `report_l1`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `report_id` int(11) NOT NULL,
  `text` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tag` int(11) NOT NULL,
  `currency` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_user` int(11) NOT NULL,
  `total_amount` decimal(60, 2) NOT NULL,
  `created_at` datetime(0) NULL DEFAULT NULL,
  `updated_at` datetime(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 27783 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for report_pay_company
-- ----------------------------
DROP TABLE IF EXISTS `report_pay_company`;
CREATE TABLE `report_pay_company`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `report_l1_id` bigint(20) UNSIGNED NOT NULL,
  `level` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '层级',
  `order_no` char(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '订单号',
  `shareholder` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '大股东',
  `account` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '会员账号',
  `account_bank` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '存款人银行',
  `depositor` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '存款人',
  `way` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '方式',
  `amount` decimal(60, 2) NOT NULL COMMENT '存入金额',
  `discount` decimal(60, 2) NOT NULL COMMENT '存款优惠',
  `other_discount1` decimal(60, 2) NOT NULL COMMENT '其他优惠',
  `other_discount2` decimal(60, 2) NOT NULL COMMENT '其他优惠',
  `total_amount` decimal(60, 2) NOT NULL COMMENT '存入总金额',
  `company_bank` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '公司银行',
  `company_bank_user` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '公司银行卡主姓名',
  `operator` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '操作者',
  `member_datetime` datetime(0) NOT NULL COMMENT '会员填写当地',
  `system_datetime` datetime(0) NOT NULL COMMENT '系统提交美东',
  `operation_datetime` datetime(0) NOT NULL COMMENT '操作时间美东',
  `created_at` datetime(0) NULL DEFAULT NULL,
  `updated_at` datetime(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 504977 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for report_pay_online
-- ----------------------------
DROP TABLE IF EXISTS `report_pay_online`;
CREATE TABLE `report_pay_online`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `report_l1_id` bigint(20) NOT NULL,
  `order_no` char(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '订单号',
  `account` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '会员账号',
  `currency` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '币别',
  `level` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '会员支付层级',
  `time` datetime(0) NOT NULL COMMENT '时间',
  `amount` decimal(60, 2) NOT NULL COMMENT '存入金额',
  `created_at` datetime(0) NULL DEFAULT NULL,
  `updated_at` datetime(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `order_no`(`order_no`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2312780 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for retry
-- ----------------------------
DROP TABLE IF EXISTS `retry`;
CREATE TABLE `retry`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_no` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '单号',
  `count` int(11) NOT NULL DEFAULT 0 COMMENT '重试次数',
  `created_at` timestamp(0) NULL DEFAULT NULL,
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1238 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for setting
-- ----------------------------
DROP TABLE IF EXISTS `setting`;
CREATE TABLE `setting`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `key` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '配置项',
  `val` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '配置项的值',
  `created_at` timestamp(0) NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP(0),
  `updated_at` timestamp(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 9 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for user
-- ----------------------------
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user`  (
  `id` smallint(4) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` char(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '用户',
  `password` char(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '密码',
  `secret` char(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT 'GoogleOTP秘钥',
  `is_bind` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否绑定GoogleOTP，1为绑定，0为未绑定',
  `otp_code` char(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL,
  `realname` char(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '姓名/备注',
  `type` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '类型，0入款人员 1管理员 2出款人员 3财务主管',
  `permissions` longtext CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '权限',
  `company_ids` varchar(218) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '所属业务平台',
  `lastlogin` datetime(0) NULL DEFAULT NULL COMMENT '最后登录时间',
  `ip` char(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '最后登录IP',
  `status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态，1 启用、0 禁用',
  `created_at` datetime(0) NOT NULL,
  `updated_at` datetime(0) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `u_name`(`username`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 43 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for vendor
-- ----------------------------
DROP TABLE IF EXISTS `vendor`;
CREATE TABLE `vendor`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `pay_type` tinyint(2) UNSIGNED NOT NULL DEFAULT 0,
  `no` char(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `key` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL,
  `callback_url` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `notify_url` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `error_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `wechat` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '微信',
  `wap_wechat` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '微信wap',
  `alipay` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '支付宝',
  `wap_alipay` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '支付宝wap',
  `netpay` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '银行',
  `qq` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'QQ',
  `wap_qq` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'QQwap',
  `jd` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '京东',
  `wap_jd` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '京东wap',
  `baidu` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '百度',
  `wap_baidu` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '百度wap',
  `union` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '银联',
  `wap_union` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '银联wap',
  `created_at` datetime(0) NOT NULL,
  `updated_at` datetime(0) NOT NULL,
  `yun` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '云闪付',
  `wap_yun` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '云闪付wap\r\n',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `v_no`(`no`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 94 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci COMMENT = '支付平台' ROW_FORMAT = Compact;

-- ----------------------------
-- Table structure for withdrawal
-- ----------------------------
DROP TABLE IF EXISTS `withdrawal`;
CREATE TABLE `withdrawal`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `platform_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '出款平台ID',
  `order_no` char(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '单号',
  `platform_order_no` char(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '流水号',
  `member_id` bigint(20) UNSIGNED NULL DEFAULT 0 COMMENT '会员id',
  `account` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '会员账号',
  `bank_no` char(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '银行卡',
  `bank_name` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '银行名称',
  `username` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '姓名',
  `amount` decimal(32, 2) UNSIGNED NULL DEFAULT NULL COMMENT '金额',
  `mobile` char(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '手机号',
  `province` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '省份',
  `city` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '城市',
  `branch` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '支行名称',
  `status` tinyint(1) UNSIGNED NULL DEFAULT 0 COMMENT '状态，0 未处理、1 处理成功、2 处理中、3 处理失败、4 已退汇、5 其他',
  `remark` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL COMMENT '备注',
  `user_id` int(10) UNSIGNED NULL DEFAULT NULL COMMENT '操作者ID',
  `note` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL COMMENT '操作者备注',
  `job_id` char(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL COMMENT '队列任务ID',
  `created_at` datetime(0) NULL DEFAULT NULL,
  `updated_at` datetime(0) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 200209 CHARACTER SET = utf8 COLLATE = utf8_unicode_ci ROW_FORMAT = Dynamic;

SET FOREIGN_KEY_CHECKS = 1;
