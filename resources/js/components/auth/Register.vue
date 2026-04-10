<template>
    <form @submit.prevent="$emit('submit', formData)">
        <div class="mb-3">
            <label for="reg-name" class="form-label">用户名</label>
            <input
                type="text"
                id="reg-name"
                class="form-control"
                :class="{ 'is-invalid': errors.name }"
                v-model="formData.name"
                placeholder="昵称"
            />
            <div v-if="errors.name" class="invalid-feedback">{{ errors.name }}</div>
        </div>
        <div class="mb-3">
            <label for="reg-email" class="form-label">邮箱地址</label>
            <input
                type="email"
                id="reg-email"
                class="form-control"
                :class="{ 'is-invalid': errors.email }"
                v-model="formData.email"
                placeholder="your@email.com"
            />
            <div v-if="errors.email" class="invalid-feedback">{{ errors.email }}</div>
        </div>
        <div class="mb-3">
            <label for="reg-password" class="form-label">密码</label>
            <input
                type="password"
                id="reg-password"
                class="form-control"
                :class="{ 'is-invalid': errors.password }"
                v-model="formData.password"
                placeholder="至少8位"
            />
            <div v-if="errors.password" class="invalid-feedback">{{ errors.password }}</div>
        </div>
        <div class="mb-4">
            <label for="reg-password-confirm" class="form-label">确认密码</label>
            <input
                type="password"
                id="reg-password-confirm"
                class="form-control"
                :class="{ 'is-invalid': errors.password_confirmation }"
                v-model="formData.password_confirmation"
                placeholder="再次输入密码"
            />
            <div v-if="errors.password_confirmation" class="invalid-feedback">{{ errors.password_confirmation }}</div>
        </div>
        <button
            type="submit"
            class="btn btn-success w-100 py-2"
            :disabled="loading"
        >
      <span
          v-if="loading"
          class="spinner-border spinner-border-sm me-2"
          role="status"
          aria-hidden="true"
      ></span>
            注册
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
    name: '',
    email: '',
    password: '',
    password_confirmation: ''
})

defineEmits(['submit'])
</script>
