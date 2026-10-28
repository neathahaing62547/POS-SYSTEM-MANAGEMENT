<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { PERMISSIONS } from '@/constants/permissions'

const router = useRouter()
const auth = useAuthStore()
const orders = ref([])
const summary = ref({})
const search = ref('')
const appliedSearch = ref('')
const statusFilter = ref('')
const loading = ref(false)
const error = ref('')
const selectedOrder = ref(null)
const updatingOrderId = ref(null)
const statusDrafts = ref({})

const canUpdateOrders = computed(() => auth.can(PERMISSIONS.ORDERS_UPDATE))
const canCreateReturn = computed(() =>
    auth.can(PERMISSIONS.SALES_RETURNS_CREATE) &&
    auth.can(PERMISSIONS.SALES_RETURN_DETAILS_VIEW)
)
const filteredOrders = computed(() => {
    const term = appliedSearch.value.toLowerCase()
    return orders.value.filter((order) => {
        const matchesTerm = !term ||
            `${order.invoice_no} ${order.cashier?.full_name || ''} ${order.cashier?.username || ''}`
                .toLowerCase().includes(term)
        return matchesTerm && (!statusFilter.value || order.status === statusFilter.value)
    })
})

function statusClass(status) {
    if (status === 'paid' || status === 'completed') return 'text-bg-success'
    if (status === 'pending' || status === 'processing') return 'text-bg-warning'
    return 'text-bg-secondary'
}

function formatMoney(value) {
    return Number(value || 0).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })
}

function formatDate(value) {
    return value ? new Date(value).toLocaleString() : '—'
}

async function loadOrders() {
    loading.value = true
    error.value = ''
    try {
        const response = await api.get('/pos/orders', {
            params: { search: appliedSearch.value || undefined }
        })
        orders.value = response.data.data
        summary.value = response.data.summary
        statusDrafts.value = Object.fromEntries(
            orders.value.map((order) => [order.id, order.status])
        )
    } catch (requestError) {
        error.value = requestError.response?.data?.message || 'Could not load orders.'
    } finally {
        loading.value = false
    }
}

function searchOrders() {
    appliedSearch.value = search.value.trim()
    loadOrders()
}

async function updateStatus(order) {
    const status = statusDrafts.value[order.id]
    if (!status || status === order.status || updatingOrderId.value) return
    updatingOrderId.value = order.id
    error.value = ''
    try {
        await api.patch(`/pos/orders/${order.id}/status`, { status })
        await loadOrders()
        if (selectedOrder.value?.id === order.id) {
            selectedOrder.value = orders.value.find((item) => item.id === order.id) || null
        }
    } catch (requestError) {
        error.value = requestError.response?.data?.message || 'Could not update the order status.'
    } finally {
        updatingOrderId.value = null
    }
}

function startReturn(order) {
    router.push({ path: '/sales-returns', query: { order: String(order.id) } })
}

onMounted(loadOrders)
</script>

<template>
    <main class="orders-page container-fluid py-4 px-3 px-md-4">
        <div class="row g-3 mb-4">
            <div v-for="item in [
                { label: 'All orders', value: summary.total, icon: 'bi-receipt', theme: 'total-card' },
                { label: 'Pending', value: summary.pending, icon: 'bi-hourglass-split', theme: 'pending-card' },
                { label: 'Paid', value: summary.paid, icon: 'bi-credit-card', theme: 'received-card' },
                { label: 'Completed', value: summary.completed, icon: 'bi-check-circle', theme: 'active-card' },
                { label: 'Cancelled', value: summary.cancelled, icon: 'bi-x-circle', theme: 'inactive-card' }
            ]" :key="item.label" class="col-6 col-lg">
                <section class="card summary-card border-0 shadow-sm h-100" :class="item.theme">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <small class="text-muted">{{ item.label }}</small>
                            <h4 class="fw-bold mb-0 mt-2">{{ item.value ?? 0 }}</h4>
                        </div>
                        <div class="icon-box" aria-hidden="true">
                            <i class="bi" :class="item.icon"></i>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <section class="card order-panel border-0 shadow-sm">
            <div class="card-header d-flex align-items-center justify-content-between gap-3 px-3 py-3">
                <h2 class="h6 mb-0">Order history</h2>
                <span class="text-muted small">{{ filteredOrders.length }} shown</span>
            </div>
            <form class="orders-toolbar d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 p-3 border-bottom"
                role="search" @submit.prevent="searchOrders">
                <div class="input-group order-search">
                    <label class="visually-hidden" for="orders-search">Search orders</label>
                    <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                    <input id="orders-search" v-model="search" class="form-control" type="search"
                        placeholder="Search invoice or cashier">
                    <button class="btn btn-outline-secondary" type="submit" :disabled="loading">Search</button>
                </div>
                <div class="order-status-filter">
                    <label class="visually-hidden" for="orders-status">Filter by status</label>
                    <select id="orders-status" v-model="statusFilter" class="form-select">
                        <option value="">All statuses</option>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="paid">Paid</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </form>
            <div v-if="error" class="alert alert-danger m-3" role="alert">{{ error }}</div>
            <div v-else-if="loading" class="p-4 text-center text-muted" role="status">Loading orders…</div>
            <div v-else-if="!filteredOrders.length" class="empty-state">
                <i class="bi bi-receipt" aria-hidden="true"></i>
                <h3>No orders found</h3>
                <p>Try changing your search or status filter.</p>
            </div>
            <div v-else class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Invoice</th>
                            <th scope="col">Date</th>
                            <th scope="col">Cashier</th>
                            <th scope="col">Items</th>
                            <th scope="col">Payment</th>
                            <th scope="col">Total</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="order in filteredOrders" :key="order.id">
                            <td class="fw-semibold">{{ order.invoice_no }}</td>
                            <td class="text-nowrap">{{ formatDate(order.created_at) }}</td>
                            <td>{{ order.cashier?.full_name || order.cashier?.username || '—' }}</td>
                            <td>{{ order.items?.reduce((sum, item) => sum + Number(item.quantity || 0), 0) || 0 }}</td>
                            <td>
                                <span>{{ order.payment?.payment_method || '—' }}</span>
                                <small v-if="order.payment?.status" class="d-block text-muted">
                                    {{ order.payment.status }}
                                </small>
                            </td>
                            <td class="text-nowrap">{{ formatMoney(order.grand_total) }}</td>
                            <td><span class="badge rounded-pill" :class="statusClass(order.status)">{{ order.status }}</span></td>
                            <td class="text-end text-nowrap">
                                <button class="btn btn-outline-secondary btn-sm table-action" type="button"
                                    :aria-label="`View order ${order.invoice_no}`" :title="`View order ${order.invoice_no}`"
                                    @click="selectedOrder = order"><i class="bi bi-eye" aria-hidden="true"></i></button>
                                <button v-if="canCreateReturn && ['paid', 'completed'].includes(order.status)"
                                    class="btn btn-outline-primary btn-sm table-action" type="button"
                                    :aria-label="`Start return for ${order.invoice_no}`" title="Start return"
                                    @click="startReturn(order)"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <div v-if="selectedOrder" class="order-modal-backdrop" role="presentation"
            @click.self="selectedOrder = null">
            <section class="order-modal card" role="dialog" aria-modal="true"
                aria-labelledby="order-detail-title">
                <header class="card-header d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <small class="text-muted">Order details</small>
                        <h2 id="order-detail-title" class="h5 mb-0">{{ selectedOrder.invoice_no }}</h2>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge rounded-pill" :class="statusClass(selectedOrder.status)">
                            {{ selectedOrder.status }}
                        </span>
                        <button class="btn-close" type="button" aria-label="Close"
                            @click="selectedOrder = null"></button>
                    </div>
                </header>
                <div class="card-body">
                    <div v-if="error" class="alert alert-danger py-2" role="alert">{{ error }}</div>
                    <div class="order-detail-meta">
                        <span>Cashier <strong>{{ selectedOrder.cashier?.full_name || selectedOrder.cashier?.username || '—' }}</strong></span>
                        <span>Payment <strong>{{ selectedOrder.payment?.payment_method || '—' }}{{ selectedOrder.payment?.status ? ` · ${selectedOrder.payment.status}` : '' }}</strong></span>
                        <span>Date <strong>{{ formatDate(selectedOrder.created_at) }}</strong></span>
                        <span>Items <strong>{{ selectedOrder.items?.reduce((sum, item) => sum + Number(item.quantity || 0), 0) || 0 }}</strong></span>
                    </div>
                    <div v-if="canUpdateOrders" class="order-status-control">
                        <label class="form-label mb-0" :for="`order-status-${selectedOrder.id}`">Update order status</label>
                        <div class="d-flex gap-2">
                            <select :id="`order-status-${selectedOrder.id}`" v-model="statusDrafts[selectedOrder.id]"
                                class="form-select form-select-sm">
                                <option v-if="selectedOrder.status === 'paid'" value="paid" disabled>Paid</option>
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <button class="btn btn-outline-primary btn-sm" type="button"
                                :disabled="updatingOrderId === selectedOrder.id || statusDrafts[selectedOrder.id] === selectedOrder.status"
                                @click="updateStatus(selectedOrder)">
                                {{ updatingOrderId === selectedOrder.id ? 'Saving…' : 'Save status' }}
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light"><tr><th>Product</th><th>Qty</th><th>Unit price</th><th>Total</th></tr></thead>
                            <tbody>
                                <tr v-for="item in selectedOrder.items" :key="item.id">
                                    <td>{{ item.product_name }}</td>
                                    <td>{{ item.quantity }}</td>
                                    <td>{{ formatMoney(item.unit_price) }}</td>
                                    <td>{{ formatMoney(item.total_price) }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr><th colspan="3" class="text-end">Grand total</th><th>{{ formatMoney(selectedOrder.grand_total) }}</th></tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <footer class="card-footer d-flex justify-content-end gap-2">
                    <button v-if="canCreateReturn && ['paid', 'completed'].includes(selectedOrder.status)"
                        class="btn btn-primary" type="button" @click="startReturn(selectedOrder)">Create return</button>
                    <button class="btn btn-outline-secondary" type="button"
                        @click="selectedOrder = null">Close</button>
                </footer>
            </section>
        </div>
    </main>
</template>

<style scoped>
.orders-page {
    color: var(--ui-text, var(--bs-body-color));
}

.orders-toolbar .form-control,
.orders-toolbar .form-select,
.order-status-control .form-select {
    min-width: 0;
    border-color: var(--ui-border, var(--bs-border-color));
    background-color: var(--ui-control, var(--bs-body-bg));
    color: var(--ui-text, var(--bs-body-color));
}

.order-search {
    width: min(100%, 420px);
}

.orders-toolbar .input-group-text {
    border-color: var(--ui-border, var(--bs-border-color));
    background: var(--ui-surface, var(--bs-body-bg));
    color: var(--ui-muted, var(--bs-secondary-color));
}

.orders-toolbar .form-control,
.orders-toolbar .form-select {
    height: 40px;
    border-radius: .5rem;
}

.order-search .input-group-text,
.order-search .form-control,
.order-search .btn {
    height: 40px;
}

.order-search .input-group-text {
    border-radius: .5rem 0 0 .5rem;
}

.order-search .form-control {
    border-radius: 0;
}

.order-search .btn {
    border-radius: 0 .5rem .5rem 0 !important;
}

.order-status-filter {
    width: min(100%, 190px);
}

.order-panel,
.order-modal {
    border-color: var(--ui-border, var(--bs-border-color));
    background: var(--ui-surface, var(--bs-body-bg));
    color: var(--ui-text, var(--bs-body-color));
}

.summary-card .icon-box {
    display: flex;
    width: 2.35rem;
    height: 2.35rem;
    flex: 0 0 auto;
    align-items: center;
    justify-content: center;
    border-radius: .65rem;
    font-size: 1.1rem;
}

.order-panel {
    overflow: hidden;
}

.order-panel > .card-header,
.order-modal .card-header,
.order-modal .card-footer {
    border-color: var(--ui-border, var(--bs-border-color));
    background: var(--ui-surface-muted, var(--bs-tertiary-bg));
}

.order-panel .table,
.order-modal .table {
    --bs-table-color: var(--ui-text, var(--bs-body-color));
    --bs-table-bg: transparent;
    --bs-table-border-color: var(--ui-border, var(--bs-border-color));
    --bs-table-hover-color: var(--ui-text, var(--bs-body-color));
    --bs-table-hover-bg: var(--ui-surface-muted, var(--bs-tertiary-bg));
}

.order-status-control {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.25rem;
    padding: .9rem 1rem;
    border: 1px solid var(--ui-border, var(--bs-border-color));
    border-radius: .65rem;
    background: var(--ui-surface-muted, var(--bs-tertiary-bg));
}

.order-status-control .form-select {
    width: 150px;
    border-color: var(--ui-border, var(--bs-border-color));
    background-color: var(--ui-control, var(--bs-body-bg));
    color: var(--ui-text, var(--bs-body-color));
}

.orders-page .btn-outline-primary {
    --bs-btn-color: #2563eb;
    --bs-btn-border-color: #3b82f6;
    --bs-btn-hover-color: #fff;
    --bs-btn-hover-bg: #2563eb;
    --bs-btn-hover-border-color: #2563eb;
}

.dark-theme .orders-page .btn-outline-primary {
    --bs-btn-color: #bfdbfe;
    --bs-btn-border-color: #60a5fa;
    --bs-btn-hover-color: #fff;
    --bs-btn-hover-bg: #1d4ed8;
    --bs-btn-hover-border-color: #60a5fa;
}

.order-modal-backdrop {
    position: fixed;
    z-index: 1080;
    inset: 0;
    display: grid;
    place-items: center;
    overflow-y: auto;
    padding: 1rem;
    background: rgb(0 0 0 / .55);
}

.order-modal {
    width: min(100%, 760px);
    max-height: calc(100vh - 2rem);
    overflow: auto;
}

.order-detail-meta {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .75rem 1rem;
    margin-bottom: 1.25rem;
    font-size: .85rem;
}

.order-detail-meta span {
    display: grid;
    gap: .2rem;
    color: var(--ui-muted, var(--bs-secondary-color));
}

.order-detail-meta strong {
    color: var(--ui-text, var(--bs-body-color));
}

.empty-state {
    padding: 3rem 1rem;
    color: var(--ui-muted, var(--bs-secondary-color));
    text-align: center;
}

.empty-state i {
    display: block;
    margin-bottom: .75rem;
    font-size: 2.5rem;
    opacity: .55;
}

.empty-state h3 {
    margin-bottom: .35rem;
    color: var(--ui-text, var(--bs-body-color));
    font-size: 1rem;
    font-weight: 700;
}

.empty-state p {
    margin: 0;
    font-size: .875rem;
}

@media (max-width: 767.98px) {
    .order-search,
    .order-status-filter {
        width: 100%;
    }

    .order-search .btn {
        padding-inline: .7rem;
    }

    .order-detail-meta {
        grid-template-columns: 1fr;
    }

    .order-status-control {
        grid-template-columns: 1fr;
    }

    .order-status-control > div {
        width: 100%;
    }
}
</style>
