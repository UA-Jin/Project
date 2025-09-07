import React, { useState, useEffect } from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import { Layout, Spin, notification } from 'antd';
import axios from 'axios';

// 布局组件
import MainLayout from './layouts/MainLayout';

// 页面组件
import Dashboard from './pages/Dashboard';
import ServerList from './pages/ServerList';
import ServerDetail from './pages/ServerDetail';
import AlertList from './pages/AlertList';
import Settings from './pages/Settings';
import Login from './pages/Login';
import Register from './pages/Register';

// API服务
import { login, getCurrentUser } from './services/auth';

const { Content } = Layout;

function App() {
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const [userInfo, setUserInfo] = useState(null);
  const [loading, setLoading] = useState(true);

  // 检查用户认证状态
  useEffect(() => {
    const checkAuth = async () => {
      try {
        const token = localStorage.getItem('token');
        if (token) {
          axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
          const response = await getCurrentUser();
          setUserInfo(response.data);
          setIsAuthenticated(true);
        }
      } catch (error) {
        localStorage.removeItem('token');
        delete axios.defaults.headers.common['Authorization'];
      }
      setLoading(false);
    };

    checkAuth();
  }, []);

  // 处理登录
  const handleLogin = async (credentials) => {
    try {
      const response = await login(credentials);
      const { token, user } = response.data;

      localStorage.setItem('token', token);
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;

      setUserInfo(user);
      setIsAuthenticated(true);

      notification.success({
        message: '登录成功',
        description: `欢迎回来，${user.username}`,
      });

      return true;
    } catch (error) {
      notification.error({
        message: '登录失败',
        description: error.response?.data?.error || '用户名或密码错误',
      });
      return false;
    }
  };

  // 处理登出
  const handleLogout = () => {
    localStorage.removeItem('token');
    delete axios.defaults.headers.common['Authorization'];
    setUserInfo(null);
    setIsAuthenticated(false);

    notification.info({
      message: '已登出',
      description: '您已成功退出系统',
    });
  };

  if (loading) {
    return (
      <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '100vh' }}>
        <Spin size="large" />
      </div>
    );
  }

  return (
    <Layout style={{ minHeight: '100vh' }}>
      {isAuthenticated ? (
        <MainLayout userInfo={userInfo} onLogout={handleLogout}>
          <Content style={{ margin: '0 16px', padding: 24, background: '#fff', minHeight: 280 }}>
            <Routes>
              <Route path="/dashboard" element={<Dashboard />} />
              <Route path="/servers" element={<ServerList />} />
              <Route path="/servers/:id" element={<ServerDetail />} />
              <Route path="/alerts" element={<AlertList />} />
              <Route path="/settings" element={<Settings />} />
              <Route path="/" element={<Navigate to="/dashboard" />} />
            </Routes>
          </Content>
        </MainLayout>
      ) : (
        <Content style={{ margin: '0 auto', maxWidth: 400, padding: 24 }}>
          <Routes>
            <Route path="/login" element={<Login onLogin={handleLogin} />} />
            <Route path="/register" element={<Register />} />
            <Route path="/*" element={<Navigate to="/login" />} />
          </Routes>
        </Content>
      )}
    </Layout>
  );
}

export default App;