<template>
    <div class="calendar-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">我的行事历</h4>
            <button class="btn btn-primary btn-sm" @click="openAddEventModal">
                <i class="bi bi-plus-lg me-1"></i> 添加事件
            </button>
        </div>

        <FullCalendar
            ref="fullCalendarRef"
            :options="calendarOptions"
        />
    </div>
</template>

<script setup>
import {ref, onMounted} from 'vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import {api} from '@/axios';
import multiMonthPlugin from '@fullcalendar/multimonth';  // 新增

const fullCalendarRef = ref(null);

// 事件数据（从后端获取）
const events = ref([]);

// 日历配置
const calendarOptions = {
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'timeGridWeek', // 默认显示周视图
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
    height: 800,
    events: events.value,
    editable: true,       // 可拖动调整
    selectable: true,     // 可选中日期
    selectMirror: true,
    dayMaxEvents: true,   // 限制每日显示事件数量
    // 点击日期弹出添加事件窗口
    dateClick: (info) => {
        // 弹窗添加事件，预填日期
        openAddEventModal(info.dateStr);
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

// 从后端加载事件
const loadEvents = async () => {
    try {
        const response = await api.get('/events'); // 假设后端路由
        events.value = response.data;
        // 更新日历的事件源
        const calendarApi = fullCalendarRef.value?.getApi();
        if (calendarApi) {
            calendarApi.refetchEvents();
        }
    } catch (error) {
        console.error('加载事件失败', error);
    }
};

// 添加事件（简单模拟）
const openAddEventModal = (defaultDate = null) => {
    const title = prompt('请输入事件标题', '新事件');
    if (title) {
        const start = defaultDate ? `${defaultDate}T10:00:00` : new Date().toISOString().slice(0, 16);
        // 调用后端创建事件
        api.post('/events', {title, start, end: start, allDay: true})
            .then(() => loadEvents())
            .catch(err => console.error(err));
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
    loadEvents();
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
    white-space: nowrap;      /* 防止标题换行 */
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
