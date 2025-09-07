import React, { useState, useEffect } from 'react';
import { Table, Button, Popconfirm, message, Spin, Card, Row, Col, Input, Select } from 'antd';
import { PlusOutlined, DeleteOutlined, EditOutlined, SearchOutlined, FilterOutlined, RefreshOutlined } from '@ant-design/icons';
import { Link } from 'react-router-dom';
import axios from 'axios';

const { Search } = Input;
const { Option } = Select;

function ServerList() {
  const [servers, setServers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchText, setSearchText] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [pagination, setPagination] = useState({
    current: 1,
    pageSize: 10,
    total: 0,
  });

  // 获取服务器列表
  const fetchServers = async () => {
    try {
      setLoading(true);

      // 构建请求参数
      const params = {
        page: pagination.current,
        pageSize: pagination.pageSize,
      };

      if (searchText) {
        params.search = searchText;
      }

      if (statusFilter !== 'all') {
        params.status = statusFilter;
      }

      // 模拟API请求
      setTimeout(() => {
        // 模拟服务器数据
        const mockServers = [
          { id: 1, server_id: 'server_12345', hostname: 'server1.example.com', ip_address: '192.168.1.101', os: 'Linux', php_version: '8.1.2', status: 'active', country: '中国', region: '北京', city: '北京', last_seen: '2025-09-07 14:30:00' },
          { id: 2, server_id: 'server_67890', hostname: 'server2.example.com', ip_address: '192.168.1.102', os: 'Linux', php_version: '8.0.15', status: 'active', country: '美国', region: '加利福尼亚', city: '洛杉矶', last_seen: '2025-09-07 14:28:00' },
          { id: 3, server_id: 'server_abcde', hostname: 'server3.example.com', ip_address: '192.168.1.103', os: 'Windows', php_version: '7.4.27', status: 'warning', country: '英国', region: '英格兰', city: '伦敦', last_seen: '2025-09-07 14:25:00' },
          { id: 4, server_id: 'server_fghij', hostname: 'server4.example.com', ip_address: '192.168.1.104', os: 'Linux', php_version: '8.1.0', status: 'error', country: '日本', region: '东京', city: '东京', last_seen: '2025-09-07 14:20:00' },
          { id: 5, server_id: 'server_klmno', hostname: 'server5.example.com', ip_address: '192.168.1.105', os: 'Linux', php_version: '8.0.10', status: 'active', country: '德国', region: '北莱茵-威斯特法伦', city: '杜塞尔多夫', last_seen: '2025-09-07 14:18:00' },
        ];

        setServers(mockServers);
        setPagination({...pagination, total: mockServers.length});
        setLoading(false);
      }, 1000);

      // 实际项目中，这里会发送真实的API请求
      /*
      const response = await axios.get('/api/servers', { params });
      setServers(response.data.items);
      setPagination({...pagination, total: response.data.total});
      setLoading(false);
      */
    } catch (error) {
      message.error('获取服务器列表失败');
      console.error('获取服务器列表失败:', error);
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchServers();
  }, [pagination.current, pagination.pageSize, searchText, statusFilter]);

  // 处理删除服务器
  const handleDelete = async (id) => {
    try {
      // 模拟API请求
      setTimeout(() => {
        // 从列表中移除服务器
        setServers(servers.filter(server => server.id !== id));
        message.success('服务器删除成功');
      }, 500);

      // 实际项目中，这里会发送真实的API请求
      /*
      await axios.delete(`/api/servers/${id}`);
      setServers(servers.filter(server => server.id !== id));
      message.success('服务器删除成功');
      */
    } catch (error) {
      message.error('服务器删除失败');
      console.error('服务器删除失败:', error);
    }
  };

  // 表格列定义
  const columns = [
    {
      title: '服务器名称',
      dataIndex: 'hostname',
      key: 'hostname',
      render: (text, record) => (
        <Link to={`/servers/${record.id}`}>{text}</Link>
      ),
    },
    {
      title: 'IP地址',
      dataIndex: 'ip_address',
      key: 'ip_address',
    },
    {
      title: '操作系统',
      dataIndex: 'os',
      key: 'os',
    },
    {
      title: 'PHP版本',
      dataIndex: 'php_version',
      key: 'php_version',
    },
    {
      title: '状态',
      dataIndex: 'status',
      key: 'status',
      render: (status) => {
        let color = 'green';
        let text = '活跃';

        if (status === 'warning') {
          color = 'orange';
          text = '警告';
        } else if (status === 'error') {
          color = 'red';
          text = '异常';
        } else if (status === 'inactive') {
          color = 'gray';
          text = '非活跃';
        }

        return (
          <span style={{ color }}>{text}</span>
        );
      },
    },
    {
      title: '地理位置',
      dataIndex: 'country',
      key: 'location',
      render: (country, record) => `${country} ${record.region} ${record.city}`,
    },
    {
      title: '最后在线',
      dataIndex: 'last_seen',
      key: 'last_seen',
    },
    {
      title: '操作',
      key: 'action',
      render: (_, record) => (
        <div style={{ display: 'flex', gap: 8 }}>
          <Link to={`/servers/${record.id}/edit`}>
            <Button type="primary" icon={<EditOutlined />} size="small">
              编辑
            </Button>
          </Link>
          <Popconfirm
            title="确定要删除此服务器吗？"
            onConfirm={() => handleDelete(record.id)}
            okText="确定"
            cancelText="取消"
          >
            <Button type="danger" icon={<DeleteOutlined />} size="small">
              删除
            </Button>
          </Popconfirm>
        </div>
      ),
    },
  ];

  // 刷新服务器列表
  const handleRefresh = () => {
    fetchServers();
  };

  return (
    <div className="fade-in">
      <Card className="card" title="服务器列表" extra={
        <div style={{ display: 'flex', gap: 8 }}>
          <Button icon={<RefreshOutlined />} onClick={handleRefresh}>刷新</Button>
          <Button type="primary" icon={<PlusOutlined />}><Link to="/servers/create">添加服务器</Link></Button>
        </div>
      }>
        <Row gutter={[16, 16]} style={{ marginBottom: 16 }}>
          <Col xs={24} md={12} lg={8}>
            <Search
              placeholder="搜索服务器名称或IP"
              value={searchText}
              onChange={(e) => setSearchText(e.target.value)}
              onSearch={fetchServers}
              enterButton
              prefix={<SearchOutlined />}
              style={{ width: '100%' }}
            />
          </Col>
          <Col xs={24} md={12} lg={4}>
            <Select
              placeholder="筛选状态"
              value={statusFilter}
              onChange={(value) => setStatusFilter(value)}
              style={{ width: '100%' }}
              prefix={<FilterOutlined />}
            >
              <Option value="all">全部状态</Option>
              <Option value="active">活跃</Option>
              <Option value="warning">警告</Option>
              <Option value="error">异常</Option>
              <Option value="inactive">非活跃</Option>
            </Select>
          </Col>
        </Row>

        {loading ? (
          <div className="loading-container"><Spin /></div>
        ) : (
          <Table
            columns={columns}
            dataSource={servers}
            rowKey="id"
            pagination={pagination}
            onChange={(pagination) => setPagination(pagination)}
            scroll={{ x: 'max-content' }}
          />
        )}
      </Card>
    </div>
  );
}

export default ServerList;