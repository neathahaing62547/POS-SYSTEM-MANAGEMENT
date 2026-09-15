<script setup>
import Sidebar from '@/Components/sidebar.vue';
import Topbar from '@/Components/topbar.vue';
  
</script>
<template>
    <div class="dashboard-layout">
            <main class="content">
                <RouterView />
            </main>
        </div>
</template>


<style scoped>
.dashboard-layout {
    min-height: 100vh;
    background: #f8f9fa;
}

.main-content {
    margin-left: 250px; 
    min-height: 100vh;
}
.content {
    padding: 25px;
}
</style>