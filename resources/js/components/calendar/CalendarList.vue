<template>
    <div>
        <!-- 操作栏：搜索、新增、删除 -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex gap-2">
                <div class="input-group" style="width: 250px;">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input
                        type="text"
                        class="form-control"
                        placeholder="搜索日历名称..."
                        v-model="searchKeyword"
                    />
                </div>
                <button class="btn btn-primary" @click="openAddModal">
                    <i class="bi bi-plus-lg me-1"></i> 新增
                </button>
                <button
                    class="btn btn-outline-danger"
                    :disabled="selectedIds.length === 0"
                    @click="deleteSelected"
                >
                    <i class="bi bi-trash me-1"></i> 删除
                </button>
            </div>
        </div>

        <!-- 隐形分割线 -->
        <hr class="my-3 opacity-25"/>

        <!-- 日历表格 -->
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-secondary">
                <tr>
                    <th width="40">
                        <input
                            type="checkbox"
                            class="form-check-input"
                            :checked="isAllSelected"
                            @change="toggleSelectAll"
                        />
                    </th>
                    <th>名称</th>
                    <th>描述</th>
                    <th>颜色</th>
                    <th>默认</th>
                    <th>可见性</th>
                    <th>创建时间</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="calendar in paginatedCalendars" :key="calendar.id">
                    <td>
                        <input
                            type="checkbox"
                            class="form-check-input"
                            v-model="selectedIds"
                            :value="calendar.id"
                        />
                    </td>
                    <td>{{ calendar.name }}</td>
                    <td>{{ calendar.description || '-' }}</td>
                    <td>
                            <span
                                class="badge"
                                :style="{ backgroundColor: calendar.color || '#6c757d', color: '#fff' }"
                            >
                                {{ calendar.color || '默认' }}
                            </span>
                    </td>
                    <td>
                        <i class="bi"
                           :class="calendar.is_default ? 'bi-check-circle-fill text-success' : 'bi-circle'"></i>
                    </td>
                    <td>{{ visibilityText(calendar.visibility) }}</td>
                    <td>{{ formatDate(calendar.created_at) }}</td>
                </tr>
                <tr v-if="filteredCalendars.length === 0">
                    <td colspan="7" class="text-center text-muted">暂无数据</td>
                </tr>
                </tbody>
            </table>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted small">
                    共 {{ filteredCalendars.length }} 条
                </div>
                <nav aria-label="分页导航">
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item" :class="{ disabled: currentPage === 1 }">
                            <a class="page-link" href="#" @click.prevent="changePage(currentPage - 1)">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                        <li class="page-item disabled">
                            <span class="page-link">{{ currentPage }} / {{ totalPages || 1 }}</span>
                        </li>
                        <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                            <a class="page-link" href="#" @click.prevent="changePage(currentPage + 1)">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>

        <!-- 新增日历模态框 -->
        <div class="modal fade" id="addCalendarModal" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">新增日历</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="submitAdd">
                            <div class="mb-3">
                                <label class="form-label">名称 *</label>
                                <input type="text" class="form-control" v-model="newCalendar.name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">描述</label>
                                <textarea class="form-control" rows="2" v-model="newCalendar.description"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">颜色</label>
                                <input type="color" class="form-control form-control-color" v-model="newCalendar.color">
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="isDefault"
                                       v-model="newCalendar.is_default">
                                <label class="form-check-label" for="isDefault">设为默认日历</label>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">可见性</label>
                                <select class="form-select" v-model="newCalendar.visibility">
                                    <option :value="1">仅自己</option>
                                    <option :value="2">共享用户</option>
                                    <option :value="3">公开</option>
                                </select>
                            </div>
                            <div class="alert alert-danger" v-if="addError">{{ addError }}</div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="button" class="btn btn-primary" @click="submitAdd" :disabled="addLoading">
                            <span v-if="addLoading" class="spinner-border spinner-border-sm me-1"></span>
                            保存
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {ref, computed, onMounted, watch} from 'vue';
import {api} from '@/axios';
import {useToast} from 'vue-toastification';
import {Modal} from 'bootstrap';

const toast = useToast();

// 数据
const calendars = ref([]);
const searchKeyword = ref('');
const selectedIds = ref([]);
const addLoading = ref(false);
const addError = ref('');
const newCalendar = ref({
    name: '',
    description: '',
    color: '#3174ad',
    is_default: false,
    visibility: 1,
});
let addModal = null;
// 分页
const itemsPerPage = 10;
const currentPage = ref(1);

// 获取日历列表
const fetchCalendars = async () => {
    try {
        const response = await api.get('/calendars');
        calendars.value = response.data;
    } catch (error) {
        toast.error('获取日历列表失败');
    }
};

// 过滤
const filteredCalendars = computed(() => {
    if (!searchKeyword.value) return calendars.value;
    const kw = searchKeyword.value.toLowerCase();
    return calendars.value.filter(c => c.name.toLowerCase().includes(kw));
});

// 分页后的数据
const paginatedCalendars = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return filteredCalendars.value.slice(start, end);
});

// 总页数
const totalPages = computed(() => {
    return Math.ceil(filteredCalendars.value.length / itemsPerPage);
});

// 页码变化时重置选中状态（可选）
const changePage = (page) => {
    if (page < 1 || page > totalPages.value) return;
    currentPage.value = page;
    selectedIds.value = []; // 清除选中
};

// 全选逻辑
const isAllSelected = computed(() => {
    return paginatedCalendars.value.length > 0 && selectedIds.value.length === paginatedCalendars.value.length;
});
const toggleSelectAll = (e) => {
    if (e.target.checked) {
        selectedIds.value = paginatedCalendars.value.map(c => c.id);
    } else {
        selectedIds.value = [];
    }
};

watch(searchKeyword, () => {
    currentPage.value = 1;
    selectedIds.value = [];
});

// 删除选中
const deleteSelected = async () => {
    if (selectedIds.value.length === 0) return;
    if (!confirm(`确定要删除选中的 ${selectedIds.value.length} 个日历吗？`)) return;
    try {
        // 批量删除接口 (建议后端支持批量)
        await Promise.all(selectedIds.value.map(id => api.delete(`/calendars/${id}`)));
        toast.success('删除成功');
        selectedIds.value = [];
        fetchCalendars();
    } catch (error) {
        toast.error('删除失败');
    }
};

// 打开新增模态框
const openAddModal = () => {
    newCalendar.value = {name: '', description: '', color: '#3174ad', is_default: false, visibility: 1};
    addError.value = '';
    addModal = new Modal(document.getElementById('addCalendarModal'));
    addModal.show();
};

// 提交新增
const submitAdd = async () => {
    if (!newCalendar.value.name) {
        addError.value = '请填写名称';
        return;
    }
    addLoading.value = true;
    addError.value = '';
    try {
        await api.post('/calendars', newCalendar.value);
        toast.success('新增成功');
        addModal.hide();
        fetchCalendars();
    } catch (error) {
        if (error.response?.status === 422) {
            const errors = error.response.data.errors;
            addError.value = Object.values(errors).flat().join(' ');
        } else {
            addError.value = error.response?.data?.message || '新增失败';
        }
    } finally {
        addLoading.value = false;
    }
};

// 辅助函数
const visibilityText = (v) => {
    const map = {1: '仅自己', 2: '共享用户', 3: '公开'};
    return map[v] || '未知';
};
const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleString();
};

onMounted(() => {
    fetchCalendars();
});
</script>

<style scoped>
.table-responsive {
    border-radius: 0.5rem;
    overflow-x: auto;
}

.table thead th {
    background-color: #e9ecef;
    border-bottom: 1px solid #dee2e6;
    font-weight: 600;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
}

hr {
    border-top: 1px solid rgba(0, 0, 0, 0.05);
}
</style>
