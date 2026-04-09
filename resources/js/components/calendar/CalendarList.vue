<template>
    <div class="calendar-list-container d-flex flex-column h-100">
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
        <div class="table-scroll-area flex-grow-1 overflow-auto">
            <table class="table table-hover align-middle">
                <thead class="table-secondary sticky-top">
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
                <tr v-for="calendar in calendars" :key="calendar.id">
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
                <tr v-if="totalCount === 0">
                    <td colspan="7" class="text-center text-muted">暂无数据</td>
                </tr>
                </tbody>
            </table>
        </div>

        <!-- 分页组件（固定在底部） -->
        <div class="pagination-bar mt-3 flex-shrink-0">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    共 {{ totalCount }} 条
                </div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item" :class="{ disabled: currentPage === 1 }">
                            <a class="page-link" href="#" @click.prevent="changePage(currentPage - 1)">上一页</a>
                        </li>
                        <li class="page-item disabled">
                            <span class="page-link">{{ currentPage }} / {{ totalPages }}</span>
                        </li>
                        <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                            <a class="page-link" href="#" @click.prevent="changePage(currentPage + 1)">下一页</a>
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

        <!-- 删除确认模态框 -->
        <div class="modal fade" id="deleteConfirmModal" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-danger">确认删除</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>确定要删除选中的日历吗？此操作不可恢复！</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="button" class="btn btn-danger" @click="confirmDelete" :disabled="deleteLoading">
                            <span v-if="deleteLoading" class="spinner-border spinner-border-sm me-1"></span>
                            确认删除
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
const deleteModal = ref(null);
const deleteLoading = ref(false);
// 分页
const currentPage = ref(1);
const totalPages = ref(1);
const totalCount = ref(0);
const perPage = 10;                 // 每页条数（固定或从后端获取）


// 获取日历列表
const fetchCalendars = async () => {
        try {
            let response = await api.get('/calendars', {
                params: {
                    keyword: searchKeyword.value,
                    page: currentPage.value,
                    per_page: perPage,   // 可选，后端可默认 10
                }
            });
            response = response.data;
            calendars.value = response.data;
            currentPage.value = response.current_page;
            totalPages.value = response.last_page;
            totalCount.value = response.total;
        } catch (error) {
            toast.error('获取日历列表失败');
        }
    }
;

// 页码变化时重置选中状态（可选）
const changePage = (page) => {
    if (page < 1 || page > totalPages.value) return;
    currentPage.value = page;
    selectedIds.value = []; // 清除选中
    fetchCalendars();
};

// 全选逻辑
const isAllSelected = computed(() => {
    return calendars.value.length > 0 && selectedIds.value.length === calendars.value.length;
});

const toggleSelectAll = (e) => {
    if (e.target.checked) {
        selectedIds.value = calendars.value.map(c => c.id);
    } else {
        selectedIds.value = [];
    }
};

watch(searchKeyword, () => {
    fetchCalendars();
});

// 打开删除确认模态框
const openDeleteModal = () => {
    deleteModal.value = new Modal(document.getElementById('deleteConfirmModal'));
    deleteModal.value.show();
};

// 删除选中
const confirmDelete = async () => {
    deleteLoading.value = true;
    try {
        // 批量删除接口 (建议后端支持批量)
        await api.delete(`/calendars`, {
            params: {
                ids: selectedIds.value
            }
        });
        toast.success('删除成功');
        selectedIds.value = [];
        await fetchCalendars();
        deleteModal.value.hide();
    } catch (error) {
        toast.error('删除失败');
        removeBackdrops();
    } finally {
        deleteLoading.value = false;
    }
};

// 修改原有的删除选中方法
const deleteSelected = () => {
    if (selectedIds.value.length === 0) return;
    openDeleteModal(selectedIds.value);
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
        await api.post('/calendar', newCalendar.value);
        toast.success('新增成功');
        addModal.hide();
        await fetchCalendars();
    } catch (error) {
        if (error.response?.status === 422) {
            const errors = error.response.data.errors;
            addError.value = Object.values(errors).flat().join(' ');
        } else {
            addError.value = error.response?.data?.message || '新增失败';
            removeBackdrops();
        }
    } finally {
        addLoading.value = false;
    }
};

// 强制移除 Bootstrap 遮罩层
const removeBackdrops = (() => {
    document.body.classList.remove('modal-open');
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => backdrop.remove());
});

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

.calendar-list-container {
    height: 100%;
    min-height: 0; /* 防止溢出 */
}

.table-scroll-area {
    overflow-y: auto;
    scrollbar-width: thin;
}

.table-scroll-area table {
    margin-bottom: 0;
}

.sticky-top {
    position: sticky;
    top: 0;
    z-index: 1;
    background-color: #e9ecef; /* 与表头背景色一致，避免滚动时透明 */
}
</style>
