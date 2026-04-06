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
                                <label class="form-label">重复结束日期</label>
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
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                                @click="removeBackdrops">取消
                        </button>
                        <button type="button" class="btn btn-primary" @click="submitEvent" :disabled="eventLoading">
                            <span v-if="eventLoading" class="spinner-border spinner-border-sm me-1"></span>
                            保存
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 编辑事件模态框 -->
        <div class="modal fade" id="editEventModal" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">修改事件</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="updateEvent">
                            <!-- 表单字段与新增一致，但绑定到 editForm -->
                            <div class="mb-3">
                                <label class="form-label">标题 *</label>
                                <input type="text" class="form-control" v-model="editForm.title" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">描述</label>
                                <textarea class="form-control" rows="2" v-model="editForm.description"></textarea>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">开始时间</label>
                                    <input type="datetime-local" class="form-control" v-model="editForm.start_time">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">结束时间</label>
                                    <input type="datetime-local" class="form-control" v-model="editForm.end_time">
                                </div>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="editAllDay"
                                       v-model="editForm.all_day">
                                <label class="form-check-label" for="editAllDay">全天事件</label>
                            </div>
                            <!-- 重复事件、颜色选择器等，与新增一致 -->
                            <div class="mb-3">
                                <label class="form-label">重复规则</label>
                                <select class="form-select" v-model="editForm.repeat_type">
                                    <option value="">不重复</option>
                                    <option value="daily">每天</option>
                                    <option value="weekly">每周</option>
                                    <option value="monthly">每月</option>
                                    <option value="yearly">每年</option>
                                </select>
                            </div>
                            <div v-if="editForm.repeat_type" class="mb-3">
                                <label class="form-label">重复结束日期（可选）</label>
                                <input type="date" class="form-control" v-model="editForm.repeat_until">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">事件颜色</label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="color" class="form-control form-control-color" v-model="editForm.color"
                                           style="width: 60px;">
                                    <span class="badge"
                                          :style="{ backgroundColor: editForm.color, width: '40px', height: '30px' }"></span>
                                </div>
                            </div>
                            <div class="alert alert-danger" v-if="editError">{{ editError }}</div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                                @click="removeBackdrops()">取消
                        </button>
                        <button type="button" class="btn btn-primary" @click="updateEvent" :disabled="editLoading">
                            <span v-if="editLoading" class="spinner-border spinner-border-sm me-1"></span>
                            保存修改
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 普通删除确认模态框 -->
        <div class="modal fade" id="deleteConfirmModal" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-danger">确认删除</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="button" class="btn btn-danger">确认删除</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 重复事件删除选择模态框 -->
        <div class="modal fade" id="deleteChoiceModal" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-danger">删除重复事件</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>请选择删除方式：</p>
                    </div>
                    <div class="modal-footer d-flex justify-content-between">
                        <button type="button" class="btn btn-outline-danger" id="delete-occurrence">仅删除当前实例
                        </button>
                        <button type="button" class="btn btn-danger" id="delete-series">删除整个系列</button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<script setup>
import {onMounted, onUnmounted, ref} from 'vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import zhCnLocale from '@fullcalendar/core/locales/zh-cn';
import rrulePlugin from '@fullcalendar/rrule'
import {api} from '@/axios';
import moment from 'moment';
import {useToast} from 'vue-toastification';
import {Modal} from 'bootstrap';

const fullCalendarRef = ref(null);
const calendar = ref([]);
const toast = useToast();

// 日历配置
const calendarOptions = {
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin, rrulePlugin],
    initialView: 'timeGridWeek', // 默认显示周视图
    locale: zhCnLocale,              // 设置中文
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
    allDaySlot: true,               // 显示全天事件
    editable: true,       // 可拖动调整
    selectable: true,     // 可选中日期
    selectMirror: true,
    dayMaxEvents: true,   // 限制每日显示事件数量
    events: async function (info, successCallback, failureCallback) {
        await getDefaultEvents(info, successCallback, failureCallback)
    },
    // 用户选择时间段（鼠标框选）时弹出新增窗口
    select: (info) => {
        openAddEventModal(info);
    },
    // 事件被拖动后更新
    eventDrop: async (info) => {
        // 调用后端更新事件日期
        await updateEventDate(info);
    },
    // 事件被调整大小时
    eventResize: async (info) => {
        await updateEventDate(info);
    },
    // 事件点击（可弹出详情）
    eventClick: (info) => {
        // 显示事件详情弹窗
        openEditModal(info.event);
    },
    eventDidMount: (info) => {
        const {event, el} = info;
        const isOccurrence = allEvents.find(value => String(value.id) === String(event.id)).rrule || false;
        const originalEventId = event.id;
        const startDate = event.start ? event.start.toISOString() : null;

        // 找到事件容器
        const eventContainer = el.closest('.fc-event');
        if (!eventContainer) return;

        // 创建一个包装容器，用于放置删除按钮（不修改原有布局）
        const wrapper = document.createElement('div');
        wrapper.style.position = 'relative';
        wrapper.style.width = '100%';
        wrapper.style.height = '100%';
        // 将原有内容包裹进 wrapper
        eventContainer.childNodes.forEach(node => wrapper.appendChild(node.cloneNode(true)));
        // 清空原容器，添加 wrapper
        eventContainer.innerHTML = '';
        eventContainer.appendChild(wrapper);

        // 创建删除按钮
        const deleteBtn = document.createElement('div');
        deleteBtn.className = 'fc-event-delete-wrapper';
        deleteBtn.innerHTML = '<i class="bi bi-trash3"></i>';
        deleteBtn.title = '删除事件';
        deleteBtn.style.position = 'absolute';
        deleteBtn.style.top = '-12px';
        deleteBtn.style.right = '-8px';
        deleteBtn.style.zIndex = '10';
        deleteBtn.style.backgroundColor = '#fff';
        deleteBtn.style.borderRadius = '50%';
        deleteBtn.style.width = '24px';
        deleteBtn.style.height = '24px';
        deleteBtn.style.display = 'flex';
        deleteBtn.style.alignItems = 'center';
        deleteBtn.style.justifyContent = 'center';
        deleteBtn.style.cursor = 'pointer';
        deleteBtn.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
        deleteBtn.style.border = '1px solid #ddd';
        deleteBtn.style.color = '#dc3545';
        deleteBtn.style.fontSize = '12px';
        deleteBtn.style.opacity = '0';
        deleteBtn.style.transition = 'opacity 0.2s';

        // 鼠标悬浮显示按钮
        eventContainer.addEventListener('mouseenter', () => {
            deleteBtn.style.opacity = '1';
        });
        eventContainer.addEventListener('mouseleave', () => {
            deleteBtn.style.opacity = '0';
        });

        // 点击删除按钮，阻止冒泡
        deleteBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            e.preventDefault();
            if (isOccurrence) {
                showDeleteChoiceModal(originalEventId, startDate);
            } else {
                confirmDeleteModal.show({
                    message: '确定删除这个事件吗？',
                    onConfirm: () => deleteEvent(event.id)
                });
            }
        });
        eventContainer.appendChild(deleteBtn);
    }
};

let allEvents = [];

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
        allEvents = response.events;
        calendar.value = response.calendar;
        let events = [];
        // 后端返回的 events 是数组，FullCalendar 需要格式为 [{ id, title, start, end, ... }]
        if (response.events) {
            events = response.events.map(event => {
                const data = {
                    id: event.id,
                    title: event.title,
                    allDay: event.all_day,
                    color: event.color,
                    extendedProps: {
                        description: event.description,
                    },
                };
                if (event.rrule) {
                    data.rrule = {
                        dtstart: event.rrule.dtstart,
                        freq: event.rrule.freq,
                        until: event.rrule.until,
                    }
                    if (!event.allDay) {
                        data.duration = event.rrule.duration;
                    }
                    data.exdate = event.rrule.exdate ?? []
                }
                data.start = event.start_time;
                data.end = event.end_time;
                return data;
            })
        }
        console.log(events)
        successCallback(events);
    } catch (error) {
        failureCallback(error);
    }

};

const addModal = ref(null);
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
    repeat_dtstart: '',
    color: '#3788d8', // 默认蓝色
});

// 打开新增模态框
const openAddEventModal = (info) => {
    const startDate = formatLocal(info.start);
    const endDate = formatLocal(info.end);
    // 重置表单
    eventForm.value = {
        title: '',
        description: '',
        start_time: startDate,
        end_time: endDate,
        all_day: info.allDay,
        repeat_type: '',
        repeat_dtstart: startDate.substring(0, 10),
        repeat_until: '',
        color: '#3788d8',
    };
    eventError.value = '';

    // 创建新实例
    addModal.value = new Modal(document.getElementById('addEventModal'));
    addModal.value.show();
};

// 提交新增事件
const submitEvent = async () => {
    if (!eventForm.value.title) {
        eventError.value = '请填写标题';
        return;
    }
    eventLoading.value = true;
    eventError.value = '';
    const startTime = new Date(eventForm.value.start_time);
    const endTime = new Date(eventForm.value.end_time);
    // 构建请求数据
    const data = {
        calendar_id: calendar.value.id, // 将由后端根据当前用户默认日历自动填充或前端传入，这里简单使用默认日历
        title: eventForm.value.title,
        description: eventForm.value.description,
        start_time: startTime.toISOString(),
        end_time: endTime.toISOString(),
        all_day: eventForm.value.all_day,
        color: eventForm.value.color,
    };

    // 处理重复规则
    if (eventForm.value.repeat_type) {
        let rrule = {
            freq: eventForm.value.repeat_type,
            dtstart: startTime.toISOString(),
        };
        if (eventForm.value.repeat_until) {
            rrule.until = eventForm.value.repeat_until;
        }
        data.rrule = rrule;
    }

    try {
        await api.post('/calendar_event', data);
        toast.success('事件添加成功');
        addModal.value.hide();
        removeBackdrops();
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


// 编辑相关状态
const editForm = ref({
    id: null,
    title: '',
    description: '',
    start_time: '',
    end_time: '',
    all_day: false,
    repeat_type: '',
    repeat_until: '',
    color: '#3788d8',
});
const editLoading = ref(false);
const editError = ref('');
const editModal = ref(null);

// 打开编辑模态框
const openEditModal = (event) => {
    // 从 FullCalendar 事件对象中提取数据
    const startTime = formatLocal(event.start);
    let endTime = '';
    if (event.allDay && !event.end) {
        endTime = new Date(event.start)
        endTime.setDate(endTime.getDate() + 1);
        endTime = formatLocal(endTime);
    }
    if (event.end) {
        endTime = formatLocal(event.end);
    }
    const rrule = allEvents.find(value => String(value.id) === String(event.id)).rrule
    editForm.value = {
        id: event.id,
        title: event.title,
        description: event.extendedProps.description || '',
        start_time: startTime,
        end_time: endTime,
        all_day: event.allDay || false,
        repeat_type: rrule ? rrule.freq : '',
        repeat_until: rrule ? rrule.until : '',
        color: event.backgroundColor || '#3788d8',
    };
    editError.value = '';
    editModal.value = new Modal(document.getElementById('editEventModal'));
    editModal.value.show();
};

// 提交更新
const updateEvent = async () => {
    if (!editForm.value.title) {
        editError.value = '请填写标题';
        return;
    }
    editLoading.value = true;
    editError.value = '';

    const data = {
        title: editForm.value.title,
        description: editForm.value.description,
        all_day: editForm.value.all_day,
        color: editForm.value.color,
        start_time: new Date(editForm.value.start_time).toISOString(),
        end_time: new Date(editForm.value.end_time).toISOString(),
    };

    let rrule = allEvents.find(value => String(value.id) === String(editForm.value.id)).rrule;

    if (!rrule) {
        rrule = {
            freq: '',
            dtstart: '',
            until: ''
        }
    }

    if (editForm.value.repeat_type) {
        rrule.freq = editForm.value.repeat_type
        rrule.dtstart = new Date(editForm.value.start_time).toISOString();
        if (editForm.value.repeat_until)
            rrule.until = editForm.value.repeat_until;
        data.rrule = rrule;
    }
    console.log(data);

    try {
        await api.patch(`/calendar_event/${editForm.value.id}`, data);
        toast.success('事件更新成功');
        editModal.value.hide();
        // 刷新日历
        if (fullCalendarRef.value) {
            fullCalendarRef.value.getApi().refetchEvents();
        }
    } catch (error) {
        editError.value = error.response?.data?.message || '更新失败，请重试';
    } finally {
        editLoading.value = false;
    }
};

// 更新事件日期
const updateEventDate = async (info) => {
    try {
        const event = info.event;
        const data = {
            all_day: event.allDay,
        };
        const rrule = allEvents.find(value => String(value.id) === String(event.id)).rrule
        //默认结束时间
        let endTime = new Date(event.start)
        if (event.allDay) {
            endTime.setDate(endTime.getDate() + 1);
            data.start_time = formatLocal(event.start);
            data.end_time = formatLocal(endTime);
        } else {
            data.start_time = event.start.toISOString();
            if (event.end) {
                data.end_time = event.end.toISOString();
            } else {
                endTime.setHours(endTime.getUTCHours() + 1);
                data.end_time = formatLocal(endTime);
            }
        }
        if (rrule) {
            data.rrule = rrule;
            data.rrule.dtstart = data.start_time;
        }
        await api.patch(`/calendar_event/${event.id}`, data);
        // 刷新日历
        if (fullCalendarRef.value) {
            fullCalendarRef.value.getApi().refetchEvents();
        }
    } catch (error) {
        console.error('更新失败', error);
        // 如果失败，可重新加载以恢复原状态
    }
};

// 删除确认模态框（用于删除事件实例）
const confirmDeleteModal = {
    show: (options) => {
        const modalEl = document.getElementById('deleteConfirmModal');
        if (!modalEl) return;
        const messageEl = modalEl.querySelector('.modal-body p');
        if (messageEl) messageEl.innerText = options.message;
        const confirmBtn = modalEl.querySelector('.btn-danger');
        const oldHandler = confirmBtn.onclick;
        confirmBtn.onclick = () => {
            options.onConfirm();
            const modal = Modal.getInstance(modalEl);
            modal.hide();
        };
        const modal = new Modal(modalEl);
        modal.show();
    }
};

// 删除普通事件
const deleteEvent = async (eventId) => {
    try {
        await api.delete(`/calendar_events`, {data: {ids: [eventId]}});
        toast.success('事件已删除');
        if (fullCalendarRef.value) {
            fullCalendarRef.value.getApi().refetchEvents();
        }
    } catch (error) {
        toast.error('删除失败');
    }
};

// 删除整个重复事件系列
const deleteEventSeries = async (originalEventId) => {
    try {
        await api.delete(`/calendar_events`, {data: {ids: [originalEventId]}});
        toast.success('重复事件系列已删除');
        if (fullCalendarRef.value) {
            fullCalendarRef.value.getApi().refetchEvents();
        }
    } catch (error) {
        toast.error('删除失败');
    }
};

// 删除当前重复事件实例（添加 EXDATE）
const deleteSingleOccurrence = async (originalEventId, startDate) => {
    console.log(startDate)
    try {
        await api.patch(`/calendar_event/${originalEventId}/exclude`, {date: startDate});
        toast.success('当前实例已删除');
        if (fullCalendarRef.value) {
            const calendarApi = fullCalendarRef.value.getApi();
            calendarApi.refetchEvents();
            calendarApi.render();
        }
    } catch (error) {
        toast.error('删除失败');
    }
};

// 显示选择删除类型的模态框（两个按钮）
const showDeleteChoiceModal = (originalEventId, startDate) => {
    const modalEl = document.getElementById('deleteChoiceModal');
    if (!modalEl) return;
    const deleteSeriesBtn = modalEl.querySelector('#delete-series');
    const deleteOccurrenceBtn = modalEl.querySelector('#delete-occurrence');
    const modal = new Modal(modalEl);
    deleteSeriesBtn.onclick = () => {
        modal.hide();
        deleteEventSeries(originalEventId);
    };
    deleteOccurrenceBtn.onclick = () => {
        modal.hide();
        deleteSingleOccurrence(originalEventId, startDate);
    };
    modal.show();
};

// 预填充时间（由点击事件设置），后端存储 utc 时间，前端将后端的 utc 时间显示为本地时间即可
const formatLocal = (date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day}T${hours}:${minutes}`;
};

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

.fc-event-custom {
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
    padding: 2px 20px 2px 4px;
}

.fc-event-delete-btn {
    background: rgba(255, 255, 255, 0.8);
    border-radius: 50%;
    padding: 2px;
}

.fc-event-delete-btn:hover {
    background: #fff;
    color: #c82333;
}
</style>
