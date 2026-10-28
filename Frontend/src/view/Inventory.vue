<script setup>
import { onMounted, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { PERMISSIONS } from '@/constants/permissions'
import { useInventoryStore } from '@/stores/Inventory'
import LoadingSkeleton from '@/Components/LoadingSkeleton.vue'
import InventorySummary from '@/Components/Inventories/InventorySummary.vue'
import InventoryToolbar from '@/Components/Inventories/InventoryToolbar.vue'
import InventoryTable from '@/Components/Inventories/InventoryTable.vue'
import InventoryDetailModal from '@/Components/Inventories/InventoryDetailModal.vue'
import InventoryHistoryModal from '@/Components/Inventories/InventoryHistoryModal.vue'

const inventoryStore = useInventoryStore()
const authStore = useAuthStore()

const search = ref('')
const modal = ref(null)
const selectedInventory = ref(null)
const selectedProductName = ref('')
const selectedHistoryProductId = ref(null)
const historyPagination = ref(null)
const modalError = ref('')

const load = (page = 1) => inventoryStore.getInventory(search.value, page)

const closeModal = () => {
    modal.value = null
    selectedInventory.value = null
    selectedProductName.value = ''
    selectedHistoryProductId.value = null
    historyPagination.value = null
    modalError.value = ''
}

onMounted(() => {
    load()
})

const changePage = (page) => {
    if (page < 1 || page > inventoryStore.pagination.last_page) return
    load(page)
}

const showInventory = async (stock) => {
    modal.value = 'detail'
    selectedInventory.value = null
    modalError.value = ''

    try {
        selectedInventory.value = await inventoryStore.getInventoryById(stock.id)
    } catch (error) {
        modalError.value = error.response?.data?.message || 'Failed to load inventory details'
    }
}

const loadHistory = async (productId, page = 1) => {
    modalError.value = ''

    try {
        historyPagination.value = await inventoryStore.getHistory(productId, page)
    } catch (error) {
        modalError.value = error.response?.data?.message || 'Failed to load stock history'
    }
}

const showHistory = async (stock) => {
    modal.value = 'history'
    selectedProductName.value = stock.product?.name || ''
    selectedHistoryProductId.value = stock.product_id
    historyPagination.value = null
    await loadHistory(selectedHistoryProductId.value)
}
</script>

<template>
    <div class="container-fluid py-4 px-3 px-md-4">

        <!-- Inventory Summary -->
        <InventorySummary :summary="inventoryStore.summary" />

        <!-- Main Card -->
        <div class="card border-0 shadow-sm">

            <!-- Toolbar -->
            <InventoryToolbar v-model:search="search" @search="load()" />

            <div class="card-body">

                <!-- Loading -->
                <LoadingSkeleton v-if="inventoryStore.loading" variant="table" :columns="7" label="Loading inventory" />

                <!-- Error -->
                <div v-else-if="inventoryStore.error" class="alert alert-danger">
                    {{ inventoryStore.error }}
                </div>

                <!-- Inventory Table -->
                <InventoryTable v-else :stocks="inventoryStore.stocks" :pagination="inventoryStore.pagination"
                    :can-history="authStore.can(PERMISSIONS.INVENTORY_HISTORY_VIEW)"
                    @page="changePage" @show="showInventory" @history="showHistory" />

            </div>
        </div>

        <InventoryDetailModal
            v-if="modal === 'detail'"
            :inventory="selectedInventory"
            :loading="inventoryStore.loading"
            :error="modalError"
            @close="closeModal"
        />
        <InventoryHistoryModal
            v-if="modal === 'history'"
            :history="inventoryStore.history"
            :pagination="historyPagination"
            :product-name="selectedProductName"
            :loading="inventoryStore.loading"
            :error="modalError"
            @page="loadHistory(selectedHistoryProductId, $event)"
            @close="closeModal"
        />
    </div>
</template>