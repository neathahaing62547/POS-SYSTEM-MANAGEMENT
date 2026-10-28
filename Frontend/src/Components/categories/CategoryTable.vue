
<script setup>
import ImageThumbnail from '@/Components/ImageThumbnail.vue'
import LoadingSkeleton from '@/Components/LoadingSkeleton.vue'

const props = defineProps({
    categories: {
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

    error: {
        type: String,
        default: null
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
    'view',
    'edit',
    'delete',
    'page'
])

const formatDate = (date) => {
    if (!date) {
        return '-'
    }

    return new Date(date).toLocaleDateString('en-GB')
}

const changePage = (page) => {
    if (!page || page === props.pagination?.current_page) {
        return
    }

    emit('page', page)
}
</script>

<template>
    <div>

        <LoadingSkeleton v-if="loading" variant="table" label="Loading categories" />

        <div
            v-else-if="error"
            class="alert alert-danger"
        >
            {{ error }}
        </div>

        <div
            v-else
            class="table-responsive"
        >
            <table class="table table-hover align-middle mb-0">

                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="text-center">
                            Action
                        </th>
                    </tr>
                </thead>

                <tbody>

                    <tr
                        v-for="category in categories"
                        :key="category.id"
                    >

                        <td>
                            <span class="text-muted">
                                #{{ category.id }}
                            </span>
                        </td>

                        <td>
                            <ImageThumbnail
                                :src="category.image"
                                class="category-image"
                                :alt="`${category.name} image`"
                                fallback="bi-grid"
                            />
                        </td>

                        <td>
                            <div class="fw-semibold text-truncate-cell">
                                {{ category.name }}
                            </div>
                        </td>

                        <td>
                            <div class="text-truncate-cell">
                                {{ category.description || '-' }}
                            </div>
                        </td>

                        <td>
                            <span
                                class="badge"
                                :class="
                                    category.status === 'active'
                                        ? 'badge-active'
                                        : 'badge-inactive'
                                "
                            >
                                {{ category.status }}
                            </span>
                        </td>

                        <td>
                            {{ formatDate(category.created_at) }}
                        </td>

                        <td class="text-center">

                            <button
                                class="btn table-action"
                                @click="emit('view', category)"
                            >
                                <i class="bi bi-eye"></i>
                            </button>

                            <button
                                v-if="canUpdate"
                                class="btn table-action"
                                @click="emit('edit', category)"
                            >
                                <i class="bi bi-pencil"></i>
                            </button>

                            <button
                                v-if="canDelete"
                                class="btn table-action action-danger"
                                @click="emit('delete', category)"
                            >
                                <i class="bi bi-trash"></i>
                            </button>

                        </td>
                    </tr>

                    <tr v-if="categories.length === 0">

                        <td
                            colspan="7"
                            class="text-center py-5"
                        >
                            <div class="empty-state">

                                <i class="bi bi-tags"></i>

                                <h5>
                                    No Categories Found
                                </h5>

                                <p>
                                    There are no categories matching your search.
                                </p>

                            </div>
                        </td>

                    </tr>

                </tbody>
            </table>
        </div>

        <!-- Pagination -->

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
                categories
            </small>

            <div class="d-flex align-items-center gap-2">

                <button
                    class="btn btn-sm btn-outline-secondary"
                    :disabled="pagination.current_page === 1"
                    @click="
                        changePage(
                            pagination.current_page - 1
                        )
                    "
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
                    @click="
                        changePage(
                            pagination.current_page + 1
                        )
                    "
                >
                    Next
                </button>

            </div>
        </div>

    </div>
</template>

<style scoped>
.text-truncate-cell {
    max-width: 220px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
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
