<script setup>
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'

import Sidebar from '@/Components/sidebar.vue'
import Topbar from '@/Components/topbar.vue'

const route = useRoute()
const sidebarOpen = ref(true)
const isDarkMode = ref(localStorage.getItem('pos-theme') === 'dark')
const showLayout = computed(() => {

    return !['/login', '/otp'].includes(route.path)
})
const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value
}
const toggleTheme = () => {
    isDarkMode.value = !isDarkMode.value
    localStorage.setItem('pos-theme', isDarkMode.value ? 'dark' : 'light')
}
</script>

<template>
    <div v-if="showLayout" class="app-layout" :class="{ 'dark-theme': isDarkMode }">
        <Transition name="sidebar">
            <Sidebar v-if="sidebarOpen" />
        </Transition>
        <div class="main-content" :class="{ 'content-full': !sidebarOpen }">
            <Topbar @toggle-sidebar="toggleSidebar" @toggle-theme="toggleTheme" :is-dark-mode="isDarkMode" />
            <main class="content">
                <RouterView />
            </main>
        </div>
    </div>
    <RouterView v-else />
</template>

<style scoped>
.app-layout {
    --page-bg: #f4f7fb;
    --surface-bg: #ffffff;
    --surface-muted: #eef2f7;
    --border-color: #dbe3ee;
    --text-color: #172033;
    --muted-text: #667085;
    --control-bg: #f8fafc;
    --sidebar-bg: #ffffff;
    --sidebar-border: #dbe3ee;
    --sidebar-text: #475467;
    --sidebar-hover: #eef2f7;
    min-height: 100vh;
    background: var(--page-bg);
    color: var(--text-color);
    transition: background-color 0.2s ease, color 0.2s ease;
}

.dark-theme {
    --page-bg: #111827;
    --surface-bg: #1f2937;
    --surface-muted: #374151;
    --border-color: #4b5563;
    --text-color: #f9fafb;
    --muted-text: #d1d5db;
    --control-bg: #273548;
    --sidebar-bg: #111827;
    --sidebar-border: #1f2937;
    --sidebar-text: #d1d5db;
    --sidebar-hover: #1f2937;
    background: var(--page-bg);
    color: var(--text-color);
}

.app-layout :deep(.card),
.app-layout :deep(.modal-content),
.app-layout :deep(.dropdown-menu),
.app-layout :deep(.table),
.app-layout :deep(.form-control),
.app-layout :deep(.form-select),
.app-layout :deep(.input-group-text),
.app-layout :deep(.search-box) {
    background-color: var(--surface-bg);
    color: var(--text-color);
    border-color: var(--border-color);
}

.app-layout :deep(.table > :not(caption) > * > *),
.app-layout :deep(.table-light th),
.app-layout :deep(.table-light td) {
    background-color: var(--surface-bg);
    color: var(--text-color);
    border-color: var(--border-color);
}

.app-layout :deep(.table-hover > tbody > tr:hover > *) {
    background-color: var(--surface-muted);
    color: var(--text-color);
}

.app-layout :deep(.summary-card) {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.app-layout :deep(.summary-card:hover) {
    transform: translateY(-4px);
    box-shadow: 0 10px 22px rgba(15, 23, 42, 0.14) !important;
}

.app-layout :deep(.form-control::placeholder),
.app-layout :deep(.form-select option),
.app-layout :deep(.text-muted) {
    color: var(--muted-text) !important;
}

.dark-theme :deep(.topbar),
.dark-theme :deep(.card),
.dark-theme :deep(.modal-content),
.dark-theme :deep(.dropdown-menu),
.dark-theme :deep(.table),
.dark-theme :deep(.form-control),
.dark-theme :deep(.form-select),
.dark-theme :deep(.input-group-text),
.dark-theme :deep(.search-box) {
    background-color: var(--surface-bg);
    color: var(--text-color);
    border-color: var(--border-color);
}

.dark-theme :deep(.topbar-left h5),
.dark-theme :deep(.search-box input),
.dark-theme :deep(.user-text strong),
.dark-theme :deep(.card h1),
.dark-theme :deep(.card h2),
.dark-theme :deep(.card h3),
.dark-theme :deep(.card h4),
.dark-theme :deep(.card h5),
.dark-theme :deep(.card h6),
.dark-theme :deep(.modal-title),
.dark-theme :deep(.table th),
.dark-theme :deep(.table td),
.dark-theme :deep(label) {
    color: var(--text-color);
}

.dark-theme :deep(.text-muted),
.dark-theme :deep(.form-text),
.dark-theme :deep(.user-text span),
.dark-theme :deep(.user-info > i) {
    color: var(--muted-text) !important;
}

.dark-theme :deep(.table-striped > tbody > tr:nth-of-type(odd) > *) {
    color: var(--text-color);
    background-color: var(--surface-muted);
}

.dark-theme :deep(.menu-btn),
.dark-theme :deep(.icon-btn) {
    background-color: var(--control-bg);
    color: var(--text-color);
    border-color: var(--border-color);
}

.dark-theme :deep(.menu-btn:hover),
.dark-theme :deep(.icon-btn:hover),
.dark-theme :deep(.user-info:hover) {
    background-color: var(--surface-muted);
}

.main-content {
    margin-left: 250px;
    transition: margin-left 0.3s ease;
}

.content-full {
    margin-left: 0;
}

.content {
    padding: 20px;
}

.sidebar-enter-active,
.sidebar-leave-active {
    transition: transform 0.3s ease;
}

.sidebar-enter-from,
.sidebar-leave-to {
    transform: translateX(-100%);
}

@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
    }

    .content {
        padding: 16px 12px;
    }
}
</style>