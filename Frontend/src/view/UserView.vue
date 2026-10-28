<script setup>
import { computed, onMounted, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useUserStore } from '@/stores/User'
import { PERMISSIONS } from '@/constants/permissions'
import UserTable from '@/Components/users/UserTable.vue'
import UserFormModal from '@/Components/users/UserFormModal.vue'
import UserDeleteModal from '@/Components/users/UserDeleteModal.vue'

const auth = useAuthStore()
const userStore = useUserStore()

const canCreate = computed(() => auth.can(PERMISSIONS.USERS_CREATE))
const canUpdate = computed(() => auth.can(PERMISSIONS.USERS_UPDATE))
const canDelete = computed(() => auth.can(PERMISSIONS.USERS_DELETE))
const canAssignRoles = computed(() => auth.can(PERMISSIONS.ROLES_VIEW))

const search = ref('')
const statusFilter = ref('')
const formOpen = ref(false)
const formMode = ref('create')
const formSaving = ref(false)
const formError = ref('')
const form = ref(emptyUser())
const deleteOpen = ref(false)
const deleting = ref(false)
const deleteError = ref('')
const selectedUser = ref(null)
const pageError = ref('')
const successMessage = ref('')

function emptyUser() {
    return {
        username: '',
        full_name: '',
        email: '',
        phone: '',
        password: '',
        status: 'active',
        role_ids: []
    }
}

const visibleUsers = computed(() => {
    const query = search.value.trim().toLowerCase()
    return userStore.users.filter((user) => {
        const matchesQuery = !query || [
            user.username,
            user.full_name,
            user.email,
            user.phone,
            ...(user.roles || []).map(role => role.name)
        ].some(value => String(value || '').toLowerCase().includes(query))
        return matchesQuery && (!statusFilter.value || user.status === statusFilter.value)
    })
})

const loadUsers = async () => {
    pageError.value = ''
    try {
        await userStore.getUsers()
    } catch (error) {
        pageError.value = userStore.error || error.message || 'Failed to load users.'
    }
}

const loadRoles = async () => {
    if (!canAssignRoles.value) return
    try {
        await userStore.getRoles()
    } catch (error) {
        formError.value = userStore.rolesError || error.message || 'Failed to load roles.'
    }
}

const openCreate = () => {
    if (!canCreate.value) return
    form.value = emptyUser()
    formMode.value = 'create'
    formError.value = ''
    formOpen.value = true
    loadRoles()
}

const openEdit = (user) => {
    if (!canUpdate.value) return
    form.value = {
        id: user.id,
        username: user.username || '',
        full_name: user.full_name || '',
        email: user.email || '',
        phone: user.phone || '',
        password: '',
        status: user.status || 'inactive',
        role_ids: (user.roles || []).map(role => role.id)
    }
    formMode.value = 'edit'
    formError.value = ''
    formOpen.value = true
    loadRoles()
}

const saveUser = async () => {
    if (formSaving.value) return
    if (formMode.value === 'create' && !canCreate.value) return
    if (formMode.value === 'edit' && !canUpdate.value) return
    formError.value = ''

    const payload = {
        username: form.value.username.trim(),
        full_name: form.value.full_name.trim() || null,
        email: form.value.email.trim(),
        phone: form.value.phone.trim() || null,
        status: form.value.status
    }
    if (form.value.password) payload.password = form.value.password
    if (canAssignRoles.value) payload.role_ids = form.value.role_ids

    formSaving.value = true
    try {
        if (formMode.value === 'create') {
            await userStore.createUser(payload)
        } else {
            await userStore.updateUser(form.value.id, payload)
        }
        formOpen.value = false
        successMessage.value = formMode.value === 'create' ? 'User created successfully.' : 'User updated successfully.'
        await loadUsers()
    } catch (error) {
        const validationErrors = error.response?.data?.errors
        formError.value = validationErrors
            ? Object.values(validationErrors).flat().join(' ')
            : userStore.error || error.response?.data?.message || 'Failed to save user.'
    } finally {
        formSaving.value = false
    }
}

const openDelete = (user) => {
    if (!canDelete.value || String(user.id) === String(auth.user?.id)) return
    selectedUser.value = user
    deleteError.value = ''
    deleteOpen.value = true
}

const deleteUser = async () => {
    if (!canDelete.value || !selectedUser.value || deleting.value) return
    deleting.value = true
    deleteError.value = ''
    try {
        await userStore.deleteUser(selectedUser.value.id)
        deleteOpen.value = false
        successMessage.value = 'User deleted successfully.'
        await loadUsers()
    } catch (error) {
        deleteError.value = userStore.error || error.response?.data?.message || 'Failed to delete user.'
    } finally {
        deleting.value = false
    }
}

onMounted(loadUsers)
</script>

<template>
    <div class="container-fluid py-4 px-3 px-md-4">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
            <div>
                <h1 class="h3 mb-1">User Management</h1>
                <p class="text-muted mb-0">Manage user accounts, roles, and access status.</p>
            </div>
            <button v-if="canCreate" type="button" class="btn btn-primary" @click="openCreate">
                <i class="bi bi-person-plus me-1"></i>
                Add User
            </button>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="row g-2 mb-3">
                    <div class="col-12 col-md">
                        <label class="visually-hidden" for="user-search">Search users</label>
                        <input id="user-search" v-model="search" type="search" class="form-control" placeholder="Search name, username, email, phone, or role">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="visually-hidden" for="user-status">Filter by status</label>
                        <select id="user-status" v-model="statusFilter" class="form-select">
                            <option value="">All statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div v-if="pageError" class="alert alert-danger">{{ pageError }}</div>
                <div v-if="successMessage" class="alert alert-success">{{ successMessage }}</div>

                <UserTable
                    :users="visibleUsers"
                    :loading="userStore.loading"
                    :can-update="canUpdate"
                    :can-delete="canDelete"
                    :current-user-id="auth.user?.id"
                    @edit="openEdit"
                    @delete="openDelete"
                />
            </div>
        </div>
    </div>

    <UserFormModal
        v-model="formOpen"
        :form="form"
        :mode="formMode"
        :roles="userStore.roles"
        :roles-loading="userStore.rolesLoading"
        :can-assign-roles="canAssignRoles"
        :saving="formSaving"
        :error="formError"
        @submit="saveUser"
    />

    <UserDeleteModal
        v-model="deleteOpen"
        :user-name="selectedUser?.full_name || selectedUser?.username"
        :deleting="deleting"
        :error="deleteError"
        @confirm="deleteUser"
    />
</template>
