<template>
    <div class="home-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="card shadow-lg border-0 rounded-4">
                        <div class="card-body p-5">
                            <div class="text-center mb-4">
                                <h1 class="h3 fw-bold">行事历</h1>
                                <p class="text-muted">记录每日工作，高效管理时间</p>
                            </div>
                            <slot></slot>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
// 无额外逻辑，仅作布局
</script>

<style scoped>
.home-container {
    min-height: 100vh;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem 1rem;
}
.card {
    backdrop-filter: blur(10px);
    background-color: rgba(255, 255, 255, 0.95);
}
</style>
