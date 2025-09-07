<?php

/**
 * 服务器监控探针系统 - 配置文件
 *
 * 本文件包含系统的核心配置信息，请确保安全存储，不要提交到版本控制系统
 */

return [
    // 系统基本设置
    'app' => [
        'name' => 'Server Monitor Dashboard',
        'version' => '1.0.0',
        'debug' => true,
        'timezone' => 'UTC',
    ],

    // 数据库配置
    'database' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'server_monitor',
        'username' => 'root',
        'password' => 'password',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
    ],

    // Redis缓存配置
    'redis' => [
        'host' => 'localhost',
        'port' => 6379,
        'password' => '',
        'database' => 0,
    ],

    // 安全设置
    'security' => [
        'csrf_protection' => true,
        'allowed_ips' => ['127.0.0.1', '::1'], // 允许访问的IP地址
        'api_keys' => [], // API密钥列表
        'encryption_key' => 'your-secret-key-here', // 用于数据加密
    ],

    // 网络设置
    'network' => [
        'timeout' => 30, // 网络请求超时时间(秒)
        'proxy' => [], // 代理服务器配置
        'allowed_countries' => [], // 允许的国家/地区代码列表，空数组表示全部允许
    ],

    // 服务器探针设置
    'probe' => [
        'update_interval' => 60, // 数据更新间隔(秒)
        'max_connections' => 100, // 最大并发连接数
        'log_level' => 'info', // 日志级别: debug, info, warning, error
    ],
];

?>