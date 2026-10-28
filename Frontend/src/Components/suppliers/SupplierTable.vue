
<template>
    <LoadingSkeleton v-if="loading" variant="table" label="Loading suppliers" />

    <div v-else class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Company</th>
                    <th>Contact</th>
                    <th>Image</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>

            <tbody>
                <tr
                    v-for="supplier in suppliers"
                    :key="supplier.id"
                >
                    <td>
                        <span class="text-muted">
                            #{{ supplier.id }}
                        </span>
                    </td>

                    <td>
                        <div class="fw-semibold text-truncate-cell">
                            {{ supplier.company_name }}
                        </div>
                    </td>

                    <td>
                        {{ supplier.contact_person || '-' }}
                    </td>

                    <td>
                        <ImageThumbnail
                            :src="supplier.image"
                            class="product-image"
                            :alt="`${supplier.company_name} image`"
                            fallback="bi-building"
                        />
                    </td>

                    <td>
                        {{ supplier.phone || '-' }}
                    </td>

                    <td>
                        {{ supplier.email || '-' }}
                    </td>

                    <td>
                        <span
                            class="badge"
                            :class="
                                supplier.status === 'active'
                                    ? 'badge-active'
                                    : 'badge-inactive'
                            "
                        >
                            {{ supplier.status || 'active' }}
                        </span>
                    </td>

                    <td class="text-center">
                        <button
                            v-if="canUpdate"
                            type="button"
                            class="btn table-action"
                            title="Edit supplier"
                            :aria-label="`Edit ${supplier.company_name}`"
                            @click="emit('edit', supplier)"
                        >
                            <i class="bi bi-pencil"></i>
                        </button>

                        <button
                            v-if="canDelete"
                            type="button"
                            class="btn table-action action-danger"
                            title="Delete supplier"
                            :aria-label="`Delete ${supplier.company_name}`"
                            @click="emit('delete', supplier)"
                        >
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>

                <tr v-if="suppliers.length === 0">
                    <td
                        colspan="8"
                        class="text-center py-5"
                    >
                        <div class="empty-state">
                            <i class="bi bi-truck"></i>

                            <h5>No Suppliers Found</h5>

                            <p>
                                There are no suppliers matching your search.
                            </p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import ImageThumbnail from '@/Components/ImageThumbnail.vue'
import LoadingSkeleton from '@/Components/LoadingSkeleton.vue'

defineProps({
    suppliers: {
        type: Array,
        default: () => []
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

const emit = defineEmits([
    'edit',
    'delete'
])

</script>

<style scoped>
.table {
    --bs-table-bg: transparent;
    --bs-table-color: var(--text-color);
    --bs-table-border-color: var(--border-color);
    --bs-table-hover-bg: var(--surface-muted);
    color: var(--text-color);
}

.table th {
    font-size: 12.5px;
    font-weight: 700;
    color: var(--muted-text);
    background: var(--surface-muted) !important;
    border-bottom: 1px solid var(--border-color);
    white-space: nowrap;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.table td {
    font-size: 14px;
    border-color: var(--border-color);
    vertical-align: middle;
}

.badge {
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    text-transform: capitalize;
}

.badge-active {
    color: green;
}

.badge-inactive {
    color: var(--muted-text);
}

.table-action {
    width: 34px;
    height: 34px;
    padding: 0;
    border-radius: 8px;
    color: var(--muted-text);
    border: 1px solid transparent;
    background: transparent;
}

.table-action:hover {
    background: var(--surface-muted);
    color: var(--text-color);
}

.action-danger:hover {
    background: rgba(239, 68, 68, 0.12);
    color: #ef4444;
}

.text-truncate-cell {
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.empty-state {
    padding: 50px 20px;
    text-align: center;
}

.empty-state i {
    font-size: 56px;
    color: var(--muted-text);
    opacity: 0.5;
    display: block;
    margin-bottom: 16px;
}

.empty-state h5 {
    font-size: 18px;
    font-weight: 700;
    color: var(--text-color);
}

.empty-state p {
    color: var(--muted-text);
    margin: 0;
}
</style>
