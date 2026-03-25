<template>
    <div class="app-layout">
        <!-- 顶部导航栏 -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4">
            <div class="container-fluid">
                <!-- 左侧：日历图标 -->
                <router-link to="/dashboard" class="navbar-brand d-flex align-items-center">
                    <i class="bi bi-calendar3 fs-4 me-2"></i>
                    <span class="fw-semibold">行事历</span>
                </router-link>

                <!-- 右侧图标区域 -->
                <div class="d-flex align-items-center gap-3">
                    <!-- 行事历管理图标 -->
                    <router-link to="/manage" class="text-dark text-decoration-none" title="行事历管理">
                        <i class="bi bi-gear fs-5"></i>
                    </router-link>
                    <!-- 个人信息图标 -->
                    <router-link to="/profile" class="text-dark text-decoration-none" title="个人信息">
                        <i class="bi bi-person-circle fs-5"></i>
                    </router-link>
                    <!-- 退出登录（可选） -->
                    <a href="#" @click.prevent="logout" class="text-dark text-decoration-none" title="退出登录">
                        <i class="bi bi-box-arrow-right fs-5"></i>
                    </a>
                </div>
            </div>
        </nav>

        <!-- 主要内容区域 -->
        <main class="main-content p-4">
            <router-view/>
        </main>
    </div>
</template>

<script setup>
import {useRouter} from 'vue-router';
import {useToast} from 'vue-toastification';
import {useAuthStore} from '@/store/auth';

const router = useRouter();
const toast = useToast();
const authStore = useAuthStore();

const logout = async () => {
    await authStore.logout();      // 调用登出 action
    toast.success('已退出登录');
    router.push({name: 'Home'});
}
</script>

<style scoped>
.app-layout {
    min-height: 100vh;
    background-color: #f8f9fc;
}

.navbar {
    position: sticky;
    top: 0;
    z-index: 1030; /* Bootstrap 默认 navbar 的 z-index 为 1030，确保层级正确 */
    border-bottom: 1px solid #e3e6f0;
}

.navbar-brand {
    font-size: 1.25rem;
    color: #4e73df;
}

.main-content {
    max-width: 1900px;
    margin: 0 auto;
}

/* 确保图标有 hover 效果 */
.navbar a:hover {
    color: #4e73df !important;
}
</style>
