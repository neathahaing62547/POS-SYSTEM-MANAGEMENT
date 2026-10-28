<script setup>
import LoadingSkeleton from '@/Components/LoadingSkeleton.vue'

defineProps({
    roles: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    canUpdate: { type: Boolean, default: false },
    canDelete: { type: Boolean, default: false }
})

defineEmits(['edit', 'delete'])
</script>

<template>
    <LoadingSkeleton v-if="loading" variant="table" :columns="5" label="Loading roles" />
    <div v-else class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Role</th>
                    <th>Description</th>
                    <th>Permissions</th>
                    <th>Created</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="role in roles" :key="role.id">
                    <td class="fw-semibold text-capitalize">{{ role.name }}</td>
                    <td>{{ role.description || '-' }}</td>
                    <td>
                        <span class="badge text-bg-light">{{ role.permissions?.length || 0 }}</span>
                        <span v-if="role.permissions?.length" class="text-muted ms-1">
                            {{ role.permissions.slice(0, 3).map(permission => permission.name).join(', ') }}
                            <span v-if="role.permissions.length > 3">, +{{ role.permissions.length - 3 }} more</span>
                        </span>
                        <span v-else class="text-muted ms-1">None</span>
                    </td>
                    <td>{{ role.created_at ? new Date(role.created_at).toLocaleDateString() : '-' }}</td>
                    <td class="text-center">
                        <button v-if="canUpdate" type="button" class="btn table-action" title="Edit role" :aria-label="`Edit ${role.name}`" @click="$emit('edit', role)">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button v-if="canDelete" type="button" class="btn table-action action-danger" title="Delete role" :aria-label="`Delete ${role.name}`" @click="$emit('delete', role)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
                <tr v-if="roles.length === 0">
                    <td colspan="5" class="text-center py-5">
                        <div class="empty-state">
                            <i class="bi bi-person-badge"></i>
                            <h5>No Roles Found</h5>
                            <p>No roles match the current search.</p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
