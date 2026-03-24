<template>
    <form @submit.prevent="$emit('submit', formData)">
        <div class="mb-3">
            <label for="login-email" class="form-label">邮箱地址</label>
            <input
                type="email"
                id="login-email"
                class="form-control"
                :class="{ 'is-invalid': errors.email }"
                v-model="formData.email"
                placeholder="your@email.com"
            />
            <div v-if="errors.email" class="invalid-feedback">{{ errors.email }}</div>
        </div>
        <div class="mb-4">
            <label for="login-password" class="form-label">密码</label>
            <input
                type="password"
                id="login-password"
                class="form-control"
                :class="{ 'is-invalid': errors.password }"
                v-model="formData.password"
                placeholder="请输入密码"
            />
            <div v-if="errors.password" class="invalid-feedback">{{ errors.password }}</div>
        </div>
        <button
            type="submit"
            class="btn btn-primary w-100 py-2"
            :disabled="loading"
        >
      <span
          v-if="loading"
          class="spinner-border spinner-border-sm me-2"
          role="status"
          aria-hidden="true"
      ></span>
            登录
        </button>
    </form>
</template>

<script setup>
import { reactive } from 'vue'

const props = defineProps({
    errors: {
        type: Object,
        default: () => ({})
    },
    loading: {
        type: Boolean,
        default: false
    }
})

const formData = reactive({
    email: '',
    password: ''
})

defineEmits(['submit'])
</script>

<style scoped>
/* 样式可复用或写在全局 */
</style>
