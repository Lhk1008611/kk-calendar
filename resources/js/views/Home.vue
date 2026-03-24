<template>
    <AuthCard>
        <!-- 选项卡 -->
        <ul class="nav nav-tabs nav-justified mb-4" role="tablist">
            <li class="nav-item" role="presentation">
                <button
                    class="nav-link"
                    :class="{ active: activeTab === 'login' }"
                    @click="activeTab = 'login'"
                    type="button"
                    role="tab"
                >
                    登录
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button
                    class="nav-link"
                    :class="{ active: activeTab === 'register' }"
                    @click="activeTab = 'register'"
                    type="button"
                    role="tab"
                >
                    注册
                </button>
            </li>
        </ul>

        <!-- 登录表单 -->
        <Login
            v-if="activeTab === 'login'"
            :errors="loginErrors"
            :loading="loginLoading"
            @submit="handleLogin"
        />

        <!-- 注册表单 -->
        <Register
            v-else
            :errors="registerErrors"
            :loading="registerLoading"
            @submit="handleRegister"
        />

        <!-- 全局消息 -->
        <div
            v-if="message.text"
            class="alert mt-4"
            :class="`alert-${message.type}`"
            role="alert"
        >
            {{ message.text }}
        </div>
    </AuthCard>
</template>

<script setup>
import {ref, reactive} from 'vue'
import AuthCard from '@/components/auth/AuthCard.vue'
import Login from '@/components/auth/Login.vue'
import Register from '@/components/auth/Register.vue'
import {sanctum, api} from "../axios";

// 选项卡状态
const activeTab = ref('login')

// 登录相关状态
const loginErrors = reactive({})
const loginLoading = ref(false)

// 注册相关状态
const registerErrors = reactive({})
const registerLoading = ref(false)

// 全局消息
const message = reactive({
    text: '',
    type: 'info'
})

const clearMessage = () => {
    message.text = ''
}

// 登录验证
const validateLogin = (formData) => {
    let valid = true
    const errors = {}

    if (!formData.email) {
        errors.email = '邮箱不能为空'
        valid = false
    } else if (!/^[^\s@]+@([^\s@]+\.)+[^\s@]+$/.test(formData.email)) {
        errors.email = '邮箱格式不正确'
        valid = false
    }

    if (!formData.password) {
        errors.password = '密码不能为空'
        valid = false
    } else if (formData.password.length < 6) {
        errors.password = '密码长度至少6位'
        valid = false
    }

    Object.assign(loginErrors, errors)
    return valid
}

// 注册验证
const validateRegister = (formData) => {
    let valid = true
    const errors = {}

    if (!formData.name) {
        errors.name = '用户名不能为空'
        valid = false
    } else if (formData.name.length < 2) {
        errors.name = '用户名至少2个字符'
        valid = false
    }

    if (!formData.email) {
        errors.email = '邮箱不能为空'
        valid = false
    } else if (!/^[^\s@]+@([^\s@]+\.)+[^\s@]+$/.test(formData.email)) {
        errors.email = '邮箱格式不正确'
        valid = false
    }

    if (!formData.password) {
        errors.password = '密码不能为空'
        valid = false
    } else if (formData.password.length < 8) {
        errors.password = '密码长度至少8位'
        valid = false
    }

    if (formData.password !== formData.password_confirmation) {
        errors.password_confirmation = '两次输入的密码不一致'
        valid = false
    }

    Object.assign(registerErrors, errors)
    return valid
}

const handleLogin = async (formData) => {
    clearMessage()
    // 清空之前的错误
    Object.keys(loginErrors).forEach(key => delete loginErrors[key])
    if (!validateLogin(formData)) return

    loginLoading.value = true
    try {
        // 1. 获取 CSRF cookie
        await sanctum.get('/sanctum/csrf-cookie');

        // 2. 发起登录请求
        const response = await api.post('/login', formData);
        console.log(response);
        // 存储 token 等操作
        message.text = '登录成功！正在跳转...'
        message.type = 'success'
        setTimeout(() => {
            // router.push('/dashboard')
            alert('演示模式：登录成功，即将进入行事历页面')
        }, 1000)
    } catch {
        message.text = '登录失败，请检查邮箱和密码'
        message.type = 'danger'
    } finally {
        loginLoading.value = false
    }
}

const handleRegister = async (formData) => {
    clearMessage()
    Object.keys(registerErrors).forEach(key => delete registerErrors[key])
    if (!validateRegister(formData)) return

    registerLoading.value = true
    try {
        // 1. 获取 CSRF cookie
        await sanctum.get('/sanctum/csrf-cookie');

        // 2. 发起登录请求
        const response = await api.post('/register', formData);

        message.text = response.message || '注册成功，请登录'
        message.type = 'success'
        activeTab.value = 'login'
        // 可选：自动填充登录邮箱
        // 由于表单数据未传出，此处无法自动填充，但可以通过ref获取登录组件的formData并设置，可后续优化
    } catch {
        message.text = '注册失败'
        message.type = 'danger'
    } finally {
        registerLoading.value = false
    }
}
</script>

<style scoped>
/* AuthCard 已经包含主要样式，这里不需要额外添加，但为了兼容可保留 */
.nav-tabs .nav-link {
    color: #6c757d;
    border: none;
    font-weight: 500;
    transition: all 0.2s;
}

.nav-tabs .nav-link:hover {
    color: #0d6efd;
    background-color: transparent;
    border-color: transparent;
}

.nav-tabs .nav-link.active {
    color: #0d6efd;
    background-color: transparent;
    border-bottom: 2px solid #0d6efd;
}
</style>
