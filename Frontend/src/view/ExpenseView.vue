<script setup>
import { computed, onMounted, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useExpenseStore } from '@/stores/Expense'
import { useStoreStore } from '@/stores/Store'
import { PERMISSIONS } from '@/constants/permissions'
import LoadingSkeleton from '@/Components/LoadingSkeleton.vue'

const auth = useAuthStore()
const expenseStore = useExpenseStore()
const storeStore = useStoreStore()

const canCreate = computed(() => auth.can(PERMISSIONS.EXPENSES_CREATE) && auth.can(PERMISSIONS.STORES_VIEW))
const canUpdate = computed(() => auth.can(PERMISSIONS.EXPENSES_UPDATE))
const canDelete = computed(() => auth.can(PERMISSIONS.EXPENSES_DELETE))
const filters = ref({ search: '', store_id: '', category: '', from: '', to: '' })
const modalOpen = ref(false)
const mode = ref('create')
const formSaving = ref(false)
const formError = ref('')
const pageError = ref('')
const successMessage = ref('')
const form = ref(emptyExpense())
const selectedExpense = ref(null)
const deleteOpen = ref(false)

function emptyExpense() {
    return {
        store_id: '',
        category: '',
        amount: '',
        expense_date: new Date().toISOString().slice(0, 10),
        payment_method: 'cash',
        reference: '',
        description: ''
    }
}

const formatAmount = (amount) => Number(amount || 0).toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
})

const loadExpenses = async (page = 1) => {
    pageError.value = ''
    try {
        await expenseStore.getExpenses({
            ...filters.value,
            page
        })
    } catch (error) {
        const validationErrors = error.response?.data?.errors
        pageError.value = validationErrors
            ? Object.values(validationErrors).flat().join(' ')
            : expenseStore.error || error.message || 'Failed to load expenses.'
    }
}

const loadStores = async () => {
    if (!auth.can(PERMISSIONS.STORES_VIEW)) return
    try {
        await storeStore.getStores()
    } catch (error) {
        pageError.value = storeStore.error || error.message || 'Failed to load stores.'
    }
}

const openCreate = () => {
    if (!canCreate.value) return
    form.value = emptyExpense()
    mode.value = 'create'
    formError.value = ''
    modalOpen.value = true
}

const openEdit = (expense) => {
    if (!canUpdate.value) return
    form.value = {
        id: expense.id,
        store_id: expense.store_id || '',
        category: expense.category || '',
        amount: expense.amount || '',
        expense_date: expense.expense_date || '',
        payment_method: expense.payment_method || 'cash',
        reference: expense.reference || '',
        description: expense.description || ''
    }
    mode.value = 'edit'
    formError.value = ''
    modalOpen.value = true
}

const saveExpense = async () => {
    if (formSaving.value || (mode.value === 'create' && !canCreate.value) || (mode.value === 'edit' && !canUpdate.value)) return
    formSaving.value = true
    formError.value = ''
    const payload = {
        store_id: Number(form.value.store_id),
        category: form.value.category.trim(),
        amount: Number(form.value.amount),
        expense_date: form.value.expense_date,
        payment_method: form.value.payment_method,
        reference: form.value.reference.trim() || null,
        description: form.value.description.trim()
    }

    try {
        if (mode.value === 'create') await expenseStore.createExpense(payload)
        else await expenseStore.updateExpense(form.value.id, payload)
        modalOpen.value = false
        successMessage.value = mode.value === 'create' ? 'Expense recorded successfully.' : 'Expense updated successfully.'
        await loadExpenses(mode.value === 'create' ? 1 : expenseStore.pagination?.current_page || 1)
    } catch (error) {
        const validationErrors = error.response?.data?.errors
        formError.value = validationErrors
            ? Object.values(validationErrors).flat().join(' ')
            : expenseStore.error || error.response?.data?.message || 'Failed to save expense.'
    } finally {
        formSaving.value = false
    }
}

const confirmDelete = (expense) => {
    if (!canDelete.value) return
    selectedExpense.value = expense
    deleteOpen.value = true
}

const deleteExpense = async () => {
    if (!canDelete.value || !selectedExpense.value || expenseStore.saving) return
    try {
        await expenseStore.deleteExpense(selectedExpense.value.id)
        deleteOpen.value = false
        successMessage.value = 'Expense deleted successfully.'
        const lastPage = Math.max(1, expenseStore.pagination?.last_page || 1)
        await loadExpenses(Math.min(expenseStore.pagination?.current_page || 1, lastPage))
    } catch (error) {
        pageError.value = expenseStore.error || error.response?.data?.message || 'Failed to delete expense.'
        deleteOpen.value = false
    }
}
const changePage = (page) => {
    if (!expenseStore.pagination || page < 1 || page > expenseStore.pagination.last_page) return
    loadExpenses(page)
}
onMounted(() => {
    loadExpenses()
    loadStores()
})
</script>

<template>
    <div class="container-fluid py-4 px-3 px-md-4">
        <div v-if="pageError" class="alert alert-danger">{{ pageError }}</div>
        <div v-if="successMessage" class="alert alert-success">{{ successMessage }}</div>

        <div class="row g-3 mb-4">
            <div class="col-6 col-md-6">
                <section class="card summary-card border-0 shadow-sm h-100 total-card">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div><small class="text-muted">Expenses matching filters</small>
                            <h4 class="fw-bold mb-0 mt-2">{{ expenseStore.summary?.count ?? 0 }}</h4>
                        </div>
                        <div class="icon-box"><i class="bi bi-receipt" aria-hidden="true"></i></div>
                    </div>
                </section>
            </div>
            <div class="col-6 col-md-6">
                <section class="card summary-card border-0 shadow-sm h-100 received-card">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div><small class="text-muted">Total amount</small>
                            <h4 class="fw-bold mb-0 mt-2">{{ formatAmount(expenseStore.summary?.total) }}</h4>
                        </div>
                        <div class="icon-box"><i class="bi bi-cash-coin" aria-hidden="true"></i></div>
                    </div>
                </section>
            </div>
        </div>

        <section class="card expense-panel border-0 shadow-sm">
            <div class="card-header d-flex align-items-center justify-content-between gap-3 px-3 py-3">
                <h2 class="h6 mb-0">Expense history</h2>
                <button v-if="canCreate" type="button" class="btn btn-primary" @click="openCreate">
                    <i class="bi bi-plus-lg me-1"></i>Record Expense
                </button>
            </div>
            <div class="expense-toolbar p-3 border-bottom">
                <div class="row g-2">
                    <div class="col-12 col-lg-3"><label class="visually-hidden" for="expense-search">Search
                            expenses</label><input id="expense-search" v-model="filters.search" class="form-control"
                            type="search" placeholder="Search description, category, reference"
                            @keyup.enter="loadExpenses()"></div>
                    <div class="col-12 col-sm-6 col-lg-2"><label class="visually-hidden"
                            for="expense-store">Store</label><select id="expense-store" v-model="filters.store_id"
                            class="form-select">
                            <option value="">All stores</option>
                            <option v-for="store in storeStore.stores" :key="store.id" :value="store.id">{{ store.name
                                }}</option>
                        </select></div>
                    <div class="col-12 col-sm-6 col-lg-2"><label class="visually-hidden"
                            for="expense-category">Category</label><input id="expense-category"
                            v-model="filters.category" class="form-control" placeholder="Category"></div>
                    <div class="col-6 col-lg-2"><label class="visually-hidden" for="expense-from">From
                            date</label><input id="expense-from" v-model="filters.from" type="date"
                            class="form-control"></div>
                    <div class="col-6 col-lg-2"><label class="visually-hidden" for="expense-to">To date</label><input
                            id="expense-to" v-model="filters.to" type="date" class="form-control"></div>
                    <div class="col-12 col-lg-1 d-grid"><button type="button" class="btn btn-outline-primary"
                            @click="loadExpenses()">Filter</button></div>
                </div>
            </div>

            <LoadingSkeleton v-if="expenseStore.loading" variant="table" :columns="8" label="Loading expenses" />
            <div v-else-if="!expenseStore.expenses.length" class="expense-empty-state">
                <i class="bi bi-receipt" aria-hidden="true"></i>
                <h3>No expenses found</h3>
                <p>No expense records match the selected filters.</p>
            </div>
            <div v-else class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Store</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Reference</th>
                            <th>Description</th>
                            <th>Recorded by</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="expense in expenseStore.expenses" :key="expense.id">
                            <td>{{ expense.expense_date }}</td>
                            <td class="text-capitalize">{{ expense.category }}</td>
                            <td>{{ expense.store?.name || 'Store removed' }}</td>
                            <td class="fw-semibold">{{ formatAmount(expense.amount) }}</td>
                            <td class="text-capitalize">{{ expense.payment_method.replaceAll('_', ' ') }}</td>
                            <td>{{ expense.reference || '-' }}</td>
                            <td class="expense-description">{{ expense.description }}</td>
                            <td>{{ expense.creator?.full_name || expense.creator?.username || '-' }}</td>
                            <td class="text-center text-nowrap">
                                <button v-if="canUpdate" type="button" class="btn table-action" title="Edit expense"
                                    :aria-label="`Edit expense ${expense.category}`" @click="openEdit(expense)"><i
                                        class="bi bi-pencil"></i></button>
                                <button v-if="canDelete" type="button" class="btn table-action action-danger"
                                    title="Delete expense" :aria-label="`Delete expense ${expense.category}`"
                                    @click="confirmDelete(expense)"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="expenseStore.pagination"
                class="expense-pagination card-footer d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                <small class="text-muted">Showing {{ expenseStore.pagination.from || 0 }}–{{ expenseStore.pagination.to
                    || 0 }} of {{ expenseStore.pagination.total }} expenses</small>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                        :disabled="expenseStore.pagination.current_page <= 1 || expenseStore.loading"
                        @click="changePage(expenseStore.pagination.current_page - 1)">Previous</button>
                    <span class="small text-muted">Page {{ expenseStore.pagination.current_page }} of {{
                        expenseStore.pagination.last_page }}</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                        :disabled="expenseStore.pagination.current_page >= expenseStore.pagination.last_page || expenseStore.loading"
                        @click="changePage(expenseStore.pagination.current_page + 1)">Next</button>
                </div>
            </div>
        </section>
    </div>

    <div v-if="modalOpen" class="expense-modal-backdrop" @click.self="modalOpen = false">
        <form class="expense-modal" @submit.prevent="saveExpense">
            <header>
                <div>
                    <p class="eyebrow">{{ mode === 'create' ? 'New record' : 'Update record' }}</p>
                    <h2>{{ mode === 'create' ? 'Record Expense' : 'Edit Expense' }}</h2>
                </div><button type="button" class="btn-close" aria-label="Close" :disabled="formSaving"
                    @click="modalOpen = false"></button>
            </header>
            <div class="body">
                <div v-if="formError" class="alert alert-danger">{{ formError }}</div>
                <label class="form-label">Store<select v-model="form.store_id" class="form-select" required>
                        <option value="" disabled>Select store</option>
                        <option v-for="store in storeStore.stores" :key="store.id" :value="store.id">{{ store.name }}
                        </option>
                    </select></label>
                <label class="form-label">Category<input v-model.trim="form.category" class="form-control"
                        maxlength="100" required placeholder="e.g. Utilities, Rent, Supplies"></label>
                <label class="form-label">Amount<input v-model="form.amount" class="form-control" type="number"
                        min="0.01" max="9999999999.99" step="0.01" required></label>
                <label class="form-label">Expense date<input v-model="form.expense_date" class="form-control"
                        type="date" required></label>
                <label class="form-label">Payment method<select v-model="form.payment_method" class="form-select"
                        required>
                        <option value="cash">Cash</option>
                        <option value="bank_transfer">Bank transfer</option>
                        <option value="card">Card</option>
                        <option value="other">Other</option>
                    </select></label>
                <label class="form-label">Reference (optional)<input v-model.trim="form.reference" class="form-control"
                        maxlength="255"></label>
                <label class="form-label expense-full-width">Description<textarea v-model.trim="form.description"
                        class="form-control" rows="3" maxlength="5000" required></textarea></label>
            </div>
            <footer><button type="button" class="btn btn-light" :disabled="formSaving"
                    @click="modalOpen = false">Cancel</button><button type="submit" class="btn btn-primary"
                    :disabled="formSaving"><span v-if="formSaving" class="spinner-border spinner-border-sm me-1"
                        aria-hidden="true"></span>{{ formSaving ? 'Saving...' : mode === 'create' ? 'Record Expense' :
                    'Save Changes' }}</button></footer>
        </form>
    </div>

    <div v-if="deleteOpen" class="expense-modal-backdrop" @click.self="deleteOpen = false">
        <section class="expense-delete-modal" role="dialog" aria-modal="true" aria-labelledby="delete-expense-title">
            <header>
                <h2 id="delete-expense-title">Delete Expense</h2><button type="button" class="btn-close"
                    aria-label="Close" @click="deleteOpen = false"></button>
            </header>
            <div class="body">
                <p>Delete this <strong>{{ selectedExpense?.category }}</strong> expense for <strong>{{
                    formatAmount(selectedExpense?.amount) }}</strong>?</p>
                <p class="text-muted small mb-0">This removes the expense record. It does not change cash register
                    transactions.</p>
            </div>
            <footer><button type="button" class="btn btn-light" :disabled="expenseStore.saving"
                    @click="deleteOpen = false">Cancel</button><button type="button" class="btn btn-danger"
                    :disabled="expenseStore.saving" @click="deleteExpense"><span v-if="expenseStore.saving"
                        class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>{{ expenseStore.saving ?
                            'Deleting...' : 'Delete Expense' }}</button></footer>
        </section>
    </div>
</template>

<style scoped>
.expense-panel {
    overflow: hidden;
    background: var(--ui-surface);
    color: var(--ui-text)
}

.expense-panel>.card-header {
    border-color: var(--ui-border);
    background: var(--ui-surface-muted)
}

.expense-panel .form-control,
.expense-panel .form-select {
    border-color: var(--ui-border);
    background: var(--ui-control);
    color: var(--ui-text)
}

.summary-card .icon-box {
    display: flex;
    width: 2.35rem;
    height: 2.35rem;
    flex: 0 0 auto;
    align-items: center;
    justify-content: center;
    border-radius: .65rem;
    background: var(--ui-primary-soft);
    color: var(--ui-primary);
    font-size: 1.1rem
}

.expense-panel .table {
    --bs-table-bg: transparent;
    --bs-table-color: var(--ui-text);
    --bs-table-border-color: var(--ui-border);
    --bs-table-hover-bg: var(--ui-surface-muted);
    --bs-table-hover-color: var(--ui-text)
}

.expense-empty-state {
    padding: 3rem 1rem;
    color: var(--ui-muted);
    text-align: center
}

.expense-empty-state i {
    display: block;
    margin-bottom: .75rem;
    font-size: 2.5rem;
    opacity: .55
}

.expense-empty-state h3 {
    margin-bottom: .35rem;
    color: var(--ui-text);
    font-size: 1rem;
    font-weight: 700
}

.expense-empty-state p {
    margin: 0;
    font-size: .875rem
}

.expense-pagination {
    border-color: var(--ui-border);
    background: var(--ui-surface)
}

.expense-description {
    min-width: 180px;
    max-width: 280px;
    white-space: normal
}

.expense-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 1050;
    display: grid;
    place-items: center;
    padding: 1rem;
    background: rgb(15 23 42 / 60%)
}

.expense-modal,
.expense-delete-modal {
    width: min(100%, 620px);
    max-height: 92vh;
    overflow: auto;
    border: 1px solid var(--ui-border);
    border-radius: var(--ui-modal-radius);
    background: var(--ui-surface);
    color: var(--ui-text);
    box-shadow: var(--ui-shadow-lg)
}

.expense-delete-modal {
    width: min(100%, 440px)
}

.expense-modal>header,
.expense-modal>footer,
.expense-delete-modal>header,
.expense-delete-modal>footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.25rem 1.5rem;
    background: var(--ui-surface-muted)
}

.expense-modal>header,
.expense-delete-modal>header {
    border-bottom: 1px solid var(--ui-border)
}

.expense-modal .eyebrow {
    margin: 0 0 .25rem;
    color: var(--ui-muted);
    font-size: .75rem;
    font-weight: 700;
    text-transform: uppercase
}

.expense-modal h2,
.expense-delete-modal h2 {
    margin: 0;
    font-size: 1.2rem
}

.expense-modal .body,
.expense-delete-modal .body {
    display: grid;
    gap: 1rem;
    padding: 1.5rem
}

.expense-modal .form-label {
    display: grid;
    gap: .4rem;
    margin: 0;
    font-weight: 600
}

.expense-full-width {
    grid-column: 1/-1
}

.expense-modal>footer,
.expense-delete-modal>footer {
    justify-content: flex-end;
    border-top: 1px solid var(--ui-border)
}

@media(max-width:575px) {

    .expense-modal>header,
    .expense-modal>.body,
    .expense-modal>footer,
    .expense-delete-modal>header,
    .expense-delete-modal>.body,
    .expense-delete-modal>footer {
        padding: 1rem
    }
}

@media(max-width:575px) {
    .expense-panel>.card-header {
        align-items: flex-start !important
    }

    .expense-toolbar .row>div {
        width: 100%
    }

    .expense-toolbar .row>div:nth-child(4),
    .expense-toolbar .row>div:nth-child(5) {
        width: 50%
    }
}
</style>
