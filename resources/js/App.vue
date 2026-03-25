<template>
    <div id="app">
        <router-view />
    </div>
</template>

<script setup>
// 全局样式可在此引入或通过 main.js 引入 Bootstrap
import { onMounted } from 'vue';
import { useAuthStore } from '@/store/auth';

const authStore = useAuthStore();

onMounted(() => {
    // 应用启动时尝试获取用户信息（如果已登录，后端会返回用户数据）
    authStore.fetchUser();
});
</script>

<style>
/* 全局样式，例如背景等可放在此处 */
</style>
