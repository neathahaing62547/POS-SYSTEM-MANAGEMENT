<script setup>
import { ref, computed, watch, onMounted, provide } from 'vue'
import { useRoute } from 'vue-router'

import Sidebar from '@/Components/layouts/sidebar.vue'
import Topbar from '@/Components/layouts/topbar.vue'

const route = useRoute()

const sidebarOpen = ref(true)

const isDarkMode = ref(
    localStorage.getItem('pos-theme') === 'dark'
)


const showLayout = computed(() => {
    return !['/login', '/otp' ,  '/recoveremail' , '/recover-otp-verify' , '/changepassword'  , '/pos'].includes(route.path)
})
const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value
}
const toggleTheme = () => {
    isDarkMode.value = !isDarkMode.value

    localStorage.setItem(
        'pos-theme',
        isDarkMode.value ? 'dark' : 'light'
    )
}

provide('appTheme', { isDarkMode, toggleTheme })

onMounted(() => {
    const savedTheme = localStorage.getItem('pos-theme')

    isDarkMode.value = savedTheme === 'dark'

})
watch(
    isDarkMode,
    (value) => {
        document.documentElement.classList.toggle(
            'dark-theme',
            value
        )
    },
    { immediate: true }
)
</script>


<template>

    <!-- Auth pages -->
    <RouterView v-if="!showLayout" />

    <!-- Main application -->
    <div
        v-else
        class="app-layout"
        :class="{ 'dark-theme': isDarkMode }"
    >

        <!-- Sidebar -->
        <Transition name="sidebar">
            <Sidebar
                v-if="sidebarOpen"
                :is-dark-mode="isDarkMode"
                @toggle-theme="toggleTheme"
            />
        </Transition>


        <!-- Main -->
        <div
            class="main-content"
            :class="{ 'content-full': !sidebarOpen }"
        >

            <!-- Topbar -->
            <Topbar
                :is-dark-mode="isDarkMode"
                @toggle-sidebar="toggleSidebar"
                @toggle-theme="toggleTheme"
            />


            <!-- Page -->
            <main class="content">
                <RouterView />
            </main>

        </div>

    </div>

</template>


<style scoped>
.app-layout {
    min-height: 100vh;
    background: var(--page-bg);
    color: var(--text-color);
    transition: background-color .2s ease, color .2s ease;
}

.main-content {
    margin-left: 250px;
    transition: margin-left .28s cubic-bezier(.4, 0, .2, 1);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

.content-full {
    margin-left: 0;
}

.content {
    padding: 24px;
    background: var(--page-bg);
    color: var(--text-color);
    flex: 1;
    min-height: calc(100vh - 64px);
}

.sidebar-enter-active,
.sidebar-leave-active {
    transition: transform .28s cubic-bezier(.4, 0, .2, 1);
}

.sidebar-enter-from,
.sidebar-leave-to {
    transform: translateX(-100%);
}

@media (max-width: 1024px) {
    .main-content {
        margin-left: 0;
    }
    .content {
        padding: 20px 16px;
    }
}

@media (max-width: 640px) {
    .content {
        padding: 16px 12px;
    }
}
</style>
