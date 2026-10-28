<script setup>
import ImageThumbnail from '@/Components/ImageThumbnail.vue'
import LoadingSkeleton from '@/Components/LoadingSkeleton.vue'

const props = defineProps({
    brands: {
        type: Array,
        default: () => []
    },
    loading: Boolean,
    error: String,
    successMessage: String,
    pagination: Object,
    canUpdate: {
        type: Boolean,
        default: false
    },
    canDelete: {
        type: Boolean,
        default: false
    }
})
defineEmits([
    'view',
    'edit',
    'delete',
    'page'
])
</script>

<template>
    <div class="card border-0 shadow-sm">

        <div class="card-body">

            <LoadingSkeleton v-if="loading" variant="table" label="Loading brands" />

            <div
                v-else-if="error"
                class="alert alert-danger"
            >
                {{ error }}
            </div>
            <div
                v-else-if="successMessage"
                class="alert alert-success"
            >
                {{ successMessage }}
            </div>

            <div
                v-else
                class="table-responsive"
            >
                <table class="table table-hover align-middle mb-0">

                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Logo</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="brand in brands"
                            :key="brand.id"
                        >
                            <td>
                                <span class="text-muted">
                                    #{{ brand.id }}
                                </span>
                            </td>
                            <td>
                                <ImageThumbnail
                                    :src="brand.logo"
                                    class="brand-image"
                                    :alt="`${brand.name} logo`"
                                    fallback="bi-tag"
                                />
                            </td>
                            <td>
                                <div class="fw-semibold text-truncate-cell">
                                    {{ brand.name }}
                                </div>
                            </td>
                            <td>
                                <div class="text-truncate-cell">
                                    {{ brand.description || '-' }}
                                </div>
                            </td>
                            <td>
                                <span
                                    class="badge"
                                    :class="
                                        brand.status === 'active'
                                            ? 'badge-active'
                                            : 'badge-inactive'
                                    "
                                >
                                    {{ brand.status }}
                                </span>
                            </td>
                            <td>
                                {{ new Date(brand.created_at).toLocaleDateString('en-GB') }}
                            </td>
                            <td class="text-center">

                                <button
                                    v-if="canUpdate"
                                    class="btn table-action"
                                    @click="$emit('edit', brand)"
                                >
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button
                                    class="btn table-action"
                                    @click="$emit('view', brand.id)"
                                >
                                    <i class="bi bi-eye"></i>
                                </button>

                                <button
                                    v-if="canDelete"
                                    class="btn table-action action-danger"
                                    @click="$emit('delete', brand)"
                                >
                                    <i class="bi bi-trash"></i>
                                </button>

                            </td>

                        </tr>

                        <tr v-if="brands.length === 0">
                            <td
                                colspan="7"
                                class="text-center py-5"
                            >
                                <div class="empty-state">
                                    <i class="bi bi-tags"></i>
                                    <h5>No Brands Found</h5>
                                    <p>
                                        There are no brands matching your search.
                                    </p>
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
                    Showing
                    {{ pagination.from || 0 }}
                    to
                    {{ pagination.to || 0 }}
                    of
                    {{ pagination.total || 0 }}
                    brands
                </small>

                <div class="d-flex align-items-center gap-2">

                    <button
                        class="btn btn-sm btn-outline-secondary"
                        :disabled="pagination.current_page === 1"
                        @click="$emit('page', pagination.current_page - 1)"
                    >
                        Previous
                    </button>

                    <span class="text-muted small">
                        Page
                        {{ pagination.current_page }}
                        of
                        {{ pagination.last_page }}
                    </span>

                    <button
                        class="btn btn-sm btn-outline-secondary"
                        :disabled="
                            pagination.current_page === pagination.last_page
                        "
                        @click="$emit('page', pagination.current_page + 1)"
                    >
                        Next
                    </button>

                </div>

            </div>

        </div>

    </div>
</template>

<style scoped>
.table th {
    font-size: 13px;
    font-weight: 700;
    color: #0f172a;
    white-space: nowrap;
}

.table td {
    font-size: 14px;
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
    color: red;
}

.table-action {
    width: 34px;
    height: 34px;
    padding: 0;
    border-radius: 8px;
    color: #64748b;
}

.table-action:hover {
    background: #f1f5f9;
    color: #0f172a;
}

.action-danger:hover {
    background: #fee2e2;
    color: #dc2626;
}

.text-truncate-cell {
    max-width: 220px;
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
    color: #cbd5e1;
    display: block;
    margin-bottom: 16px;
}

.empty-state h5 {
    font-size: 18px;
    font-weight: 700;
    color: #334155;
}

.empty-state p {
    color: #64748b;
    margin: 0;
}
</style>