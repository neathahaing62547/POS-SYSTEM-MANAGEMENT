<script setup>
import ImageThumbnail from '@/Components/ImageThumbnail.vue'

defineProps({
    products: {
        type: Array,
        default: () => []
    },
    pagination: {
        type: Object,
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
defineEmits(['view', 'edit', 'delete', 'page'])
</script>

<template>
<div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Barcode</th>
                    <th>Category</th>
                    <th>Brand</th>
                    <th>Cost</th>
                    <th>Sale</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="p in products" :key="p.id">
                    <td><span class="text-muted">#{{ p.id }}</span></td>
                    <td>
                        <ImageThumbnail :src="p.image" class="product-image" :alt="`${p.name} image`" fallback="bi-box-seam" />
                    </td>
                    <td>
                        <div class="fw-semibold text-truncate-cell">{{ p.name }}</div>
                        <small class="text-muted">{{ p.unit }}</small>
                    </td>
                    <td>{{ p.sku || '-' }}</td>
                    <td>{{ p.barcode || '-' }}</td>
                    <td>{{ p.category?.name || '-' }}</td>
                    <td>{{ p.brand?.name || '-' }}</td>
                    <td>${{ p.cost_price }}</td>
                    <td class="fw-semibold">${{ p.sale_price }}</td>
                    <td :class="(p.inventory_stocks_sum_quantity ?? 0) <= 10 ? 'text-danger fw-bold' : 'text-success fw-semibold'">
                        {{ p.inventory_stocks_sum_quantity ?? 0 }}
                    </td>
                    <td>
                        <span class="badge" :class="p.status === 'active' ? 'badge-active' : 'badge-inactive'">
                            {{ p.status }}
                        </span>
                    </td>
                    <td>{{ new Date(p.created_at).toLocaleDateString('en-GB') }}</td>
                    <td class="text-center">
                        <button v-if="canUpdate" class="btn table-action" @click="$emit('edit', p)">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn table-action" @click="$emit('view', p.id)">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button v-if="canDelete" class="btn table-action action-danger" @click="$emit('delete', p)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>

                <tr v-if="products.length === 0">
                    <td colspan="13" class="text-center py-5">
                        <div class="empty-state">
                            <i class="bi bi-box-seam"></i>
                            <h5>No Products Found</h5>
                            <p>There are no products matching your search.</p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <!-- Pagination -->
    <div v-if="pagination" class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 mt-3">
        <small class="text-muted">
            Showing {{ pagination.from || 0 }} to {{ pagination.to || 0 }} of {{ pagination.total || 0 }} products
        </small>
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm btn-outline-secondary" :disabled="pagination.current_page === 1" @click="$emit('page', pagination.current_page - 1)">Previous</button>
            <span class="text-muted small">Page {{ pagination.current_page }} of {{ pagination.last_page }}</span>
            <button class="btn btn-sm btn-outline-secondary" :disabled="pagination.current_page === pagination.last_page" @click="$emit('page', pagination.current_page + 1)">Next</button>
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
    max-width: 180px;
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
