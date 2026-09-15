<template>
    <header class="topbar">
        <div class="topbar-left">
            <button class="menu-btn" @click="$emit('toggle-sidebar')">
                <i class="bi bi-list"></i>
            </button>

            <h5>{{ pageTitle }}</h5>
        </div>

        <div class="topbar-right">
            <div class="search-box">
                <i class="bi bi-search"></i>
                <input type="text" placeholder="Search..." />
            </div>

            <button class="icon-btn theme-btn" :aria-label="isDarkMode ? 'Switch to light mode' : 'Switch to dark mode'"
                :title="isDarkMode ? 'Light mode' : 'Dark mode'" @click="$emit('toggle-theme')">
                <i :class="isDarkMode ? 'bi bi-sun' : 'bi bi-moon-stars'"></i>
            </button>

            <button class="icon-btn">
                <i class="bi bi-bell"></i>
                <span class="notification-badge">3</span>
            </button>

            <div class="user-menu">
                <div class="user-info" @click="showUserMenu = !showUserMenu">
                    <div class="avatar">
                        {{ user?.username?.charAt(0)?.toUpperCase() }}
                    </div>

                    <div class="user-text">
                        <strong>{{ user?.username }}</strong>
                        <span>{{ user?.roles?.[0]?.name }}</span>
                    </div>

                    <i class="bi bi-chevron-down"></i>
                </div>

                <div v-if="showUserMenu" class="user-dropdown">
                    <button @click="logout">
                        <i class="bi bi-box-arrow-right"></i>
                        Logout
                    </button>
                </div>
            </div>
        </div>
    </header>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

defineProps({
    isDarkMode: {
        type: Boolean,
        default: false
    }
})

defineEmits(['toggle-sidebar', 'toggle-theme'])

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const user = computed(() => auth.user)
const showUserMenu = ref(false)

const logout = () => {
    auth.logout()
    showUserMenu.value = false
    router.push('/login')
}

const pageTitle = computed(() => {
    const titles = {
        '/dashboard': 'Dashboard',
        '/pos': 'POS',
        '/analysis': 'Analysis',
        '/products': 'Products',
        '/categories': 'Categories',
        '/brands': 'Brands',
        '/suppliers': 'Suppliers',
        '/purchases': 'Purchases',
        '/inventory': 'Inventory',
        '/orders': 'Orders',
        '/payments': 'Payments',
        '/payment-methods': 'Payment Methods',
        '/sales-returns': 'Sales Returns',
        '/cash-registers': 'Cash Registers',
        '/shifts': 'My Shift',
        '/cash-transactions': 'Cash Transactions',
        '/expenses': 'Expenses',
        '/users': 'Users',
        '/roles': 'Roles',
        '/permissions': 'Permissions',
        '/stores': 'Stores',
        '/reports': 'Reports',
        '/notifications': 'Notifications',
        '/audit-logs': 'Audit Logs',
        '/settings': 'Settings'
    }

    return titles[route.path] || 'POS System'
})
</script>

<style scoped>
.topbar {
    position: sticky;
    top: 0;
    z-index: 1050;
    height: 64px;
    padding: 0 24px;
    background: var(--surface-bg, #ffffff);
    border-bottom: 1px solid var(--border-color, #dbe3ee);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.topbar-left {
    display: flex;
    align-items: center;
    gap: 14px;
}

.topbar-left h5 {
    margin: 0;
    color: var(--text-color, #172033);
    font-size: 18px;
    font-weight: 700;
}

.menu-btn {
    width: 40px;
    height: 40px;
    border: 0;
    border-radius: 8px;
    background: var(--control-bg, #f8fafc);
    color: var(--text-color, #172033);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    cursor: pointer;
    transition: 0.2s;
}

.menu-btn:hover {
    background: var(--surface-muted, #f8fbff);
}

.topbar-right {
    display: flex;
    align-items: center;
    gap: 12px;
}

.search-box {
    width: 240px;
    height: 40px;
    padding: 0 12px;
    background: var(--control-bg, #f8fafc);
    border: 1px solid var(--border-color, #dbe3ee);
    border-radius: 8px;
    display: flex;
    align-items: center;
}

.search-box i {
    color: #6b7280;
    font-size: 14px;
}

.search-box input {
    width: 100%;
    margin-left: 8px;
    border: 0;
    outline: 0;
    background: transparent;
    color: var(--text-color, #172033);
    font-size: 13px;
}

.search-box input::placeholder {
    color: #9ca3af;
}

.icon-btn {
    position: relative;
    width: 40px;
    height: 40px;
    border: 1px solid var(--border-color, #dbe3ee);
    border-radius: 8px;
    background: var(--surface-bg, #ffffff);
    color: var(--text-color, #172033);
    font-size: 17px;
    cursor: pointer;
}

.icon-btn:hover {
    background: var(--surface-muted, #eef2f7);
}

.notification-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    min-width: 17px;
    height: 17px;
    padding: 0 4px;
    border: 2px solid #fff;
    border-radius: 20px;
    background: #ef4444;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 9px;
    font-weight: 700;
}

.user-menu {
    position: relative;
}

.user-info {
    height: 42px;
    padding: 0 8px;
    display: flex;
    align-items: center;
    gap: 9px;
    cursor: pointer;
    border-radius: 8px;
}

.user-info:hover {
    background: var(--surface-muted, #eef2f700);
}

.avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #111827;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 700;
}

.user-text {
    display: flex;
    flex-direction: column;
    line-height: 1.2;
}

.user-text strong {
    color: var(--text-color, #172033);
    font-size: 13px;
    font-weight: 700;
}

.user-text span {
    margin-top: 3px;
    color: var(--muted-text, #667085);
    font-size: 11px;
}

.user-info>i {
    color: var(--muted-text, #667085);
    font-size: 10px;
}

.user-dropdown {
    position: absolute;
    top: 48px;
    right: 0;
    width: 150px;
    padding: 6px;
    background: #faf3f3;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
    z-index: 1000;
}

.user-dropdown button {
    width: 100%;
    padding: 9px 10px;
    border: 0;
    border-radius: 6px;
    background: transparent;
    color: #bd0f0f;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    cursor: pointer;
    text-align: left;
}

.user-dropdown button:hover {
    background: #f3f4f600;
    color: #dc2626;
}

@media (max-width: 900px) {
    .search-box {
        width: 180px;
    }
}

@media (max-width: 700px) {
    .topbar {
        padding: 0 15px;
    }

    .search-box {
        display: none;
    }

    .user-text,
    .user-info>i {
        display: none;
    }
}
</style>