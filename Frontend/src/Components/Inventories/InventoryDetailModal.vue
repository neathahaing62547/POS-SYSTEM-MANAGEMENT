<script setup>
import LoadingSkeleton from '@/Components/LoadingSkeleton.vue'

defineProps({
    inventory: { type: Object, default: null },
    loading: Boolean,
    error: { type: String, default: '' }
})

defineEmits(['close'])
</script>

<template>
    <div class="modal-backdrop" @click.self="$emit('close')">
        <section class="modal-box" role="dialog" aria-modal="true" aria-labelledby="inventory-detail-title">
            <header class="modal-header">
                <h5 id="inventory-detail-title">Inventory Details</h5>
                <button type="button" aria-label="Close" @click="$emit('close')">
                    <i class="bi bi-x-lg"></i>
                </button>
            </header>

            <div class="modal-body">
                <LoadingSkeleton v-if="loading" variant="spinner" label="Loading inventory details" />
                <div v-else-if="error" class="alert alert-danger mb-0">{{ error }}</div>
                <dl v-else-if="inventory" class="details-grid mb-0">
                    <dt>Product</dt>
                    <dd>{{ inventory.product?.name || '-' }}</dd>
                    <dt>SKU / Barcode</dt>
                    <dd>{{ inventory.product?.sku || inventory.product?.barcode || '-' }}</dd>
                    <dt>Store</dt>
                    <dd>{{ inventory.store?.name || '-' }}</dd>
                    <dt>Quantity</dt>
                    <dd>{{ inventory.quantity }}</dd>
                    <dt>Alert quantity</dt>
                    <dd>{{ inventory.alert_quantity }}</dd>
                    <dt>Inventory record</dt>
                    <dd>#{{ inventory.id }}</dd>
                </dl>
            </div>

            <footer class="modal-footer">
                <button type="button" class="btn btn-secondary" @click="$emit('close')">Close</button>
            </footer>
        </section>
    </div>
</template>

<style scoped>
.modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 1050;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgba(0, 0, 0, .45);
}

.modal-box {
    width: 100%;
    max-width: 520px;
    overflow: hidden;
    color: var(--text-color, #0f172a);
    background: var(--surface-bg, #fff);
    border-radius: 12px;
}

.modal-header,
.modal-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid var(--border-color, #e5e7eb);
}

.modal-header h5 {
    margin: 0;
}

.modal-header button {
    border: 0;
    color: inherit;
    background: transparent;
}

.modal-body {
    padding: 20px;
}

.details-grid {
    display: grid;
    grid-template-columns: minmax(120px, 1fr) 2fr;
    gap: 12px;
}

.details-grid dt {
    color: var(--muted-text, #64748b);
}

.details-grid dd {
    margin: 0;
    font-weight: 600;
}

.modal-footer {
    justify-content: flex-end;
    border-top: 1px solid var(--border-color, #e5e7eb);
    border-bottom: 0;
}
</style>
