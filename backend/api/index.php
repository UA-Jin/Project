<?php

/**
 * 服务器监控探针系统 - API入口文件
 *
 * 此文件是API的入口点，负责路由请求和处理响应
 */

// 加载引导文件
$config = require dirname(__DIR__) . '/bootstrap.php';

// 允许跨域请求
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 处理OPTIONS请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 获取请求路径
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = dirname($_SERVER['SCRIPT_NAME']);
$path = str_replace($basePath, '', $requestUri);
$pathParts = explode('/', trim($path, '/'));

// 获取请求方法
$method = $_SERVER['REQUEST_METHOD'];

// 验证API密钥
$apiKey = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!empty($apiKey)) {
    $apiKey = str_replace('Bearer ', '', $apiKey);
    if (!in_array($apiKey, $config['security']['api_keys'])) {
        http_response_code(401);
        echo json_encode(['error' => '无效的API密钥']);
        exit;
    }
}

// 简单路由处理
switch ($pathParts[0]) {
    case 'servers':
        // 服务器相关API
        if ($method === 'GET') {
            if (isset($pathParts[1])) {
                // 获取单个服务器信息
                $serverId = $pathParts[1];
                require __DIR__ . '/servers/get.php';
            } else {
                // 获取所有服务器列表
                require __DIR__ . '/servers/list.php';
            }
        } elseif ($method === 'POST') {
            // 添加新服务器
            require __DIR__ . '/servers/create.php';
        } elseif ($method === 'PUT' && isset($pathParts[1])) {
            // 更新服务器信息
            $serverId = $pathParts[1];
            require __DIR__ . '/servers/update.php';
        } elseif ($method === 'DELETE' && isset($pathParts[1])) {
            // 删除服务器
            $serverId = $pathParts[1];
            require __DIR__ . '/servers/delete.php';
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'API端点不存在']);
        }
        break;

    case 'metrics':
        // 指标数据相关API
        if ($method === 'GET' && isset($pathParts[1])) {
            $serverId = $pathParts[1];
            $metricType = $pathParts[2] ?? '';
            require __DIR__ . '/metrics/get.php';
        } elseif ($method === 'POST') {
            // 接收探针数据
            require __DIR__ . '/metrics/receive.php';
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'API端点不存在']);
        }
        break;

    case 'alerts':
        // 警报相关API
        if ($method === 'GET') {
            if (isset($pathParts[1])) {
                // 获取单个警报
                $alertId = $pathParts[1];
                require __DIR__ . '/alerts/get.php';
            } else {
                // 获取警报列表
                require __DIR__ . '/alerts/list.php';
            }
        } elseif ($method === 'POST') {
            // 创建警报规则
            require __DIR__ . '/alerts/create.php';
        } elseif ($method === 'PUT' && isset($pathParts[1])) {
            // 更新警报规则
            $alertId = $pathParts[1];
            require __DIR__ . '/alerts/update.php';
        } elseif ($method === 'DELETE' && isset($pathParts[1])) {
            // 删除警报规则
            $alertId = $pathParts[1];
            require __DIR__ . '/alerts/delete.php';
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'API端点不存在']);
        }
        break;

    case 'probe':
        // 探针相关API
        if ($method === 'GET' && isset($pathParts[1])) {
            // 获取探针配置
            $serverId = $pathParts[1];
            require __DIR__ . '/probe/config.php';
        } elseif ($method === 'PUT' && isset($pathParts[1])) {
            // 更新探针配置
            $serverId = $pathParts[1];
            require __DIR__ . '/probe/update.php';
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'API端点不存在']);
        }
        break;

    case 'auth':
        // 认证相关API
        if ($method === 'POST' && $pathParts[1] === 'login') {
            require __DIR__ . '/auth/login.php';
        } elseif ($method === 'POST' && $pathParts[1] === 'register') {
            require __DIR__ . '/auth/register.php';
        } elseif ($method === 'POST' && $pathParts[1] === 'refresh') {
            require __DIR__ . '/auth/refresh.php';
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'API端点不存在']);
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'API端点不存在']);
        break;
}

?>