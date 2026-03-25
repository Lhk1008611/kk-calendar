import { defineStore } from 'pinia';
import { api } from '../axios';   // 你的 axios 实例（withCredentials: true）

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,          // 用户信息对象，null 表示未登录
        loading: false,      // 正在验证登录状态
    }),
    getters: {
        isAuthenticated: (state) => !!state.user,  // 通过是否有用户信息判断
    },
    actions: {
        setUser(userData) {
            this.user = userData;
        },
        // 初始化：从后端获取当前用户信息
        async fetchUser() {
            this.loading = true
            try {
                // 调用后端 /api/user 获取当前登录用户（需要认证）
                const response = await api.get('/user');
                this.user = response.data;
            } catch (error) {
                // 请求失败（401 等），说明未登录或 token 无效
                this.user = null;
            } finally {
                this.loading = false;
            }
        },

        // 登出
        async logout() {
            try {
                // 调用后端登出接口（销毁服务端会话）
                await api.post('/logout');
            } catch (error) {
                console.error('登出请求失败', error);
            } finally {
                // 无论后端是否成功，前端都清除用户状态
                this.user = null;
            }
        },

        // 可选：清除状态（例如 token 失效时调用）
        clear() {
            this.user = null;
        }
    }
});
