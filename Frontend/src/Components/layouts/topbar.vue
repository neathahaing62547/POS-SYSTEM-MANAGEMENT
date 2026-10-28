<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
defineProps({
    isDarkMode: {
        type: Boolean,
        default: false
    }
})
defineEmits([
    'toggle-sidebar',
    'toggle-theme'
])
const auth = useAuthStore()

const route = useRoute()

const router = useRouter()

const user = computed(() => auth.user)

const showUserMenu = ref(false)

const profileOpen = ref(false)

const profileLoading = ref(false)

const profileError = ref('')

const profile = ref(null)

const openProfile = async () => {
    showUserMenu.value = false
    profileOpen.value = true
    profileLoading.value = true
    profileError.value = ''
    profile.value = null
    try {
        profile.value = await auth.refreshUser()
        if (!profile.value) {
            profileError.value = 'Your session has expired. Sign in again to view your profile.'
        }
    } catch (error) {
        profileError.value = error.response?.data?.message || auth.error || 'Could not load your profile.'
    } finally {
        profileLoading.value = false
    }
}
const closeProfile = () => {
    profileOpen.value = false
    avatarFile.value = null
    avatarPreview.value = ''
    avatarError.value = ''
}
const avatarFile = ref(null)

const avatarPreview = ref('')

const avatarUploading = ref(false)

const avatarError = ref('')

const onAvatarSelected = (event) => {
    const file = event.target.files[0]
    if (!file) return
    avatarFile.value = file
    avatarError.value = ''
    avatarPreview.value = URL.createObjectURL(file)
}
const uploadAvatar = async () => {
    if (!avatarFile.value) return
    avatarUploading.value = true
    avatarError.value = ''
    try {
        const form = new FormData()
        form.append('avatar', avatarFile.value)
        const updated = await auth.updateAvatar(form)
        profile.value = updated || profile.value
        avatarFile.value = null
        avatarPreview.value = ''
    } catch (error) {
        avatarError.value = error.response?.data?.message || 'Could not upload photo. Try again.'
    } finally {
        avatarUploading.value = false
    }
}
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
const pingMs = ref(null)

const checking = ref(false)

let timer = null
function pingClass() {
    if (checking.value) return 'ping-checking'
    if (pingMs.value === null) return 'ping-error'
    if (pingMs.value <= 50) return 'ping-excellent'
    if (pingMs.value <= 150) return 'ping-good'
    return 'ping-slow'
}
async function checkPing() {
    if (checking.value) return
    checking.value = true
    const start = performance.now()
    try {
        await fetch('http://localhost:5173/', {
            mode: 'no-cors',
            cache: 'no-store',
            signal: AbortSignal.timeout(5000)
        })
        pingMs.value = Math.round(performance.now() - start)
    } catch {
        pingMs.value = null
    } finally {
        checking.value = false
    }
}
onMounted(() => {
    checkPing()
    timer = setInterval(checkPing, 10000)
})
onUnmounted(() => {
    clearInterval(timer)
})
</script>

<template>
    <header class="topbar">
        <div class="topbar-left">
            <button class="menu-btn" @click="$emit('toggle-sidebar')" title="Toggle sidebar">
                <i class="bi bi-list"></i>
            </button>
            <h5>
                {{ pageTitle }}
            </h5>
        </div>
        <div class="topbar-right">
            <button type="button" class="network-ping-btn" :disabled="checking" @click="checkPing"
                :title="checking ? 'Checking network...' : 'Check network latency'">
                <span class="network-ping-icon">
                    <i class="bi bi-activity"></i>
                </span>
                <span class="network-ping-value">
                    {{ checking ? '...' : pingMs !== null ? `${pingMs} ms` : '-- ms' }}
                </span>
                <span class="network-ping-dot" :class="pingClass()"></span>
            </button>
            <button class="icon-btn theme-btn" :aria-label="isDarkMode
                ? 'Switch to light mode'
                : 'Switch to dark mode'
                " :title="isDarkMode
                    ? 'Light mode'
                    : 'Dark mode'
                    " @click="$emit('toggle-theme')">
                <i :class="isDarkMode
                    ? 'bi bi-sun'
                    : 'bi bi-moon-stars'
                    "></i>
            </button>
            <button class="icon-btn notification-btn" title="Notifications">
                <i class="bi bi-bell"></i>
                <span class="notification-badge">
                    3
                </span>
            </button>
            <div class="user-menu">
                <div class="user-info">
                    <button type="button" class="avatar" title="View profile" @click.stop="openProfile">
                        <img v-if="user?.avatar" :src="user.avatar" :alt="user?.username" class="avatar-img" />
                        <span v-else>
                            {{ user?.username?.charAt(0)?.toUpperCase() }}
                        </span>
                    </button>
                    <div class="user-text" @click="showUserMenu = !showUserMenu">
                        <strong>
                            {{ user?.username }}
                        </strong>
                        <span>
                            {{ user?.roles?.[0]?.name }}
                        </span>
                    </div>
                    <i class="bi bi-chevron-down" @click="showUserMenu = !showUserMenu"></i>
                </div>
                <div v-if="showUserMenu" class="user-dropdown">
                    <button type="button" @click="openProfile">
                        <i class="bi bi-person-circle"></i>
                        <span>Profile</span>
                    </button>
                    <button type="button" @click="logout">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>
                            Logout
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </header>
    <div v-if="profileOpen" class="profile-backdrop" @click.self="closeProfile">
        <section class="profile-modal" role="dialog" aria-modal="true" aria-labelledby="profile-title">
            <header class="profile-modal-header">
                <div>
                    <p class="profile-eyebrow">Account</p>
                    <h2 id="profile-title">My profile</h2>
                </div>
                <button type="button" class="profile-close" aria-label="Close profile" @click="closeProfile">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </header>
            <div v-if="profileLoading" class="profile-state" role="status">
                <span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
                Loading profile…
            </div>
            <div v-else-if="profileError" class="alert alert-danger m-3" role="alert">
                {{ profileError }}
                <button type="button" class="btn btn-link p-0 ms-2" @click="openProfile">Try again</button>
            </div>
            <template v-else-if="profile">
                <div class="photo-card">
                    <div class="photo-preview">
                        <img v-if="avatarPreview || profile.avatar" :src="avatarPreview || profile.avatar"
                            alt="Profile photo" />
                        <span v-else>{{ profile.username?.charAt(0)?.toUpperCase() }}</span>
                    </div>
                    <div class="photo-actions">
                        <label class="btn-choose">
                            <i class="bi bi-camera"></i>
                            Choose photo
                            <input type="file" accept="image/*" hidden @change="onAvatarSelected" />
                        </label>
                        <button v-if="avatarFile" type="button" class="btn-upload" :disabled="avatarUploading"
                            @click="uploadAvatar">
                            {{ avatarUploading ? 'Uploading...' : 'Save photo' }}
                        </button>
                    </div>
                    <p v-if="avatarError" class="photo-error">{{ avatarError }}</p>
                </div>
                <dl class="profile-details">
                    <div>
                        <dt>Full name</dt>
                        <dd>{{ profile.full_name || '—' }}</dd>
                    </div>
                    <div>
                        <dt>Username</dt>
                        <dd>{{ profile.username || '—' }}</dd>
                    </div>
                    <div>
                        <dt>Email</dt>
                        <dd>{{ profile.email || '—' }}</dd>
                    </div>
                    <div>
                        <dt>Phone</dt>
                        <dd>{{ profile.phone || '—' }}</dd>
                    </div>
                    <div>
                        <dt>Role</dt>
                        <dd class="text-capitalize">{{profile.roles?.map(role => role.name).join(', ') || '—'}}</dd>
                    </div>
                    <div>
                        <dt>Account status</dt>
                        <dd class="text-capitalize">{{ profile.status || '—' }}</dd>
                    </div>
                    <div>
                        <dt>Last login</dt>
                        <dd>{{ profile.last_login_at ? new Date(profile.last_login_at).toLocaleString() : '—' }}</dd>
                    </div>
                </dl>
            </template>

        </section>
    </div>
</template>

<style scoped>

.topbar {
    position: sticky;
    top: 0;
    z-index: 1050;
    height: 64px;
    padding: 0 24px;
    background: var(--surface-bg, #ffffff);
    border-bottom:
        1px solid var(--border-color, #dbe3ee);
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition:
        background-color .2s ease,
        border-color .2s ease,
        color .2s ease;
}

.topbar-left {
    display: flex;
    align-items: center;
    gap: 14px;
}

.topbar-left h5 {
    margin: 0;
    color:
        var(--text-color, #172033);
    font-size: 18px;
    font-weight: 700;
    transition: color .2s ease;
}

.menu-btn {
    width: 40px;
    height: 40px;
    border: 1px solid transparent;
    border-radius: 8px;
    background:
        var(--control-bg, #f8fafc);
    color:
        var(--text-color, #172033);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    cursor: pointer;
    transition:
        background .2s ease,
        color .2s ease;
}

.menu-btn:hover {
    background:
        var(--surface-muted, #eef2f7);
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
    background:
        var(--control-bg, #f8fafc);
    border:
        1px solid var(--border-color, #dbe3ee);
    border-radius: 8px;
    display: flex;
    align-items: center;
    transition:
        background .2s ease,
        border-color .2s ease;
}

.network-ping-btn {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-width: 94px;
    height: 38px;
    padding: 0 12px;
    border: 1px solid var(--border-color, #e5e7eb);
    border-radius: 10px;
    background: var(--surface-bg, #ffffff);
    color: var(--text-color, #374151);
    font-family: inherit;
    font-size: 12px;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    cursor: pointer;
    transition:
        background-color 0.2s ease,
        border-color 0.2s ease,
        box-shadow 0.2s ease;
}

.network-ping-btn:hover:not(:disabled) {
    background: var(--hover-bg, #f9fafb);
    border-color: #cbd5e1;
}

.network-ping-btn:focus-visible {
    outline: 2px solid #64748b;
    outline-offset: 2px;
}

.network-ping-btn:disabled {
    cursor: wait;
}

.network-ping-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    color: var(--text-color, #374151);
}

.network-ping-value {
    white-space: nowrap;
}

.network-ping-dot {
    position: absolute;
    top: 5px;
    right: 5px;
    width: 7px;
    height: 7px;
    border: 1.5px solid var(--surface-bg, #ffffff);
    border-radius: 50%;
}

.ping-excellent {
    background-color: #16a34a;
}

.ping-good {
    background-color: #eab308;
}

.ping-slow,

.ping-error {
    background-color: #dc2626;
}

.ping-checking {
    background-color: #9ca3af;
}

.search-box i {
    color:
        var(--muted-text, #6b7280);
    font-size: 14px;
}

.search-box input {
    width: 100%;
    margin-left: 8px;
    border: 0;
    outline: 0;
    background: transparent;
    color:
        var(--text-color, #172033);
    font-size: 13px;
}

.search-box input::placeholder {
    color:
        var(--muted-text, #9ca3af);
}

.icon-btn {
    position: relative;
    width: 40px;
    height: 40px;
    border:
        1px solid var(--border-color, #dbe3ee);
    border-radius: 8px;
    background:
        var(--surface-bg, #ffffff);
    color:
        var(--text-color, #172033);
    font-size: 17px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition:
        background .2s ease,
        border-color .2s ease,
        color .2s ease;
}

.icon-btn:hover {
    background:
        var(--surface-muted, #eef2f7);
}

.theme-btn i {
    transition:
        transform .25s ease;
}

.theme-btn:hover i {
    transform: rotate(15deg);
}

.notification-btn {
    position: relative;
}

.notification-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    min-width: 17px;
    height: 17px;
    padding: 0 4px;
    border:
        2px solid var(--surface-bg, #ffffff);
    border-radius: 20px;
    background: #ef4444;
    color: #ffffff;
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
    transition:
        background .2s ease;
}

.user-info:hover {
    background:
        var(--surface-muted, #eef2f7);
}

.avatar {
    width: 36px;
    height: 36px;
    padding: 0;
    border: 0;
    border-radius: 50%;
    background: #2563eb;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    overflow: hidden;
    flex: none;
    transition: box-shadow .2s ease;
}

.avatar:hover {
    box-shadow: 0 0 0 3px rgb(37 99 235 / 0.25);
}

.avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.user-text {
    display: flex;
    flex-direction: column;
    line-height: 1.2;
}

.user-text strong {
    color:
        var(--text-color, #172033);
    font-size: 13px;
    font-weight: 700;
}

.user-text span {
    margin-top: 3px;
    color:
        var(--muted-text, #667085);
    font-size: 11px;
}

.user-info>i {
    color:
        var(--muted-text, #667085);
    font-size: 10px;
}

.user-dropdown {
    position: absolute;
    top: 48px;
    right: 0;
    width: 190px;
    padding: 6px;
    background:
        var(--surface-bg, #ffffff);
    border:
        1px solid var(--border-color, #e5e7eb);
    border-radius: 9px;
    box-shadow:
        0 10px 25px rgba(0, 0, 0, .12);
    z-index: 1000;
    animation:
        dropdown .15s ease-out;
}

.user-dropdown button {
    width: 100%;
    padding: 9px 10px;
    border: 0;
    border-radius: 6px;
    background: transparent;
    color:
        var(--text-color, #172033);
    display: flex;
    align-items: center;
    gap: 9px;
    font-family: inherit;
    font-size: 13px;
    cursor: pointer;
    text-align: left;
    transition:
        background .2s ease,
        color .2s ease;
}

.user-dropdown button i {
    color:
        var(--muted-text, #667085);
}

.user-dropdown button:hover {
    background:
        var(--surface-muted, #f3f4f6);
    color: #dc2626;
}

.user-dropdown button:hover i {
    color: #dc2626;
}

.user-dropdown button:first-child:hover,

.user-dropdown button:first-child:hover i {
    color: var(--ui-primary, #1f5eff);
}

.profile-backdrop {
    position: fixed;
    inset: 0;
    z-index: 1100;
    display: grid;
    place-items: center;
    overflow-y: auto;
    padding: 1rem;
    background: rgb(15 23 42 / 60%);
}

.profile-modal {
    width: min(100%, 26rem);
    overflow: hidden;
    border: 1px solid var(--border-color, #e2e8f0);
    border-radius: 14px;
    background: var(--surface-bg, #fff);
    color: var(--text-color, #0f172a);
    box-shadow: 0 20px 45px rgb(15 23 42 / 25%);
}

.profile-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.1rem 1.25rem;
    border-bottom: 1px solid var(--border-color, #e2e8f0);
    background: var(--surface-muted, #f1f5f9);
}

.profile-eyebrow {
    margin: 0 0 .2rem;
    color: var(--muted-text, #64748b);
    font-size: .75rem;
}

.profile-modal-header h2 {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 700;
}

.profile-close {
    display: grid;
    width: 2.25rem;
    height: 2.25rem;
    flex: none;
    place-items: center;
    border: 1px solid var(--border-color, #e2e8f0);
    border-radius: 8px;
    background: var(--surface-bg, #fff);
    color: var(--text-color, #0f172a);
    font-size: .9rem;
}

.profile-state {
    padding: 2rem 1rem;
    color: var(--muted-text, #64748b);
    text-align: center;
}

.photo-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .85rem;
    padding: 1.75rem 1.25rem 1.5rem;
    border-bottom: 1px solid var(--border-color, #e2e8f0);
    background: var(--surface-muted, #f8fafc);
    text-align: center;
}

.photo-preview {
    display: flex;
    width: 6.5rem;
    height: 6.5rem;
    flex: none;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    border-radius: 50%;
    background: #2563eb;
    color: #fff;
    font-size: 2.1rem;
    font-weight: 700;
    border: 3px solid var(--surface-bg, #fff);
    box-shadow: 0 3px 12px rgb(0 0 0 / .12);
}

.photo-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.photo-actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: .6rem;
}

.btn-choose,

.btn-upload {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .55rem .95rem;
    border: 1px solid var(--border-color, #e2e8f0);
    border-radius: 8px;
    background: var(--surface-bg, #fff);
    color: var(--text-color, #0f172a);
    font-size: .85rem;
    font-weight: 600;
    cursor: pointer;
}

.btn-choose:hover {
    background: var(--surface-muted, #eef2f7);
}

.btn-upload {
    border-color: #2563eb;
    background: #2563eb;
    color: #fff;
}

.btn-upload:hover {
    background: #1d4ed8;
}

.btn-upload:disabled {
    opacity: .65;
    cursor: not-allowed;
}

.photo-error {
    width: 100%;
    margin: 0;
    color: #dc2626;
    font-size: .8rem;
}
:global(.dark-theme) .photo-card {
    border-color: #334155;
    background: #334155;
}
:global(.dark-theme) .btn-choose {
    border-color: #475569;
    background: #1e293b;
    color: #f1f5f9;
}

.profile-details {
    display: grid;
    gap: 0;
    margin: 0;
    padding: .5rem 1.25rem;
}

.profile-details>div {
    display: grid;
    grid-template-columns: minmax(7rem, .8fr) minmax(0, 1.2fr);
    gap: .75rem;
    padding: .85rem 0;
    border-bottom: 1px solid var(--border-color, #e2e8f0);
}

.profile-details>div:last-child {
    border-bottom: 0;
}

.profile-details dt {
    color: var(--muted-text, #64748b);
    font-size: .88rem;
    font-weight: 500;
}

.profile-details dd {
    min-width: 0;
    margin: 0;
    overflow-wrap: anywhere;
    font-size: .95rem;
    font-weight: 600;
}
:global(.dark-theme) .profile-modal {
    border-color: #334155;
    background: #1e293b;
    color: #f1f5f9;
}
:global(.dark-theme) .profile-modal-header {
    border-color: #334155;
    background: #334155;
}
:global(.dark-theme) .profile-details>div {
    border-color: #334155;
}
:global(.dark-theme) .profile-close {
    border-color: #475569;
    background: #1e293b;
    color: #f1f5f9;
}

@keyframes dropdown {
    from {
        opacity: 0;
        transform:
            translateY(-4px);
    }
    to {
        opacity: 1;
        transform:
            translateY(0);
    }
}

:global(.dark-theme) .topbar {
    background:
        var(--surface-bg);
    border-color:
        var(--border-color);
}
:global(.dark-theme) .search-box {
    background:
        #1e293b;
    border-color:
        #334155;
}
:global(.dark-theme) .icon-btn {
    background:
        #1e293b;
    border-color:
        #334155;
}
:global(.dark-theme) .menu-btn {
    background:
        #1e293b;
    color:
        #f1f5f9;
}
:global(.dark-theme) .menu-btn:hover,
:global(.dark-theme) .icon-btn:hover,
:global(.dark-theme) .user-info:hover {
    background:
        #334155;
}
:global(.dark-theme) .user-dropdown {
    background:
        #1e293b;
    border-color:
        #334155;
    box-shadow:
        0 12px 30px rgba(0, 0, 0, .35);
}
:global(.dark-theme) .user-dropdown button {
    color:
        #f1f5f9;
}
:global(.dark-theme) .user-dropdown button:hover {
    background:
        #334155;
    color:
        #f87171;
}
:global(.dark-theme) .user-dropdown button i {
    color:
        #94a3b8;
}
:global(.dark-theme) .user-dropdown button:hover i {
    color:
        #f87171;
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
@media (max-width: 450px) {
    .topbar-right {
        gap: 6px;
    }
    .icon-btn,
    .menu-btn {
        width: 36px;
        height: 36px;
    }
    .topbar-left {
        gap: 8px;
    }
    .topbar-left h5 {
        font-size: 16px;
    }
}
</style>
