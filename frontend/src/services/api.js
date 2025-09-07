import axios from 'axios';

// 创建axios实例
const api = axios.create({
  baseURL: '/api',
  timeout: 5000,
  headers: {
    'Content-Type': 'application/json',
  },
});

// 请求拦截器
api.interceptors.request.use(
  (config) => {
    // 添加认证token
    const token = localStorage.getItem('token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// 响应拦截器
api.interceptors.response.use(
  (response) => {
    return response;
  },
  (error) => {
    // 处理认证错误
    if (error.response && error.response.status === 401) {
      localStorage.removeItem('token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

// 服务器相关API
export const getServers = (params) => api.get('/servers', { params });
export const getServerById = (id) => api.get(`/servers/${id}`);
export const createServer = (data) => api.post('/servers', data);
export const updateServer = (id, data) => api.put(`/servers/${id}`, data);
export const deleteServer = (id) => api.delete(`/servers/${id}`);
export const getServerStats = () => api.get('/servers/stats');

// 指标数据相关API
export const getServerMetrics = (serverId, metricType, params) => api.get(`/metrics/${serverId}/${metricType}`, { params });
export const sendMetrics = (data) => api.post('/metrics', data);

// 警报相关API
export const getAlerts = (params) => api.get('/alerts', { params });
export const getAlertById = (id) => api.get(`/alerts/${id}`);
export const createAlert = (data) => api.post('/alerts', data);
export const updateAlert = (id, data) => api.put(`/alerts/${id}`, data);
export const deleteAlert = (id) => api.delete(`/alerts/${id}`);
export const resolveAlert = (id) => api.put(`/alerts/${id}/resolve`);

// 探针配置相关API
export const getProbeConfig = (serverId) => api.get(`/probe/${serverId}`);
export const updateProbeConfig = (serverId, data) => api.put(`/probe/${serverId}`, data);

export default api;