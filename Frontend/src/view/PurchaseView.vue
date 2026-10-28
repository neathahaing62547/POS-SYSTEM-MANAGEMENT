<script setup>
import { usePurchaseStore } from '@/stores/Purchase'
import { onMounted, ref, computed } from 'vue'
import { Offcanvas } from 'bootstrap'
import { useAuthStore } from '@/stores/auth'
import { PERMISSIONS } from '@/constants/permissions'

import PurchaseSummary from '@/Components/purchase/PurchaseSummary.vue'
import PurchaseToolbar from '@/Components/purchase/PurchaseToolbar.vue'
import PurchaseTable from '@/Components/purchase/PurchaseTable.vue'
import PurchasePagination from '@/Components/purchase/PurchasePagination.vue'
import CreatePurchaseModal from '@/Components/purchase/CreatePurchaseModal.vue'
import PurchaseDetail from '@/Components/purchase/PurchaseDetail.vue'
import EditPurchaseModal from '@/Components/purchase/Purchaseeditmodel.vue'
import ConfirmModal from '@/Components/purchase/ConfirmModal.vue'

const purchaseStore = usePurchaseStore()
const auth = useAuthStore()

const canView = computed(() => auth.can(PERMISSIONS.PURCHASES_VIEW))
const canCreate = computed(() => auth.can(PERMISSIONS.PURCHASES_CREATE))
const canUpdate = computed(() => auth.can(PERMISSIONS.PURCHASES_UPDATE))
const canReceive = computed(() => auth.can(PERMISSIONS.PURCHASES_RECEIVE))

const statusFilter = ref('')
const storeFilter = ref('')
const supplierFilter = ref('')

const open_model_creatpurchase = ref(false)
const successMessage = ref('')
const purchaseDetail = computed(() => purchaseStore.purchaseDetail)

const searchProduct = async (barcode) => {
    await purchaseStore.searchProduct(barcode)
}

const loadPurchases = async (page = 1) => {
    await purchaseStore.getPurchases(
        '',
        page,
        statusFilter.value,
        storeFilter.value,
        supplierFilter.value
    )
}

onMounted(() => {
    loadPurchases()
})

const changePage = (page) => {
    if (page < 1 || page > purchaseStore.pagination.last_page) {
        return
    }
    loadPurchases(page)
}

const open_creatpurchase = () => {
    if (!canCreate.value) return
    open_model_creatpurchase.value = true
}

const close_creatpurchase = () => {
    open_model_creatpurchase.value = false
}

const openPurchaseDetail = async (id) => {
    if (!canView.value) return

    try {
        await purchaseStore.getPurchaseDetail(id)
    } catch (e) {
        console.error('Failed to load purchase detail', e)
        return
    }

    const el = document.getElementById('purchaseDetail')
    if (!el) return

    Offcanvas.getOrCreateInstance(el).show()
}

const create_purchase = async (
    storeId,
    supplierId,
    orderedAt,
    selectedProducts
) => {
    if (!canCreate.value) return

    const items = selectedProducts.map(product => ({
        product_id: product.id,
        quantity: product.quantity,
        cost_price: product.purchase_cost
    }))

    await purchaseStore.createPurchase(
        storeId,
        supplierId,
        orderedAt,
        items
    )

    open_model_creatpurchase.value = false
    await loadPurchases(1)

    successMessage.value = 'Purchase created successfully'
    setTimeout(() => {
        successMessage.value = ''
    }, 3000)
}

const open_edit = ref(false)
const editingPurchase = ref(null)
const editSaving = ref(false)
const editError = ref('')

const openEditPurchase = async (id) => {
    if (!canUpdate.value) return

    editError.value = ''

    try {
        await purchaseStore.getPurchaseDetail(id)
    } catch (e) {
        console.error('Failed to load purchase', e)
        return
    }

    const detail = purchaseStore.purchaseDetail

    if (detail?.status !== 'pending') {
        infoModal.value = {
            show: true,
            title: 'Cannot Edit',
            message: 'Only pending purchases can be edited.'
        }
        return
    }

    editingPurchase.value = detail
    open_edit.value = true
}

const close_edit = () => {
    open_edit.value = false
    editingPurchase.value = null
}

const update_purchase = async (id, storeId, supplierId, orderedAt, items) => {
    if (!canUpdate.value) return

    editSaving.value = true
    editError.value = ''

    try {
        await purchaseStore.updatePurchase(id, {
            store_id: storeId,
            supplier_id: supplierId,
            ordered_at: orderedAt,
            items
        })

        close_edit()
        await loadPurchases(purchaseStore.pagination?.current_page || 1)

        successMessage.value = 'Purchase updated successfully'
        setTimeout(() => {
            successMessage.value = ''
        }, 3000)
    } catch (e) {
        editError.value =
            e.response?.data?.message || 'Failed to update purchase.'
    } finally {
        editSaving.value = false
    }
}

const errorMessage = ref('')

const infoModal = ref({ show: false, title: '', message: '' })

const showReceiveConfirm = ref(false)
const receiveId = ref(null)
const receiving = ref(false)

const openReceiveConfirm = (id) => {
    if (!canReceive.value) return
    receiveId.value = id
    showReceiveConfirm.value = true
}

const receivePurchase = async () => {
    if (!canReceive.value || !receiveId.value) return

    receiving.value = true
    errorMessage.value = ''

    try {
        await purchaseStore.receivePurchase(receiveId.value)
        showReceiveConfirm.value = false
        await loadPurchases(purchaseStore.pagination?.current_page || 1)

        successMessage.value = 'Purchase received successfully'
        setTimeout(() => {
            successMessage.value = ''
        }, 3000)
    } catch (e) {
        showReceiveConfirm.value = false
        errorMessage.value =
            e.response?.data?.message ||
            e.message ||
            'Failed to receive purchase.'
        setTimeout(() => {
            errorMessage.value = ''
        }, 5000)
    } finally {
        receiving.value = false
    }
}

const filterPurchases = () => {
    loadPurchases(1)
}
</script>

<template>
    <div class="container-fluid py-4 px-3 px-md-4">

        <PurchaseSummary :summary="purchaseStore.summary" />

        <div class="card border-0 shadow-sm">

            <PurchaseToolbar
                :status="statusFilter"
                :store="storeFilter"
                :supplier="supplierFilter"
                :can-create="canCreate"
                @update:status="statusFilter = $event"
                @update:store="storeFilter = $event"
                @update:supplier="supplierFilter = $event"
                @filter="filterPurchases"
                @create="open_creatpurchase"
            />

            <div class="card-body">

                <div v-if="successMessage" class="alert alert-success mb-3">
                    {{ successMessage }}
                </div>

                <div v-if="errorMessage" class="alert alert-danger mb-3">
                    {{ errorMessage }}
                </div>

                <PurchaseTable
                    :purchases="purchaseStore.purchases"
                    :loading="purchaseStore.loading"
                    @view="openPurchaseDetail"
                    :can-edit="canUpdate"
                    :can-receive="canReceive"
                    @edit="openEditPurchase"
                    @receive="openReceiveConfirm"
                />

                <PurchasePagination
                    :pagination="purchaseStore.pagination"
                    @change="changePage"
                />
            </div>
        </div>
    </div>

    <CreatePurchaseModal
        :show="open_model_creatpurchase"
        :stores="purchaseStore.stores"
        :suppliers="purchaseStore.suppliers"
        :products="purchaseStore.products"
        @search-product="searchProduct"
        @close="close_creatpurchase"
        @create="create_purchase"
    />

    <ConfirmModal
        :show="showReceiveConfirm"
        title="Receive Purchase?"
        message="Stock will be added to inventory.&#10;This action cannot be undone."
        confirm-text="Yes, Receive"
        variant="success"
        :loading="receiving"
        @confirm="receivePurchase"
        @close="showReceiveConfirm = false"
    />

    <ConfirmModal
        :show="infoModal.show"
        :title="infoModal.title"
        :message="infoModal.message"
        confirm-text="OK"
        variant="warning"
        :show-cancel="false"
        @confirm="infoModal.show = false"
        @close="infoModal.show = false"
    />

    <EditPurchaseModal
        :show="open_edit"
        :purchase="editingPurchase"
        :stores="purchaseStore.stores"
        :suppliers="purchaseStore.suppliers"
        :products="purchaseStore.products"
        :saving="editSaving"
        :error="editError"
        @search-product="searchProduct"
        @close="close_edit"
        @update="update_purchase"
    />

    <PurchaseDetail :purchase-detail="purchaseDetail" />
</template>