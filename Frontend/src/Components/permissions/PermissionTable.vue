<script setup>
import LoadingSkeleton from '@/Components/LoadingSkeleton.vue'

defineProps({
    permissions: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    canUpdate: { type: Boolean, default: false },
    canDelete: { type: Boolean, default: false }
})

defineEmits(['edit', 'delete'])
</script>

<template>
    <LoadingSkeleton v-if="loading" variant="table" :columns="4" label="Loading permissions" />
    <div v-else class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Permission</th>
                    <th>Module</th>
                    <th>Description</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="permission in permissions" :key="permission.id">
                    <td><code>{{ permission.name }}</code></td>
                    <td class="text-capitalize">{{ permission.name.split('.')[0]?.replaceAll('_', ' ') || '-' }}</td>
                    <td>{{ permission.description || '-' }}</td>
                    <td class="text-center">
                        <button v-if="canUpdate" type="button" class="btn table-action" title="Edit permission" :aria-label="`Edit ${permission.name}`" @click="$emit('edit', permission)">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button v-if="canDelete" type="button" class="btn table-action action-danger" title="Delete permission" :aria-label="`Delete ${permission.name}`" @click="$emit('delete', permission)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
                <tr v-if="permissions.length === 0">
                    <td colspan="4" class="text-center py-5">
                        <div class="empty-state">
                            <i class="bi bi-shield-lock"></i>
                            <h5>No Permissions Found</h5>
                            <p>No permissions match the current search.</p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
