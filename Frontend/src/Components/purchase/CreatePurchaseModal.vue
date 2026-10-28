<script setup>
import { ref, computed } from 'vue'
import ImageThumbnail from '@/Components/ImageThumbnail.vue'

const searchProduct = ref('')
const selectedProducts = ref([])
const showProductSearch = ref(false)

const storeId = ref('')
const supplierId = ref('')
const orderedAt = ref(new Date().toISOString().slice(0, 10))

const errors = ref({
    store: '',
    supplier: '',
    orderedAt: '',
    products: '',
    quantity: {},
    cost: {}
})

const searchBarcode = () => {
    if (!searchProduct.value.trim()) {
        return
    }

    emit('search-product', searchProduct.value)
    showProductSearch.value = true
}

const pasteSearch = async () => {
    searchProduct.value = await navigator.clipboard.readText()

    if (searchProduct.value.trim()) {
        emit('search-product', searchProduct.value)
        showProductSearch.value = true
    }
}

const addProduct = (product) => {
    const existingProduct = selectedProducts.value.find(
        item => item.id === product.id
    )

    if (existingProduct) {
        existingProduct.quantity += 1
    } else {
        selectedProducts.value.push({
            ...product,
            quantity: 1,
            purchase_cost: Number(product.cost_price) || 0
        })
    }

    errors.value.products = ''
    showProductSearch.value = false
}

const removeProduct = (index) => {
    selectedProducts.value.splice(index, 1)
}

const purchaseTotal = computed(() => {
    return selectedProducts.value.reduce((total, product) => {
        return total +
            ((Number(product.quantity) || 0) *
                (Number(product.purchase_cost) || 0))
    }, 0)
})
const createpurchase = () => {
    errors.value = {
        store: '',
        supplier: '',
        orderedAt: '',
        products: '',
        quantity: {},
        cost: {}
    }

    if (!storeId.value) {
        errors.value.store = 'Please select a store'
    }
    if (!supplierId.value) {
        errors.value.supplier = 'Please select a supplier'
    }
    if (!orderedAt.value) {
        errors.value.orderedAt = 'Please select ordered date'
    }

    if (!selectedProducts.value.length) {
        errors.value.products = 'Please add at least one product'
    }

    selectedProducts.value.forEach(product => {
        if (
            !product.quantity ||
            !Number.isInteger(Number(product.quantity)) ||
            Number(product.quantity) < 1
        ) {
            errors.value.quantity[product.id] =
                'Quantity must be an integer and at least 1'
        }
        if (
            product.purchase_cost === null ||
            product.purchase_cost === undefined ||
            product.purchase_cost === '' ||
            Number(product.purchase_cost) < 0
        ) {
            errors.value.cost[product.id] =
                'Cost price must be 0 or greater'
        }
    })
    if (
        errors.value.store ||
        errors.value.supplier ||
        errors.value.orderedAt ||
        errors.value.products ||
        Object.keys(errors.value.quantity).length ||
        Object.keys(errors.value.cost).length
    ) {
        return
    }
    emit(
        'create',
        storeId.value,
        supplierId.value,
        orderedAt.value,
        selectedProducts.value
    )
}
const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    stores: {
        type: Array,
        default: () => []
    },
    suppliers: {
        type: Array,
        default: () => []
    },
    products: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits([
    'close',
    'create',
    'search-product'
])
</script>

<template>
    <div v-if="show" class="modal-backdrop-custom">
        <div class="create-purchase-modal">

            <div class="modal-header-custom">
                <div>
                    <h5>
                        Create Purchase
                    </h5>

                    <span>
                        Create a new purchase order
                    </span>
                </div>

                <button
                    type="button"
                    class="btn-close"
                    @click="$emit('close')"
                ></button>
            </div>

            <div class="modal-body-custom">

                <div class="form-section">

                    <div class="section-title">
                        Purchase Information
                    </div>

                    <div class="row g-3">

                        <div class="col-md-4">

                            <label class="form-label">
                                Store
                            </label>

                            <select
                                v-model="storeId"
                                class="form-select"
                                :class="{ 'is-invalid': errors.store }"
                            >
                                <option value="">
                                    Select Store
                                </option>

                                <option
                                    v-for="store in props.stores"
                                    :key="store.id"
                                    :value="store.id"
                                >
                                    {{ store.name }}
                                </option>
                            </select>

                            <small
                                v-if="errors.store"
                                class="text-danger"
                            >
                                {{ errors.store }}
                            </small>

                        </div>

                        <div class="col-md-4">

                            <label class="form-label">
                                Supplier
                            </label>

                            <select
                                v-model="supplierId"
                                class="form-select"
                                :class="{ 'is-invalid': errors.supplier }"
                            >
                                <option value="">
                                    Select Supplier
                                </option>

                                <option
                                    v-for="supplier in props.suppliers"
                                    :key="supplier.id"
                                    :value="supplier.id"
                                >
                                    {{ supplier.company_name }}
                                </option>
                            </select>

                            <small
                                v-if="errors.supplier"
                                class="text-danger"
                            >
                                {{ errors.supplier }}
                            </small>

                        </div>

                        <div class="col-md-4">

                            <label class="form-label">
                                Ordered Date
                            </label>

                            <input
                                v-model="orderedAt"
                                type="date"
                                class="form-control"
                                :class="{ 'is-invalid': errors.orderedAt }"
                            >

                            <small
                                v-if="errors.orderedAt"
                                class="text-danger"
                            >
                                {{ errors.orderedAt }}
                            </small>

                        </div>

                    </div>

                </div>

                <div class="form-section">

                    <div class="section-title">
                        Add Products
                    </div>

                    <div class="product-search">

                        <div class="input-group">

                            <span class="input-group-text">
                                <i class="bi bi-search"></i>
                            </span>

                            <input
                                type="text"
                                class="form-control"
                                placeholder="Search product by name, SKU or barcode..."
                                v-model="searchProduct"
                            >

                            <button
                                type="button"
                                class="btn btn-outline-secondary"
                                @click="pasteSearch"
                                title="Paste"
                            >
                                <i class="bi bi-clipboard"></i>
                            </button>

                            <button
                                type="button"
                                class="btn btn-primary"
                                @click="searchBarcode"
                                title="Search"
                            >
                                <i class="bi bi-search"></i>
                            </button>

                        </div>

                    </div>

                    <small
                        v-if="errors.products"
                        class="text-danger"
                    >
                        {{ errors.products }}
                    </small>

                    <div
                        v-if="showProductSearch && props.products.length"
                        class="product-search-popup"
                    >

                        <div class="product-popup-header">

                            <div>

                                <strong>
                                    Search Products
                                </strong>

                                <span>
                                    {{ props.products.length }} products found
                                </span>

                            </div>

                            <button
                                type="button"
                                class="product-popup-close"
                                @click="showProductSearch = false"
                            >
                                <i class="bi bi-x-lg"></i>
                            </button>

                        </div>

                        <div class="product-search-list">

                            <button
                                v-for="product in props.products"
                                :key="product.id"
                                type="button"
                                class="product-search-item"
                                @click="addProduct(product)"
                            >

                                <div class="product-search-image">

                                    <ImageThumbnail
                                        :src="product.image"
                                        :alt="`${product.name} image`"
                                        class="purchase-search-image"
                                        fallback="bi-box-seam"
                                    />

                                </div>

                                <div class="product-search-info">

                                    <strong>
                                        {{ product.name }}
                                    </strong>

                                    <div class="product-meta">

                                        <span>
                                            SKU: {{ product.sku || 'N/A' }}
                                        </span>

                                        <span>
                                            Barcode: {{ product.barcode || 'N/A' }}
                                        </span>

                                    </div>

                                    <div class="product-meta">

                                        <span>
                                            Category:
                                            {{ product.category?.name || 'N/A' }}
                                        </span>

                                        <span>
                                            Brand:
                                            {{ product.brand?.name || 'N/A' }}
                                        </span>

                                        <span>
                                            Unit:
                                            {{ product.unit || 'N/A' }}
                                        </span>

                                    </div>

                                </div>

                                <div class="product-search-price">

                                    <span>
                                        Cost
                                    </span>

                                    <strong>
                                        ${{ product.cost_price }}
                                    </strong>

                                    <small>
                                        Stock:
                                        {{ product.inventory_stocks_sum_quantity ?? 0 }}
                                    </small>

                                </div>

                            </button>

                        </div>

                    </div>

                </div>

                <div class="form-section">

                    <div class="section-title d-flex justify-content-between">

                        <span>
                            Purchase Items
                        </span>

                        <span class="item-count">
                            {{ selectedProducts.length }} Items
                        </span>

                    </div>

                    <div class="purchase-items-table">

                        <div class="table-responsive">

                            <table class="table align-middle mb-0">

                                <thead>

                                    <tr>

                                        <th>
                                            Product
                                        </th>

                                        <th width="120">
                                            Qty
                                        </th>

                                        <th width="150">
                                            Cost Price
                                        </th>

                                        <th
                                            width="150"
                                            class="text-end"
                                        >
                                            Subtotal
                                        </th>

                                        <th width="60"></th>

                                    </tr>

                                </thead>

                                <tbody>

                                    <tr
                                        v-if="selectedProducts.length === 0"
                                    >

                                        <td
                                            colspan="5"
                                            class="text-center"
                                        >
                                            No products selected
                                        </td>

                                    </tr>

                                    <tr
                                        v-for="(product, index) in selectedProducts"
                                        :key="product.id"
                                    >

                                        <td>

                                            <div class="purchase-product">

                                                <ImageThumbnail
                                                    :src="product.image"
                                                    :alt="`${product.name} image`"
                                                    class="purchase-line-image"
                                                    fallback="bi-box-seam"
                                                />

                                                <div>

                                                    <div class="product-name">
                                                        {{ product.name }}
                                                    </div>

                                                    <small>
                                                        SKU:
                                                        {{ product.sku || 'N/A' }}
                                                    </small>

                                                </div>

                                            </div>

                                        </td>

                                        <td>

                                            <input
                                                type="number"
                                                min="1"
                                                v-model.number="product.quantity"
                                                class="form-control"
                                                :class="{
                                                    'is-invalid':
                                                        errors.quantity[product.id]
                                                }"
                                            >

                                            <small
                                                v-if="errors.quantity[product.id]"
                                                class="text-danger d-block mt-1"
                                            >
                                                {{ errors.quantity[product.id] }}
                                            </small>

                                        </td>

                                        <td>

                                            <input
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                v-model.number="product.purchase_cost"
                                                class="form-control"
                                                :class="{
                                                    'is-invalid':
                                                        errors.cost[product.id]
                                                }"
                                            >

                                            <small
                                                v-if="errors.cost[product.id]"
                                                class="text-danger d-block mt-1"
                                            >
                                                {{ errors.cost[product.id] }}
                                            </small>

                                        </td>

                                        <td class="text-end">

                                            <strong>
                                                ${{
                                                    (
                                                        (Number(product.quantity) || 0) *
                                                        (Number(product.purchase_cost) || 0)
                                                    ).toFixed(2)
                                                }}
                                            </strong>

                                        </td>

                                        <td>

                                            <button
                                                type="button"
                                                class="remove-item-btn"
                                                @click="removeProduct(index)"
                                            >
                                                <i class="bi bi-trash"></i>
                                            </button>

                                        </td>

                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>

                <div class="purchase-total">

                    <span>
                        Purchase Total
                    </span>

                    <strong>
                        ${{ purchaseTotal.toFixed(2) }}
                    </strong>

                </div>

            </div>

            <div class="modal-footer-custom">

                <button
                    type="button"
                    class="btn btn-outline-secondary"
                    @click="$emit('close')"
                >
                    Cancel
                </button>

                <button
                    type="button"
                    class="btn btn-primary"
                    @click="createpurchase"
                >
                    <i class="bi bi-check-lg"></i>
                    Create Purchase
                </button>

            </div>

        </div>
    </div>
</template>

<style scoped>
.modal-backdrop-custom {
    position: fixed;
    inset: 0;
    z-index: 1055;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgba(0, 0, 0, 0.55);
}

.create-purchase-modal {
    width: 100%;
    max-width: 1100px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid var(--border-color);
    border-radius: 12px;
    background: var(--surface-bg);
    color: var(--text-color);
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.modal-header-custom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 22px;
    border-bottom: 1px solid var(--border-color);
    background: var(--surface-bg);
}

.modal-header-custom h5 {
    margin: 0;
    font-size: 17px;
    font-weight: 700;
    color: var(--text-color);
}

.modal-header-custom span {
    display: block;
    margin-top: 4px;
    font-size: 12px;
    color: var(--muted-text);
}

.modal-header-custom .btn-close {
    margin: 0;
    padding: 8px;
    opacity: 0.7;
}

.modal-body-custom {
    flex: 1;
    overflow-y: auto;
    padding: 22px;
    background: var(--surface-bg);
}

.form-section {
    margin-bottom: 24px;
}

.form-section:last-child {
    margin-bottom: 0;
}

.section-title {
    margin-bottom: 14px;
    font-size: 14px;
    font-weight: 700;
    color: var(--text-color);
}

.form-section .form-label {
    margin-bottom: 6px;
    font-size: 12px;
    font-weight: 600;
    color: var(--text-color);
}

.form-section .form-control,
.form-section .form-select {
    min-height: 40px;
    border: 1px solid var(--border-color);
    background: var(--surface-bg);
    color: var(--text-color);
    border-radius: 8px;
}

.form-section .form-control:focus,
.form-section .form-select:focus {
    border-color: var(--primary-color, #0d6efd);
    background: var(--surface-bg);
    color: var(--text-color);
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.12);
}

.form-section .form-control::placeholder {
    color: var(--muted-text);
    opacity: 0.8;
}

.text-danger {
    font-size: 12px;
}

.product-search {
    margin-bottom: 6px;
}

.product-search .input-group-text {
    border: 1px solid var(--border-color);
    border-right: 0;
    background: var(--surface-muted);
    color: var(--muted-text);
}

.product-search .form-control {
    border-left: 0;
}

.product-search-popup {
    margin-top: 8px;
    border: 1px solid var(--border-color);
    border-radius: 10px;
    background: var(--surface-bg);
    overflow: hidden;
}

.product-popup-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    border-bottom: 1px solid var(--border-color);
    background: var(--surface-muted);
}

.product-popup-header strong {
    display: block;
    font-size: 13px;
    color: var(--text-color);
}

.product-popup-header span {
    display: block;
    margin-top: 2px;
    font-size: 11px;
    color: var(--muted-text);
}

.product-popup-close {
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 0;
    border-radius: 6px;
    background: transparent;
    color: var(--muted-text);
    cursor: pointer;
}

.product-popup-close:hover {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.product-search-list {
    display: flex;
    flex-direction: column;
    max-height: 300px;
    overflow-y: auto;
}

.product-search-item {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    border: 0;
    border-bottom: 1px solid var(--border-color);
    background: var(--surface-bg);
    color: var(--text-color);
    text-align: left;
    cursor: pointer;
}

.product-search-item:last-child {
    border-bottom: 0;
}

.product-search-item:hover {
    background: var(--surface-muted);
}

.product-search-image {
    flex-shrink: 0;
}

.product-search-image img {
    width: 52px;
    height: 52px;
    display: block;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid var(--border-color);
}

.product-image-empty {
    width: 52px;
    height: 52px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background: var(--surface-muted);
    color: var(--muted-text);
}

.product-search-info {
    flex: 1;
    min-width: 0;
}

.product-search-info strong {
    display: block;
    font-size: 13px;
    color: var(--text-color);
}

.product-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 4px;
}

.product-meta span {
    font-size: 11px;
    color: var(--muted-text);
}

.product-search-price {
    min-width: 80px;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}

.product-search-price span {
    font-size: 10px;
    color: var(--muted-text);
}

.product-search-price strong {
    font-size: 13px;
    color: var(--text-color);
}

.product-search-price small {
    margin-top: 3px;
    font-size: 10px;
    color: var(--muted-text);
}

.purchase-items-table {
    border: 1px solid var(--border-color);
    border-radius: 10px;
    overflow: hidden;
}

.purchase-items-table .table {
    color: var(--text-color);
}

.purchase-items-table th {
    padding: 11px 14px;
    font-size: 11px;
    font-weight: 700;
    color: var(--muted-text);
    background: var(--surface-muted);
    border-bottom: 1px solid var(--border-color);
    white-space: nowrap;
}

.purchase-items-table td {
    padding: 12px 14px;
    border-bottom: 1px solid var(--border-color);
    vertical-align: top;
}

.purchase-items-table tbody tr:last-child td {
    border-bottom: 0;
}

.purchase-product {
    display: flex;
    align-items: center;
    gap: 10px;
}

.purchase-product img {
    display: block;
    object-fit: cover;
    border: 1px solid var(--border-color);
    border-radius: 7px;
}

.purchase-product-image-empty {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border: 1px solid var(--border-color);
    border-radius: 7px;
    background: var(--surface-muted);
    color: var(--muted-text);
}

.product-name {
    font-size: 13px;
    font-weight: 600;
    color: var(--text-color);
}

.purchase-product small {
    font-size: 10px;
    color: var(--muted-text);
}

.purchase-items-table .form-control {
    min-width: 100px;
}

.purchase-items-table .is-invalid {
    border-color: #dc3545;
}

.remove-item-btn {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 0;
    border-radius: 7px;
    background: transparent;
    color: #dc3545;
    cursor: pointer;
}

.remove-item-btn:hover {
    background: rgba(220, 53, 69, 0.1);
}

.item-count {
    font-size: 11px;
    font-weight: 500;
    color: var(--muted-text);
}

.purchase-total {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 18px;
    padding: 15px 18px;
    border: 1px solid var(--border-color);
    border-radius: 10px;
    background: var(--surface-muted);
}

.purchase-total span {
    font-size: 13px;
    font-weight: 600;
    color: var(--muted-text);
}

.purchase-total strong {
    font-size: 20px;
    color: var(--text-color);
}

.modal-footer-custom {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 15px 22px;
    border-top: 1px solid var(--border-color);
    background: var(--surface-bg);
}

.modal-footer-custom .btn {
    min-height: 40px;
    border-radius: 8px;
}

@media (max-width: 768px) {
    .modal-backdrop-custom {
        padding: 10px;
    }

    .create-purchase-modal {
        max-height: 95vh;
    }

    .modal-body-custom {
        padding: 16px;
    }

    .modal-header-custom,
    .modal-footer-custom {
        padding: 14px 16px;
    }
}
</style>