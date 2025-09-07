<?php

/**
 * 服务器监控探针系统 - 引导文件
 *
 * 此文件负责加载环境变量、配置文件并初始化系统
 */

// 定义项目根目录
define('APP_ROOT', dirname(__DIR__));

try {
    // 加载环境变量
    $dotenv = file_get_contents(APP_ROOT . '/.env');
    if ($dotenv !== false) {
        $lines = explode('\n', $dotenv);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    // 加载配置文件
    $config = require APP_ROOT . '/config/config.php';

    // 合并环境变量到配置
    foreach ($config as $section => $settings) {
        foreach ($settings as $key => $value) {
            $envKey = strtoupper("{$section}_{$key}");
            if (isset($_ENV[$envKey])) {
                // 尝试转换类型
                if (is_numeric($_ENV[$envKey])) {
                    $config[$section][$key] = $_ENV[$envKey] + 0;
                } elseif (strtolower($_ENV[$envKey]) === 'true') {
                    $config[$section][$key] = true;
                } elseif (strtolower($_ENV[$envKey]) === 'false') {
                    $config[$section][$key] = false;
                } else {
                    $config[$section][$key] = $_ENV[$envKey];
                }
            }
        }
    }

    // 初始化错误处理
    error_reporting(E_ALL);
    if ($config['app']['debug']) {
        ini_set('display_errors', 1);
    } else {
        ini_set('display_errors', 0);
        ini_set('log_errors', 1);
        ini_set('error_log', APP_ROOT . '/logs/error.log');
    }

    // 设置时区
    date_default_timezone_set($config['app']['timezone']);

    // 初始化数据库连接
    $dbConfig = $config['database'];
    try {
        $pdo = new PDO(
            "{$dbConfig['driver']}:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}",
            $dbConfig['username'],
            $dbConfig['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        $GLOBALS['db'] = $pdo;
    } catch (PDOException $e) {
        throw new Exception("数据库连接失败: " . $e->getMessage());
    }

    // 初始化Redis连接
    $redisConfig = $config['redis'];
    try {
        $redis = new Redis();
        $redis->connect($redisConfig['host'], $redisConfig['port']);
        if (!empty($redisConfig['password'])) {
            $redis->auth($redisConfig['password']);
        }
        $redis->select($redisConfig['database']);
        $GLOBALS['redis'] = $redis;
    } catch (RedisException $e) {
        throw new Exception("Redis连接失败: " . $e->getMessage());
    }

    return $config;

} catch (Exception $e) {
    die("引导程序错误: " . $e->getMessage());
}

?>