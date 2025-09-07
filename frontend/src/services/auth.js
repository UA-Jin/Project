import api from './api';

// 认证相关API
export const login = (credentials) => api.post('/auth/login', credentials);
export const register = (userData) => api.post('/auth/register', userData);
export const refreshToken = () => api.post('/auth/refresh');
export const getCurrentUser = () => api.get('/auth/me');
export const logout = () => {
  localStorage.removeItem('token');
  window.location.href = '/login';
};

// 检查用户是否已认证
export const isAuthenticated = () => {
  return !!localStorage.getItem('token');
};

// 获取认证token
export const getToken = () => {
  return localStorage.getItem('token');
};

// 设置认证token
export const setToken = (token) => {
  localStorage.setItem('token', token);
};