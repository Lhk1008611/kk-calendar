import {createRouter, createWebHistory} from 'vue-router';
import Home from '@/views/Home.vue'
import Layout from '@/layouts/Layout.vue';
import Calendar from '@/views/Calendar.vue';
import Profile from '@/views/Profile.vue';
import Manage from '@/views/Manage.vue';
import {useAuthStore} from '../store/auth';
import {watch} from "vue";

const routes = [
    {
        path: '/',
        name: 'Home',
        component: Home,
        meta: {requiresGuest: true} // 未登录才能访问
    },
    {
        path: '/calendar',
        component: Layout,
        meta: {requiresAuth: true},
        children: [
            {
                path: '',
                name: 'Calendar',
                component: Calendar
            },
            {
                path: '/profile',
                name: 'Profile',
                component: Profile
            },
            {
                path: '/manage',
                name: 'Manage',
                component: Manage
            }
        ]
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// 路由守卫：检查登录状态
router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();

    // 等待 fetchUser 完成（如果正在加载中）
    if (authStore.loading) {
        // 可以显示一个全局加载指示器
        // 这里简单等待几毫秒，或者使用 watch 更优雅
        await new Promise(resolve => {
            const unwatch = watch(
                () => authStore.loading,
                (val) => {
                    if (!val) {
                        unwatch();
                        resolve();
                    }
                }
            );
        });
    }

    // 未登录则跳转到首页
    if (!authStore.isAuthenticated && to.meta.requiresAuth) {
        next({name: 'Home'});
        return;
    }

    // 如果访问的是游客页面（如登录页），但已经登录，则跳转到 calendar
    if (to.meta.requiresGuest && authStore.isAuthenticated) {
        next({name: 'Calendar'});
        return;
    }
    // 其他情况放行
    next();
});

export default router;
