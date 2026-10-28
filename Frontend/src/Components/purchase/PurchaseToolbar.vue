
<script setup>

import { onMounted } from 'vue'
import { usePurchaseStore } from '@/stores/Purchase'

const purchaseStore = usePurchaseStore()

const props = defineProps({
    canCreate: {
        type: Boolean,
        default: false
    },
    status: {
        type: String,
        default: ''
    },
    store: {
        type: String,
        default: ''
    },
    supplier: {
        type: String,
        default: ''
    }
})
const emit = defineEmits([
    'create',
    'filter',
    'update:status',
    'update:store',
    'update:supplier'
])
onMounted(async () => {
    await purchaseStore.getStore()
    await purchaseStore.getSubpllier()
})

</script>

<template>

    <div
        class="toolbar d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 p-3 border-bottom"
    >

        <div class="d-flex flex-wrap gap-2 align-items-center filter-bar">

            <select
                :value="status"
                @change="emit('update:status', $event.target.value)"
                class="form-select filter-select"
            >
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="received">Received</option>
                <option value="cancelled">Cancelled</option>
            </select>

            <select
                :value="store"
                @change="emit('update:store', $event.target.value)"
                class="form-select filter-select"
            >
                <option value="">All Stores</option>

                <option
                    v-for="storeItem in purchaseStore.stores"
                    :key="storeItem.id"
                    :value="storeItem.id"
                >
                    {{ storeItem.name }}
                </option>
            </select>

            <select
                :value="supplier"
                @change="emit('update:supplier', $event.target.value)"
                class="form-select filter-select"
            >
                <option value="">All Suppliers</option>

                <option
                    v-for="supplierItem in purchaseStore.suppliers"
                    :key="supplierItem.id"
                    :value="supplierItem.id"
                >
                    {{ supplierItem.company_name }}
                </option>
            </select>

            <button
                @click="emit('filter')"
                class="btn btn-outline-secondary filter-btn"
            >
                <i class="bi bi-funnel me-1"></i>
                Filter
            </button>

        </div>

        <button
            v-if="canCreate"
            type="button"
            @click="emit('create')"
            class="btn btn-primary"
        >
            <i class="bi bi-plus-lg"></i>
            Create Purchase
        </button>

    </div>

</template>

<style scoped>

.toolbar {
    background: var(--surface-muted);
    border-top-left-radius: 12px;
    border-top-right-radius: 12px;
    border-bottom: 1px solid var(--border-color) !important;
}

.filter-select,
.filter-btn {
    height: 40px;
    border-radius: 8px;
    font-size: 14px;
}

.filter-select {
    width: 150px;
    max-width: 150px;
}

</style>
