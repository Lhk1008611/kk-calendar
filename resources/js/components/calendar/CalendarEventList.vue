<template>
    <div>
        <!-- 操作栏：搜索、删除（无新增） -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex gap-2">
                <div class="input-group" style="width: 250px;">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input
                        type="text"
                        class="form-control"
                        placeholder="搜索事件标题..."
                        v-model="searchKeyword"
                    />
                </div>
                <button
                    class="btn btn-outline-danger"
                    :disabled="selectedIds.length === 0"
                    @click="deleteSelected"
                >
                    <i class="bi bi-trash me-1"></i> 删除
                </button>
            </div>
        </div>

        <hr class="my-3 opacity-25"/>

        <!-- 事件表格 -->
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
                    <th>标题</th>
                    <th>开始时间</th>
                    <th>结束时间</th>
                    <th>全天</th>
                    <th>状态</th>
                    <th>优先级</th>
                    <th>地点</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="event in events" :key="event.id">
                    <td>
                        <input
                            type="checkbox"
                            class="form-check-input"
                            v-model="selectedIds"
                            :value="event.id"
                        />
                    </td>
                    <td>{{ event.title }}</td>
                    <td>{{ formatDateTime(event.start_time) }}</td>
                    <td>{{ formatDateTime(event.end_time) }}</td>
                    <td>
                        <i class="bi"
                           :class="event.all_day ? 'bi-check-circle-fill text-success' : 'bi-x-circle text-muted'"></i>
                    </td>
                    <td>{{ statusText(event.status) }}</td>
                    <td>{{ priorityText(event.priority) }}</td>
                    <td>{{ event.location || '-' }}</td>
                </tr>
                <tr v-if="events.length === 0">
                    <td colspan="8" class="text-center text-muted">暂无数据</td>
                </tr>
                </tbody>
            </table>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted small">
                    共 {{ totalCount }} 条
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

        <!-- 删除确认模态框 -->
        <div class="modal fade" id="deleteConfirmModal" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-danger">确认删除</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>确定要删除选中的事件吗？此操作不可恢复！</p>
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
import {api} from '../../axios';
import {useToast} from 'vue-toastification';
import {Modal} from 'bootstrap';


const toast = useToast();

const events = ref([]);
const searchKeyword = ref('');
const selectedIds = ref([]);

const deleteModal = ref(null);
const deleteLoading = ref(false);

const perPage = 10;
const totalPages = ref(1);
const totalCount = ref(0);
const currentPage = ref(1);


// 获取事件列表
const fetchEvents = async () => {
    try {
        let response = await api.get('/calendar_events', {
            params: {
                keyword: searchKeyword.value,
                page: currentPage.value,
                per_page: perPage,   // 可选，后端可默认 10
            }
        });
        response = response.data;
        events.value = response.data;
        currentPage.value = response.current_page
        totalPages.value = response.last_page;
        totalCount.value = response.total;
    } catch (error) {
        toast.error('获取事件列表失败');
    }
};

// 页码变化时重置选中状态（可选）
const changePage = (page) => {
    if (page < 1 || page > totalPages.value) return;
    currentPage.value = page;
    selectedIds.value = []; // 清除选中
};

// 全选逻辑
const isAllSelected = computed(() => {
    return events.value.length > 0 && selectedIds.value.length === events.value.length;
});


const toggleSelectAll = (e) => {
    if (e.target.checked) {
        selectedIds.value = events.value.map(c => c.id);
    } else {
        selectedIds.value = [];
    }
};

watch(searchKeyword, () => {
    fetchEvents();
});

const openDeleteModal = () => {
    deleteModal.value = new Modal(document.getElementById('deleteConfirmModal'));
    deleteModal.value.show();
};

// 删除选中
const confirmDelete = async () => {
    try {
        await api.delete(`/calendar_events`,
            {
                params: {
                    ids: selectedIds.value
                }
            });
        toast.success('删除成功');
        selectedIds.value = [];
        await fetchEvents();
        deleteModal.value.hide();
    } catch (error) {
        toast.error('删除失败');
        removeBackdrops();
    }
};

// 强制移除 Bootstrap 遮罩层
const removeBackdrops = (() => {
    document.body.classList.remove('modal-open');
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => backdrop.remove());
});

const deleteSelected = () => {
    if (selectedIds.value.length === 0) return;
    openDeleteModal(selectedIds.value);
};

// 辅助函数
const formatDateTime = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleString();
};
const statusText = (status) => {
    const map = {1: '待办', 2: '进行中', 3: '已完成', 4: '已取消'};
    return map[status] || '未知';
};
const priorityText = (priority) => {
    if (!priority) return '-';
    const map = {1: '低', 2: '中', 3: '高'};
    return map[priority];
};

onMounted(() => {
    fetchEvents();
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
</style>
