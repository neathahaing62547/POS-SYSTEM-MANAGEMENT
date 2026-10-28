<script setup>
import { computed, onMounted, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useCashRegisterStore } from '@/stores/CashRegister'
import { PERMISSIONS } from '@/constants/permissions'
import LoadingSkeleton from '@/Components/LoadingSkeleton.vue'

const auth = useAuthStore()
const cashStore = useCashRegisterStore()

const canCashIn = computed(() => auth.can(PERMISSIONS.CASH_REGISTER_CASH_IN))
const canCashOut = computed(() => auth.can(PERMISSIONS.CASH_REGISTER_CASH_OUT))
const hasOpenShift = computed(() => Boolean(cashStore.currentShift?.id && cashStore.currentShift?.status === 'open'))
const canRecord = computed(() => hasOpenShift.value && (canCashIn.value || canCashOut.value))

const filters = ref({ type: '', from: '', to: '' })
const formOpen = ref(false)
const formType = ref('cash_in')
const form = ref({ amount: '', reason: '' })
const formError = ref('')
const formSaving = ref(false)
const pageError = ref('')
const successMessage = ref('')

const formatAmount = (amount) => Number(amount || 0).toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
})

const loadTransactions = async (page = 1) => {
    pageError.value = ''
    try {
        await cashStore.getTransactions({
            ...filters.value,
            page
        })
    } catch (error) {
        const validationErrors = error.response?.data?.errors
        pageError.value = validationErrors
            ? Object.values(validationErrors).flat().join(' ')
            : cashStore.error || error.message || 'Failed to load cash transactions.'
    }
}

const loadPage = async () => {
    pageError.value = ''
    const results = await Promise.allSettled([
        cashStore.getCurrentShift(),
        loadTransactions()
    ])
    const failed = results.find(result => result.status === 'rejected')
    if (failed && !pageError.value) {
        pageError.value = cashStore.error || failed.reason?.message || 'Failed to load cash transaction data.'
    }
}

const applyFilters = () => loadTransactions(1)

const openForm = (type) => {
    if (!canRecord.value || (type === 'cash_in' && !canCashIn.value) || (type === 'cash_out' && !canCashOut.value)) return
    formType.value = type
    form.value = { amount: '', reason: '' }
    formError.value = ''
    formOpen.value = true
}

const submitTransaction = async () => {
    if (formSaving.value || !canRecord.value) return
    if ((formType.value === 'cash_in' && !canCashIn.value) || (formType.value === 'cash_out' && !canCashOut.value)) return

    formSaving.value = true
    formError.value = ''
    try {
        const payload = {
            amount: Number(form.value.amount),
            reason: form.value.reason.trim()
        }
        if (formType.value === 'cash_in') await cashStore.recordCashIn(payload)
        else await cashStore.recordCashOut(payload)
        formOpen.value = false
        successMessage.value = `${formType.value === 'cash_in' ? 'Cash-in' : 'Cash-out'} recorded successfully.`
        await loadTransactions(cashStore.transactionPagination?.current_page || 1)
    } catch (error) {
        const validationErrors = error.response?.data?.errors
        formError.value = validationErrors
            ? Object.values(validationErrors).flat().join(' ')
            : cashStore.error || error.response?.data?.message || 'Failed to record cash transaction.'
    } finally {
        formSaving.value = false
    }
}

const changePage = (page) => {
    const pagination = cashStore.transactionPagination
    if (!pagination || page < 1 || page > pagination.last_page) return
    loadTransactions(page)
}

onMounted(loadPage)
</script>

<template>
    <div class="container-fluid py-4 px-3 px-md-4">
        <div v-if="pageError" class="alert alert-danger">{{ pageError }}</div>
        <div v-if="successMessage" class="alert alert-success">{{ successMessage }}</div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-4">
                <section class="card summary-card border-0 shadow-sm h-100 total-card"><div class="card-body d-flex align-items-center justify-content-between">
                    <div><small class="text-muted">Transactions</small><h4 class="fw-bold mb-0 mt-2">{{ cashStore.transactionSummary?.total_count ?? 0 }}</h4></div>
                    <div class="icon-box"><i class="bi bi-arrow-left-right" aria-hidden="true"></i></div>
                </div></section>
            </div>
            <div class="col-12 col-md-4">
                <section class="card summary-card border-0 shadow-sm h-100 active-card"><div class="card-body d-flex align-items-center justify-content-between">
                    <div><small class="text-muted">Cash In</small><h4 class="fw-bold mb-0 mt-2">{{ formatAmount(cashStore.transactionSummary?.cash_in_total) }}</h4></div>
                    <div class="icon-box"><i class="bi bi-arrow-down-left" aria-hidden="true"></i></div>
                </div></section>
            </div>
            <div class="col-12 col-md-4">
                <section class="card summary-card border-0 shadow-sm h-100 pending-card"><div class="card-body d-flex align-items-center justify-content-between">
                    <div><small class="text-muted">Cash Out</small><h4 class="fw-bold mb-0 mt-2">{{ formatAmount(cashStore.transactionSummary?.cash_out_total) }}</h4></div>
                    <div class="icon-box"><i class="bi bi-arrow-up-right" aria-hidden="true"></i></div>
                </div></section>
            </div>
        </div>

        <section class="card shift-status-panel border-0 shadow-sm mb-3">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <span class="badge" :class="hasOpenShift ? 'badge-active' : 'badge-inactive'">
                        {{ cashStore.shiftLoading ? 'Checking shift' : hasOpenShift ? 'Shift open' : 'No open shift' }}
                    </span>
                    <p class="mb-0 mt-2">
                        <strong>{{ hasOpenShift ? cashStore.currentShift.cash_register?.name : 'No active register' }}</strong>
                        <span v-if="hasOpenShift && cashStore.currentShift.cash_register?.store?.name" class="text-muted"> · {{ cashStore.currentShift.cash_register.store.name }}</span>
                    </p>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <RouterLink v-if="!hasOpenShift && !cashStore.shiftLoading" to="/shifts" class="btn btn-outline-primary">
                        <i class="bi bi-clock-history me-1"></i>Open My Shift
                    </RouterLink>
                    <button v-if="canCashIn" type="button" class="btn btn-success" :disabled="!hasOpenShift || cashStore.shiftLoading" @click="openForm('cash_in')">
                        <i class="bi bi-arrow-down-left me-1"></i>Cash In
                    </button>
                    <button v-if="canCashOut" type="button" class="btn btn-danger" :disabled="!hasOpenShift || cashStore.shiftLoading" @click="openForm('cash_out')">
                        <i class="bi bi-arrow-up-right me-1"></i>Cash Out
                    </button>
                </div>
                <p v-if="hasOpenShift && !canRecord" class="text-muted small mb-0">You do not have permission to record cash transactions.</p>
                <p v-if="!hasOpenShift && !cashStore.shiftLoading && (!canCashIn && !canCashOut)" class="text-muted small mb-0">You do not have permission to record cash transactions.</p>
            </div>
        </section>

        <section class="card transaction-panel border-0 shadow-sm">
            <div class="card-header d-flex align-items-center justify-content-between gap-3 px-3 py-3">
                <h2 class="h6 mb-0">Cash transaction history</h2>
                <span class="text-muted small">{{ cashStore.transactionPagination?.total ?? 0 }} records</span>
            </div>
            <div class="cash-transaction-filters p-3 border-bottom">
                <div class="row g-2 mb-3">
                    <div class="col-12 col-md-4">
                        <label class="visually-hidden" for="cash-transaction-type">Transaction type</label>
                        <select id="cash-transaction-type" v-model="filters.type" class="form-select" @change="applyFilters">
                            <option value="">All transaction types</option>
                            <option value="cash_in">Cash In</option>
                            <option value="cash_out">Cash Out</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="visually-hidden" for="cash-transaction-from">From date</label>
                        <input id="cash-transaction-from" v-model="filters.from" type="date" class="form-control">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="visually-hidden" for="cash-transaction-to">To date</label>
                        <input id="cash-transaction-to" v-model="filters.to" type="date" class="form-control">
                    </div>
                    <div class="col-12 col-md-2 d-grid">
                        <button type="button" class="btn btn-outline-primary" @click="applyFilters"><i class="bi bi-funnel me-1"></i>Filter</button>
                    </div>
                </div>
            </div>

            <LoadingSkeleton v-if="cashStore.transactionsLoading" variant="table" :columns="7" label="Loading cash transactions" />
            <div v-else-if="!cashStore.transactions.length" class="cash-empty-state">
                <i class="bi bi-wallet2" aria-hidden="true"></i>
                <h3>No cash transactions found</h3>
                <p>Cash-in and cash-out transactions will appear here.</p>
            </div>
            <div v-else class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>Date</th><th>Type</th><th>Amount</th><th>Reason</th><th>Register / Store</th><th>Shift user</th><th>Recorded by</th></tr></thead>
                        <tbody>
                            <tr v-for="transaction in cashStore.transactions" :key="transaction.id">
                                <td>{{ transaction.created_at ? new Date(transaction.created_at).toLocaleString() : '-' }}</td>
                                <td><span class="badge" :class="transaction.type === 'cash_in' ? 'badge-active' : 'badge-inactive'">{{ transaction.type === 'cash_in' ? 'Cash In' : 'Cash Out' }}</span></td>
                                <td class="fw-semibold" :class="transaction.type === 'cash_in' ? 'text-success' : 'text-danger'">{{ formatAmount(transaction.amount) }}</td>
                                <td>{{ transaction.reason || '-' }}</td>
                                <td>{{ transaction.shift?.cash_register?.name || '-' }}<small class="d-block text-muted">{{ transaction.shift?.cash_register?.store?.name || '' }}</small></td>
                                <td>{{ transaction.shift?.user?.full_name || transaction.shift?.user?.username || '-' }}</td>
                                <td>{{ transaction.creator?.full_name || transaction.creator?.username || '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
            </div>

            <div v-if="cashStore.transactionPagination" class="card-footer d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                <small class="text-muted">Showing {{ cashStore.transactionPagination.from || 0 }}–{{ cashStore.transactionPagination.to || 0 }} of {{ cashStore.transactionPagination.total }} transactions</small>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" :disabled="cashStore.transactionPagination.current_page <= 1 || cashStore.transactionsLoading" @click="changePage(cashStore.transactionPagination.current_page - 1)">Previous</button>
                    <span class="small text-muted">Page {{ cashStore.transactionPagination.current_page }} of {{ cashStore.transactionPagination.last_page }}</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary" :disabled="cashStore.transactionPagination.current_page >= cashStore.transactionPagination.last_page || cashStore.transactionsLoading" @click="changePage(cashStore.transactionPagination.current_page + 1)">Next</button>
                </div>
            </div>
        </section>
    </div>

    <div v-if="formOpen" class="cash-transaction-backdrop" @click.self="formOpen = false">
        <form class="cash-transaction-modal" @submit.prevent="submitTransaction">
            <header>
                <div><p class="eyebrow">{{ formType === 'cash_in' ? 'Add cash to drawer' : 'Remove cash from drawer' }}</p><h2>{{ formType === 'cash_in' ? 'Record Cash In' : 'Record Cash Out' }}</h2></div>
                <button type="button" class="btn-close" aria-label="Close" :disabled="formSaving" @click="formOpen = false"></button>
            </header>
            <div class="body">
                <div v-if="formError" class="alert alert-danger">{{ formError }}</div>
                <p class="text-muted small mb-0">Shift: {{ cashStore.currentShift?.cash_register?.name }} · {{ cashStore.currentShift?.cash_register?.store?.name }}</p>
                <label class="form-label">Amount
                    <input v-model="form.amount" class="form-control" type="number" min="0.01" max="99999999.99" step="0.01" required>
                </label>
                <label class="form-label">Reason
                    <textarea v-model.trim="form.reason" class="form-control" rows="3" maxlength="2000" required placeholder="Enter why cash is being added or removed"></textarea>
                </label>
            </div>
            <footer>
                <button type="button" class="btn btn-light" :disabled="formSaving" @click="formOpen = false">Cancel</button>
                <button type="submit" class="btn" :class="formType === 'cash_in' ? 'btn-success' : 'btn-danger'" :disabled="formSaving">
                    <span v-if="formSaving" class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                    {{ formSaving ? 'Recording...' : formType === 'cash_in' ? 'Record Cash In' : 'Record Cash Out' }}
                </button>
            </footer>
        </form>
    </div>
</template>

<style scoped>
.summary-card .icon-box{display:flex;width:2.35rem;height:2.35rem;flex:0 0 auto;align-items:center;justify-content:center;border-radius:.65rem;background:var(--ui-primary-soft);color:var(--ui-primary);font-size:1.1rem}
.shift-status-panel,.transaction-panel{background:var(--ui-surface);color:var(--ui-text)}
.transaction-panel{overflow:hidden}
.transaction-panel>.card-header,.transaction-panel>.card-footer{border-color:var(--ui-border);background:var(--ui-surface-muted)}
.transaction-panel .form-control,.transaction-panel .form-select{border-color:var(--ui-border);background:var(--ui-control);color:var(--ui-text)}
.transaction-panel .table{--bs-table-bg:transparent;--bs-table-color:var(--ui-text);--bs-table-border-color:var(--ui-border);--bs-table-hover-bg:var(--ui-surface-muted);--bs-table-hover-color:var(--ui-text)}
.cash-empty-state{padding:3rem 1rem;color:var(--ui-muted);text-align:center}
.cash-empty-state i{display:block;margin-bottom:.75rem;font-size:2.5rem;opacity:.55}
.cash-empty-state h3{margin-bottom:.35rem;color:var(--ui-text);font-size:1rem;font-weight:700}
.cash-empty-state p{margin:0;font-size:.875rem}
.transaction-panel>.card-footer{padding:.75rem 1rem}
.cash-transaction-backdrop{position:fixed;inset:0;z-index:1050;display:grid;place-items:center;padding:1rem;background:rgb(15 23 42 / 60%)}
.cash-transaction-modal{width:min(100%,520px);max-height:92vh;overflow:auto;border:1px solid var(--ui-border);border-radius:var(--ui-modal-radius);background:var(--ui-surface);color:var(--ui-text);box-shadow:var(--ui-shadow-lg)}
.cash-transaction-modal>header,.cash-transaction-modal>footer{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1.25rem 1.5rem;background:var(--ui-surface-muted)}
.cash-transaction-modal>header{border-bottom:1px solid var(--ui-border)}
.cash-transaction-modal .eyebrow{margin:0 0 .25rem;color:var(--ui-muted);font-size:.75rem;font-weight:700;text-transform:uppercase}
.cash-transaction-modal h2{margin:0;font-size:1.25rem}
.cash-transaction-modal .body{display:grid;gap:1rem;padding:1.5rem}
.cash-transaction-modal .body .form-label{display:grid;gap:.4rem;margin:0;font-weight:600}
.cash-transaction-modal>footer{justify-content:flex-end;border-top:1px solid var(--ui-border)}
@media(max-width:575px){.cash-transaction-modal>header,.cash-transaction-modal>.body,.cash-transaction-modal>footer{padding:1rem}}
@media(max-width:575px){.shift-status-panel .card-body>div:last-of-type{width:100%}.shift-status-panel .card-body>div:last-of-type .btn{flex:1}}
</style>
