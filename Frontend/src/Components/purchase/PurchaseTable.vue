<script setup>
import LoadingSkeleton from '@/Components/LoadingSkeleton.vue'

defineProps({
    purchases: {
        type: Array,
        default: () => []
    },
    loading: {
        type: Boolean,
        default: false
    },
    canEdit: {
        type: Boolean,
        default: false
    },
    canReceive: {
        type: Boolean,
        default: false
    }
})

defineEmits(['view', 'edit', 'receive'])
</script>

<template>
<div class="table-container">

    <LoadingSkeleton v-if="loading" variant="table" :columns="10" label="Loading purchases" />

    <div v-else class="table-responsive">
        <table class="table purchase-table align-middle mb-0">

            <thead>
                <tr>
                    <th>Id</th>
                    <th>PO Number</th>
                    <th>Store</th>
                    <th>Supplier</th>
                    <th class="text-center">Items</th>
                    <th class="text-center">Qty</th>
                    <th class="text-end">Total Cost</th>
                    <th class="text-center">Status</th>
                    <th>Ordered At</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>

            <tbody>

                <tr v-for="item in purchases" :key="item.id">

                        <td>
                            <span class="po-number">#{{ item.id }}</span>
                        </td>

                        <td>
                            <span class="po-number">#{{ item.po_number || '-' }}</span>
                        </td>

                        <td>
                            <span class="table-primary-text">
                                {{ item.store?.name || item.store_name || '-' }}
                            </span>
                        </td>

                        <td>
                            <span class="table-primary-text">
                                {{ item.supplier?.company_name || item.supplier_name || '-' }}
                            </span>
                        </td>

                        <td class="text-center">
                            {{ item.items?.length || 0 }}
                        </td>

                        <td class="text-center">
                            {{ item.total_quantity || item.quantity || 0 }}
                        </td>

                        <td class="text-end">
                            <span class="cost-value">
                                ${{ Number(item.total_cost || item.total || 0).toLocaleString() }}
                            </span>
                        </td>

                        <td class="text-center">
                            <span
                                class="status-badge"
                                :class="{
                                    'badge-pending': item.status === 'pending',
                                    'badge-received': item.status === 'received',
                                    'badge-cancelled': item.status === 'cancelled'
                                }"
                            >
                                {{ item.status || '-' }}
                            </span>
                        </td>

                        <td class="date-value">
                            {{
                                item.ordered_at
                                    ? new Date(item.ordered_at).toLocaleDateString('en-GB')
                                    : '-'
                            }}
                        </td>

                        <td class="text-center">
                            <div class="action-buttons">

                                <!-- No data-bs-toggle here: the page opens the offcanvas after the role check + data load -->
                                <button
                                    class="table-action"
                                    title="View"
                                    type="button"
                                    @click="$emit('view', item.id)"
                                >
                                    <i class="bi bi-eye"></i>
                                </button>

                                <button
                                    v-if="canEdit && item.status === 'pending'"
                                    class="table-action"
                                    title="Edit"
                                    type="button"
                                    @click="$emit('edit', item.id)"
                                >
                                    <i class="bi bi-pencil"></i>
                                </button>

                                <button
                                    v-if="canReceive && item.status === 'pending'"
                                    class="table-action receive-action"
                                    title="Receive"
                                    type="button"
                                    @click="$emit('receive', item.id)"
                                >
                                    <i class="bi bi-box-arrow-in-down"></i>
                                </button>

                            </div>
                        </td>

                </tr>

                <tr v-if="!purchases.length">
                    <td colspan="10" class="empty-table">
                        <div class="empty-state">
                            <i class="bi bi-cart-plus"></i>
                            <h5>No Purchases Found</h5>
                            <p>There are no purchases matching your search.</p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

    </div>

</div>
</template>

<style scoped>
.table-container {
    width: 100%;
    border: 1px solid var(--border-color);
    border-radius: 12px;
    overflow: hidden;
    background: var(--surface-bg);
}

.table-responsive {
    width: 100%;
    overflow-x: auto;
}

.purchase-table {
    width: 100%;
    min-width: 1050px;
    table-layout: fixed;
    margin: 0;
}

.purchase-table thead th {
    height: 48px;
    padding: 0 16px;
    background: var(--surface-muted);
    color: var(--text-color);
    border-bottom: 1px solid var(--border-color);
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
    vertical-align: middle;
}

.purchase-table tbody td {
    height: 62px;
    padding: 0 16px;
    background: var(--surface-bg);
    color: var(--text-color);
    border-bottom: 1px solid var(--border-color);
    font-size: 13px;
    vertical-align: middle;
}

.purchase-table tbody tr:last-child td {
    border-bottom: none;
}

.purchase-table tbody tr:hover td {
    background: var(--surface-muted);
}

.table-primary-text {
    display: block;
    color: var(--text-color);
    font-weight: 600;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.po-number {
    color: var(--muted-text);
    font-weight: 600;
}

.cost-value {
    color: var(--text-color);
    font-weight: 700;
}

.date-value {
    color: var(--muted-text);
}

.status-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 76px;
    height: 26px;
    padding: 0 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    text-transform: capitalize;
}

.badge-pending {
    color: #c2410c;
}

.badge-received {
    color: #15803d;
}

.badge-cancelled {
    color: #b91c1c;
}

.action-buttons {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.table-action {
    width: 32px;
    height: 32px;
    padding: 0;
    border: 0;
    border-radius: 7px;
    background: transparent;
    color: var(--muted-text);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.table-action:hover {
    background: var(--surface-muted);
    color: var(--text-color);
}

.receive-action:hover {
    color: #15803d;
}

.empty-table {
    height: 260px !important;
    text-align: center !important;
    white-space: normal !important;
    background: var(--surface-bg) !important;
}

.empty-state i {
    font-size: 36px;
    color: var(--muted-text);
}

.empty-state h5 {
    color: var(--text-color);
    font-size: 15px;
}

.empty-state p {
    color: var(--muted-text);
    font-size: 13px;
}
</style>