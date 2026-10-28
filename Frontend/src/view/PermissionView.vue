<script setup>
import { computed, onMounted, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRolePermissionStore } from '@/stores/RolePermission'
import { PERMISSIONS } from '@/constants/permissions'
import PermissionTable from '@/Components/permissions/PermissionTable.vue'
import PermissionFormModal from '@/Components/permissions/PermissionFormModal.vue'
import DeleteConfirmModal from '@/Components/management/DeleteConfirmModal.vue'

const auth = useAuthStore()
const dataStore = useRolePermissionStore()
const canCreate = computed(() => auth.can(PERMISSIONS.PERMISSIONS_CREATE))
const canUpdate = computed(() => auth.can(PERMISSIONS.PERMISSIONS_UPDATE))
const canDelete = computed(() => auth.can(PERMISSIONS.PERMISSIONS_DELETE))

const search = ref('')
const formOpen = ref(false)
const formMode = ref('create')
const saving = ref(false)
const formError = ref('')
const form = ref(emptyForm())
const deleteOpen = ref(false)
const deleting = ref(false)
const deleteError = ref('')
const selectedPermission = ref(null)
const pageError = ref('')
const successMessage = ref('')

function emptyForm() {
    return { name: '', description: '' }
}

const visiblePermissions = computed(() => {
    const query = search.value.trim().toLowerCase()
    return dataStore.permissions.filter(permission => !query || [
        permission.name,
        permission.description
    ].some(value => String(value || '').toLowerCase().includes(query)))
})

const loadPermissions = async () => {
    pageError.value = ''
    try {
        await dataStore.getPermissions()
    } catch (error) {
        pageError.value = dataStore.error || error.message || 'Failed to load permissions.'
    }
}

const openCreate = () => {
    if (!canCreate.value) return
    form.value = emptyForm()
    formMode.value = 'create'
    formError.value = ''
    formOpen.value = true
}

const openEdit = (permission) => {
    if (!canUpdate.value) return
    form.value = {
        id: permission.id,
        name: permission.name || '',
        description: permission.description || ''
    }
    formMode.value = 'edit'
    formError.value = ''
    formOpen.value = true
}

const savePermission = async () => {
    if (saving.value) return
    if (formMode.value === 'create' && !canCreate.value) return
    if (formMode.value === 'edit' && !canUpdate.value) return

    const payload = {
        name: form.value.name.trim(),
        description: form.value.description.trim() || null
    }
    saving.value = true
    formError.value = ''
    try {
        if (formMode.value === 'create') await dataStore.createPermission(payload)
        else await dataStore.updatePermission(form.value.id, payload)
        formOpen.value = false
        successMessage.value = formMode.value === 'create' ? 'Permission created successfully.' : 'Permission updated successfully.'
        await loadPermissions()
    } catch (error) {
        const validationErrors = error.response?.data?.errors
        formError.value = validationErrors
            ? Object.values(validationErrors).flat().join(' ')
            : dataStore.error || error.response?.data?.message || 'Failed to save permission.'
    } finally {
        saving.value = false
    }
}

const openDelete = (permission) => {
    if (!canDelete.value) return
    selectedPermission.value = permission
    deleteError.value = ''
    deleteOpen.value = true
}

const deletePermission = async () => {
    if (!canDelete.value || !selectedPermission.value || deleting.value) return
    deleting.value = true
    deleteError.value = ''
    try {
        await dataStore.deletePermission(selectedPermission.value.id)
        deleteOpen.value = false
        successMessage.value = 'Permission deleted successfully.'
        await loadPermissions()
    } catch (error) {
        deleteError.value = dataStore.error || error.response?.data?.message || 'Failed to delete permission.'
    } finally {
        deleting.value = false
    }
}

onMounted(loadPermissions)
</script>

<template>
    <div class="container-fluid py-4 px-3 px-md-4">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
            <div>
                <h1 class="h3 mb-1">Permission Management</h1>
                <p class="text-muted mb-0">Manage permissions that can be assigned to roles.</p>
            </div>
            <button v-if="canCreate" type="button" class="btn btn-primary" @click="openCreate">
                <i class="bi bi-plus-lg me-1"></i>
                Add Permission
            </button>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <label class="visually-hidden" for="permission-search">Search permissions</label>
                <input id="permission-search" v-model="search" type="search" class="form-control mb-3" placeholder="Search name or description">
                <div v-if="pageError" class="alert alert-danger">{{ pageError }}</div>
                <div v-if="successMessage" class="alert alert-success">{{ successMessage }}</div>
                <PermissionTable
                    :permissions="visiblePermissions"
                    :loading="dataStore.permissionsLoading"
                    :can-update="canUpdate"
                    :can-delete="canDelete"
                    @edit="openEdit"
                    @delete="openDelete"
                />
            </div>
        </div>
    </div>

    <PermissionFormModal v-model="formOpen" :form="form" :mode="formMode" :saving="saving" :error="formError" @submit="savePermission" />
    <DeleteConfirmModal
        v-model="deleteOpen"
        title="Delete Permission"
        :item-name="selectedPermission?.name"
        warning="This permission will be removed from every role that currently has it."
        :deleting="deleting"
        :error="deleteError"
        @confirm="deletePermission"
    />
</template>
