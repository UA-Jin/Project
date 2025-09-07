import React, { useState, useEffect } from 'react';
import { Card, Row, Col, Statistic, Spin, Alert, Tooltip } from 'antd';
import { LineChart, Line, BarChart, Bar, PieChart, Pie, Cell, ResponsiveContainer, XAxis, YAxis, CartesianGrid, Tooltip as RechartsTooltip, Legend } from 'recharts';
import { ClockCircleOutlined, CpuOutlined, DatabaseOutlined, NetworkOutlined, AlertTriangleOutlined, ServerOutlined } from '@ant-design/icons';
import axios from 'axios';

// 模拟数据
const mockCpuData = [
  { time: '00:00', usage: 30 },
  { time: '02:00', usage: 25 },
  { time: '04:00', usage: 20 },
  { time: '06:00', usage: 40 },
  { time: '08:00', usage: 65 },
  { time: '10:00', usage: 75 },
  { time: '12:00', usage: 60 },
  { time: '14:00', usage: 70 },
  { time: '16:00', usage: 80 },
  { time: '18:00', usage: 65 },
  { time: '20:00', usage: 50 },
  { time: '22:00', usage: 40 },
];

const mockMemoryData = [
  { name: '已使用', value: 65 },
  { name: '可用', value: 35 },
];

const mockDiskData = [
  { name: '系统盘', used: 75, total: 100 },
  { name: '数据盘1', used: 45, total: 100 },
  { name: '数据盘2', used: 30, total: 100 },
];

const mockNetworkData = [
  { time: '00:00', in: 10, out: 5 },
  { time: '04:00', in: 8, out: 3 },
  { time: '08:00', in: 25, out: 15 },
  { time: '12:00', in: 30, out: 20 },
  { time: '16:00', in: 40, out: 30 },
  { time: '20:00', in: 20, out: 15 },
];

const COLORS = ['#1890ff', '#e8f4ff', '#ff7875', '#ffccc7', '#52c41a', '#f6ffed'];

function Dashboard() {
  const [loading, setLoading] = useState(true);
  const [serverStats, setServerStats] = useState({ total: 0, active: 0, warning: 0, error: 0 });
  const [recentAlerts, setRecentAlerts] = useState([]);
  const [performanceData, setPerformanceData] = useState({ cpu: [], memory: [], disk: [], network: [] });

  useEffect(() => {
    // 模拟API请求
    setTimeout(() => {
      // 模拟服务器统计数据
      setServerStats({ total: 15, active: 12, warning: 2, error: 1 });

      // 模拟最近警报
      setRecentAlerts([
        { id: 1, server: '服务器A', type: 'CPU使用率过高', value: '95%', time: '10分钟前', status: 'warning' },
        { id: 2, server: '服务器B', type: '内存不足', value: '90%', time: '25分钟前', status: 'error' },
        { id: 3, server: '服务器C', type: '磁盘空间不足', value: '85%', time: '1小时前', status: 'warning' },
      ]);

      // 设置性能数据
      setPerformanceData({
        cpu: mockCpuData,
        memory: mockMemoryData,
        disk: mockDiskData,
        network: mockNetworkData,
      });

      setLoading(false);
    }, 1000);

    // 实际项目中，这里会发送真实的API请求
    /*
    const fetchData = async () => {
      try {
        setLoading(true);

        // 获取服务器统计
        const serverStatsResponse = await axios.get('/api/servers/stats');
        setServerStats(serverStatsResponse.data);

        // 获取最近警报
        const alertsResponse = await axios.get('/api/alerts?limit=3');
        setRecentAlerts(alertsResponse.data);

        // 获取性能数据
        const performanceResponse = await axios.get('/api/metrics/overview');
        setPerformanceData(performanceResponse.data);
      } catch (error) {
        console.error('获取数据失败:', error);
      } finally {
        setLoading(false);
      }
    };

    fetchData();
    */
  }, []);

  return (
    <div className="fade-in">
      <h1 style={{ marginBottom: 24 }}>仪表盘概览</h1>

      {/* 服务器统计卡片 */}
      <Row gutter={[16, 16]} style={{ marginBottom: 24 }}>
        <Col xs={24} sm={12} md={6}>
          <Card className="card">
            <Statistic
              title="总服务器数"
              value={serverStats.total}
              prefix={<ServerOutlined />}
              style={{ color: '#1890ff' }}
            />
          </Card>
        </Col>
        <Col xs={24} sm={12} md={6}>
          <Card className="card">
            <Statistic
              title="活跃服务器"
              value={serverStats.active}
              prefix={<ServerOutlined />}
              style={{ color: '#52c41a' }}
            />
          </Card>
        </Col>
        <Col xs={24} sm={12} md={6}>
          <Card className="card">
            <Statistic
              title="警告服务器"
              value={serverStats.warning}
              prefix={<AlertTriangleOutlined />}
              style={{ color: '#faad14' }}
            />
          </Card>
        </Col>
        <Col xs={24} sm={12} md={6}>
          <Card className="card">
            <Statistic
              title="异常服务器"
              value={serverStats.error}
              prefix={<AlertTriangleOutlined />}
              style={{ color: '#ff4d4f' }}
            />
          </Card>
        </Col>
      </Row>

      {/* 最近警报 */}
      <div style={{ marginBottom: 24 }}>
        <h2 style={{ marginBottom: 16 }}>最近警报</h2>
        {recentAlerts.length > 0 ? (
          <Row gutter={[16, 16]}>
            {recentAlerts.map((alert) => (
              <Col xs={24} key={alert.id}>
                <Alert
                  message={`${alert.server} - ${alert.type}`}
                  description={`值: ${alert.value} | 时间: ${alert.time}`}
                  type={alert.status === 'error' ? 'error' : 'warning'}
                  showIcon
                  style={{ borderRadius: 8 }}
                />
              </Col>
            ))}
          </Row>
        ) : (
          <Alert message="暂无警报" type="success" showIcon style={{ borderRadius: 8 }} />
        )}
      </div>

      {/* 性能图表 */}
      <Row gutter={[16, 16]}>
        {/* CPU使用率图表 */}
        <Col xs={24} lg={12}>
          <Card className="card" title="CPU使用率趋势" extra={<ClockCircleOutlined />}>:
            {loading ? (
              <div className="loading-container"><Spin /></div>
            ) : (
              <ResponsiveContainer width="100%" height={300}>
                <LineChart data={performanceData.cpu} margin={{ top: 5, right: 30, left: 20, bottom: 5 }}>
                  <CartesianGrid strokeDasharray="3 3" />
                  <XAxis dataKey="time" />
                  <YAxis domain={[0, 100]} unit="%" />
                  <RechartsTooltip formatter={(value) => [`${value}%`, '使用率']} />
                  <Legend />
                  <Line type="monotone" dataKey="usage" stroke="#1890ff" activeDot={{ r: 8 }} name="CPU使用率" />
                </LineChart>
              </ResponsiveContainer>
            )}
          </Card>
        </Col>

        {/* 内存使用图表 */}
        <Col xs={24} lg={12}>
          <Card className="card" title="内存使用情况" extra={<DatabaseOutlined />}>:
            {loading ? (
              <div className="loading-container"><Spin /></div>
            ) : (
              <ResponsiveContainer width="100%" height={300}>
                <PieChart>
                  <Pie
                    data={performanceData.memory}
                    cx="50%"
                    cy="50%"
                    labelLine={false}
                    outerRadius={100}
                    fill="#8884d8"
                    dataKey="value"
                    label={({ name, percent }) => `${name}: ${(percent * 100).toFixed(0)}%`}
                  >
                    {performanceData.memory.map((entry, index) => (
                      <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                    ))}
                  </Pie>
                  <RechartsTooltip formatter={(value) => [`${value}%`, '使用率']} />
                  <Legend />
                </PieChart>
              </ResponsiveContainer>
            )}
          </Card>
        </Col>

        {/* 磁盘使用图表 */}
        <Col xs={24} lg={12}>
          <Card className="card" title="磁盘使用情况" extra={<DatabaseOutlined />}>:
            {loading ? (
              <div className="loading-container"><Spin /></div>
            ) : (
              <ResponsiveContainer width="100%" height={300}>
                <BarChart data={performanceData.disk} margin={{ top: 5, right: 30, left: 20, bottom: 5 }}>
                  <CartesianGrid strokeDasharray="3 3" />
                  <XAxis dataKey="name" />
                  <YAxis domain={[0, 100]} unit="%" />
                  <RechartsTooltip formatter={(value) => [`${value}%`, '使用率']} />
                  <Legend />
                  <Bar dataKey="used" name="已使用" fill="#1890ff" />
                  <Bar dataKey="total" name="总量" fill="#e8f4ff" />
                </BarChart>
              </ResponsiveContainer>
            )}
          </Card>
        </Col>

        {/* 网络流量图表 */}
        <Col xs={24} lg={12}>
          <Card className="card" title="网络流量趋势" extra={<NetworkOutlined />}>:
            {loading ? (
              <div className="loading-container"><Spin /></div>
            ) : (
              <ResponsiveContainer width="100%" height={300}>
                <LineChart data={performanceData.network} margin={{ top: 5, right: 30, left: 20, bottom: 5 }}>
                  <CartesianGrid strokeDasharray="3 3" />
                  <XAxis dataKey="time" />
                  <YAxis unit="MB/s" />
                  <RechartsTooltip formatter={(value) => [`${value} MB/s`, '流量']} />
                  <Legend />
                  <Line type="monotone" dataKey="in" stroke="#52c41a" name="流入" />
                  <Line type="monotone" dataKey="out" stroke="#ff4d4f" name="流出" />
                </LineChart>
              </ResponsiveContainer>
            )}
          </Card>
        </Col>
      </Row>
    </div>
  );
}

export default Dashboard;