<script setup>
import { onMounted, ref } from 'vue'
import api from '@/services/api'

const payments = ref([])
const summary = ref({})
const search = ref('')
const loading = ref(false)
const error = ref('')

async function loadPayments() {
    loading.value = true
    error.value = ''
    try {
        const response = await api.get('/payments', {
            params: { search: search.value.trim() || undefined }
        })
        payments.value = response.data.data
        summary.value = response.data.summary
    } catch (requestError) {
        error.value = requestError.response?.data?.message || 'Could not load payments.'
    } finally {
        loading.value = false
    }
}

function formatMoney(amount) {
    return Number(amount || 0).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })
}

function formatDate(value) {
    return value ? new Date(value).toLocaleString() : '—'
}

function statusClass(status) {
    if (status === 'paid') return 'text-bg-success'
    if (status === 'pending') return 'text-bg-warning'
    return 'text-bg-secondary'
}

onMounted(() => loadPayments())
</script>

<template>
    <main class="payment-page container-fluid py-4 px-3 px-md-4">
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg">
                <section class="card summary-card border-0 shadow-sm h-100 total-card">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div><small class="text-muted">Total payments</small><h4 class="fw-bold mb-0 mt-2">{{ summary.payment_count ?? '—' }}</h4></div>
                        <div class="icon-box" aria-hidden="true"><i class="bi bi-receipt"></i></div>
                    </div>
                </section>
            </div>
            <div class="col-6 col-lg">
                <section class="card summary-card border-0 shadow-sm h-100 received-card">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div><small class="text-muted">Total amount</small><h4 class="fw-bold mb-0 mt-2">{{ summary.total_amount === undefined ? '—' : formatMoney(summary.total_amount) }}</h4></div>
                        <div class="icon-box" aria-hidden="true"><i class="bi bi-cash-coin"></i></div>
                    </div>
                </section>
            </div>
            <div class="col-6 col-lg">
                <section class="card summary-card border-0 shadow-sm h-100 active-card">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div><small class="text-muted">Cash payments</small><h4 class="fw-bold mb-0 mt-2">{{ summary.cash_payments ?? '—' }}</h4></div>
                        <div class="icon-box" aria-hidden="true"><i class="bi bi-wallet2"></i></div>
                    </div>
                </section>
            </div>
            <div class="col-6 col-lg">
                <section class="card summary-card border-0 shadow-sm h-100 info-card">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div><small class="text-muted">ABA payments</small><h4 class="fw-bold mb-0 mt-2">{{ summary.aba_payments ?? '—' }}</h4></div>
                        <div class="icon-box" aria-hidden="true"><i class="bi bi-credit-card"></i></div>
                    </div>
                </section>
            </div>
            <div class="col-6 col-lg">
                <section class="card summary-card border-0 shadow-sm h-100 pending-card">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div><small class="text-muted">Pending payments</small><h4 class="fw-bold mb-0 mt-2">{{ summary.pending_payments ?? '—' }}</h4></div>
                        <div class="icon-box" aria-hidden="true"><i class="bi bi-hourglass-split"></i></div>
                    </div>
                </section>
            </div>
        </div>

        <section class="card payment-panel border-0 shadow-sm">
            <div class="card-header d-flex align-items-center justify-content-between gap-3 px-3 py-3">
                <h2 class="h6 mb-0">Payment history</h2>
                <span class="text-muted small">{{ payments.length }} records</span>
            </div>
            <form class="payment-toolbar d-flex align-items-center justify-content-between gap-2 p-3 border-bottom"
                role="search" @submit.prevent="loadPayments">
                <div class="input-group payment-search-form">
                    <label class="visually-hidden" for="payments-search">Search payments</label>
                    <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                    <input id="payments-search" v-model="search" class="form-control" type="search"
                        placeholder="Search transaction, method, amount, status">
                    <button class="btn btn-outline-secondary" type="submit" :disabled="loading">Search</button>
                </div>
            </form>
            <div v-if="error" class="alert alert-danger m-3" role="alert">{{ error }}</div>
            <div v-else-if="loading" class="p-4 text-center text-muted" role="status">Loading payments…</div>
            <div v-else-if="!payments.length" class="empty-state">
                <i class="bi bi-credit-card" aria-hidden="true"></i>
                <h3>No payments found</h3>
                <p>Try a different transaction, method, amount, or status.</p>
            </div>
            <div v-else class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Invoice</th>
                            <th scope="col">Transaction</th>
                            <th scope="col">Cashier</th>
                            <th scope="col">Method</th>
                            <th scope="col">Amount</th>
                            <th scope="col">Status</th>
                            <th scope="col">Paid at</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="payment in payments" :key="payment.id">
                            <td class="fw-semibold">{{ payment.order?.invoice_no || '—' }}</td>
                            <td>{{ payment.transaction_id || '—' }}</td>
                            <td>{{ payment.order?.cashier?.username || '—' }}</td>
                            <td>{{ payment.payment_method || '—' }}</td>
                            <td class="text-nowrap">{{ formatMoney(payment.amount) }}</td>
                            <td>
                                <span class="badge rounded-pill" :class="statusClass(payment.status)">
                                    {{ payment.status }}
                                </span>
                            </td>
                            <td class="text-nowrap">{{ formatDate(payment.paid_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</template>

<style scoped>
.payment-page {
    color: var(--ui-text, var(--bs-body-color));
}

.payment-search-form {
    width: min(100%, 460px);
}

.payment-search-form .form-control,
.payment-search-form .input-group-text {
    height: 40px;
    min-width: 0;
    border-color: var(--ui-border, var(--bs-border-color));
    background: var(--ui-control, var(--bs-body-bg));
    color: var(--ui-text, var(--bs-body-color));
}

.payment-search-form .input-group-text {
    border-radius: .5rem 0 0 .5rem;
    color: var(--ui-muted, var(--bs-secondary-color));
}

.payment-search-form .form-control {
    border-radius: 0;
}

.payment-search-form .btn {
    height: 40px;
    border-radius: 0 .5rem .5rem 0 !important;
}

.payment-search-form .form-control::placeholder {
    color: var(--ui-muted, var(--bs-secondary-color));
    opacity: 1;
}

.payment-panel {
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
    background: var(--ui-primary-soft, var(--bs-primary-bg-subtle));
    color: var(--ui-primary, var(--bs-primary));
    font-size: 1.1rem;
}

.payment-panel {
    overflow: hidden;
}

.payment-panel > .card-header {
    border-color: var(--ui-border, var(--bs-border-color));
    background: var(--ui-surface-muted, var(--bs-tertiary-bg));
}

.payment-panel .table {
    --bs-table-color: var(--ui-text, var(--bs-body-color));
    --bs-table-bg: transparent;
    --bs-table-border-color: var(--ui-border, var(--bs-border-color));
    --bs-table-hover-color: var(--ui-text, var(--bs-body-color));
    --bs-table-hover-bg: var(--ui-surface-muted, var(--bs-tertiary-bg));
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
    .payment-toolbar {
        align-items: stretch !important;
    }

    .payment-search-form {
        width: 100%;
    }
}
</style>
