<?php

/**
 * 服务器监控探针系统 - 探针类
 *
 * 此类负责收集服务器的各种性能数据
 */
class Probe {
    /**
     * 配置信息
     * @var array
     */
    private $config;

    /**
     * 服务器ID
     * @var string
     */
    private $serverId;

    /**
     * 构造函数
     * @param array $config 配置信息
     * @param string $serverId 服务器ID
     */
    public function __construct($config, $serverId = null) {
        $this->config = $config;
        $this->serverId = $serverId ?: uniqid('server_');
    }

    /**
     * 获取服务器基本信息
     * @return array
     */
    public function getBasicInfo() {
        return [
            'server_id' => $this->serverId,
            'hostname' => gethostname(),
            'os' => PHP_OS,
            'php_version' => PHP_VERSION,
            'ip_address' => $this->getIpAddress(),
            'timestamp' => time(),
            'timezone' => date_default_timezone_get(),
        ];
    }

    /**
     * 获取服务器IP地址
     * @return string
     */
    private function getIpAddress() {
        if (!empty($_SERVER['SERVER_ADDR'])) {
            return $_SERVER['SERVER_ADDR'];
        }

        // 尝试获取本地IP
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if ($socket) {
            socket_connect($socket, '8.8.8.8', 80);
            $ip = socket_getsockname($socket, $port);
            socket_close($socket);
            return $ip;
        }

        return '127.0.0.1';
    }

    /**
     * 获取CPU使用率
     * @return array
     */
    public function getCpuUsage() {
        // 根据不同操作系统实现CPU使用率检测
        if (strtolower(substr(PHP_OS, 0, 3)) === 'win') {
            return $this->getWindowsCpuUsage();
        } else {
            return $this->getLinuxCpuUsage();
        }
    }

    /**
     * 获取Windows系统的CPU使用率
     * @return array
     */
    private function getWindowsCpuUsage() {
        // Windows系统实现
        $wmi = new COM('WinMgmts:\\.\root\cimv2');
        $cpus = $wmi->ExecQuery('SELECT * FROM Win32_PerfFormattedData_PerfOS_Processor WHERE Name != "_Total"');

        $usage = [];
        foreach ($cpus as $cpu) {
            $usage[] = [
                'cpu' => $cpu->Name,
                'usage' => (float) $cpu->PercentProcessorTime,
            ];
        }

        // 获取总使用率
        $totalCpu = $wmi->ExecQuery('SELECT * FROM Win32_PerfFormattedData_PerfOS_Processor WHERE Name = "_Total"');
        foreach ($totalCpu as $cpu) {
            $totalUsage = (float) $cpu->PercentProcessorTime;
        }

        return [
            'total' => $totalUsage ?? 0,
            'cores' => $usage,
        ];
    }

    /**
     * 获取Linux系统的CPU使用率
     * @return array
     */
    private function getLinuxCpuUsage() {
        // Linux系统实现
        $stat = file_get_contents('/proc/stat');
        $lines = explode("\n", $stat);
        $cpuLine = $lines[0];

        preg_match_all('/\d+/', $cpuLine, $matches);
        $cpuData = $matches[0];

        $user = $cpuData[0];
        $nice = $cpuData[1];
        $system = $cpuData[2];
        $idle = $cpuData[3];
        $iowait = $cpuData[4] ?? 0;
        $irq = $cpuData[5] ?? 0;
        $softirq = $cpuData[6] ?? 0;

        $total = $user + $nice + $system + $idle + $iowait + $irq + $softirq;
        $usage = 100 - (($idle / $total) * 100);

        // 获取每个核心的使用率
        $cores = [];
        for ($i = 1; $i < count($lines); $i++) {
            if (strpos($lines[$i], 'cpu') === 0 && strpos($lines[$i], 'cpu') !== false) {
                preg_match_all('/\d+/', $lines[$i], $coreMatches);
                $coreData = $coreMatches[0];

                $coreUser = $coreData[0];
                $coreNice = $coreData[1];
                $coreSystem = $coreData[2];
                $coreIdle = $coreData[3];
                $coreIowait = $coreData[4] ?? 0;
                $coreIrq = $coreData[5] ?? 0;
                $coreSoftirq = $coreData[6] ?? 0;

                $coreTotal = $coreUser + $coreNice + $coreSystem + $coreIdle + $coreIowait + $coreIrq + $coreSoftirq;
                $coreUsage = 100 - (($coreIdle / $coreTotal) * 100);

                $cores[] = [
                    'cpu' => str_replace('cpu', '', substr($lines[$i], 0, strpos($lines[$i], ' '))),
                    'usage' => $coreUsage,
                ];
            }
        }

        return [
            'total' => $usage,
            'cores' => $cores,
        ];
    }

    /**
     * 获取内存使用情况
     * @return array
     */
    public function getMemoryUsage() {
        if (strtolower(substr(PHP_OS, 0, 3)) === 'win') {
            return $this->getWindowsMemoryUsage();
        } else {
            return $this->getLinuxMemoryUsage();
        }
    }

    /**
     * 获取Windows系统的内存使用情况
     * @return array
     */
    private function getWindowsMemoryUsage() {
        $wmi = new COM('WinMgmts:\\.\root\cimv2');
        $memory = $wmi->ExecQuery('SELECT * FROM Win32_OperatingSystem');

        foreach ($memory as $m) {
            $totalMemory = $m->TotalVisibleMemorySize * 1024;
            $freeMemory = $m->FreePhysicalMemory * 1024;
            $usedMemory = $totalMemory - $freeMemory;

            return [
                'total' => $totalMemory,
                'used' => $usedMemory,
                'free' => $freeMemory,
                'usage_percent' => ($usedMemory / $totalMemory) * 100,
            ];
        }

        return [
            'total' => 0,
            'used' => 0,
            'free' => 0,
            'usage_percent' => 0,
        ];
    }

    /**
     * 获取Linux系统的内存使用情况
     * @return array
     */
    private function getLinuxMemoryUsage() {
        $memInfo = file_get_contents('/proc/meminfo');
        $lines = explode("\n", $memInfo);

        $memData = [];
        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $key = trim($key);
                $value = trim($value);
                $memData[$key] = $value;
            }
        }

        $totalMemory = $this->convertToBytes($memData['MemTotal']);
        $freeMemory = $this->convertToBytes($memData['MemFree']);
        $buffers = $this->convertToBytes($memData['Buffers']);
        $cached = $this->convertToBytes($memData['Cached']);

        $usedMemory = $totalMemory - $freeMemory - $buffers - $cached;

        return [
            'total' => $totalMemory,
            'used' => $usedMemory,
            'free' => $freeMemory,
            'buffers' => $buffers,
            'cached' => $cached,
            'usage_percent' => ($usedMemory / $totalMemory) * 100,
        ];
    }

    /**
     * 转换内存单位到字节
     * @param string $value 内存值，如"1234 kB"
     * @return int 字节数
     */
    private function convertToBytes($value) {
        $value = trim($value);
        $unit = strtolower(substr($value, -2));
        $number = (int) substr($value, 0, -2);

        switch ($unit) {
            case 'kb':
                return $number * 1024;
            case 'mb':
                return $number * 1024 * 1024;
            case 'gb':
                return $number * 1024 * 1024 * 1024;
            default:
                return $number;
        }
    }

    /**
     * 获取磁盘使用情况
     * @return array
     */
    public function getDiskUsage() {
        if (strtolower(substr(PHP_OS, 0, 3)) === 'win') {
            return $this->getWindowsDiskUsage();
        } else {
            return $this->getLinuxDiskUsage();
        }
    }

    /**
     * 获取Windows系统的磁盘使用情况
     * @return array
     */
    private function getWindowsDiskUsage() {
        $wmi = new COM('WinMgmts:\\.\root\cimv2');
        $disks = $wmi->ExecQuery('SELECT * FROM Win32_LogicalDisk WHERE DriveType = 3');

        $diskInfo = [];
        foreach ($disks as $disk) {
            $totalSize = $disk->Size;
            $freeSpace = $disk->FreeSpace;
            $usedSpace = $totalSize - $freeSpace;

            $diskInfo[] = [
                'device' => $disk->DeviceID,
                'total' => $totalSize,
                'used' => $usedSpace,
                'free' => $freeSpace,
                'usage_percent' => ($usedSpace / $totalSize) * 100,
                'filesystem' => $disk->FileSystem,
                'volume_name' => $disk->VolumeName,
            ];
        }

        return $diskInfo;
    }

    /**
     * 获取Linux系统的磁盘使用情况
     * @return array
     */
    private function getLinuxDiskUsage() {
        $output = shell_exec('df -h');
        $lines = explode("\n", $output);

        $diskInfo = [];
        // 跳过标题行
        for ($i = 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if (empty($line)) continue;

            $parts = preg_split('/\s+/', $line);
            if (count($parts) < 6) continue;

            $device = $parts[0];
            $total = $this->convertToBytes($parts[1] . 'b');
            $used = $this->convertToBytes($parts[2] . 'b');
            $free = $this->convertToBytes($parts[3] . 'b');
            $usagePercent = (int) str_replace('%', '', $parts[4]);
            $mountPoint = $parts[5];

            $diskInfo[] = [
                'device' => $device,
                'total' => $total,
                'used' => $used,
                'free' => $free,
                'usage_percent' => $usagePercent,
                'mount_point' => $mountPoint,
            ];
        }

        return $diskInfo;
    }

    /**
     * 获取网络状态
     * @return array
     */
    public function getNetworkStatus() {
        if (strtolower(substr(PHP_OS, 0, 3)) === 'win') {
            return $this->getWindowsNetworkStatus();
        } else {
            return $this->getLinuxNetworkStatus();
        }
    }

    /**
     * 获取Windows系统的网络状态
     * @return array
     */
    private function getWindowsNetworkStatus() {
        $wmi = new COM('WinMgmts:\\.\root\cimv2');
        $networks = $wmi->ExecQuery('SELECT * FROM Win32_PerfFormattedData_Tcpip_NetworkInterface');

        $networkInfo = [];
        foreach ($networks as $network) {
            $networkInfo[] = [
                'interface' => $network->Name,
                'bytes_sent' => $network->BytesSentPerSec,
                'bytes_received' => $network->BytesReceivedPerSec,
                'packets_sent' => $network->PacketsSentPerSec,
                'packets_received' => $network->PacketsReceivedPerSec,
                'errors_outgoing' => $network->OutputErrors,
                'errors_incoming' => $network->InputErrors,
            ];
        }

        return $networkInfo;
    }

    /**
     * 获取Linux系统的网络状态
     * @return array
     */
    private function getLinuxNetworkStatus() {
        $netStats = file_get_contents('/proc/net/dev');
        $lines = explode("\n", $netStats);

        $networkInfo = [];
        // 跳过标题行
        for ($i = 2; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if (empty($line)) continue;

            $parts = preg_split('/:\s+/', $line);
            if (count($parts) < 2) continue;

            $interface = $parts[0];
            $stats = preg_split('/\s+/', $parts[1]);

            $networkInfo[] = [
                'interface' => $interface,
                'bytes_received' => (int) $stats[0],
                'packets_received' => (int) $stats[1],
                'errors_incoming' => (int) $stats[2],
                'bytes_sent' => (int) $stats[8],
                'packets_sent' => (int) $stats[9],
                'errors_outgoing' => (int) $stats[10],
            ];
        }

        return $networkInfo;
    }

    /**
     * 获取所有监控数据
     * @return array
     */
    public function getAllMetrics() {
        return [
            'basic_info' => $this->getBasicInfo(),
            'cpu' => $this->getCpuUsage(),
            'memory' => $this->getMemoryUsage(),
            'disk' => $this->getDiskUsage(),
            'network' => $this->getNetworkStatus(),
        ];
    }

    /**
     * 保存监控数据到数据库
     * @param array $metrics 监控数据
     * @return bool 是否保存成功
     */
    public function saveMetricsToDatabase($metrics) {
        try {
            $db = $GLOBALS['db'];

            // 检查服务器是否已存在
            $stmt = $db->prepare('SELECT id FROM servers WHERE server_id = :server_id');
            $stmt->execute([':server_id' => $metrics['basic_info']['server_id']]);
            $server = $stmt->fetch();

            if (!$server) {
                // 插入新服务器
                $stmt = $db->prepare('INSERT INTO servers (server_id, hostname, ip_address, os, php_version, created_at, updated_at) VALUES (:server_id, :hostname, :ip_address, :os, :php_version, NOW(), NOW())');
                $stmt->execute([
                    ':server_id' => $metrics['basic_info']['server_id'],
                    ':hostname' => $metrics['basic_info']['hostname'],
                    ':ip_address' => $metrics['basic_info']['ip_address'],
                    ':os' => $metrics['basic_info']['os'],
                    ':php_version' => $metrics['basic_info']['php_version'],
                ]);
                $serverId = $db->lastInsertId();
            } else {
                // 更新服务器信息
                $serverId = $server['id'];
                $stmt = $db->prepare('UPDATE servers SET hostname = :hostname, ip_address = :ip_address, os = :os, php_version = :php_version, updated_at = NOW() WHERE id = :id');
                $stmt->execute([
                    ':hostname' => $metrics['basic_info']['hostname'],
                    ':ip_address' => $metrics['basic_info']['ip_address'],
                    ':os' => $metrics['basic_info']['os'],
                    ':php_version' => $metrics['basic_info']['php_version'],
                    ':id' => $serverId,
                ]);
            }

            // 插入CPU数据
            $stmt = $db->prepare('INSERT INTO cpu_metrics (server_id, total_usage, timestamp) VALUES (:server_id, :total_usage, FROM_UNIXTIME(:timestamp))');
            $stmt->execute([
                ':server_id' => $serverId,
                ':total_usage' => $metrics['cpu']['total'],
                ':timestamp' => $metrics['basic_info']['timestamp'],
            ]);

            // 插入内存数据
            $stmt = $db->prepare('INSERT INTO memory_metrics (server_id, total, used, free, usage_percent, timestamp) VALUES (:server_id, :total, :used, :free, :usage_percent, FROM_UNIXTIME(:timestamp))');
            $stmt->execute([
                ':server_id' => $serverId,
                ':total' => $metrics['memory']['total'],
                ':used' => $metrics['memory']['used'],
                ':free' => $metrics['memory']['free'],
                ':usage_percent' => $metrics['memory']['usage_percent'],
                ':timestamp' => $metrics['basic_info']['timestamp'],
            ]);

            // 插入磁盘数据
            foreach ($metrics['disk'] as $disk) {
                $stmt = $db->prepare('INSERT INTO disk_metrics (server_id, device, total, used, free, usage_percent, timestamp) VALUES (:server_id, :device, :total, :used, :free, :usage_percent, FROM_UNIXTIME(:timestamp))');
                $stmt->execute([
                    ':server_id' => $serverId,
                    ':device' => $disk['device'],
                    ':total' => $disk['total'],
                    ':used' => $disk['used'],
                    ':free' => $disk['free'],
                    ':usage_percent' => $disk['usage_percent'],
                    ':timestamp' => $metrics['basic_info']['timestamp'],
                ]);
            }

            // 插入网络数据
            foreach ($metrics['network'] as $network) {
                $stmt = $db->prepare('INSERT INTO network_metrics (server_id, interface, bytes_sent, bytes_received, packets_sent, packets_received, errors_outgoing, errors_incoming, timestamp) VALUES (:server_id, :interface, :bytes_sent, :bytes_received, :packets_sent, :packets_received, :errors_outgoing, :errors_incoming, FROM_UNIXTIME(:timestamp))');
                $stmt->execute([
                    ':server_id' => $serverId,
                    ':interface' => $network['interface'],
                    ':bytes_sent' => $network['bytes_sent'],
                    ':bytes_received' => $network['bytes_received'],
                    ':packets_sent' => $network['packets_sent'],
                    ':packets_received' => $network['packets_received'],
                    ':errors_outgoing' => $network['errors_outgoing'],
                    ':errors_incoming' => $network['errors_incoming'],
                    ':timestamp' => $metrics['basic_info']['timestamp'],
                ]);
            }

            return true;
        } catch (PDOException $e) {
            error_log('数据库保存失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 发送监控数据到API
     * @param array $metrics 监控数据
     * @param string $apiUrl API地址
     * @param string $apiKey API密钥
     * @return array 响应结果
     */
    public function sendMetricsToApi($metrics, $apiUrl, $apiKey) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($metrics));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->config['network']['timeout']);

        // 如果有代理设置
        if (!empty($this->config['network']['proxy'])) {
            curl_setopt($ch, CURLOPT_PROXY, $this->config['network']['proxy']['host']);
            curl_setopt($ch, CURLOPT_PROXYPORT, $this->config['network']['proxy']['port']);
            if (!empty($this->config['network']['proxy']['username']) && !empty($this->config['network']['proxy']['password'])) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->config['network']['proxy']['username'] . ':' . $this->config['network']['proxy']['password']);
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'http_code' => $httpCode,
            'response' => $response,
            'error' => $error,
        ];
    }
}

?>