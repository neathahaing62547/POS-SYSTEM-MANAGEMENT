<script setup>
import { computed, onMounted, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRolePermissionStore } from '@/stores/RolePermission'
import { PERMISSIONS } from '@/constants/permissions'
import RoleTable from '@/Components/roles/RoleTable.vue'
import RoleFormModal from '@/Components/roles/RoleFormModal.vue'
import DeleteConfirmModal from '@/Components/management/DeleteConfirmModal.vue'

const auth = useAuthStore()
const dataStore = useRolePermissionStore()
const canCreate = computed(() => auth.can(PERMISSIONS.ROLES_CREATE))
const canUpdate = computed(() => auth.can(PERMISSIONS.ROLES_UPDATE))
const canDelete = computed(() => auth.can(PERMISSIONS.ROLES_DELETE))
const canManagePermissions = computed(() => auth.can(PERMISSIONS.PERMISSIONS_VIEW))

const search = ref('')
const formOpen = ref(false)
const formMode = ref('create')
const saving = ref(false)
const formError = ref('')
const form = ref(emptyForm())
const deleteOpen = ref(false)
const deleting = ref(false)
const deleteError = ref('')
const selectedRole = ref(null)
const pageError = ref('')
const successMessage = ref('')

function emptyForm() {
    return { name: '', description: '', permission_ids: [] }
}

const visibleRoles = computed(() => {
    const query = search.value.trim().toLowerCase()
    return dataStore.roles.filter(role => !query || [
        role.name,
        role.description,
        ...(role.permissions || []).map(permission => permission.name)
    ].some(value => String(value || '').toLowerCase().includes(query)))
})

const loadRoles = async () => {
    pageError.value = ''
    try {
        await dataStore.getRoles()
    } catch (error) {
        pageError.value = dataStore.error || error.message || 'Failed to load roles.'
    }
}

const openCreate = () => {
    if (!canCreate.value) return
    form.value = emptyForm()
    formMode.value = 'create'
    formError.value = ''
    formOpen.value = true
    if (canManagePermissions.value && !dataStore.permissions.length) loadPermissions()
}

const openEdit = (role) => {
    if (!canUpdate.value) return
    form.value = {
        id: role.id,
        name: role.name || '',
        description: role.description || '',
        permission_ids: (role.permissions || []).map(permission => permission.id)
    }
    formMode.value = 'edit'
    formError.value = ''
    formOpen.value = true
    if (canManagePermissions.value && !dataStore.permissions.length) loadPermissions()
}

const loadPermissions = async () => {
    try {
        await dataStore.getPermissions()
    } catch (error) {
        formError.value = dataStore.error || error.message || 'Failed to load permissions.'
    }
}

const saveRole = async () => {
    if (saving.value) return
    if (formMode.value === 'create' && !canCreate.value) return
    if (formMode.value === 'edit' && !canUpdate.value) return

    const payload = {
        name: form.value.name.trim(),
        description: form.value.description.trim() || null
    }
    if (canManagePermissions.value) payload.permission_ids = form.value.permission_ids

    saving.value = true
    formError.value = ''
    try {
        if (formMode.value === 'create') await dataStore.createRole(payload)
        else await dataStore.updateRole(form.value.id, payload)
        formOpen.value = false
        successMessage.value = formMode.value === 'create' ? 'Role created successfully.' : 'Role updated successfully.'
        await loadRoles()
    } catch (error) {
        const validationErrors = error.response?.data?.errors
        formError.value = validationErrors
            ? Object.values(validationErrors).flat().join(' ')
            : dataStore.error || error.response?.data?.message || 'Failed to save role.'
    } finally {
        saving.value = false
    }
}

const openDelete = (role) => {
    if (!canDelete.value) return
    selectedRole.value = role
    deleteError.value = ''
    deleteOpen.value = true
}

const deleteRole = async () => {
    if (!canDelete.value || !selectedRole.value || deleting.value) return
    deleting.value = true
    deleteError.value = ''
    try {
        await dataStore.deleteRole(selectedRole.value.id)
        deleteOpen.value = false
        successMessage.value = 'Role deleted successfully.'
        await loadRoles()
    } catch (error) {
        deleteError.value = dataStore.error || error.response?.data?.message || 'Failed to delete role.'
    } finally {
        deleting.value = false
    }
}

onMounted(async () => {
    await loadRoles()
    if (canManagePermissions.value) {
        try {
            await dataStore.getPermissions()
        } catch (error) {
            pageError.value = dataStore.error || error.message || 'Failed to load permissions.'
        }
    }
})
</script>

<template>
    <div class="container-fluid py-4 px-3 px-md-4">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
            <div>
                <h1 class="h3 mb-1">Role Management</h1>
                <p class="text-muted mb-0">Manage roles and the permissions granted to each role.</p>
            </div>
            <button v-if="canCreate" type="button" class="btn btn-primary" @click="openCreate">
                <i class="bi bi-plus-lg me-1"></i>
                Add Role
            </button>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <label class="visually-hidden" for="role-search">Search roles</label>
                <input id="role-search" v-model="search" type="search" class="form-control mb-3" placeholder="Search roles or permissions">
                <div v-if="pageError" class="alert alert-danger">{{ pageError }}</div>
                <div v-if="successMessage" class="alert alert-success">{{ successMessage }}</div>
                <RoleTable :roles="visibleRoles" :loading="dataStore.rolesLoading" :can-update="canUpdate" :can-delete="canDelete" @edit="openEdit" @delete="openDelete" />
            </div>
        </div>
    </div>

    <RoleFormModal
        v-model="formOpen"
        :form="form"
        :mode="formMode"
        :permissions="dataStore.permissions"
        :can-manage-permissions="canManagePermissions"
        :saving="saving"
        :error="formError"
        @submit="saveRole"
    />
    <DeleteConfirmModal
        v-model="deleteOpen"
        title="Delete Role"
        :item-name="selectedRole?.name"
        warning="Users assigned to this role will lose its permissions."
        :deleting="deleting"
        :error="deleteError"
        @confirm="deleteRole"
    />
</template>
