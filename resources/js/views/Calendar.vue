<template>
    <div class="calendar-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">{{ calendar.name }}</h4>
        </div>

        <FullCalendar
            ref="fullCalendarRef"
            :options="calendarOptions"
        />

        <!-- 新增事件模态框 -->
        <div class="modal fade" id="addEventModal" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">新增事件</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="submitEvent">
                            <div class="mb-3">
                                <label class="form-label">标题 *</label>
                                <input type="text" class="form-control" v-model="eventForm.title" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">描述</label>
                                <textarea class="form-control" rows="2" v-model="eventForm.description"></textarea>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">开始时间</label>
                                    <input type="datetime-local" class="form-control" v-model="eventForm.start_time">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">结束时间</label>
                                    <input type="datetime-local" class="form-control" v-model="eventForm.end_time">
                                </div>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="allDay" v-model="eventForm.all_day">
                                <label class="form-check-label" for="allDay">全天事件</label>
                            </div>

                            <!-- 重复事件 -->
                            <div class="mb-3">
                                <label class="form-label">重复规则</label>
                                <select class="form-select" v-model="eventForm.repeat_type">
                                    <option value="">不重复</option>
                                    <option value="daily">每天</option>
                                    <option value="weekly">每周</option>
                                    <option value="monthly">每月</option>
                                    <option value="yearly">每年</option>
                                </select>
                            </div>
                            <div v-if="eventForm.repeat_type" class="mb-3">
                                <label class="form-label">重复结束日期（可选）</label>
                                <input type="date" class="form-control" v-model="eventForm.repeat_until">
                            </div>

                            <!-- 颜色选择器 -->
                            <div class="mb-3">
                                <label class="form-label">事件颜色</label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="color" class="form-control form-control-color"
                                           v-model="eventForm.color" style="width: 60px;">
                                    <span class="badge"
                                          :style="{ backgroundColor: eventForm.color, width: '40px', height: '30px' }"></span>
                                </div>
                            </div>

                            <div class="alert alert-danger" v-if="eventError">{{ eventError }}</div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="button" class="btn btn-primary" @click="submitEvent" :disabled="eventLoading">
                            <span v-if="eventLoading" class="spinner-border spinner-border-sm me-1"></span>
                            保存
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {ref, onMounted} from 'vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import zhCnLocale from '@fullcalendar/core/locales/zh-cn';
import {api} from '@/axios';
import moment from 'moment';
import {useToast} from 'vue-toastification';
import {Modal} from 'bootstrap';
import {useAuthStore} from '@/store/auth';

const fullCalendarRef = ref(null);
const calendar = ref([]);

const toast = useToast();
const authStore = useAuthStore();

let addModal = null;
const eventLoading = ref(false);
const eventError = ref('');

// 表单数据
const eventForm = ref({
    title: '',
    description: '',
    start_time: '',
    end_time: '',
    all_day: false,
    repeat_type: '',
    repeat_until: '',
    color: '#3788d8', // 默认蓝色
});

// 预填充时间（由点击事件设置），后端存储 utc 时间，前端将后端的 utc 时间显示为本地时间即可
const presetDates = (start, end) => {
    const formatLocal = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${year}-${month}-${day}T${hours}:${minutes}`;
    };
    eventForm.value.start_time = formatLocal(start);
    eventForm.value.end_time = formatLocal(end);
};

// 打开新增模态框
const openAddEventModal = (startDate = null, endDate = null) => {
    // 先销毁旧实例（如果存在）
    if (addModal) {
        addModal.dispose();
        addModal = null;
    }

    // 重置表单
    eventForm.value = {
        title: '',
        description: '',
        start_time: '',
        end_time: '',
        all_day: false,
        repeat_type: '',
        repeat_until: '',
        color: '#3788d8',
    };
    eventError.value = '';
    if (startDate && endDate) {
        presetDates(startDate, endDate);
    } else {
        // 如果没有传入，使用当前时间前后1小时
        const now = new Date();
        const start = new Date(now);
        const end = new Date(now);
        end.setHours(now.getHours() + 1);
        presetDates(start, end);
    }
    // 创建新实例
    const modalElement = document.getElementById('addEventModal');
    addModal = new Modal(modalElement);

    // 监听隐藏事件，确保清理
    modalElement.addEventListener('hidden.bs.modal', () => {
        if (addModal) {
            addModal.dispose();
            addModal = null;
        }
        // 手动清理可能残留的 backdrop
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => backdrop.remove());
        document.body.classList.remove('modal-open');
    }, {once: true});

    addModal.show();
};

// 提交新增事件
const submitEvent = async () => {
    if (!eventForm.value.title) {
        eventError.value = '请填写标题';
        return;
    }

    eventLoading.value = true;
    eventError.value = '';

    // 构建请求数据
    const data = {
        calendar_id: calendar.value.id, // 将由后端根据当前用户默认日历自动填充或前端传入，这里简单使用默认日历
        title: eventForm.value.title,
        description: eventForm.value.description,
        start_time: new Date(eventForm.value.start_time).toISOString(),
        end_time: new Date(eventForm.value.end_time).toISOString(),
        all_day: eventForm.value.all_day,
        color: eventForm.value.color,
    };

    // 处理重复规则
    if (eventForm.value.repeat_type) {
        let rrule = {freq: eventForm.value.repeat_type.toUpperCase()};
        if (eventForm.value.repeat_until) {
            rrule.until = eventForm.value.repeat_until;
        }
        data.rrule = JSON.stringify(rrule);
    }

    try {
        await api.post('/calendar_event', data);
        toast.success('事件添加成功');
        addModal.hide();
        // 刷新日历事件
        if (fullCalendarRef.value) {
            fullCalendarRef.value.getApi().refetchEvents();
        }
    } catch (error) {
        console.error(error);
        eventError.value = error.response?.data?.message || '添加失败，请重试';
    } finally {
        eventLoading.value = false;
    }
};

// 日历配置
const calendarOptions = {
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'timeGridWeek', // 默认显示周视图
    locale: zhCnLocale,              // 设置中文
    // timeZone: 'UTC',
    headerToolbar: {
        left: 'today',
        center: 'prev,title,next',
        right: 'timeGridWeek,dayGridMonth,dayGridYear'
    },
    buttonText: {
        today: '今天',
        month: '月',
        week: '周',
        dayGridYear: '年'
    },
    slotLabelFormat: {
        hour: 'numeric',
        minute: '2-digit',
        hour12: false,                // 启用12小时制
        meridiem: false              // 不显示上午/下午，只显示数字如 12:00
    },
    slotDuration: '00:15:00',      // 每个时间槽长度 30 分钟
    slotLabelInterval: '00:30:00', // 标签显示间隔 30 分钟
    slotMinTime: '08:00:00',       // 时间轴从 8:00 开始
    slotMaxTime: '24:00:00',       // 时间轴结束时间（可根据需要调整）
    height: 800,
    events: async function (info, successCallback, failureCallback) {
        await getDefaultEvents(info, successCallback, failureCallback)
    },
    editable: true,       // 可拖动调整
    selectable: true,     // 可选中日期
    selectMirror: true,
    dayMaxEvents: true,   // 限制每日显示事件数量
    select: (info) => {
        // 用户选择时间段（鼠标框选）时弹出新增窗口
        openAddEventModal(info.start, info.end);
    },
    // 点击日期弹出添加事件窗口
    dateClick: (info) => {
        // 点击日期时弹出新增窗口，默认事件持续1小时
        const start = info.date;
        const end = new Date(start);
        end.setHours(start.getHours() + 0.25);
        openAddEventModal(start, end);
    },
    // 事件被拖动后更新
    eventDrop: async (info) => {
        // 调用后端更新事件日期
        const event = info.event;
        await updateEventDate(event.id, event.startStr, event.endStr);
    },
    // 事件被调整大小时
    eventResize: async (info) => {
        await updateEventDate(info.event.id, info.event.startStr, info.event.endStr);
    },
    // 事件点击（可弹出详情）
    eventClick: (info) => {
        // 显示事件详情弹窗
        alert(`事件：${info.event.title}\n时间：${info.event.startStr}`);
    }
};

const getDefaultEvents = async function (info, successCallback, failureCallback) {
    try {
        // info 包含 start 和 end 对象（moment 或 Date）
        const startLocal = moment(info.start).format('YYYY-MM-DD HH:mm:ss');
        const endLocal = moment(info.end).format('YYYY-MM-DD HH:mm:ss');
        let response = await api.get('/calendar_event', {
            params: {
                start: startLocal,
                end: endLocal
            }
        });
        response = response.data;
        calendar.value = response.calendar;
        let events = [];
        // 后端返回的 events 是数组，FullCalendar 需要格式为 [{ id, title, start, end, ... }]
        if (response.events) {
            events = response.events.map(event => ({
                id: event.id,
                title: event.title,
                start: event.start_time.toLocaleString(),
                end: event.end_time.toLocaleString(),
                allDay: event.all_day,
                color: event.color,
            }));
        }
        successCallback(events);
    } catch (error) {
        failureCallback(error);
    }

};

// 更新事件日期
const updateEventDate = async (eventId, start, end) => {
    try {
        await api.put(`/events/${eventId}`, {start, end});
        // 成功后可提示
    } catch (error) {
        console.error('更新失败', error);
        // 如果失败，可重新加载以恢复原状态
        loadEvents();
    }
};

onMounted(() => {
    // 确保模态框实例清理
    window.addEventListener('beforeunload', () => {
        if (addModal) addModal.dispose();
    });
});

// 强制移除 Bootstrap 遮罩层
const removeBackdrops = (() => {
    document.body.classList.remove('modal-open');
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => backdrop.remove());
});
</script>

<style scoped>
.calendar-container {
    background: white;
    border-radius: 0.75rem;
    padding: 1rem;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
}

:deep(.fc) {
    font-size: 0.9rem;
}

:deep(.fc .fc-toolbar-title) {
    font-size: 1.2rem;
    white-space: nowrap; /* 防止标题换行 */
    display: inline-block;
    margin: 0 0.5rem;
}

/* 工具栏整体布局 */
:deep(.fc-toolbar) {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
}

/* 每个区块都使用 Flex 横向布局 */
:deep(.fc-toolbar .fc-toolbar-chunk) {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: nowrap;
}

/* 左侧区块：左对齐 */
:deep(.fc-toolbar .fc-toolbar-chunk:first-child) {
    justify-content: flex-start;
}

/* 中间区块：居中对齐，内部元素强制不换行 */
:deep(.fc-toolbar .fc-toolbar-chunk:nth-child(2)) {
    justify-content: center;
    flex-wrap: nowrap;
}

/* 右侧区块：右对齐 */
:deep(.fc-toolbar .fc-toolbar-chunk:last-child) {
    justify-content: flex-end;
}

/* 确保 prev/next 按钮与标题在同一行且不换行 */
:deep(.fc-toolbar .fc-toolbar-chunk:nth-child(2) > *) {
    display: inline-block;
    vertical-align: middle;
}

/* 可选：调整按钮与标题的间距 */
:deep(.fc-prev-button),
:deep(.fc-next-button) {
    margin: 0 0.25rem;
}
</style>
