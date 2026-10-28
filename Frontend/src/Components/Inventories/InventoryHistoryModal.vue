<script setup>
import LoadingSkeleton from '@/Components/LoadingSkeleton.vue'

defineProps({
    history: { type: Array, default: () => [] },
    pagination: { type: Object, default: null },
    productName: { type: String, default: '' },
    loading: Boolean,
    error: { type: String, default: '' }
})

const emit = defineEmits(['close', 'page'])
</script>

<template>
    <div class="modal-backdrop" @click.self="$emit('close')">
        <section class="modal-box" role="dialog" aria-modal="true" aria-labelledby="inventory-history-title">
            <header class="modal-header">
                <div>
                    <h5 id="inventory-history-title">Stock History</h5>
                    <small v-if="productName" class="text-muted">{{ productName }}</small>
                </div>
                <button type="button" aria-label="Close" @click="$emit('close')">
                    <i class="bi bi-x-lg"></i>
                </button>
            </header>

            <div class="modal-body">
                <LoadingSkeleton v-if="loading" variant="spinner" label="Loading stock history" />
                <div v-else-if="error" class="alert alert-danger mb-0">{{ error }}</div>
                <div v-else-if="history.length === 0" class="text-center text-muted py-4">
                    No stock history found for this product.
                </div>
                <div v-else class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Change</th>
                                <th>Type</th>
                                <th>Store</th>
                                <th>User</th>
                                <th>Reason / Reference</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="entry in history" :key="entry.id">
                                <td>{{ entry.created_at ? new Date(entry.created_at).toLocaleString() : '-' }}</td>
                                <td>{{ entry.quantity_changed }}</td>
                                <td>{{ entry.type || '-' }}</td>
                                <td>{{ entry.store?.name || '-' }}</td>
                                <td>{{ entry.user?.name || '-' }}</td>
                                <td>{{ [entry.reason, entry.reference_no].filter(Boolean).join(' / ') || '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <footer class="modal-footer">
                <small v-if="pagination" class="text-muted me-auto">
                    Page {{ pagination.current_page }} of {{ pagination.last_page }}
                </small>
                <button
                    v-if="pagination"
                    type="button"
                    class="btn btn-outline-secondary"
                    :disabled="loading || pagination.current_page <= 1"
                    @click="emit('page', pagination.current_page - 1)"
                >
                    Previous
                </button>
                <button
                    v-if="pagination"
                    type="button"
                    class="btn btn-outline-secondary"
                    :disabled="loading || pagination.current_page >= pagination.last_page"
                    @click="emit('page', pagination.current_page + 1)"
                >
                    Next
                </button>
                <button type="button" class="btn btn-secondary" @click="$emit('close')">Close</button>
            </footer>
        </section>
    </div>
</template>

<style scoped>
.modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 1050;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgba(0, 0, 0, .45);
}

.modal-box {
    display: flex;
    flex-direction: column;
    width: 100%;
    max-width: 1000px;
    max-height: 90vh;
    overflow: hidden;
    color: var(--text-color, #0f172a);
    background: var(--surface-bg, #fff);
    border-radius: 12px;
}

.modal-header,
.modal-footer {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 16px 20px;
    border-bottom: 1px solid var(--border-color, #e5e7eb);
}

.modal-header {
    justify-content: space-between;
}

.modal-header h5 {
    margin: 0;
}

.modal-header button {
    border: 0;
    color: inherit;
    background: transparent;
}

.modal-body {
    overflow: auto;
    padding: 20px;
}

.modal-footer {
    justify-content: flex-end;
    border-top: 1px solid var(--border-color, #e5e7eb);
    border-bottom: 0;
}

@media (max-width: 700px) {
    .modal-box {
        max-height: 95vh;
    }
}
</style>
