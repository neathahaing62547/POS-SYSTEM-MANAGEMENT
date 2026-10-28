<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { PERMISSIONS } from '@/constants/permissions'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const returns = ref([])
const summary = ref({})
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
const search = ref('')
const loading = ref(false)
const submitting = ref(false)
const error = ref('')
const selectedReturn = ref(null)
const returnOrder = ref(null)
const returnFormOpen = ref(false)
const returnReason = ref('')
const returnQuantities = ref({})

const canCreateReturn = computed(() => auth.can(PERMISSIONS.SALES_RETURNS_CREATE))
const returnableItems = computed(() => {
    if (!returnOrder.value) return []
    const alreadyReturned = new Map()
    for (const saleReturn of returnOrder.value.sales_returns || []) {
        for (const item of saleReturn.items || []) {
            alreadyReturned.set(
                Number(item.product_id),
                (alreadyReturned.get(Number(item.product_id)) || 0) + Number(item.quantity || 0)
            )
        }
    }
    return (returnOrder.value.items || []).map((item) => ({
        ...item,
        returnable_quantity: Math.max(
            0,
            Number(item.quantity) - (alreadyReturned.get(Number(item.product_id)) || 0)
        )
    })).filter((item) => item.returnable_quantity > 0)
})
const selectedRefundTotal = computed(() =>
    returnableItems.value.reduce((sum, item) =>
        sum + Number(item.unit_price) * Number(returnQuantities.value[item.product_id] || 0), 0)
)

function formatMoney(value) {
    return Number(value || 0).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })
}

function formatDate(value) {
    return value ? new Date(value).toLocaleString() : '—'
}

function statusClass(status) {
    return status === 'completed' ? 'text-bg-success' : 'text-bg-secondary'
}

async function loadReturns(page = 1) {
    loading.value = true
    error.value = ''
    try {
        const response = await api.get('/sales-returns', {
            params: { search: search.value.trim() || undefined, page }
        })
        summary.value = response.data.summary
        pagination.value = response.data.data
        returns.value = response.data.data.data
    } catch (requestError) {
        error.value = requestError.response?.data?.message || 'Could not load sales returns.'
    } finally {
        loading.value = false
    }
}

async function openReturnDetails(saleReturn) {
    loading.value = true
    error.value = ''
    try {
        const response = await api.get(`/sales-returns/${saleReturn.id}`)
        selectedReturn.value = response.data.data
    } catch (requestError) {
        error.value = requestError.response?.data?.message || 'Could not load return details.'
    } finally {
        loading.value = false
    }
}

async function loadReturnOrder(orderId) {
    returnOrder.value = null
    returnFormOpen.value = false
    error.value = ''
    if (!orderId) return
    if (!canCreateReturn.value) {
        error.value = 'You do not have permission to create a sales return.'
        return
    }
    loading.value = true
    try {
        const response = await api.get(`/orders/${orderId}/return`)
        const order = response.data.data
        if (!['paid', 'completed'].includes(order.status)) {
            error.value = 'Only paid or completed orders can be returned.'
            return
        }
        returnOrder.value = order
        returnQuantities.value = Object.fromEntries(
            order.items.map((item) => [item.product_id, 0])
        )
        returnReason.value = ''
        returnFormOpen.value = true
    } catch (requestError) {
        error.value = requestError.response?.data?.message || 'Could not load the order for return.'
    } finally {
        loading.value = false
    }
}

async function submitReturn() {
    if (!returnOrder.value || submitting.value) return
    const items = returnableItems.value
        .filter((item) => Number(returnQuantities.value[item.product_id]) > 0)
        .map((item) => ({
            product_id: item.product_id,
            quantity: Number(returnQuantities.value[item.product_id])
        }))

    if (!items.length) {
        error.value = 'Enter a return quantity for at least one item.'
        return
    }

    submitting.value = true
    error.value = ''
    try {
        await api.post(`/orders/${returnOrder.value.id}/return`, {
            items,
            reason: returnReason.value.trim() || null
        })
        returnFormOpen.value = false
        returnOrder.value = null
        await loadReturns()
        await router.replace({ path: '/sales-returns', query: {} })
    } catch (requestError) {
        const validationErrors = requestError.response?.data?.errors
        error.value = validationErrors
            ? Object.values(validationErrors).flat().join(' ')
            : requestError.response?.data?.message || 'Could not create the sales return.'
    } finally {
        submitting.value = false
    }
}

function closeReturnForm() {
    returnFormOpen.value = false
    returnOrder.value = null
    error.value = ''
    if (route.query.order) router.replace({ path: '/sales-returns', query: {} })
}

watch(() => route.query.order, (orderId) => {
    if (orderId) loadReturnOrder(String(orderId))
}, { immediate: true })

onMounted(() => loadReturns())
</script>

<template>
    <main class="returns-page container-fluid py-4 px-3 px-md-4">
        <div class="row g-3 mb-4">
            <div v-for="item in [
                { label: 'Total returns', value: summary.total_returns, icon: 'bi-arrow-counterclockwise', theme: 'total-card' },
                { label: 'Refund recorded', value: summary.total_refunded === undefined ? null : formatMoney(summary.total_refunded), icon: 'bi-cash-coin', theme: 'received-card' },
                { label: 'Completed', value: summary.completed_returns, icon: 'bi-check-circle', theme: 'active-card' },
                { label: 'Items returned', value: summary.total_returned_quantity, icon: 'bi-box-seam', theme: 'inactive-card' }
            ]" :key="item.label" class="col-6 col-xl-3">
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

        <section class="card returns-panel border-0 shadow-sm">
            <div class="card-header d-flex align-items-center justify-content-between gap-3 px-3 py-3">
                <h2 class="h6 mb-0">Return history</h2>
                <span class="text-muted small">{{ pagination.total || 0 }} records</span>
            </div>
            <form class="returns-toolbar d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 p-3 border-bottom"
                role="search" @submit.prevent="loadReturns(1)">
                <div class="input-group return-search">
                    <label class="visually-hidden" for="returns-search">Search returns</label>
                    <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                    <input id="returns-search" v-model="search" class="form-control" type="search"
                        placeholder="Search invoice, reason, amount">
                    <button class="btn btn-outline-secondary" type="submit" :disabled="loading">Search</button>
                </div>
                <router-link v-if="canCreateReturn" class="btn btn-primary" to="/orders">
                    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Start from order
                </router-link>
            </form>
            <div v-if="error && !returnFormOpen" class="alert alert-danger m-3" role="alert">{{ error }}</div>
            <div v-else-if="loading && !returnFormOpen" class="p-4 text-center text-muted" role="status">Loading returns…</div>
            <div v-else-if="!returns.length" class="empty-state">
                <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                <h3>No sales returns found</h3>
                <p>Try a different invoice, reason, or amount.</p>
            </div>
            <div v-else class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Return #</th>
                            <th scope="col">Invoice</th>
                            <th scope="col">Date</th>
                            <th scope="col">Processed by</th>
                            <th scope="col">Items</th>
                            <th scope="col">Refund recorded</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="saleReturn in returns" :key="saleReturn.id">
                            <td class="fw-semibold">#{{ saleReturn.id }}</td>
                            <td>{{ saleReturn.order?.invoice_no || `Order #${saleReturn.order_id}` }}</td>
                            <td class="text-nowrap">{{ formatDate(saleReturn.created_at) }}</td>
                            <td>{{ saleReturn.user?.username || '—' }}</td>
                            <td>{{ saleReturn.items?.reduce((sum, item) => sum + Number(item.quantity || 0), 0) || 0 }}</td>
                            <td class="text-nowrap">{{ formatMoney(saleReturn.total_refunded) }}</td>
                            <td><span class="badge rounded-pill" :class="statusClass(saleReturn.status)">{{ saleReturn.status }}</span></td>
                            <td class="text-end text-nowrap">
                                <button class="btn btn-outline-secondary btn-sm table-action" type="button"
                                    :aria-label="`View return ${saleReturn.id}`" :title="`View return ${saleReturn.id}`"
                                    @click="openReturnDetails(saleReturn)"><i class="bi bi-eye" aria-hidden="true"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <footer v-if="pagination.last_page > 1" class="return-pagination">
                <button class="btn btn-outline-secondary btn-sm" type="button"
                    :disabled="loading || pagination.current_page <= 1"
                    @click="loadReturns(pagination.current_page - 1)">Previous</button>
                <span>Page {{ pagination.current_page }} of {{ pagination.last_page }}</span>
                <button class="btn btn-outline-secondary btn-sm" type="button"
                    :disabled="loading || pagination.current_page >= pagination.last_page"
                    @click="loadReturns(pagination.current_page + 1)">Next</button>
            </footer>
        </section>

        <div v-if="returnFormOpen && returnOrder" class="return-modal-backdrop" role="presentation"
            @click.self="closeReturnForm">
            <section class="return-modal card" role="dialog" aria-modal="true"
                aria-labelledby="return-form-title">
                <header class="card-header d-flex align-items-start justify-content-between gap-3">
                    <div>
                        <small class="text-muted">Original order</small>
                        <h2 id="return-form-title" class="h5 mb-0">{{ returnOrder.invoice_no }}</h2>
                    </div>
                    <button class="btn-close" type="button" aria-label="Close" @click="closeReturnForm"></button>
                </header>
                <div class="card-body">
                    <div v-if="error" class="alert alert-danger" role="alert">{{ error }}</div>
                    <p class="text-muted small">Select the quantities being returned. The refund amount is calculated from the original sale price.</p>
                    <div v-if="!returnableItems.length" class="alert alert-info" role="status">
                        All items in this order have already been returned.
                    </div>
                    <div v-else class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light"><tr><th>Product</th><th>Sold</th><th>Returnable</th><th>Unit price</th><th>Return qty</th></tr></thead>
                            <tbody>
                                <tr v-for="item in returnableItems" :key="item.id">
                                    <td>{{ item.product_name }}</td>
                                    <td>{{ item.quantity }}</td>
                                    <td>{{ item.returnable_quantity }}</td>
                                    <td>{{ formatMoney(item.unit_price) }}</td>
                                    <td>
                                        <input v-model.number="returnQuantities[item.product_id]" class="form-control return-quantity"
                                            type="number" min="0" :max="item.returnable_quantity"
                                            :aria-label="`Return quantity for ${item.product_name}`" :disabled="submitting">
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <label class="form-label w-100 mt-2">
                        Reason (optional)
                        <textarea v-model="returnReason" class="form-control" rows="3" maxlength="2000"
                            placeholder="Enter the reason for this return" :disabled="submitting"></textarea>
                    </label>
                    <div class="return-total">
                        <span>Refund to record</span>
                        <strong>{{ formatMoney(selectedRefundTotal) }}</strong>
                    </div>
                </div>
                <footer class="card-footer d-flex justify-content-end gap-2">
                    <button class="btn btn-outline-secondary" type="button" :disabled="submitting"
                        @click="closeReturnForm">Cancel</button>
                    <button class="btn btn-primary" type="button"
                        :disabled="submitting || !returnableItems.length || selectedRefundTotal <= 0"
                        @click="submitReturn">
                        {{ submitting ? 'Saving…' : 'Record return' }}
                    </button>
                </footer>
            </section>
        </div>

        <div v-if="selectedReturn" class="return-modal-backdrop" role="presentation"
            @click.self="selectedReturn = null">
            <section class="return-modal card" role="dialog" aria-modal="true"
                aria-labelledby="return-detail-title">
                <header class="card-header d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <small class="text-muted">Return details</small>
                        <h2 id="return-detail-title" class="h5 mb-0">Return #{{ selectedReturn.id }}</h2>
                    </div>
                    <button class="btn-close" type="button" aria-label="Close" @click="selectedReturn = null"></button>
                </header>
                <div class="card-body">
                    <div class="return-detail-meta">
                        <span>Invoice <strong>{{ selectedReturn.order?.invoice_no || `Order #${selectedReturn.order_id}` }}</strong></span>
                        <span>Processed by <strong>{{ selectedReturn.user?.username || '—' }}</strong></span>
                        <span>Status <strong>{{ selectedReturn.status }}</strong></span>
                        <span>Date <strong>{{ formatDate(selectedReturn.created_at) }}</strong></span>
                        <span>Reason <strong>{{ selectedReturn.reason || '—' }}</strong></span>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light"><tr><th>Product</th><th>Qty</th><th>Unit refund</th><th>Total</th></tr></thead>
                            <tbody>
                                <tr v-for="item in selectedReturn.items" :key="item.id">
                                    <td>{{ item.product?.name || `Product #${item.product_id}` }}</td>
                                    <td>{{ item.quantity }}</td>
                                    <td>{{ formatMoney(item.refund_price) }}</td>
                                    <td>{{ formatMoney(item.refund_price * item.quantity) }}</td>
                                </tr>
                            </tbody>
                            <tfoot><tr><th colspan="3" class="text-end">Refund recorded</th><th>{{ formatMoney(selectedReturn.total_refunded) }}</th></tr></tfoot>
                        </table>
                    </div>
                </div>
                <footer class="card-footer d-flex justify-content-end">
                    <button class="btn btn-outline-secondary" type="button"
                        @click="selectedReturn = null">Close</button>
                </footer>
            </section>
        </div>
    </main>
</template>

<style scoped>
.returns-page {
    color: var(--ui-text, var(--bs-body-color));
}

.returns-toolbar .form-control,
.return-modal .form-control {
    min-width: 0;
    border-color: var(--ui-border, var(--bs-border-color));
    background: var(--ui-control, var(--bs-body-bg));
    color: var(--ui-text, var(--bs-body-color));
}

.return-search {
    width: min(100%, 420px);
}

.returns-toolbar .input-group-text {
    border-color: var(--ui-border, var(--bs-border-color));
    background: var(--ui-surface, var(--bs-body-bg));
    color: var(--ui-muted, var(--bs-secondary-color));
}

.returns-toolbar .form-control {
    height: 40px;
    border-radius: 0;
}

.returns-toolbar .input-group-text,
.returns-toolbar .btn {
    height: 40px;
}

.returns-toolbar .input-group-text {
    border-radius: .5rem 0 0 .5rem;
}

.returns-toolbar .btn {
    border-radius: 0 .5rem .5rem 0 !important;
}

.returns-toolbar .form-control::placeholder,
.return-modal textarea::placeholder {
    color: var(--ui-muted, var(--bs-secondary-color));
    opacity: 1;
}

.returns-panel,
.return-modal {
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

.returns-panel {
    overflow: hidden;
}

.returns-panel > .card-header,
.return-modal .card-header,
.return-modal .card-footer {
    border-color: var(--ui-border, var(--bs-border-color));
    background: var(--ui-surface-muted, var(--bs-tertiary-bg));
}

.returns-panel .table,
.return-modal .table {
    --bs-table-color: var(--ui-text, var(--bs-body-color));
    --bs-table-bg: transparent;
    --bs-table-border-color: var(--ui-border, var(--bs-border-color));
    --bs-table-hover-color: var(--ui-text, var(--bs-body-color));
    --bs-table-hover-bg: var(--ui-surface-muted, var(--bs-tertiary-bg));
}

.return-pagination {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 1rem;
    padding: .85rem 1rem;
    border-top: 1px solid var(--ui-border, var(--bs-border-color));
    font-size: .85rem;
}

.return-modal-backdrop {
    position: fixed;
    z-index: 1080;
    inset: 0;
    display: grid;
    place-items: center;
    overflow-y: auto;
    padding: 1rem;
    background: rgb(0 0 0 / .55);
}

.return-modal {
    width: min(100%, 850px);
    max-height: calc(100vh - 2rem);
    overflow: auto;
}

.return-quantity {
    width: 95px;
}

.return-total {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding-top: .85rem;
    border-top: 1px solid var(--ui-border, var(--bs-border-color));
}

.return-total strong {
    font-size: 1.1rem;
}

.return-detail-meta {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .75rem 1rem;
    margin-bottom: 1.25rem;
    font-size: .85rem;
}

.return-detail-meta span {
    display: grid;
    gap: .2rem;
    color: var(--ui-muted, var(--bs-secondary-color));
}

.return-detail-meta strong {
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
    .return-search {
        width: 100%;
    }

    .return-detail-meta {
        grid-template-columns: 1fr;
    }
}
</style>
