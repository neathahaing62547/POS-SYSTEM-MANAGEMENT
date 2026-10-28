<script setup>
import ImageThumbnail from '@/Components/ImageThumbnail.vue'

defineProps({
    product: {
        type: Object,
        default: null
    }
})
defineEmits(['close'])
</script>

<template>
<div class="modal-backdrop-custom">
    <div class="product-modal">
        <div class="modal-heading">
            <h2>View Product</h2>
            <button class="close-button" @click="$emit('close')"><i class="bi bi-x-lg"></i></button>
        </div>

        <div v-if="product" class="modal-content-body">
            <div class="form-grid">
                <div class="full-width product-detail-image">
                    <ImageThumbnail
                        :src="product.image"
                        class="detail-image"
                        :alt="`${product.name} image`"
                        fallback="bi-box-seam"
                    />
                </div>
                <label>Product Name<input class="form-control" :value="product.name" readonly></label>
                <label>SKU<input class="form-control" :value="product.sku" readonly></label>
                <label>Barcode<input class="form-control" :value="product.barcode" readonly></label>
                <label>Unit<input class="form-control" :value="product.unit" readonly></label>
                <label>Category<input class="form-control" :value="product.category?.name || '-'" readonly></label>
                <label>Brand<input class="form-control" :value="product.brand?.name || '-'" readonly></label>
                <label>Cost Price<input class="form-control" :value="product.cost_price" readonly></label>
                <label>Sale Price<input class="form-control" :value="product.sale_price" readonly></label>
                <label>Stock<input class="form-control" :value="product.inventory_stocks_sum_quantity ?? 0" readonly></label>
                <label>Status<input class="form-control" :value="product.status" readonly></label>
                <label class="full-width">Description
                    <textarea class="form-control" rows="3" readonly>{{ product.description || '-' }}</textarea>
                </label>
            </div>
        </div>

        <div class="modal-actions">
            <button class="btn btn-light" @click="$emit('close')">Close</button>
        </div>
    </div>
</div>
</template>

<style scoped>
.modal-backdrop-custom {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, .55);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1050;
    padding: 1rem;
}

.product-modal {
    background: #fff;
    border-radius: 16px;
    width: 100%;
    max-width: 720px;
    max-height: 92vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.modal-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 1.75rem;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
}

.modal-heading h2 {
    font-size: 1.3rem;
    font-weight: 700;
    margin: 0;
}

.close-button {
    border: none;
    background: transparent;
    font-size: 18px;
    color: #64748b;
    cursor: pointer;
}

.close-button:hover {
    color: #dc2626;
}

.modal-content-body {
    padding: 1.5rem 1.75rem;
    overflow-y: auto;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.25rem;
}

.form-grid label {
    display: flex;
    flex-direction: column;
    gap: .4rem;
    font-size: 13px;
    font-weight: 600;
}

.full-width {
    grid-column: 1 / -1;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: .75rem;
    padding: 1.25rem 1.75rem;
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
}

input[readonly],
textarea[readonly] {
    background: #f1f5f9;
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
}
</style>
