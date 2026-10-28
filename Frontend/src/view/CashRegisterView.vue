<script setup>
import { computed, onMounted, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useCashRegisterStore } from '@/stores/CashRegister'
import { useStoreStore } from '@/stores/Store'
import { PERMISSIONS } from '@/constants/permissions'
import LoadingSkeleton from '@/Components/LoadingSkeleton.vue'

const auth = useAuthStore()
const cashStore = useCashRegisterStore()
const storeStore = useStoreStore()
const canCreate = computed(() => auth.can(PERMISSIONS.CASH_REGISTERS_CREATE) && auth.can(PERMISSIONS.STORES_VIEW))
const search = ref('')
const modalOpen = ref(false)
const selectedRegister = ref(null)
const form = ref({ store_id: '', name: '', status: 'active' })
const formError = ref('')
const pageError = ref('')
const successMessage = ref('')

const visibleRegisters = computed(() => {
    const query = search.value.trim().toLowerCase()
    return cashStore.registers.filter(register =>
        !query || [register.name, register.store?.name, register.status]
            .some(value => String(value || '').toLowerCase().includes(query))
    )
})

const loadRegisters = async () => {
    pageError.value = ''
    try {
        await cashStore.getRegisters()
    } catch (error) {
        pageError.value = cashStore.error || error.message || 'Failed to load cash registers.'
    }
}

const openCreate = async () => {
    if (!canCreate.value) return
    formError.value = ''
    form.value = { store_id: '', name: '', status: 'active' }
    try {
        await storeStore.getStores()
        modalOpen.value = true
    } catch (error) {
        formError.value = storeStore.error || error.message || 'Failed to load stores.'
        modalOpen.value = true
    }
}

const createRegister = async () => {
    if (!canCreate.value || cashStore.saving) return
    formError.value = ''
    try {
        await cashStore.createRegister({
            store_id: Number(form.value.store_id),
            name: form.value.name.trim(),
            status: form.value.status
        })
        modalOpen.value = false
        successMessage.value = 'Cash register created successfully.'
        await loadRegisters()
    } catch (error) {
        const validationErrors = error.response?.data?.errors
        formError.value = validationErrors
            ? Object.values(validationErrors).flat().join(' ')
            : cashStore.error || error.response?.data?.message || 'Failed to create cash register.'
    }
}

const showRegister = async (register) => {
    selectedRegister.value = register
    try {
        await cashStore.getRegister(register.id)
    } catch (error) {
        pageError.value = cashStore.error || error.message || 'Failed to load register details.'
    }
}

const closeDetails = () => {
    selectedRegister.value = null
    cashStore.selectedRegister = null
}

onMounted(loadRegisters)
</script>

<template>
    <div class="container-fluid py-4 px-3 px-md-4">
        <section class="card register-panel border-0 shadow-sm">
            <div class="card-header d-flex align-items-center justify-content-between gap-3 px-3 py-3">
                <h2 class="h6 mb-0">Cash registers</h2>
                <span class="text-muted small">{{ visibleRegisters.length }} shown</span>
            </div>
            <div class="register-toolbar d-flex align-items-center justify-content-between gap-2 p-3 border-bottom">
                <label class="visually-hidden" for="cash-register-search">Search registers</label>
                <div class="input-group register-search">
                    <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                    <input id="cash-register-search" v-model="search" class="form-control" type="search" placeholder="Search register, store, or status">
                </div>
                <button v-if="canCreate" type="button" class="btn btn-primary" @click="openCreate">
                    <i class="bi bi-plus-lg me-1"></i>Add Register
                </button>
            </div>
            <div v-if="pageError" class="alert alert-danger m-3">{{ pageError }}</div>
            <div v-if="successMessage" class="alert alert-success m-3">{{ successMessage }}</div>
            <LoadingSkeleton v-if="cashStore.loading" variant="table" :columns="5" label="Loading cash registers" />
            <div v-else-if="!visibleRegisters.length" class="register-empty-state">
                <i class="bi bi-safe" aria-hidden="true"></i>
                <h3>No cash registers found</h3>
                <p>No registers match your search.</p>
            </div>
            <div v-else class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>ID</th><th>Register</th><th>Store</th><th>Status</th><th class="text-center">Actions</th></tr></thead>
                        <tbody>
                            <tr v-for="register in visibleRegisters" :key="register.id">
                                <td class="text-muted">#{{ register.id }}</td>
                                <td class="fw-semibold">{{ register.name }}</td>
                                <td>{{ register.store?.name || '-' }}</td>
                                <td><span class="badge" :class="register.status === 'active' ? 'badge-active' : 'badge-inactive'">{{ register.status }}</span></td>
                                <td class="text-center">
                                    <button type="button" class="btn table-action" :aria-label="`View ${register.name} shifts`" title="View shift history" @click="showRegister(register)">
                                        <i class="bi bi-clock-history"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
            </div>
        </section>
    </div>

    <div v-if="modalOpen" class="cash-modal-backdrop" @click.self="modalOpen = false">
        <form class="cash-modal" @submit.prevent="createRegister">
            <header><div><p class="eyebrow">New register</p><h2>Add Cash Register</h2></div><button type="button" class="btn-close" aria-label="Close" :disabled="cashStore.saving" @click="modalOpen = false"></button></header>
            <div class="body">
                <div v-if="formError" class="alert alert-danger">{{ formError }}</div>
                <label class="form-label">Store
                    <select v-model="form.store_id" class="form-select" required :disabled="storeStore.loading">
                        <option value="" disabled>Select a store</option>
                        <option v-for="store in storeStore.stores.filter(item => item.status === 'active')" :key="store.id" :value="store.id">{{ store.name }}</option>
                    </select>
                </label>
                <label class="form-label">Register name<input v-model.trim="form.name" class="form-control" maxlength="255" required></label>
                <label class="form-label">Status<select v-model="form.status" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select></label>
            </div>
            <footer><button type="button" class="btn btn-light" :disabled="cashStore.saving" @click="modalOpen = false">Cancel</button><button type="submit" class="btn btn-primary" :disabled="cashStore.saving || storeStore.loading"><span v-if="cashStore.saving" class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>{{ cashStore.saving ? 'Saving...' : 'Create Register' }}</button></footer>
        </form>
    </div>

    <div v-if="selectedRegister" class="cash-modal-backdrop" @click.self="closeDetails">
        <section class="cash-modal cash-modal-wide" role="dialog" aria-modal="true" aria-label="Cash register shift history">
            <header><div><p class="eyebrow">{{ cashStore.selectedRegister?.store?.name || selectedRegister.store?.name || 'Register' }}</p><h2>{{ cashStore.selectedRegister?.name || selectedRegister.name }} — Shift History</h2></div><button type="button" class="btn-close" aria-label="Close" @click="closeDetails"></button></header>
            <div class="body">
                <LoadingSkeleton v-if="cashStore.detailLoading" variant="spinner" label="Loading shift history" />
                <div v-else-if="pageError" class="alert alert-danger">{{ pageError }}</div>
                <div v-else class="table-responsive">
                    <table class="table table-hover align-middle mb-0"><thead><tr><th>User</th><th>Opened</th><th>Closed</th><th>Opening cash</th><th>Closing cash</th><th>Status</th></tr></thead>
                        <tbody>
                            <tr v-for="shift in cashStore.selectedRegister?.user_shifts || []" :key="shift.id">
                                <td>{{ shift.user?.full_name || shift.user?.username || '-' }}</td><td>{{ shift.opened_at ? new Date(shift.opened_at).toLocaleString() : '-' }}</td><td>{{ shift.closed_at ? new Date(shift.closed_at).toLocaleString() : '-' }}</td><td>{{ shift.opening_cash }}</td><td>{{ shift.closing_cash ?? '-' }}</td><td class="text-capitalize">{{ shift.status }}</td>
                            </tr>
                            <tr v-if="!(cashStore.selectedRegister?.user_shifts || []).length"><td colspan="6" class="text-center text-muted py-4">No shifts recorded for this register.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <footer><button type="button" class="btn btn-secondary" @click="closeDetails">Close</button></footer>
        </section>
    </div>
</template>

<style scoped>
.register-panel {
    overflow: hidden;
    background: var(--ui-surface);
    color: var(--ui-text);
}
.register-panel > .card-header {
    border-color: var(--ui-border);
    background: var(--ui-surface-muted);
}
.register-search {
    width: min(100%, 420px);
}
.register-search .input-group-text,
.register-search .form-control {
    height: 40px;
    border-color: var(--ui-border);
    background: var(--ui-control);
    color: var(--ui-text);
}
.register-search .input-group-text {
    border-radius: .5rem 0 0 .5rem;
    color: var(--ui-muted);
}
.register-search .form-control {
    border-radius: 0 .5rem .5rem 0;
}
.register-panel .table {
    --bs-table-bg: transparent;
    --bs-table-color: var(--ui-text);
    --bs-table-border-color: var(--ui-border);
    --bs-table-hover-bg: var(--ui-surface-muted);
    --bs-table-hover-color: var(--ui-text);
}
.register-empty-state {
    padding: 3rem 1rem;
    color: var(--ui-muted);
    text-align: center;
}
.register-empty-state i {
    display: block;
    margin-bottom: .75rem;
    font-size: 2.5rem;
    opacity: .55;
}
.register-empty-state h3 {
    margin-bottom: .35rem;
    color: var(--ui-text);
    font-size: 1rem;
    font-weight: 700;
}
.register-empty-state p {
    margin: 0;
    font-size: .875rem;
}
.cash-modal-backdrop { position: fixed; inset: 0; z-index: 1050; display: grid; place-items: center; padding: 1rem; background: rgb(15 23 42 / 60%); }
.cash-modal { width: min(100%, 560px); max-height: 92vh; overflow: auto; border: 1px solid var(--ui-border); border-radius: var(--ui-modal-radius); background: var(--ui-surface); color: var(--ui-text); box-shadow: var(--ui-shadow-lg); }
.cash-modal-wide { width: min(100%, 1000px); }
.cash-modal > header, .cash-modal > footer { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.25rem 1.5rem; background: var(--ui-surface-muted); }
.cash-modal > header { border-bottom: 1px solid var(--ui-border); }
.cash-modal > header .eyebrow { margin: 0 0 .25rem; color: var(--ui-muted); font-size: .75rem; font-weight: 700; text-transform: uppercase; }
.cash-modal h2 { margin: 0; font-size: 1.25rem; }
.cash-modal .body { display: grid; gap: 1rem; padding: 1.5rem; }
.cash-modal .body .form-label { display: grid; gap: .4rem; margin: 0; font-weight: 600; }
.cash-modal > footer { justify-content: flex-end; border-top: 1px solid var(--ui-border); }
@media(max-width:575px){.cash-modal>header,.cash-modal>.body,.cash-modal>footer{padding:1rem}}
@media(max-width:575px){.register-toolbar{align-items:stretch!important;flex-direction:column}.register-search{width:100%}.register-toolbar>.btn{width:100%}}
</style>
