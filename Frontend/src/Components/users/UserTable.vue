<script setup>
import LoadingSkeleton from '@/Components/LoadingSkeleton.vue'

defineProps({
    users: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    canUpdate: { type: Boolean, default: false },
    canDelete: { type: Boolean, default: false },
    currentUserId: { type: [Number, String], default: null }
})

defineEmits(['edit', 'delete'])
</script>

<template>
    <LoadingSkeleton v-if="loading" variant="table" :columns="7" label="Loading users" />
    <div v-else class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Username</th>
                    <th>Phone</th>
                    <th>Roles</th>
                    <th>Status</th>
                    <th>Last login</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="user in users" :key="user.id">
                    <td>
                        <div class="fw-semibold">{{ user.full_name || user.username }}</div>
                        <small class="text-muted">{{ user.email }}</small>
                    </td>
                    <td>{{ user.username }}</td>
                    <td>{{ user.phone || '-' }}</td>
                    <td>
                        <span v-if="user.roles?.length" class="d-flex flex-wrap gap-1">
                            <span v-for="role in user.roles" :key="role.id" class="badge text-bg-light">
                                {{ role.name }}
                            </span>
                        </span>
                        <span v-else class="text-muted">No role</span>
                    </td>
                    <td>
                        <span class="badge" :class="user.status === 'active' ? 'badge-active' : 'badge-inactive'">
                            {{ user.status || 'inactive' }}
                        </span>
                    </td>
                    <td>{{ user.last_login_at ? new Date(user.last_login_at).toLocaleString() : 'Never' }}</td>
                    <td class="text-center">
                        <button
                            v-if="canUpdate"
                            type="button"
                            class="btn table-action"
                            title="Edit user"
                            :aria-label="`Edit ${user.username}`"
                            @click="$emit('edit', user)"
                        >
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button
                            v-if="canDelete"
                            type="button"
                            class="btn table-action action-danger"
                            :disabled="String(user.id) === String(currentUserId)"
                            :title="String(user.id) === String(currentUserId) ? 'You cannot delete your own account' : 'Delete user'"
                            :aria-label="`Delete ${user.username}`"
                            @click="$emit('delete', user)"
                        >
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
                <tr v-if="users.length === 0">
                    <td colspan="7" class="text-center py-5">
                        <div class="empty-state">
                            <i class="bi bi-people"></i>
                            <h5>No Users Found</h5>
                            <p>No users match the current search or filter.</p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<style scoped>
.badge {
    text-transform: capitalize;
}
</style>
