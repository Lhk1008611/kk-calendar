<template>
    <div class="manage-container">
        <div class="row g-4">
            <!-- 左侧导航栏 -->
            <div class="col-md-3 col-lg-2">
                <div class="nav-card card shadow-sm border-0 rounded-4">
                    <div class="list-group list-group-flush">
                        <button
                            type="button"
                            class="list-group-item list-group-item-action"
                            :class="{ active: activeTab === 'calendars' }"
                            @click="activeTab = 'calendars'"
                        >
                            <i class="bi bi-calendar3 me-2"></i> 个人日历管理
                        </button>
                        <button
                            type="button"
                            class="list-group-item list-group-item-action"
                            :class="{ active: activeTab === 'events' }"
                            @click="activeTab = 'events'"
                        >
                            <i class="bi bi-calendar-event me-2"></i> 日历事件管理
                        </button>
                    </div>
                </div>
            </div>

            <!-- 右侧内容区 -->
            <div class="col-md-9 col-lg-10">
                <div class="content-card card shadow-sm border-0 rounded-4" style="height: calc(100vh - 100px);">
                    <div class="card-body d-flex flex-column p-4" style="height: 100%;">
                        <component
                            :is="currentComponent"
                            :key="activeTab"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import CalendarList from '@/components/calendar/CalendarList.vue';
import CalendarEventList from '@/components/calendar/CalendarEventList.vue';

const activeTab = ref('calendars');

const currentComponent = computed(() => {
    return activeTab.value === 'calendars' ? CalendarList : CalendarEventList;
});
</script>

<style scoped>
.manage-container {
    padding: 1rem;
    /* 确保父容器高度 */
    height: 100%;
}
.nav-card {
    background-color: #fff;
}
.list-group-item {
    border: none;
    padding: 0.75rem 1rem;
    font-weight: 500;
    transition: all 0.2s;
}
.list-group-item.active {
    background-color: #e7f1ff;
    color: #0d6efd;
    border-left: 3px solid #0d6efd;
}
</style>1
