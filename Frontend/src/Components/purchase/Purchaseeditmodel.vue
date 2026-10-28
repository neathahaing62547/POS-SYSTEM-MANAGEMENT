<template>
    <div v-if="show" class="edit-backdrop" @click.self="$emit('close')">
        <div class="edit-dialog">

            <div class="edit-header">
                <div>
                    <h5>Edit Purchase</h5>
                    <span>#{{ purchase?.po_number || '-' }}</span>
                </div>
                <button type="button" class="btn-close" @click="$emit('close')"></button>
            </div>

            <div class="edit-body">

                <div v-if="error" class="alert alert-danger py-2">{{ error }}</div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Store</label>
                        <select v-model="form.store_id" class="form-select">
                            <option value="" disabled>Select store</option>
                            <option v-for="s in stores" :key="s.id" :value="s.id">
                                {{ s.name }}
                            </option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Supplier</label>
                        <select v-model="form.supplier_id" class="form-select">
                            <option value="" disabled>Select supplier</option>
                            <option v-for="s in suppliers" :key="s.id" :value="s.id">
                                {{ s.company_name }}
                            </option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Ordered At</label>
                        <input v-model="form.ordered_at" type="date" class="form-control" />
                    </div>
                </div>

                <!-- Add product -->
                <div class="section-title">Add Product</div>
                <div class="input-group mb-2">
                    <input
                        v-model="barcode"
                        type="text"
                        class="form-control"
                        placeholder="Scan / type barcode or SKU"
                        @keyup.enter="search"
                    />
                    <button type="button" class="btn btn-outline-primary" @click="search">
                        Search
                    </button>
                </div>

                <div v-if="products?.length" class="results mb-4">
                    <div v-for="p in products" :key="p.id" class="result-row">
                        <div>
                            <strong>{{ p.name }}</strong>
                            <span>{{ p.sku }}</span>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary" @click="addProduct(p)">
                            Add
                        </button>
                    </div>
                </div>

                <!-- Items -->
                <div class="section-title">Products</div>

                <div v-if="!form.items.length" class="text-muted small mb-3">
                    No products. Add at least one.
                </div>

                <div v-for="(item, i) in form.items" :key="item.product_id" class="item-row">
                    <div class="item-name">
                        <strong>{{ item.name }}</strong>
                        <span>{{ item.sku }}</span>
                    </div>

                    <div class="item-field">
                        <label>Qty</label>
                        <input v-model.number="item.quantity" type="number" min="1" class="form-control form-control-sm" />
                    </div>

                    <div class="item-field">
                        <label>Cost</label>
                        <input v-model.number="item.cost_price" type="number" min="0" step="0.01" class="form-control form-control-sm" />
                    </div>

                    <div class="item-subtotal">{{ money(subtotal(item)) }}</div>

                    <button type="button" class="btn btn-sm btn-outline-danger" @click="removeItem(i)">
                        &times;
                    </button>
                </div>

                <div class="edit-total">
                    <span>Total Cost</span>
                    <strong>{{ money(total) }}</strong>
                </div>
            </div>

            <div class="edit-footer">
                <button type="button" class="btn btn-light" @click="$emit('close')">Cancel</button>
                <button type="button" class="btn btn-primary" :disabled="!valid || saving" @click="submit">
                    {{ saving ? 'Saving...' : 'Save Changes' }}
                </button>
            </div>

        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'

const props = defineProps({
    show: { type: Boolean, default: false },
    purchase: { type: Object, default: null },
    stores: { type: Array, default: () => [] },
    suppliers: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    saving: { type: Boolean, default: false },
    error: { type: String, default: '' }
})

const emit = defineEmits(['close', 'update', 'search-product'])

const barcode = ref('')

const form = reactive({
    store_id: '',
    supplier_id: '',
    ordered_at: '',
    items: []
})

// Fill the form each time the modal opens
watch(
    () => [props.show, props.purchase],
    () => {
        if (!props.show || !props.purchase) return

        const p = props.purchase
        form.store_id = p.store_id ?? p.store?.id ?? ''
        form.supplier_id = p.supplier_id ?? p.supplier?.id ?? ''
        form.ordered_at = p.ordered_at ? String(p.ordered_at).slice(0, 10) : ''
        form.items = (p.items || []).map(i => ({
            product_id: i.product_id ?? i.product?.id,
            name: i.product?.name || '-',
            sku: i.product?.sku || '',
            quantity: Number(i.quantity) || 1,
            cost_price: Number(i.cost_price) || 0
        }))
        barcode.value = ''
    },
    { immediate: true }
)

const subtotal = (item) =>
    (Number(item.quantity) || 0) * (Number(item.cost_price) || 0)

const total = computed(() =>
    form.items.reduce((sum, i) => sum + subtotal(i), 0)
)

const valid = computed(() =>
    form.store_id &&
    form.supplier_id &&
    form.ordered_at &&
    form.items.length > 0 &&
    form.items.every(i => i.quantity >= 1 && i.cost_price >= 0)
)

const money = (v) =>
    '$' + Number(v || 0).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })

const search = () => {
    if (!barcode.value.trim()) return
    emit('search-product', barcode.value.trim())
}

const addProduct = (p) => {
    const existing = form.items.find(i => i.product_id === p.id)

    if (existing) {
        existing.quantity += 1   // backend rejects duplicate products
        return
    }

    form.items.push({
        product_id: p.id,
        name: p.name,
        sku: p.sku,
        quantity: 1,
        cost_price: Number(p.purchase_cost ?? p.cost_price ?? 0)
    })
}

const removeItem = (index) => {
    form.items.splice(index, 1)
}

const submit = () => {
    if (!valid.value) return

    emit(
        'update',
        props.purchase.id,
        form.store_id,
        form.supplier_id,
        form.ordered_at,
        form.items.map(i => ({
            product_id: i.product_id,
            quantity: i.quantity,
            cost_price: i.cost_price
        }))
    )
}
</script>

<style scoped>
.edit-backdrop {
    position: fixed;
    inset: 0;
    z-index: 1200;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    background: rgba(0, 0, 0, 0.55);
}

.edit-dialog {
    display: flex;
    flex-direction: column;
    width: 100%;
    max-width: 860px;
    max-height: 92vh;
    border: 1px solid var(--border-color);
    border-radius: 12px;
    background: var(--surface-bg);
    color: var(--text-color);
}

.edit-header,
.edit-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
}

.edit-header {
    border-bottom: 1px solid var(--border-color);
}

.edit-header h5 {
    margin: 0;
    color: var(--text-color);
}

.edit-header span {
    font-size: 12px;
    color: var(--muted-text);
}

.edit-footer {
    justify-content: flex-end;
    gap: 10px;
    border-top: 1px solid var(--border-color);
}

.edit-body {
    padding: 20px;
    overflow-y: auto;
}

.form-label {
    font-size: 12px;
    color: var(--muted-text);
}

.section-title {
    margin-bottom: 10px;
    font-size: 14px;
    font-weight: 700;
}

.results {
    border: 1px solid var(--border-color);
    border-radius: 10px;
    max-height: 160px;
    overflow-y: auto;
}

.result-row,
.item-row {
    display: flex;
    align-items: center;
    gap: 12px;
}

.result-row {
    justify-content: space-between;
    padding: 10px 14px;
    border-bottom: 1px solid var(--border-color);
}

.result-row:last-child {
    border-bottom: 0;
}

.result-row strong,
.item-name strong {
    display: block;
    font-size: 13px;
}

.result-row span,
.item-name span {
    display: block;
    font-size: 11px;
    color: var(--muted-text);
}

.item-row {
    margin-bottom: 10px;
    padding: 12px 14px;
    border: 1px solid var(--border-color);
    border-radius: 10px;
    background: var(--surface-muted);
}

.item-name {
    flex: 1;
    min-width: 0;
}

.item-field {
    width: 90px;
}

.item-field label {
    display: block;
    margin-bottom: 2px;
    font-size: 10px;
    color: var(--muted-text);
}

.item-subtotal {
    width: 90px;
    text-align: right;
    font-size: 13px;
    font-weight: 700;
    white-space: nowrap;
}

.edit-total {
    display: flex;
    justify-content: space-between;
    margin-top: 16px;
    padding: 14px 16px;
    border: 1px solid var(--border-color);
    border-radius: 10px;
    background: var(--surface-muted);
}

.edit-total strong {
    font-size: 18px;
}

@media (max-width: 768px) {
    .item-row {
        flex-wrap: wrap;
    }

    .item-name {
        flex-basis: 100%;
    }
}
</style>