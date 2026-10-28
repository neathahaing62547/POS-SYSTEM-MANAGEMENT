<script setup>
import { computed, onMounted, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { PERMISSIONS } from '@/constants/permissions'
import { useStoreStore } from '@/stores/Store'
import StoreTable from '@/Components/stores/StoreTable.vue'
import StoreFormModal from '@/Components/stores/StoreFormModal.vue'
import StoreDeleteModal from '@/Components/stores/StoreDeleteModal.vue'

const auth = useAuthStore()
const storeStore = useStoreStore()

const canCreate = computed(() => auth.can(PERMISSIONS.STORES_CREATE))
const canUpdate = computed(() => auth.can(PERMISSIONS.STORES_UPDATE))
const canDelete = computed(() => auth.can(PERMISSIONS.STORES_DELETE))

const page = ref(1)
const formOpen = ref(false)
const formMode = ref('create')
const formSaving = ref(false)
const formError = ref('')
const form = ref(emptyStore())

const deleteOpen = ref(false)
const deleting = ref(false)
const deleteError = ref('')
const selectedStore = ref(null)

function emptyStore() {
    return {
        name: '',
        phone: '',
        address: '',
        status: 'active'
    }
}

const loadStores = async (nextPage = 1) => {
    page.value = nextPage
    try {
        await storeStore.getStores(nextPage)
    } catch {
        // The store keeps the API error for the page to display.
    }
}

const openCreate = () => {
    if (!canCreate.value) return
    form.value = emptyStore()
    formMode.value = 'create'
    formError.value = ''
    formOpen.value = true
}

const openEdit = (store) => {
    if (!canUpdate.value) return
    form.value = {
        name: store.name || '',
        phone: store.phone || '',
        address: store.address || '',
        status: store.status || 'active',
        id: store.id
    }
    formMode.value = 'edit'
    formError.value = ''
    formOpen.value = true
}

const saveStore = async () => {
    if (formSaving.value) return
    if (formMode.value === 'create' && !canCreate.value) return
    if (formMode.value === 'edit' && !canUpdate.value) return

    formError.value = ''
    if (!form.value.name.trim()) {
        formError.value = 'Store name is required.'
        return
    }

    formSaving.value = true
    let saved = false
    try {
        if (formMode.value === 'create') {
            await storeStore.createStore(form.value)
        } else {
            await storeStore.updateStore(form.value.id, form.value)
        }
        saved = true
        formOpen.value = false
        form.value = emptyStore()
    } catch (error) {
        formError.value =
            storeStore.error || error.message || 'Failed to save store.'
    } finally {
        formSaving.value = false
    }

    if (saved) {
        await loadStores(formMode.value === 'create' ? 1 : page.value)
    }
}

const openDelete = (store) => {
    if (!canDelete.value) return
    selectedStore.value = store
    deleteError.value = ''
    deleteOpen.value = true
}

const deleteStore = async () => {
    if (!canDelete.value || !selectedStore.value || deleting.value) return

    deleting.value = true
    deleteError.value = ''
    try {
        await storeStore.deleteStore(selectedStore.value.id)
        deleteOpen.value = false
        const lastPage = Math.max(1, storeStore.pagination?.last_page || 1)
        await loadStores(Math.min(page.value, lastPage))
    } catch (error) {
        deleteError.value =
            storeStore.error || error.message || 'Failed to delete store.'
    } finally {
        deleting.value = false
    }
}

onMounted(() => loadStores())
</script>

<template>
    <div class="container-fluid py-4 px-3 px-md-4">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
            <div>
                <h1 class="h3 mb-1">Stores</h1>
                <p class="text-muted mb-0">Manage store locations and their status.</p>
            </div>
            <button
                v-if="canCreate"
                type="button"
                class="btn btn-primary"
                @click="openCreate"
            >
                <i class="bi bi-plus-lg me-1"></i>
                Add Store
            </button>
        </div>

        <div class="card border-0 shadow-sm">
            <div v-if="storeStore.error" class="alert alert-danger m-3 mb-0">
                {{ storeStore.error }}
            </div>
            <div class="card-body">
                <StoreTable
                    :stores="storeStore.stores"
                    :pagination="storeStore.pagination"
                    :loading="storeStore.loading"
                    :can-update="canUpdate"
                    :can-delete="canDelete"
                    @edit="openEdit"
                    @delete="openDelete"
                    @page="loadStores"
                />
            </div>
        </div>
    </div>

    <StoreFormModal
        v-model="formOpen"
        :form="form"
        :mode="formMode"
        :saving="formSaving"
        :error="formError"
        @submit="saveStore"
    />

    <StoreDeleteModal
        v-model="deleteOpen"
        :store-name="selectedStore?.name"
        :deleting="deleting"
        :error="deleteError"
        @confirm="deleteStore"
    />
</template>
