<script setup>
import { computed, inject, onMounted, ref } from 'vue'
import { usePosStore } from '@/stores/Pos'
import { resolveImageUrl } from '@/utils/imageUrl'

const pos = usePosStore()
const appTheme = inject('appTheme')
const isDarkMode = computed(() => appTheme.isDarkMode.value)
const toggleTheme = () => appTheme.toggleTheme()
const search = ref('')
const selectedCategoryId = ref('')
const addingProductId = ref(null)
const changingCartItemId = ref(null)
const checkoutOpen = ref(false)
const checkoutLoading = ref(false)
const checkoutError = ref('')
const checkoutResult = ref(null)
const selectedPaymentMethod = ref('')
const imageFailures = ref(new Set())
const abaPaymentOpen = ref(false)
const abaPayment = ref(null)

const cartItems = computed(() => pos.cart?.items || [])
const supportedPaymentMethods = computed(() => pos.paymentMethods)
const selectedPaymentMethodDetails = computed(() =>
    supportedPaymentMethods.value.find(
        (method) => String(method.id) === selectedPaymentMethod.value
    )
)
const paymentMethodDescription = (method) => {
    const code = String(method.code || '').trim().toLowerCase()
    const name = String(method.name || '').trim().toLowerCase()
    if (code === 'cash' || name === 'cash') return 'Payment is recorded as paid immediately.'
    return 'Payment stays pending until it is confirmed by the payment provider.'
}
const paymentMethodIcon = (method) => {
    const code = String(method.code || '').trim().toLowerCase()
    const name = String(method.name || '').trim().toLowerCase()
    if (code === 'cash' || name === 'cash') return 'bi-cash-coin'
    if (code.includes('aba') || name.includes('aba')) return 'bi-qr-code'
    return 'bi-credit-card'
}
const cartQuantity = computed(() =>
    cartItems.value.reduce((sum, item) => sum + Number(item.quantity || 0), 0)
)

const formatMoney = (value) =>
    Number(value || 0).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })

const stockFor = (product) => Number(product.available_stock || 0)
const productImage = (product) =>
    imageFailures.value.has(product.id) ? '' : resolveImageUrl(product.image)

function imageFailed(productId) {
    imageFailures.value = new Set([...imageFailures.value, productId])
}
function scrollToCart() {
    document.getElementById('pos-current-cart')?.scrollIntoView({
        behavior: 'instant',
        block: 'start'
    })
}
function errorMessage(error, fallback) {
    const validationErrors = error.response?.data?.errors
    return validationErrors
        ? Object.values(validationErrors).flat().join(' ')
        : error.response?.data?.message || fallback
}
async function loadProducts() {
    try {
        await pos.getProducts({
            search: search.value.trim(),
            categoryId: selectedCategoryId.value
        })
    } catch (error) {
        pos.pageError = errorMessage(error, 'Could not load products.')
    }
}
async function addToCart(product) {
    if (addingProductId.value || stockFor(product) < 1) return
    addingProductId.value = product.id
    pos.pageError = ''
    try {
        await pos.addToCart(product.id)
        await pos.getCart()
    } catch (error) {
        pos.pageError = errorMessage(error, 'Could not add this product to the cart.')
    } finally {
        addingProductId.value = null
    }
}
async function updateQuantity(item, action) {
    if (changingCartItemId.value) return
    changingCartItemId.value = item.id
    pos.pageError = ''
    try {
        if (action === 'remove') await pos.removeCartItem(item.id)
        else await pos.changeCartQuantity(item.id, action)
        await pos.getCart()
    } catch (error) {
        pos.pageError = errorMessage(error, 'Could not update the cart.')
    } finally {
        changingCartItemId.value = null
    }
}
async function openCheckout() {
    checkoutError.value = ''
    checkoutResult.value = null
    checkoutLoading.value = true
    try {
        pos.checkoutData = await pos.getCheckout()
        if (!supportedPaymentMethods.value.length) {
            checkoutError.value = 'No active payment methods are available. Activate a payment method in Payment Methods before checkout.'
        } else {
            selectedPaymentMethod.value = String(supportedPaymentMethods.value[0].id)
            checkoutOpen.value = true
        }
    } catch (error) {
        checkoutError.value = errorMessage(error, 'Could not prepare checkout.')
        window.location.reload()
        checkoutOpen.value = true
    } finally {
        checkoutLoading.value = false
    }
}

async function confirmCheckout() {
    if (checkoutLoading.value || !selectedPaymentMethod.value) return
    const method = selectedPaymentMethodDetails.value
    const methodName = `${method?.code || ''} ${method?.name || ''}`
        .toLowerCase()
    if (methodName.includes('aba')) {
        await openAbaPayWay()
        return
    }
    checkoutLoading.value = true
    checkoutError.value = ''

    try {
        const result = await pos.confirmCheckout(
            Number(selectedPaymentMethod.value)
        )
        checkoutResult.value = result
        checkoutOpen.value = false
        await Promise.all([
            pos.getCart(),
            loadProducts()
        ])
    } catch (error) {
        checkoutError.value =
            error.response?.data?.message || 'Checkout failed.'
    } finally {
        checkoutLoading.value = false
    }
}


onMounted(async () => {
    await Promise.all([
        loadProducts(),
        pos.getCategories().catch((error) => {
            pos.pageError = errorMessage(error, 'Could not load product categories.')
        }),
        pos.getCart().catch((error) => {
            pos.pageError = errorMessage(error, 'Could not load the cart.')
        }),
        pos.getPaymentMethods().catch((error) => {
            pos.pageError = errorMessage(error, 'Could not load payment methods.')
        })
    ])
})
</script>

<template>
    <div class="pos-page container-fluid px-3 px-md-4 py-4" :class="{ 'pos-dark-mode': isDarkMode }">
        <div v-if="pos.pageError" class="alert alert-danger alert-dismissible" role="alert">
            {{ pos.pageError }}
            <button type="button" class="btn-close" aria-label="Dismiss" @click="pos.pageError = ''"></button>
        </div>
        <div v-if="checkoutResult" class="alert alert-success" role="status">
            {{ checkoutResult.message }}
            <span v-if="checkoutResult.data?.order?.invoice_no" class="ms-1">
                Invoice {{ checkoutResult.data.order.invoice_no }}.
            </span>
            <span v-if="checkoutResult.data?.payment?.status === 'pending'" class="ms-1">
                Payment is pending; confirm it with the payment provider before handing over the order.
            </span>
        </div>
        <div v-if="checkoutError && !checkoutOpen" class="alert alert-danger" role="alert">{{ checkoutError }}</div>

        <div class="row pos-layout g-3 align-items-stretch">
            <section class="col-12 col-lg-8" aria-label="Products">
                <div class="card pos-panel pos-products-panel">
                    <div class="pos-products-heading">
                        <div>
                            <h2 class="pos-section-title">Products</h2>
                            <p class="pos-section-subtitle">
                                {{ pos.storeName ? `Stock at ${pos.storeName} · ` : '' }}{{ pos.products.length }}
                                products found
                            </p>
                        </div>
                        <div class="pos-heading-actions">
                            <button class="btn btn-outline-secondary pos-theme-toggle"
                                :class="{ 'pos-theme-toggle-dark': isDarkMode }" type="button"
                                :aria-label="isDarkMode ? 'Switch to light mode' : 'Switch to dark mode'"
                                @click="toggleTheme">
                                <i class="bi" :class="isDarkMode ? 'bi-sun' : 'bi-moon-stars'" aria-hidden="true"></i>
                                {{ isDarkMode ? 'Light mode' : 'Dark mode' }}
                            </button>
                            <label class="visually-hidden" for="pos-category-filter">Filter by category</label>
                            <select id="pos-category-filter" v-model="selectedCategoryId"
                                class="form-select pos-category-filter" @change="loadProducts()">
                                <option value="">All categories</option>
                                <option v-for="category in pos.categories" :key="category.id"
                                    :value="String(category.id)">
                                    {{ category.name }}
                                </option>
                            </select>
                            <form class="pos-search" @submit.prevent="loadProducts()">
                                <label class="visually-hidden" for="pos-search">Search products</label>
                                <i class="bi bi-search"></i>
                                <input id="pos-search" v-model="search" class="form-control" type="search"
                                    placeholder="Search name, barcode, SKU">
                                <button class="btn btn-outline-primary" type="submit" :disabled="pos.productsLoading">
                                    Search
                                </button>
                            </form>
                            <a class="btn btn-primary pos-open-cart" href="#pos-current-cart"
                                @click.prevent="scrollToCart">
                                <i class="bi bi-basket2 me-2" aria-hidden="true"></i>
                                Cart <span class="pos-cart-badge">{{ cartQuantity }}</span>
                            </a>
                        </div>
                    </div>

                    <div class="card-body pos-products-body">
                        <div v-if="pos.productsLoading && !pos.products.length" class="pos-product-grid">
                            <div v-for="item in 8" :key="item" class="pos-product-skeleton"></div>
                        </div>
                        <div v-else-if="pos.products.length" class="pos-product-grid">
                            <article v-for="product in pos.products" :key="product.id" class="pos-product-card">
                                <div class="pos-product-image">
                                    <img v-if="productImage(product)" :src="productImage(product)" :alt="product.name"
                                        loading="lazy" @error="imageFailed(product.id)">
                                    <i v-else class="bi bi-box-seam" aria-hidden="true"></i>
                                </div>
                                <div class="pos-product-info">
                                    <h2 class="pos-product-name" :title="product.name">{{ product.name }}</h2>
                                    <div class="pos-product-meta">
                                        <span class="pos-product-price">{{ formatMoney(product.sale_price) }}</span>
                                        <span class="pos-stock" :class="{ 'pos-stock-empty': stockFor(product) < 1 }">
                                            {{ stockFor(product) }} in stock
                                        </span>
                                    </div>
                                    <button class="btn btn-primary w-100 mt-3 pos-add-button" type="button"
                                        :disabled="stockFor(product) < 1 || addingProductId === product.id"
                                        @click="addToCart(product)">
                                        <span v-if="addingProductId === product.id"
                                            class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                                        <i v-else class="bi bi-plus-lg me-1"></i>
                                        {{ stockFor(product) < 1 ? 'Out of stock' : 'Add to cart' }} </button>
                                </div>
                            </article>
                        </div>
                        <div v-else class="pos-empty text-center py-5">
                            <i class="bi bi-box-seam"></i>
                            <h2 class="h5 mt-3">No products found</h2>
                            <p class="text-muted mb-0">Try another search or check that products are active and in
                                stock.</p>
                        </div>

                    </div>
                </div>
            </section>
            <aside id="pos-current-cart" class="col-12 col-lg-4 pos-cart-column" aria-label="Current cart">
                <div class="card pos-panel pos-cart-card">
                    <div class="pos-cart-heading">
                        <div>
                            <h2 class="pos-section-title"><i class="bi bi-basket2 me-2" aria-hidden="true"></i>Current
                                order</h2>
                            <p class="pos-section-subtitle">{{ cartItems.length }} product lines</p>
                        </div>
                        <span class="pos-cart-count">{{ cartQuantity }} {{ cartQuantity === 1 ? 'item' : 'items'
                            }}</span>
                    </div>
                    <div class="card-body pos-cart-body">
                        <div v-if="pos.cartLoading && !cartItems.length" class="text-center text-muted py-5">
                            <span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Loading
                            cart...
                        </div>
                        <div v-else-if="!cartItems.length" class="pos-empty py-5">
                            <i class="bi bi-cart3"></i>
                            <p class="mt-3 mb-0">Your cart is empty. Add a product to start an order.</p>
                        </div>
                        <ul v-else class="list-unstyled pos-cart-items mb-0">
                            <li v-for="item in cartItems" :key="item.id" class="pos-cart-item">
                                <div class="min-w-0">
                                    <div class="fw-semibold text-truncate">{{ item.product?.name || 'Product' }}</div>
                                    <small class="text-muted">{{ formatMoney(item.price) }} each</small>
                                </div>
                                <div class="text-end">
                                    <div class="pos-quantity-control">
                                        <button class="btn btn-light btn-sm" type="button"
                                            aria-label="Decrease quantity" :disabled="changingCartItemId === item.id"
                                            @click="updateQuantity(item, 'decrease')">
                                            <i class="bi bi-dash"></i>
                                        </button>
                                        <span>{{ item.quantity }}</span>
                                        <button class="btn btn-light btn-sm" type="button"
                                            aria-label="Increase quantity" :disabled="changingCartItemId === item.id"
                                            @click="updateQuantity(item, 'increase')">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                    </div>
                                    <button class="btn pos-remove-button mt-2" type="button"
                                        :aria-label="`Remove ${item.product?.name || 'product'} from cart`"
                                        :disabled="changingCartItemId === item.id"
                                        @click="updateQuantity(item, 'remove')">
                                        <i class="bi bi-trash3" aria-hidden="true"></i>
                                        <span>Remove</span>
                                    </button>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div class="pos-cart-footer">
                        <div class="pos-subtotal">
                            <span>Subtotal</span>
                            <span>{{ formatMoney(pos.cart?.total) }}</span>
                        </div>
                        <p class="pos-tax-note">Tax and final total are calculated at checkout.</p>
                        <button class="btn btn-primary w-100 pos-checkout-button" type="button"
                            :disabled="!cartItems.length || checkoutLoading" @click="openCheckout">
                            <span v-if="checkoutLoading" class="spinner-border spinner-border-sm me-2"
                                aria-hidden="true"></span>
                            <i v-else class="bi bi-credit-card me-2"></i>
                            Checkout
                        </button>
                        <p class="pos-shift-note">
                            Need to start a shift? <router-link to="/shifts">Open My Shift</router-link>
                        </p>
                    </div>
                </div>
            </aside>
        </div>

        <div v-if="checkoutOpen" class="pos-modal-backdrop" role="presentation" @click.self="checkoutOpen = false">
            <section class="pos-checkout-modal card border-0 shadow" role="dialog" aria-modal="true"
                aria-labelledby="pos-checkout-title">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted">Review order</small>
                        <h2 id="pos-checkout-title" class="h5 mb-0">Checkout</h2>
                    </div>
                    <button class="btn-close" type="button" aria-label="Close" @click="checkoutOpen = false"></button>
                </div>
                <div class="card-body">
                    <div v-if="checkoutError" class="alert alert-danger" role="alert">{{ checkoutError }}</div>
                    <div v-if="pos.checkoutData" class="mb-4">
                        <div v-for="item in pos.checkoutData.items" :key="item.product_id"
                            class="d-flex justify-content-between gap-3 py-2 border-bottom">
                            <span>{{ item.product_name }} <small class="text-muted">× {{ item.quantity }}</small></span>
                            <span class="text-nowrap">{{ formatMoney(item.total_price) }}</span>
                        </div>
                        <dl class="pos-totals mt-3 mb-0">
                            <div>
                                <dt>Subtotal</dt>
                                <dd>{{ formatMoney(pos.checkoutData.subtotal) }}</dd>
                            </div>
                            <div>
                                <dt>Tax</dt>
                                <dd>{{ formatMoney(pos.checkoutData.tax_amount) }}</dd>
                            </div>
                            <div v-if="Number(pos.checkoutData.discount_amount)">
                                <dt>Discount</dt>
                                <dd>−{{ formatMoney(pos.checkoutData.discount_amount) }}</dd>
                            </div>
                            <div class="pos-grand-total">
                                <dt>Total due</dt>
                                <dd>{{ formatMoney(pos.checkoutData.grand_total) }}</dd>
                            </div>
                        </dl>
                    </div>
                    <div class="pos-payment-section">
                        <h3 class="pos-payment-title">Payment method</h3>
                        <div class="pos-payment-methods" role="radiogroup" aria-label="Payment method">
                            <label v-for="method in supportedPaymentMethods" :key="method.id" class="pos-payment-option"
                                :class="{ 'pos-payment-option-selected': selectedPaymentMethod === String(method.id) }">
                                <input v-model="selectedPaymentMethod" type="radio" name="pos-payment-method"
                                    :value="String(method.id)" :disabled="checkoutLoading">
                                <i class="bi" :class="paymentMethodIcon(method)" aria-hidden="true"></i>
                                <span class="pos-payment-copy">
                                    <strong>{{ method.name }}</strong>
                                    <small>{{ paymentMethodDescription(method) }}</small>
                                </span>
                            </label>
                        </div>
                        <p v-if="!supportedPaymentMethods.length" class="text-muted small mb-0">
                            No supported active payment methods are available.
                        </p>
                        <p v-else-if="selectedPaymentMethodDetails" class="pos-payment-note">
                            {{ paymentMethodDescription(selectedPaymentMethodDetails) }}
                        </p>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end gap-2">
                    <button class="btn btn-outline-secondary" type="button" :disabled="checkoutLoading"
                        @click="checkoutOpen = false">Cancel</button>
                    <button class="btn btn-primary" type="button"
                        :disabled="checkoutLoading || !selectedPaymentMethod || !pos.checkoutData"
                        @click="confirmCheckout">
                        <span v-if="checkoutLoading" class="spinner-border spinner-border-sm me-2"
                            aria-hidden="true"></span>
                        Confirm payment
                    </button>
                </div>
            </section>
        </div>
        <div v-if="abaPaymentOpen && abaPayment" class="pos-modal-backdrop" @click.self="abaPaymentOpen = false">
            <div class="pos-checkout-modal">
                <h3>ABA PayWay</h3>

                <p>Payment ID: {{ abaPayment.paymentId }}</p>
                <p>Order ID: {{ abaPayment.orderId }}</p>
                <p>Transaction ID: {{ abaPayment.transactionId }}</p>
                <p>
                    Amount: {{ abaPayment.amount }}
                    {{ abaPayment.currency }}
                </p>

                <img :src="abaPayment.qrImage" alt="ABA PayWay QR Code"
                    style="display:block;width:260px;max-width:100%;margin:20px auto" />

                <button type="button" @click="abaPaymentOpen = false">
                    Close
                </button>
            </div>
        </div>
        <router-link class="pos-dashboard-fab" to="/dashboard" aria-label="Go to dashboard">
            <i class="bi bi-grid" aria-hidden="true"></i>
            <span>Dashboard</span>
        </router-link>
    </div>
</template>

<style scoped>
.pos-page {
    min-height: 100%;
    background-color: var(--ui-page, var(--bs-body-bg));
    color: var(--ui-text, var(--bs-body-color));
}

:global(.pos-dark-mode) {
    color-scheme: dark;
    --ui-border: #475569;
    --bs-secondary-color: var(--ui-muted);
    --bs-secondary-bg: var(--ui-surface-muted);
    --bs-tertiary-bg: var(--ui-surface-muted);
}

.pos-page .text-muted {
    color: var(--ui-muted, var(--bs-secondary-color)) !important;
}

.pos-page .alert-danger {
    border-color: color-mix(in srgb, var(--ui-danger, var(--bs-danger)) 28%, transparent);
    background: var(--ui-danger-soft, #fef3f2);
    color: var(--ui-danger, var(--bs-danger));
}

.pos-page .alert-success {
    border-color: color-mix(in srgb, var(--ui-success, var(--bs-success)) 28%, transparent);
    background: var(--ui-success-soft, #f0fdf4);
    color: var(--ui-success, var(--bs-success));
}

.pos-section-subtitle {
    margin: .25rem 0 0;
    color: var(--ui-muted, var(--bs-secondary-color));
    font-size: .84rem;
}

.pos-cart-count {
    display: inline-flex;
    align-items: center;
    min-height: 2.25rem;
    padding: .45rem .75rem;
    border: 1px solid var(--ui-border, var(--bs-border-color));
    border-radius: 999px;
    background: var(--ui-surface-muted, var(--bs-tertiary-bg));
    color: var(--ui-text-secondary, var(--bs-secondary-color));
    font-size: .78rem;
    font-weight: 600;
    white-space: nowrap;
}

.pos-panel {
    overflow: hidden;
    border: 1px solid var(--ui-border, var(--bs-border-color));
    border-radius: .85rem;
    background: var(--ui-surface, var(--bs-body-bg));
    box-shadow: var(--ui-shadow-sm, 0 2px 8px rgb(16 24 40 / .06));
}

.pos-layout>section,
.pos-cart-column {
    min-width: 0;
}

.pos-cart-column {
    scroll-margin-top: 1rem;
}

.pos-products-panel {
    display: flex;
    height: calc(100vh - 7rem);
    height: calc(100dvh - 7rem);
    min-height: 420px;
    flex-direction: column;
}

.pos-products-heading,
.pos-cart-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.15rem 1.25rem;
    border-bottom: 1px solid var(--ui-border, var(--bs-border-color));
}

.pos-heading-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: .75rem;
    flex: 1;
}

.pos-cart-badge {
    display: inline-grid;
    min-width: 1.35rem;
    height: 1.35rem;
    margin-left: .35rem;
    padding: 0 .25rem;
    place-items: center;
    border-radius: 999px;
    background: rgb(255 255 255 / .22);
    font-size: .75rem;
}

.pos-section-title {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
}

.pos-search {
    display: flex;
    align-items: center;
    width: min(100%, 410px);
    gap: .6rem;
    padding: .25rem;
    border: 1px solid var(--ui-border, var(--bs-border-color));
    border-radius: .65rem;
    background: var(--ui-control, var(--bs-body-bg));
}

.pos-category-filter {
    flex: 0 0 190px;
    width: 190px;
}

.pos-dark-mode .pos-category-filter {
    border-color: var(--ui-border);
    background-color: var(--ui-control);
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23cbd5e1' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
    color: var(--ui-text);
}

.pos-search>i {
    padding-left: .55rem;
    color: var(--ui-muted, var(--bs-secondary-color));
}

.pos-search .form-control {
    min-width: 0;
    padding-left: .15rem;
    border: 0;
    box-shadow: none;
}

.pos-dark-mode .pos-search .form-control {
    background-color: transparent;
    color: var(--ui-text);
}

.pos-search .form-control::placeholder {
    color: var(--ui-muted, var(--bs-secondary-color));
    opacity: 1;
}

.pos-search .form-control:focus {
    background: transparent;
}

.pos-search .btn {
    flex: 0 0 auto;
}

.pos-theme-toggle-dark {
    border-color: var(--ui-border);
    color: var(--ui-text-secondary);
    --bs-btn-color: var(--ui-text-secondary);
    --bs-btn-border-color: #64748b;
    --bs-btn-hover-color: var(--ui-text);
    --bs-btn-hover-bg: var(--ui-surface-muted);
    --bs-btn-hover-border-color: #94a3b8;
}

.pos-theme-toggle-dark:hover {
    background: var(--ui-surface-muted);
    color: var(--ui-text);
}

.pos-dark-mode .btn-primary {
    --bs-btn-color: #fff;
    --bs-btn-bg: #3b82f6;
    --bs-btn-border-color: #3b82f6;
    --bs-btn-hover-color: #fff;
    --bs-btn-hover-bg: #2563eb;
    --bs-btn-hover-border-color: #60a5fa;
    --bs-btn-focus-shadow-rgb: 59, 130, 246;
    --bs-btn-active-color: #fff;
    --bs-btn-active-bg: #1d4ed8;
    --bs-btn-active-border-color: #1d4ed8;
    --bs-btn-disabled-color: #fff;
    --bs-btn-disabled-bg: #3b82f6;
    --bs-btn-disabled-border-color: #3b82f6;
}

.pos-dark-mode .pos-open-cart {
    font-weight: 600;
    box-shadow: 0 2px 8px rgb(59 130 246 / .28);
}

.pos-dark-mode .pos-cart-badge {
    background: #dbeafe;
    color: #1e3a8a;
}

.pos-dark-mode .pos-search .btn-outline-primary {
    --bs-btn-color: #bfdbfe;
    --bs-btn-border-color: #60a5fa;
    --bs-btn-hover-color: #fff;
    --bs-btn-hover-bg: #1d4ed8;
    --bs-btn-hover-border-color: #60a5fa;
    --bs-btn-active-color: #fff;
    --bs-btn-active-bg: #1e40af;
    --bs-btn-active-border-color: #60a5fa;
}

.pos-products-body {
    min-height: 0;
    padding: 1.15rem;
    overflow-y: auto;
    overscroll-behavior: contain;
}

.pos-product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(min(100%, 165px), 1fr));
    gap: .85rem;
}

.pos-product-card {
    display: flex;
    overflow: hidden;
    flex-direction: column;
    min-width: 0;
    border: 1px solid var(--ui-border, var(--bs-border-color));
    border-radius: .7rem;
    background: var(--ui-surface, var(--bs-body-bg));
    transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
}

.pos-product-card:hover {
    transform: translateY(-2px);
    border-color: color-mix(in srgb, var(--ui-primary, var(--bs-primary)) 40%, var(--ui-border, var(--bs-border-color)));
    box-shadow: 0 6px 18px rgb(15 23 42 / .08);
}

.pos-product-image {
    display: grid;
    aspect-ratio: 4 / 3;
    place-items: center;
    overflow: hidden;
    background: var(--ui-surface-muted, var(--bs-tertiary-bg));
    color: var(--ui-muted, var(--bs-secondary-color));
    font-size: 1.7rem;
}

.pos-product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.pos-product-info {
    display: flex;
    flex: 1;
    flex-direction: column;
    padding: .75rem;
}

.pos-product-name {
    display: -webkit-box;
    overflow: hidden;
    min-height: 2.45rem;
    margin-bottom: .65rem;
    font-size: .86rem;
    font-weight: 600;
    line-height: 1.22rem;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}

.pos-product-meta {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: .4rem;
}

.pos-product-price {
    font-size: .92rem;
    font-weight: 700;
    white-space: nowrap;
}

.pos-stock {
    color: var(--ui-muted, var(--bs-secondary-color));
    font-size: .74rem;
    font-weight: 500;
    white-space: nowrap;
}

.pos-stock-empty {
    color: var(--ui-danger, var(--bs-danger));
}

.pos-add-button {
    margin-top: auto;
    font-size: .8rem;
    font-weight: 600;
}

.pos-cart-card {
    position: sticky;
    top: 1rem;
    display: flex;
    width: 100%;
    height: calc(100vh - 7rem);
    height: calc(100dvh - 7rem);
    min-height: 420px;
    flex-direction: column;
    border-radius: .85rem;
}

.pos-cart-heading {
    min-height: 76px;
    padding: 1rem 1.15rem;
}

.pos-cart-body {
    flex: 1;
    min-height: 0;
    padding: .4rem 1.15rem;
    overflow-y: auto;
}

.pos-cart-items {
    max-height: none;
}

.pos-cart-item {
    display: flex;
    justify-content: space-between;
    gap: .75rem;
    padding: .85rem 0;
    border-bottom: 1px solid var(--ui-border, var(--bs-border-color));
}

.pos-quantity-control {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: .4rem;
}

.pos-quantity-control .btn {
    display: grid;
    width: 30px;
    height: 30px;
    padding: 0;
    place-items: center;
    border: 1px solid var(--ui-border, var(--bs-border-color));
    border-radius: .45rem;
    background: var(--ui-surface-muted, var(--bs-tertiary-bg));
    color: var(--ui-text, var(--bs-body-color));
}

.pos-dark-mode .pos-quantity-control .btn {
    border-color: #64748b;
    background: #475569;
    color: #f8fafc;
}

.pos-quantity-control .btn:hover:not(:disabled) {
    border-color: var(--ui-primary, var(--bs-primary));
    background: var(--ui-primary-soft, var(--bs-primary-bg-subtle));
    color: var(--ui-primary, var(--bs-primary));
}

.pos-remove-button {
    display: inline-flex;
    min-height: 1.9rem;
    align-items: center;
    justify-content: center;
    gap: .35rem;
    padding: .25rem .55rem;
    border: 1px solid transparent;
    border-radius: .45rem;
    background: var(--ui-danger-soft, var(--bs-danger-bg-subtle));
    color: var(--ui-danger, var(--bs-danger));
    font-size: .75rem;
    font-weight: 600;
}

.pos-remove-button:hover:not(:disabled),
.pos-remove-button:focus-visible {
    border-color: color-mix(in srgb, var(--ui-danger, var(--bs-danger)) 35%, var(--ui-border, var(--bs-border-color)));
    background: var(--ui-danger-soft, var(--bs-danger-bg-subtle));
    color: var(--ui-danger, var(--bs-danger));
}

.pos-dark-mode .pos-quantity-control .btn:hover:not(:disabled) {
    border-color: #93c5fd;
    background: #1e40af;
    color: #fff;
}

.pos-empty {
    color: var(--ui-muted, var(--bs-secondary-color));
    text-align: center;
}

.pos-empty>i {
    font-size: 1.8rem;
}

.pos-cart-footer {
    flex: 0 0 auto;
    padding: 1rem 1.15rem 1.15rem;
    border-top: 1px solid var(--ui-border, var(--bs-border-color));
}

.pos-subtotal {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: .9rem;
}

.pos-subtotal>span:last-child {
    font-size: 1rem;
    font-weight: 700;
}

.pos-tax-note,
.pos-shift-note {
    margin: .4rem 0 .9rem;
    color: var(--ui-muted, var(--bs-secondary-color));
    font-size: .75rem;
}

.pos-checkout-button {
    min-height: 2.85rem;
    font-size: .95rem;
}

.pos-shift-note {
    margin: .7rem 0 0;
    text-align: center;
}

.pos-product-skeleton {
    height: 250px;
    border-radius: .7rem;
    background: linear-gradient(100deg, var(--ui-surface-muted, var(--bs-tertiary-bg)) 30%, var(--bs-secondary-bg) 50%, var(--ui-surface-muted, var(--bs-tertiary-bg)) 70%);
    background-size: 200% 100%;
    animation: pos-shimmer 1.3s ease-in-out infinite;
}

.pos-modal-backdrop {
    position: fixed;
    z-index: 1080;
    inset: 0;
    display: grid;
    place-items: center;
    overflow-y: auto;
    padding: 1rem;
    background: rgb(0 0 0 / 50%);
}

.pos-checkout-modal {
    width: min(100%, 540px);
    max-height: calc(100vh - 2rem);
    overflow: auto;
    border-color: var(--ui-border, var(--bs-border-color));
    background: var(--ui-surface, var(--bs-body-bg));
    color: var(--ui-text, var(--bs-body-color));
}

.pos-checkout-modal .card-header,
.pos-checkout-modal .card-footer {
    border-color: var(--ui-border, var(--bs-border-color));
    background: var(--ui-surface-muted, var(--bs-tertiary-bg));
    color: var(--ui-text, var(--bs-body-color));
}

.pos-checkout-modal .card-body {
    background: var(--ui-surface, var(--bs-body-bg));
    color: var(--ui-text, var(--bs-body-color));
}

.pos-checkout-modal .border-bottom {
    border-color: var(--ui-border, var(--bs-border-color)) !important;
}

.pos-checkout-modal .form-select {
    border-color: var(--ui-border, var(--bs-border-color));
    background-color: var(--ui-control, var(--bs-body-bg));
    color: var(--ui-text, var(--bs-body-color));
}

:global(.dark-theme) .pos-checkout-modal .btn-close {
    filter: invert(1) grayscale(100%) brightness(200%);
}

.pos-totals>div {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: .5rem;
}

.pos-totals dt,
.pos-totals dd {
    margin: 0;
}

.pos-grand-total {
    padding-top: .75rem;
    border-top: 1px solid var(--ui-border, var(--bs-border-color));
    font-size: 1.1rem;
    font-weight: 700;
}

.pos-payment-section {
    margin-top: 1.25rem;
}

.pos-payment-title {
    margin-bottom: .65rem;
    font-size: .9rem;
    font-weight: 700;
}

.pos-payment-methods {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(145px, 1fr));
    gap: .65rem;
}

.pos-payment-option {
    display: flex;
    min-width: 0;
    align-items: center;
    gap: .65rem;
    padding: .8rem;
    border: 1px solid var(--ui-border, var(--bs-border-color));
    border-radius: .65rem;
    background: var(--ui-surface, var(--bs-body-bg));
    cursor: pointer;
}

.pos-payment-option-selected {
    border-color: var(--ui-primary, var(--bs-primary));
    background: var(--ui-primary-soft, var(--bs-primary-bg-subtle));
}

.pos-payment-option>input {
    flex: 0 0 auto;
    accent-color: var(--ui-primary, var(--bs-primary));
}

.pos-payment-option>.bi {
    color: var(--ui-primary, var(--bs-primary));
    font-size: 1.15rem;
}

.pos-payment-copy {
    display: grid;
    min-width: 0;
    gap: .15rem;
}

.pos-payment-copy strong {
    font-size: .82rem;
}

.pos-payment-copy small,
.pos-payment-note {
    color: var(--ui-muted, var(--bs-secondary-color));
    font-size: .72rem;
}

.pos-payment-note {
    margin: .65rem 0 0;
}

.pos-dashboard-fab {
    position: fixed;
    z-index: 1040;
    right: 1.5rem;
    bottom: 1.5rem;
    display: inline-flex;
    min-height: 2.75rem;
    align-items: center;
    gap: .6rem;
    padding: .7rem 1rem;
    border: 1px solid var(--ui-primary, var(--bs-primary));
    border-radius: 999px;
    background: var(--ui-primary, var(--bs-primary));
    box-shadow: 0 6px 20px rgb(15 23 42 / .2);
    color: #fff;
    font-size: .85rem;
    font-weight: 600;
    text-decoration: none;
    transition: transform .15s ease, box-shadow .15s ease;
}

.pos-dashboard-fab:hover {
    transform: translateY(-2px);
    box-shadow: 0 9px 24px rgb(15 23 42 / .25);
    color: #fff;
}

@keyframes pos-shimmer {
    to {
        background-position-x: -200%;
    }
}

@media (max-height: 700px) and (min-width: 1200px) {
    .pos-products-panel {
        height: calc(100vh - 6rem);
        min-height: 320px;
    }

    .pos-cart-card {
        height: calc(100vh - 6rem);
        min-height: 320px;
    }
}

@media (max-width: 991.98px) {
    .pos-cart-card {
        position: static;
        height: calc(100vh - 7rem);
        height: calc(100dvh - 7rem);
    }
}

@media (max-width: 575.98px) {

    .pos-products-heading,
    .pos-heading-actions {
        align-items: stretch;
        flex-direction: column;
    }

    .pos-dashboard-fab {
        right: 1rem;
        bottom: max(1rem, env(safe-area-inset-bottom));
    }

    .pos-search {
        width: 100%;
    }

    .pos-category-filter {
        width: 100%;
        flex-basis: auto;
    }

    .pos-open-cart {
        width: 100%;
    }

    .pos-products-body {
        padding: .85rem;
    }

    .pos-product-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .65rem;
    }

    .pos-product-info {
        padding: .6rem;
    }

    .pos-product-meta {
        align-items: flex-start;
        flex-direction: column;
        gap: .1rem;
    }
}
</style>
