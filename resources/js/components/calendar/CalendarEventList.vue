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

        <hr class="my-3 opacity-25" />

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
                <tr v-for="event in paginatedEvents" :key="event.id">
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
                        <i class="bi" :class="event.all_day ? 'bi-check-circle-fill text-success' : 'bi-x-circle text-muted'"></i>
                    </td>
                    <td>{{ statusText(event.status) }}</td>
                    <td>{{ priorityText(event.priority) }}</td>
                    <td>{{ event.location || '-' }}</td>
                </tr>
                <tr v-if="filteredEvents.length === 0">
                    <td colspan="8" class="text-center text-muted">暂无数据</td>
                </tr>
                </tbody>
            </table>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted small">
                    共 {{ filteredEvents.length }} 条
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
    </div>
</template>

<script setup>
import {ref, computed, onMounted, watch} from 'vue';
import { api } from '@/axios';
import { useToast } from 'vue-toastification';

const toast = useToast();

const events = ref([]);
const searchKeyword = ref('');
const selectedIds = ref([]);

const itemsPerPage = 10;
const currentPage = ref(1);



// 获取事件列表
const fetchEvents = async () => {
    try {
        const response = await api.get('/calendar-events');
        events.value = response.data;
    } catch (error) {
        toast.error('获取事件列表失败');
    }
};

// 过滤
const filteredEvents = computed(() => {
    if (!searchKeyword.value) return events.value;
    const kw = searchKeyword.value.toLowerCase();
    return events.value.filter(e => e.title.toLowerCase().includes(kw));
});


// 分页后的数据
const paginatedEvents = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return filteredEvents.value.slice(start, end);
});

// 总页数
const totalPages = computed(() => {
    return Math.ceil(filteredEvents.value.length / itemsPerPage);
});

// 页码变化时重置选中状态（可选）
const changePage = (page) => {
    if (page < 1 || page > totalPages.value) return;
    currentPage.value = page;
    selectedIds.value = []; // 清除选中
};

// 全选逻辑
const isAllSelected = computed(() => {
    return paginatedEvents.value.length > 0 && selectedIds.value.length === paginatedEvents.value.length;
});


const toggleSelectAll = (e) => {
    if (e.target.checked) {
        selectedIds.value = paginatedEvents.value.map(c => c.id);
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
    if (!confirm(`确定要删除选中的 ${selectedIds.value.length} 个事件吗？`)) return;
    try {
        await Promise.all(selectedIds.value.map(id => api.delete(`/calendar-events/${id}`)));
        toast.success('删除成功');
        selectedIds.value = [];
        fetchEvents();
    } catch (error) {
        toast.error('删除失败');
    }
};

// 辅助函数
const formatDateTime = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleString();
};
const statusText = (status) => {
    const map = { 1: '待办', 2: '进行中', 3: '已完成', 4: '已取消' };
    return map[status] || '未知';
};
const priorityText = (priority) => {
    if (!priority) return '-';
    const map = { 1: '低', 2: '中', 3: '高' };
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
