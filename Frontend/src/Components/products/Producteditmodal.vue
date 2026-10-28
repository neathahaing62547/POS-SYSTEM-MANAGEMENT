<script setup>
import ImageField from '@/Components/ImageField.vue'

import {
    ref
} from 'vue'

const props = defineProps({
    product: {
        type: Object,
        required: true
    },
    categories: {
        type: Array,
        default: () => []
    },
    brands: {
        type: Array,
        default: () => []
    }
})
const emit = defineEmits(['save', 'close'])
const form = ref({
    id: props.product.id,
    category_id: props.product.category_id || '',
    brand_id: props.product.brand_id || '',
    barcode: props.product.barcode || '',
    sku: props.product.sku || '',
    name: props.product.name || '',
    description: props.product.description || '',
    cost_price: props.product.cost_price || '',
    sale_price: props.product.sale_price || '',
    unit: props.product.unit || '',
    image: null,
    status: props.product.status || 'active'
})
</script>

<template>
<div class="modal-backdrop-custom">
    <div class="product-modal">
        <div class="modal-heading">
            <h2>Update Product</h2>
            <button class="close-button" @click="emit('close')"><i class="bi bi-x-lg"></i></button>
        </div>

        <div class="modal-content-body">
            <div class="form-grid">
                <label>Product Name
                    <input v-model="form.name" class="form-control" placeholder="Enter product name">
                </label>
                <label>SKU
                    <input v-model="form.sku" class="form-control" placeholder="Enter SKU">
                </label>
                <label>Barcode
                    <input v-model="form.barcode" class="form-control" placeholder="Enter barcode">
                </label>
                <label>Unit
                    <input v-model="form.unit" class="form-control" placeholder="pcs">
                </label>
                <label>Category
                    <select v-model="form.category_id" class="form-select">
                        <option value="">Select Category</option>
                        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </label>
                <label>Brand
                    <select v-model="form.brand_id" class="form-select">
                        <option value="">Select Brand</option>
                        <option v-for="b in brands" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                </label>
                <label>Cost Price
                    <input v-model="form.cost_price" type="number" class="form-control" placeholder="0.00">
                </label>
                <label>Sale Price
                    <input v-model="form.sale_price" type="number" class="form-control" placeholder="0.00">
                </label>
                <label>Status
                    <select v-model="form.status" class="form-select">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </label>
                <ImageField
                    class="full-width"
                    v-model="form.image"
                    :current-image="product.image"
                    label="Product image"
                    hint="Choose a new image to replace the current one"
                />
                <label class="full-width">Description
                    <textarea v-model="form.description" class="form-control" rows="3"></textarea>
                </label>
            </div>
        </div>

        <div class="modal-actions">
            <button class="btn btn-light" @click="emit('close')">Cancel</button>
            <button class="btn btn-primary" @click="emit('save', form)">
                <i class="bi bi-check2"></i> Update Product
            </button>
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
