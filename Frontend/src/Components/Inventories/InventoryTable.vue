```vue
<script setup>
import ImageThumbnail from '@/Components/ImageThumbnail.vue'
import LoadingSkeleton from '@/Components/LoadingSkeleton.vue'


const props = defineProps({
    stocks: {
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
    canHistory: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['page', 'show', 'history'])

const stockBadge = (stock) => {
    if (stock.quantity <= 0) {
        return {
            text: 'Out of stock',
            class: 'badge-out'
        }
    }

    if (stock.quantity <= stock.alert_quantity) {
        return {
            text: 'Low stock',
            class: 'badge-low'
        }
    }

    return {
        text: 'In stock',
        class: 'badge-in'
    }
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

        <LoadingSkeleton v-if="loading" variant="table" label="Loading inventory" />

        <!-- Error -->
        <div
            v-else-if="error"
            class="alert alert-danger"
        >
            {{ error }}
        </div>

        <!-- Table -->
        <div
            v-else
            class="table-responsive"
        >

            <table class="table table-hover align-middle mb-0">

                <thead class="table-light">

                    <tr>
                        <th>ID</th>
                        <th>Product</th>
                        <th>Store</th>
                        <th>Quantity</th>
                        <th>Alert At</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>

                    </tr>

                </thead>

                <tbody>

                    <tr
                        v-for="stock in stocks"
                        :key="stock.id"
                    >

                        <!-- ID -->
                        <td>
                            <span class="text-muted">
                                #{{ stock.id }}
                            </span>
                        </td>

                        <!-- Product -->
                        <td>

                            <div class="d-flex align-items-center gap-2">

                                <ImageThumbnail
                                    :src="stock.product?.image"
                                    class="product-image"
                                    :alt="`${stock.product?.name || 'Product'} image`"
                                    fallback="bi-box-seam"
                                />

                                <div>

                                    <div class="fw-semibold text-truncate-cell">
                                        {{ stock.product?.name || '-' }}
                                    </div>

                                    <div class="text-muted small">
                                        {{ stock.product?.sku || stock.product?.barcode || '-' }}
                                    </div>

                                </div>

                            </div>

                        </td>

                        <!-- Store -->
                        <td>
                            {{ stock.store?.name || '-' }}
                        </td>

                        <!-- Quantity -->
                        <td>
                            <span class="fw-semibold">
                                {{ stock.quantity }}
                            </span>
                        </td>

                        <!-- Alert -->
                        <td>
                            {{ stock.alert_quantity }}
                        </td>

                        <!-- Status -->
                        <td>
                            <span
                                class="badge"
                                :class="stockBadge(stock).class"
                            >
                                {{ stockBadge(stock).text }}
                            </span>

                        </td>

                        <td class="text-center">
                            <button
                                class="btn table-action"
                                title="View inventory"
                                aria-label="View inventory"
                                @click="emit('show', stock)"
                            >
                                <i class="bi bi-eye"></i>
                            </button>
                            <button
                                v-if="canHistory"
                                class="btn table-action"
                                title="Stock history"
                                aria-label="Stock history"
                                @click="emit('history', stock)"
                            >
                                <i class="bi bi-clock-history"></i>
                            </button>
                        </td>

                    </tr>

                    <!-- Empty -->
                    <tr v-if="stocks.length === 0">

                        <td
                            colspan="7"
                            class="text-center py-5"
                        >

                            <div class="empty-state">

                                <i class="bi bi-box-seam"></i>

                                <h5>
                                    No Inventory Found
                                </h5>

                                <p>
                                    There are no inventory records matching your search.
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

                inventory records

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

.badge-in {
    color: green;
}

.badge-low {
    color: #b45309;
}

.badge-out {
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
