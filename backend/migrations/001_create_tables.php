<?php

/**
 * 服务器监控探针系统 - 数据库迁移文件
 *
 * 此文件包含创建系统所需数据表的SQL语句
 */

// 确保在命令行中运行
if (php_sapi_name() !== 'cli') {
    die("只能在命令行中运行此迁移脚本");
}

// 加载引导文件
$config = require dirname(__DIR__, 2) . '/backend/bootstrap.php';

// 获取数据库连接
$db = $GLOBALS['db'];

// 迁移日志
$migrations = [];

// 函数：执行SQL语句
function executeSql($db, $sql, $description) {
    global $migrations;
    try {
        $db->exec($sql);
        $migrations[] = [
            'status' => 'success',
            'description' => $description,
            'sql' => $sql,
        ];
        echo "✅ 成功: $description\n";
    } catch (PDOException $e) {
        $migrations[] = [
            'status' => 'error',
            'description' => $description,
            'sql' => $sql,
            'error' => $e->getMessage(),
        ];
        echo "❌ 失败: $description - " . $e->getMessage() . "\n";
    }
}

// 1. 创建服务器表
executeSql(
    $db,
    "CREATE TABLE IF NOT EXISTS servers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        server_id VARCHAR(64) NOT NULL UNIQUE,
        hostname VARCHAR(255) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        os VARCHAR(100) NOT NULL,
        php_version VARCHAR(20) NOT NULL,
        status ENUM('active', 'inactive', 'warning', 'error') DEFAULT 'active',
        country VARCHAR(100) NULL,
        region VARCHAR(100) NULL,
        city VARCHAR(100) NULL,
        timezone VARCHAR(100) NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        last_seen DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "创建服务器表"
);

// 2. 创建CPU指标表
executeSql(
    $db,
    "CREATE TABLE IF NOT EXISTS cpu_metrics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        server_id INT NOT NULL,
        total_usage FLOAT NOT NULL,
        timestamp DATETIME NOT NULL,
        FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "创建CPU指标表"
);

// 3. 创建内存指标表
executeSql(
    $db,
    "CREATE TABLE IF NOT EXISTS memory_metrics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        server_id INT NOT NULL,
        total BIGINT NOT NULL,
        used BIGINT NOT NULL,
        free BIGINT NOT NULL,
        usage_percent FLOAT NOT NULL,
        timestamp DATETIME NOT NULL,
        FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "创建内存指标表"
);

// 4. 创建磁盘指标表
executeSql(
    $db,
    "CREATE TABLE IF NOT EXISTS disk_metrics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        server_id INT NOT NULL,
        device VARCHAR(100) NOT NULL,
        total BIGINT NOT NULL,
        used BIGINT NOT NULL,
        free BIGINT NOT NULL,
        usage_percent FLOAT NOT NULL,
        timestamp DATETIME NOT NULL,
        FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE,
        UNIQUE KEY unique_disk_metric (server_id, device, timestamp)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "创建磁盘指标表"
);

// 5. 创建网络指标表
executeSql(
    $db,
    "CREATE TABLE IF NOT EXISTS network_metrics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        server_id INT NOT NULL,
        interface VARCHAR(100) NOT NULL,
        bytes_sent BIGINT NOT NULL,
        bytes_received BIGINT NOT NULL,
        packets_sent BIGINT NOT NULL,
        packets_received BIGINT NOT NULL,
        errors_outgoing INT NOT NULL,
        errors_incoming INT NOT NULL,
        timestamp DATETIME NOT NULL,
        FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE,
        UNIQUE KEY unique_network_metric (server_id, interface, timestamp)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "创建网络指标表"
);

// 6. 创建警报规则表
executeSql(
    $db,
    "CREATE TABLE IF NOT EXISTS alert_rules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        server_id INT NULL,
        metric_type ENUM('cpu', 'memory', 'disk', 'network') NOT NULL,
        threshold FLOAT NOT NULL,
        comparison ENUM('>', '<', '>=', '<=', '=') NOT NULL,
        duration INT NOT NULL DEFAULT 60, -- 持续时间(秒)
        enabled BOOLEAN NOT NULL DEFAULT TRUE,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "创建警报规则表"
);

// 7. 创建警报记录表
executeSql(
    $db,
    "CREATE TABLE IF NOT EXISTS alerts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        server_id INT NOT NULL,
        alert_rule_id INT NULL,
        metric_type ENUM('cpu', 'memory', 'disk', 'network') NOT NULL,
        metric_value FLOAT NOT NULL,
        threshold FLOAT NOT NULL,
        status ENUM('active', 'resolved') NOT NULL DEFAULT 'active',
        triggered_at DATETIME NOT NULL,
        resolved_at DATETIME NULL,
        FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE,
        FOREIGN KEY (alert_rule_id) REFERENCES alert_rules(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "创建警报记录表"
);

// 8. 创建用户表
executeSql(
    $db,
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
        status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
        last_login DATETIME NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "创建用户表"
);

// 9. 创建用户服务器关联表
executeSql(
    $db,
    "CREATE TABLE IF NOT EXISTS user_servers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        server_id INT NOT NULL,
        permission ENUM('read', 'write', 'admin') NOT NULL DEFAULT 'read',
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_server (user_id, server_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "创建用户服务器关联表"
);

// 10. 创建探针配置表
executeSql(
    $db,
    "CREATE TABLE IF NOT EXISTS probe_configs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        server_id INT NOT NULL UNIQUE,
        update_interval INT NOT NULL DEFAULT 60,
        send_to_api BOOLEAN NOT NULL DEFAULT TRUE,
        api_url VARCHAR(255) NULL,
        api_key VARCHAR(255) NULL,
        encryption_enabled BOOLEAN NOT NULL DEFAULT FALSE,
        encryption_key VARCHAR(255) NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "创建探针配置表"
);

// 输出迁移结果摘要
$successCount = count(array_filter($migrations, function($m) { return $m['status'] === 'success'; }));
$errorCount = count(array_filter($migrations, function($m) { return $m['status'] === 'error'; }));

echo "\n📊 迁移结果摘要:\n";
echo "✅ 成功: $successCount\n";
echo "❌ 失败: $errorCount\n";

if ($errorCount > 0) {
    echo "\n❌ 错误详情:\n";
    foreach ($migrations as $m) {
        if ($m['status'] === 'error') {
            echo "- {$m['description']}: {$m['error']}\n";
        }
    }
}

?>