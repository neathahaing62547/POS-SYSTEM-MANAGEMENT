<script setup>
import LoadingSkeleton from '@/Components/LoadingSkeleton.vue'

defineProps({
    stores: {
        type: Array,
        default: () => []
    },
    pagination: {
        type: Object,
        default: null
    },
    loading: {
        type: Boolean,
        default: false
    },
    canUpdate: {
        type: Boolean,
        default: false
    },
    canDelete: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['edit', 'delete', 'page'])

const mapUrl = (address) => {
    const value = address?.trim()
    if (!value) return ''

    try {
        const url = new URL(value)
        if (url.protocol === 'https:' || url.protocol === 'http:') {
            return url.href
        }
    } catch {
        // Plain-text addresses are opened as a Google Maps search.
    }

    return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(value)}`
}
</script>

<template>
    <div>
        <LoadingSkeleton v-if="loading" variant="table" label="Loading stores" />

        <div v-else class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Store</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="store in stores" :key="store.id">
                        <td class="text-muted">#{{ store.id }}</td>
                        <td class="fw-semibold">{{ store.name }}</td>
                        <td>{{ store.phone || '-' }}</td>
                        <td class="store-address">
                            <a
                                v-if="store.address"
                                :href="mapUrl(store.address)"
                                target="_blank"
                                rel="noopener noreferrer"
                                :aria-label="`Open map for ${store.name}: ${store.address}`"
                            >
                                <i class="bi bi-geo-alt me-1" aria-hidden="true"></i>
                                {{ store.address }}
                            </a>
                            <span v-else>-</span>
                        </td>
                        <td>
                            <span
                                class="badge"
                                :class="store.status === 'active' ? 'badge-active' : 'badge-inactive'"
                            >
                                {{ store.status || 'inactive' }}
                            </span>
                        </td>
                        <td class="text-center">
                            <button
                                v-if="canUpdate"
                                type="button"
                                class="btn table-action"
                                title="Edit store"
                                :aria-label="`Edit ${store.name}`"
                                @click="emit('edit', store)"
                            >
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button
                                v-if="canDelete"
                                type="button"
                                class="btn table-action action-danger"
                                title="Delete store"
                                :aria-label="`Delete ${store.name}`"
                                @click="emit('delete', store)"
                            >
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <tr v-if="stores.length === 0">
                        <td colspan="6" class="text-center py-5">
                            <div class="empty-state">
                                <i class="bi bi-shop-window"></i>
                                <h5>No Stores Found</h5>
                                <p>No stores have been created yet.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-if="pagination"
            class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 mt-3"
        >
            <small class="text-muted">
                Showing {{ pagination.from || 0 }}–{{ pagination.to || 0 }}
                of {{ pagination.total || 0 }} stores
            </small>
            <div class="d-flex align-items-center gap-2">
                <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary"
                    :disabled="pagination.current_page <= 1"
                    @click="emit('page', pagination.current_page - 1)"
                >
                    Previous
                </button>
                <span class="text-muted small">
                    Page {{ pagination.current_page }} of {{ pagination.last_page }}
                </span>
                <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary"
                    :disabled="pagination.current_page >= pagination.last_page"
                    @click="emit('page', pagination.current_page + 1)"
                >
                    Next
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.store-address {
    min-width: 180px;
    max-width: 320px;
    white-space: normal;
}

.store-address a {
    color: var(--ui-primary);
    text-decoration: none;
}

.store-address a:hover {
    text-decoration: underline;
}

.badge {
    text-transform: capitalize;
}
</style>
