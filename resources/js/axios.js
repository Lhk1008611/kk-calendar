import axios from 'axios';
import router from "@/router";
import { useToast } from 'vue-toastification';
// 业务 API 实例（带 /api 前缀）
export const api = axios.create({
    baseURL: '/api',
    withCredentials: true,           // 携带 Cookie（Sanctum 必需）
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
    }
});

// 响应拦截器
api.interceptors.response.use(
    // 2xx 状态码直接返回数据
    response => response,
    // 处理错误状态码
    error => {
        const { response } = error;
        const toast = useToast();

        // 如果没有 response（如网络断开），单独处理
        if (!response) {
            toast.error('网络连接失败，请检查网络');
            return Promise.reject(error);
        }

        const { status, data } = response;

        // 根据状态码分类处理
        switch (status) {
            case 400:
                toast.error(data?.message || '请求参数错误');
                break;
            case 401:
                // toast.warning('登录已过期，请重新登录');
                // router.push({ name: 'Home' }); // 跳转到登录页
                break;
            case 403:
                toast.error('没有权限执行此操作');
                break;
            case 404:
                toast.error('请求的资源不存在');
                break;
            case 422:
                // 验证错误，通常由业务组件自行处理字段错误，此处可做全局提示
                // 但不自动弹 toast，避免覆盖组件内错误展示
                // 如果有全局提示需求，可提取 data.errors 的通用消息
                if (data.message) {
                    toast.warning(data.message);
                }
                break;
            case 429:
                toast.error('请求过于频繁，请稍后再试');
                break;
            case 500:
                toast.error('服务器错误，请稍后重试或联系管理员');
                break;
            default:
                toast.error(data?.message || `请求失败 (${status})`);
        }

        return Promise.reject(error);
    }
);

// Sanctum CSRF 实例（不带前缀）
export const sanctum = axios.create({
    baseURL: '/',                    // 相对路径即可
    withCredentials: true,
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
    }
});

export default {api, sanctum};
