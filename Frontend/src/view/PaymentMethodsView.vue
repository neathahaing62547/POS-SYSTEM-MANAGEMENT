<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { PERMISSIONS } from '@/constants/permissions'

const auth = useAuthStore()
const methods = ref([])
const loading = ref(false)
const saving = ref(false)
const deleting = ref(false)
const error = ref('')
const success = ref('')
const search = ref('')
const editorOpen = ref(false)
const deleteTarget = ref(null)
const editingId = ref(null)
const form = ref({ name: '', code: '', status: 'active' })

const canCreate = computed(() => auth.can(PERMISSIONS.PAYMENT_METHODS_CREATE))
const canUpdate = computed(() => auth.can(PERMISSIONS.PAYMENT_METHODS_UPDATE))
const canDelete = computed(() => auth.can(PERMISSIONS.PAYMENT_METHODS_DELETE))
const filteredMethods = computed(() => {
    const term = search.value.trim().toLowerCase()
    if (!term) return methods.value
    return methods.value.filter((method) =>
        `${method.name} ${method.code} ${method.status}`.toLowerCase().includes(term)
    )
})

async function loadMethods() {
    loading.value = true
    error.value = ''
    try {
        const response = await api.get('/payment-methods')
        methods.value = response.data.data
    } catch (requestError) {
        error.value = requestError.response?.data?.message || 'Could not load payment methods.'
    } finally {
        loading.value = false
    }
}

function openCreate() {
    editingId.value = null
    form.value = { name: '', code: '', status: 'active' }
    error.value = ''
    editorOpen.value = true
}

function openEdit(method) {
    editingId.value = method.id
    form.value = { name: method.name, code: method.code, status: method.status }
    error.value = ''
    editorOpen.value = true
}

async function saveMethod() {
    if (saving.value) return
    saving.value = true
    error.value = ''
    success.value = ''
    try {
        const payload = {
            name: form.value.name.trim(),
            code: form.value.code.trim(),
            status: form.value.status
        }
        if (editingId.value) {
            await api.put(`/payment-methods/${editingId.value}`, payload)
            success.value = 'Payment method updated.'
        } else {
            await api.post('/payment-methods', payload)
            success.value = 'Payment method created.'
        }
        editorOpen.value = false
        await loadMethods()
    } catch (requestError) {
        const validationErrors = requestError.response?.data?.errors
        error.value = validationErrors
            ? Object.values(validationErrors).flat().join(' ')
            : requestError.response?.data?.message || 'Could not save the payment method.'
    } finally {
        saving.value = false
    }
}

async function deleteMethod() {
    if (!deleteTarget.value || deleting.value) return
    deleting.value = true
    error.value = ''
    success.value = ''
    try {
        await api.delete(`/payment-methods/${deleteTarget.value.id}`)
        success.value = 'Payment method deleted.'
        deleteTarget.value = null
        await loadMethods()
    } catch (requestError) {
        error.value = requestError.response?.data?.message || 'Could not delete the payment method.'
    } finally {
        deleting.value = false
    }
}

onMounted(loadMethods)
</script>

<template>
    <main class="payment-page container-fluid py-4 px-3 px-md-4">
        <section class="card payment-panel border-0 shadow-sm">
            <div class="card-header d-flex align-items-center justify-content-between gap-3 px-3 py-3">
                <h2 class="h6 mb-0">Payment method list</h2>
                <span class="badge rounded-pill text-bg-secondary">{{ filteredMethods.length }} shown</span>
            </div>
            <div class="payment-toolbar d-flex align-items-center justify-content-between gap-2 p-3 border-bottom">
                <label class="visually-hidden" for="payment-method-search">Search payment methods</label>
                <div class="input-group payment-method-search">
                    <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                    <input id="payment-method-search" v-model="search" class="form-control"
                        type="search" placeholder="Search name, code, or status">
                </div>
                <button v-if="canCreate" class="btn btn-primary" type="button" @click="openCreate">
                    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Add payment method
                </button>
            </div>
            <div v-if="error" class="alert alert-danger m-3" role="alert">{{ error }}</div>
            <div v-else-if="loading" class="p-4 text-center text-muted" role="status">Loading payment methods…</div>
            <div v-else-if="!filteredMethods.length" class="empty-state">
                <i class="bi bi-credit-card-2-front" aria-hidden="true"></i>
                <h3>{{ methods.length ? 'No matching payment methods' : 'No payment methods found' }}</h3>
                <p>{{ methods.length ? 'Try a different name, code, or status.' : 'Payment methods will appear here when available.' }}</p>
            </div>
            <div v-else class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Code</th>
                            <th scope="col">Status</th>
                            <th scope="col">Created</th>
                            <th v-if="canUpdate || canDelete" scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="method in filteredMethods" :key="method.id">
                            <td class="fw-semibold">{{ method.name }}</td>
                            <td><code>{{ method.code }}</code></td>
                            <td>
                                <span class="badge rounded-pill"
                                    :class="method.status === 'active' ? 'text-bg-success' : 'text-bg-secondary'">
                                    {{ method.status }}
                                </span>
                            </td>
                            <td>{{ method.created_at ? new Date(method.created_at).toLocaleDateString() : '—' }}</td>
                            <td v-if="canUpdate || canDelete" class="text-end text-nowrap">
                                <button v-if="canUpdate" class="btn btn-sm table-action" type="button"
                                    :aria-label="`Edit ${method.name}`" :title="`Edit ${method.name}`"
                                    @click="openEdit(method)">
                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                </button>
                                <button v-if="canDelete" class="btn btn-sm table-action action-danger" type="button"
                                    :aria-label="`Delete ${method.name}`" :title="`Delete ${method.name}`"
                                    @click="deleteTarget = method; error = ''">
                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="success" class="alert alert-success m-3" role="status">{{ success }}</div>
        </section>

        <div v-if="editorOpen" class="payment-modal-backdrop" role="presentation"
            @click.self="!saving && (editorOpen = false)">
            <section class="payment-modal card" role="dialog" aria-modal="true"
                aria-labelledby="payment-method-form-title">
                <header class="card-header d-flex align-items-center justify-content-between gap-3">
                    <h2 id="payment-method-form-title" class="h5 mb-0">
                        {{ editingId ? 'Edit payment method' : 'Add payment method' }}
                    </h2>
                    <button class="btn-close" type="button" aria-label="Close" :disabled="saving"
                        @click="editorOpen = false"></button>
                </header>
                <form @submit.prevent="saveMethod">
                    <div class="card-body">
                        <div v-if="error" class="alert alert-danger" role="alert">{{ error }}</div>
                        <label class="form-label w-100">
                            Name
                            <input v-model="form.name" class="form-control" type="text" maxlength="255"
                                autocomplete="off" required :disabled="saving">
                        </label>
                        <label class="form-label w-100 mt-2">
                            Code
                            <input v-model="form.code" class="form-control" type="text" maxlength="50"
                                autocomplete="off" required :disabled="saving">
                        </label>
                        <label class="form-label w-100 mt-2">
                            Status
                            <select v-model="form.status" class="form-select" required :disabled="saving">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </label>
                    </div>
                    <footer class="card-footer d-flex justify-content-end gap-2">
                        <button class="btn btn-outline-secondary" type="button" :disabled="saving"
                            @click="editorOpen = false">Cancel</button>
                        <button class="btn btn-primary" type="submit" :disabled="saving">
                            {{ saving ? 'Saving…' : editingId ? 'Save changes' : 'Add method' }}
                        </button>
                    </footer>
                </form>
            </section>
        </div>

        <div v-if="deleteTarget" class="payment-modal-backdrop" role="presentation"
            @click.self="!deleting && (deleteTarget = null)">
            <section class="payment-modal card delete-modal" role="alertdialog" aria-modal="true"
                aria-labelledby="payment-method-delete-title">
                <header class="card-header">
                    <h2 id="payment-method-delete-title" class="h5 mb-0">Delete payment method?</h2>
                </header>
                <div class="card-body">
                    <div v-if="error" class="alert alert-danger" role="alert">{{ error }}</div>
                    <p class="mb-0">Delete <strong>{{ deleteTarget.name }}</strong>? This cannot be undone.</p>
                </div>
                <footer class="card-footer d-flex justify-content-end gap-2">
                    <button class="btn btn-outline-secondary" type="button" :disabled="deleting"
                        @click="deleteTarget = null">Cancel</button>
                    <button class="btn btn-danger" type="button" :disabled="deleting" @click="deleteMethod">
                        {{ deleting ? 'Deleting…' : 'Delete' }}
                    </button>
                </footer>
            </section>
        </div>
    </main>
</template>

<style scoped>
.payment-page {
    color: var(--ui-text, var(--bs-body-color));
}

.payment-method-search {
    width: min(100%, 420px);
}

.payment-method-search .form-control,
.payment-method-search .input-group-text {
    height: 40px;
    border-color: var(--ui-border, var(--bs-border-color));
    background: var(--ui-control, var(--bs-body-bg));
    color: var(--ui-text, var(--bs-body-color));
}

.payment-method-search .input-group-text {
    border-radius: .5rem 0 0 .5rem;
    color: var(--ui-muted, var(--bs-secondary-color));
}

.payment-method-search .form-control {
    border-radius: 0 .5rem .5rem 0;
}

.payment-method-search .form-control::placeholder {
    color: var(--ui-muted, var(--bs-secondary-color));
    opacity: 1;
}

.payment-panel .form-control,
.payment-panel .form-select,
.payment-modal .form-control,
.payment-modal .form-select {
    border-color: var(--ui-border, var(--bs-border-color));
    background-color: var(--ui-control, var(--bs-body-bg));
    color: var(--ui-text, var(--bs-body-color));
}

.payment-panel {
    overflow: hidden;
    border-color: var(--ui-border, var(--bs-border-color));
    background: var(--ui-surface, var(--bs-body-bg));
    color: var(--ui-text, var(--bs-body-color));
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

.payment-modal-backdrop {
    position: fixed;
    z-index: 1080;
    inset: 0;
    display: grid;
    place-items: center;
    overflow-y: auto;
    padding: 1rem;
    background: rgb(0 0 0 / .55);
}

.payment-modal {
    width: min(100%, 520px);
    max-height: calc(100vh - 2rem);
    overflow: auto;
    border-color: var(--ui-border, var(--bs-border-color));
    background: var(--ui-surface, var(--bs-body-bg));
    color: var(--ui-text, var(--bs-body-color));
}

.payment-modal .card-header,
.payment-modal .card-footer {
    border-color: var(--ui-border, var(--bs-border-color));
    background: var(--ui-surface-muted, var(--bs-tertiary-bg));
}

.delete-modal {
    width: min(100%, 420px);
}

.table-action {
    display: inline-flex;
    width: 2rem;
    min-width: 2rem;
    height: 2rem;
    align-items: center;
    justify-content: center;
    margin-left: .25rem;
    padding: 0;
    border: 1px solid var(--ui-border, var(--bs-border-color));
    border-radius: .4375rem;
    background: transparent;
    color: var(--ui-muted, var(--bs-secondary-color));
}

.table-action:hover {
    border-color: var(--ui-primary, var(--bs-primary));
    background: var(--ui-primary-soft, var(--bs-primary-bg-subtle));
    color: var(--ui-primary, var(--bs-primary));
}

.table-action.action-danger:hover {
    border-color: var(--ui-danger, var(--bs-danger));
    background: var(--ui-danger-soft, var(--bs-danger-bg-subtle));
    color: var(--ui-danger, var(--bs-danger));
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
        flex-direction: column;
    }

    .payment-method-search {
        width: 100%;
    }

    .payment-toolbar > .btn {
        width: 100%;
    }
}
</style>
