<template>
    <div class="profile-container">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4">
                <h4 class="card-title mb-4">个人信息</h4>

                <!-- 只读信息展示 -->
                <div class="mb-4">
                    <div class="mb-3">
                        <label class="form-label text-muted">用户名</label>
                        <div class="p-2 bg-light rounded">{{ user?.name || '未设置' }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">邮箱</label>
                        <div class="p-2 bg-light rounded">{{ user?.email || '未设置' }}</div>
                    </div>
                </div>

                <!-- 按钮区域：水平并排 -->
                <div class="d-flex gap-3 justify-content-start">
                    <button class="btn btn-primary" @click="openEditModal">
                        <i class="bi bi-pencil-square me-1"></i> 修改资料
                    </button>
                    <button class="btn btn-outline-danger" @click="confirmDelete">
                        <i class="bi bi-trash me-1"></i> 注销账号
                    </button>
                </div>
            </div>
        </div>

        <!-- 修改资料模态框 -->
        <div class="modal fade" id="editProfileModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">修改资料</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="submitEdit">
                            <div class="mb-3">
                                <label for="edit-name" class="form-label">用户名</label>
                                <input type="text" class="form-control" id="edit-name" v-model="editForm.name" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit-email" class="form-label">邮箱</label>
                                <input type="email" class="form-control" id="edit-email" v-model="editForm.email"
                                       required>
                            </div>
                            <div class="mb-3">
                                <label for="edit-password" class="form-label">新密码（留空则不修改）</label>
                                <input type="password" class="form-control" id="edit-password"
                                       v-model="editForm.password">
                            </div>
                            <div class="mb-3">
                                <label for="edit-password-confirm" class="form-label">确认新密码</label>
                                <input type="password" class="form-control" id="edit-password-confirm"
                                       v-model="editForm.password_confirmation">
                            </div>
                            <div class="alert alert-danger" v-if="editError">{{ editError }}</div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="button" class="btn btn-primary" @click="submitEdit" :disabled="editLoading">
                            <span v-if="editLoading" class="spinner-border spinner-border-sm me-1"></span>
                            保存
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- 注销账号确认模态框 -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">注销账号</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>确定要注销账号吗？此操作不可恢复！</p>
                    <div class="alert alert-warning" v-if="deleteError">{{ deleteError }}</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-danger" @click="deleteAccount" :disabled="deleteLoading">
                        <span v-if="deleteLoading" class="spinner-border spinner-border-sm me-1"></span>
                        确认注销
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {ref, onMounted} from 'vue';
import {useAuthStore} from '@/store/auth';
import {api} from '@/axios';
import {useRouter} from 'vue-router';
import {useToast} from 'vue-toastification';
import {Modal} from 'bootstrap';

const authStore = useAuthStore();
const router = useRouter();
const toast = useToast();

const user = ref();
const editForm = ref({name: '', email: '', password: '', password_confirmation: ''});
const editLoading = ref(false);
const editError = ref('');
const deleteLoading = ref(false);
const deleteError = ref('');
let editModal = null;
let deleteModal = null;


// 打开修改模态框
const openEditModal = () => {
    if (!user.value) return;
    editForm.value = {
        name: user.value.name,
        email: user.value.email
    };
    editError.value = '';
    editModal = new Modal(document.getElementById('editProfileModal'));
    editModal.show();
};

// 提交修改
const submitEdit = async () => {
    if (editForm.value.password !== editForm.value.password_confirmation) {
        editError.value = '两次输入的新密码不一致';
        return;
    }
    if (editForm.value.password && editForm.value.password.length < 8) {
        editError.value = '密码长度至少8位';
        return;
    }
    editLoading.value = true;
    editError.value = '';
    try {
        const response = await api.put(`/profile`, editForm.value);
        const user = response.data
        user.value = user;
        authStore.setUser(user);
        toast.success('资料更新成功');
        editModal.hide();
    } catch (error) {
        editError.value = error.response?.data?.message || '更新失败，请重试';
        removeBackdrops();
    } finally {
        editLoading.value = false;
    }
};

// 确认注销
const confirmDelete = () => {
    deleteError.value = '';
    deleteModal = new Modal(document.getElementById('deleteConfirmModal'));
    deleteModal.show();
};

const deleteAccount = async () => {
    try {
        await api.delete(`/profile`);
        toast.success('账号已注销');
        // 清除本地状态并跳转到首页
        authStore.clear();
        // 关闭模态框
        if (deleteModal) {
            deleteModal.hide();
        }
        removeBackdrops();
        // 延迟跳转，确保遮罩完全移除
        setTimeout(() => {
            router.push({ name: 'Home' });
        }, 100);
    } catch (error) {
        console.error('注销失败', error);
        toast.error('注销失败，请重试');
    }
};

// 强制移除 Bootstrap 遮罩层
const removeBackdrops = (()=>{
    document.body.classList.remove('modal-open');
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => backdrop.remove());
});


// 获取用户信息
onMounted(() => {
    if (authStore.isAuthenticated) {
        user.value = authStore.user
    } else {
        router.push({name: 'Home'});
    }
});
</script>

<style scoped>
.profile-container {
    max-width: 600px;
    margin: 0 auto;
}

.card {
    background-color: #fff;
}

.bg-light {
    background-color: #f8f9fa !important;
}

.btn {
    min-width: 120px;
}
</style>
