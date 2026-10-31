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
const cartQuantity = computed(() =>
    cartItems.value.reduce((sum, item) => sum + Number(item.quantity || 0), 0)
)

const paymentMethodDescription = (method) => {
    const code = String(method.code || '').trim().toLowerCase()
    const name = String(method.name || '').trim().toLowerCase()
    if (code === 'cash' || name === 'cash') return 'Recorded as paid immediately.'
    return 'Stays pending until the provider confirms it.'
}

const paymentMethodIcon = (method) => {
    const code = String(method.code || '').trim().toLowerCase()
    const name = String(method.name || '').trim().toLowerCase()
    if (code === 'cash' || name === 'cash') return 'bi-cash-coin'
    if (code.includes('aba') || name.includes('aba')) return 'bi-qr-code'
    return 'bi-credit-card'
}

const formatMoney = (value) =>
    Number(value || 0).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })

const lineTotal = (item) => Number(item.price || 0) * Number(item.quantity || 0)
const stockFor = (product) => Number(product.available_stock || 0)
const productImage = (product) =>
    imageFailures.value.has(product.id) ? '' : resolveImageUrl(product.image)

function imageFailed(productId) {
    imageFailures.value = new Set([...imageFailures.value, productId])
}

function scrollToCart() {
    document.getElementById('pos-current-cart')?.scrollIntoView({
        behavior: 'smooth',
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
            checkoutError.value =
                'No active payment methods are available. Activate a payment method in Payment Methods before checkout.'
        } else {
            selectedPaymentMethod.value = String(supportedPaymentMethods.value[0].id)
            checkoutOpen.value = true
        }
    } catch (error) {
        checkoutError.value = errorMessage(error, 'Could not prepare checkout.')
    } finally {
        checkoutLoading.value = false
    }
}

async function confirmCheckout() {
    if (checkoutLoading.value || !selectedPaymentMethod.value) return
    const method = selectedPaymentMethodDetails.value
    const methodName = `${method?.code || ''} ${method?.name || ''}`.toLowerCase()
    if (methodName.includes('aba')) {
        await openAbaPayWay()
        return
    }
    checkoutLoading.value = true
    checkoutError.value = ''
    try {
        const result = await pos.confirmCheckout(Number(selectedPaymentMethod.value))
        checkoutResult.value = result
        checkoutOpen.value = false
        await Promise.all([pos.getCart(), loadProducts()])
    } catch (error) {
        checkoutError.value = error.response?.data?.message || 'Checkout failed.'
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
        <div v-if="pos.pageError" class="pos-alert pos-alert-danger" role="alert">
            <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
            <span>{{ pos.pageError }}</span>
            <button type="button" class="btn-close" aria-label="Dismiss" @click="pos.pageError = ''"></button>
        </div>

        <div v-if="checkoutResult" class="pos-alert pos-alert-success" role="status">
            <i class="bi bi-check-circle" aria-hidden="true"></i>
            <span>
                {{ checkoutResult.message }}
                <template v-if="checkoutResult.data?.order?.invoice_no">
                    Invoice {{ checkoutResult.data.order.invoice_no }}.
                </template>
                <template v-if="checkoutResult.data?.payment?.status === 'pending'">
                    Payment is pending. Confirm it with the payment provider before handing over the order.
                </template>
            </span>
            <button type="button" class="btn-close" aria-label="Dismiss" @click="checkoutResult = null"></button>
        </div>

        <div v-if="checkoutError && !checkoutOpen" class="pos-alert pos-alert-danger" role="alert">
            <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
            <span>{{ checkoutError }}</span>
            <button type="button" class="btn-close" aria-label="Dismiss" @click="checkoutError = ''"></button>
        </div>

        <div class="row pos-layout g-3 align-items-stretch">
            <section class="col-12 col-lg-8" aria-label="Products">
                <div class="pos-panel pos-products-panel">
                    <header class="pos-toolbar">
                        <div class="pos-toolbar-title">
                            <h1>Products</h1>
                            <p>
                                {{ pos.storeName ? `${pos.storeName} · ` : '' }}{{ pos.products.length }}
                                {{ pos.products.length === 1 ? 'product' : 'products' }}
                            </p>
                        </div>

                        <div class="pos-toolbar-actions">
                            <form class="pos-search" @submit.prevent="loadProducts()">
                                <label class="visually-hidden" for="pos-search">Search products</label>
                                <i class="bi bi-search" aria-hidden="true"></i>
                                <input id="pos-search" v-model="search" type="search"
                                    placeholder="Search name, barcode, SKU">
                                <button type="submit" :disabled="pos.productsLoading">Search</button>
                            </form>

                            <label class="visually-hidden" for="pos-category-filter">Filter by category</label>
                            <select id="pos-category-filter" v-model="selectedCategoryId"
                                class="form-select pos-category-filter" @change="loadProducts()">
                                <option value="">All categories</option>
                                <option v-for="category in pos.categories" :key="category.id"
                                    :value="String(category.id)">
                                    {{ category.name }}
                                </option>
                            </select>

                            <button class="pos-icon-button" type="button"
                                :aria-label="isDarkMode ? 'Switch to light mode' : 'Switch to dark mode'"
                                :title="isDarkMode ? 'Light mode' : 'Dark mode'" @click="toggleTheme">
                                <i class="bi" :class="isDarkMode ? 'bi-sun' : 'bi-moon-stars'" aria-hidden="true"></i>
                            </button>

                            <a class="pos-cart-link" href="#pos-current-cart" @click.prevent="scrollToCart">
                                <i class="bi bi-basket2" aria-hidden="true"></i>
                                <span>Cart</span>
                                <span :key="cartQuantity" class="pos-cart-badge">{{ cartQuantity }}</span>
                            </a>
                        </div>
                    </header>

                    <div class="pos-products-body">
                        <div v-if="pos.productsLoading && !pos.products.length" class="pos-product-grid">
                            <div v-for="item in 8" :key="item" class="pos-product-skeleton"></div>
                        </div>

                        <div v-else-if="pos.products.length" class="pos-product-grid">
                            <article v-for="(product, index) in pos.products" :key="product.id"
                                class="pos-product-card" :class="{ 'is-out': stockFor(product) < 1 }"
                                :style="{ '--i': Math.min(index, 11) }">
                                <div class="pos-product-image">
                                    <img v-if="productImage(product)" :src="productImage(product)" :alt="product.name"
                                        loading="lazy" @error="imageFailed(product.id)">
                                    <i v-else class="bi bi-box-seam" aria-hidden="true"></i>
                                    <span class="pos-stock-pill" :class="{ empty: stockFor(product) < 1 }">
                                        {{ stockFor(product) < 1 ? 'Out of stock' : `${stockFor(product)} in stock` }}
                                            </span>
                                </div>

                                <div class="pos-product-info">
                                    <h3 class="pos-product-name" :title="product.name">{{ product.name }}</h3>
                                    <div class="pos-product-price">{{ formatMoney(product.sale_price) }}</div>
                                    <button class="pos-add-button" type="button"
                                        :disabled="stockFor(product) < 1 || addingProductId === product.id"
                                        @click="addToCart(product)">
                                        <span v-if="addingProductId === product.id"
                                            class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                                        <i v-else class="bi bi-plus-lg" aria-hidden="true"></i>
                                        {{ stockFor(product) < 1 ? 'Unavailable' : 'Add to cart' }} </button>
                                </div>
                            </article>
                        </div>

                        <div v-else class="pos-empty">
                            <i class="bi bi-box-seam" aria-hidden="true"></i>
                            <h2>No products found</h2>
                            <p>Try another search, or check that products are active and in stock.</p>
                        </div>
                    </div>
                </div>
            </section>

            <aside id="pos-current-cart" class="col-12 col-lg-4 pos-cart-column" aria-label="Current cart">
                <div class="pos-panel pos-cart-card">
                    <header class="pos-cart-heading">
                        <div class="pos-toolbar-title">
                            <h2>Current order</h2>
                            <p>{{ cartItems.length }} {{ cartItems.length === 1 ? 'line' : 'lines' }}</p>
                        </div>
                        <span class="pos-cart-count">
                            {{ cartQuantity }} {{ cartQuantity === 1 ? 'item' : 'items' }}
                        </span>
                    </header>

                    <div class="pos-cart-body">
                        <div v-if="pos.cartLoading && !cartItems.length" class="pos-empty">
                            <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                            <p>Loading cart...</p>
                        </div>

                        <div v-else-if="!cartItems.length" class="pos-empty">
                            <i class="bi bi-cart3" aria-hidden="true"></i>
                            <h2>Cart is empty</h2>
                            <p>Add a product to start an order.</p>
                        </div>

                        <TransitionGroup v-else name="pos-cart" tag="ul" class="pos-cart-items">
                            <li v-for="item in cartItems" :key="item.id" class="pos-cart-item">
                                <div class="pos-cart-item-main">
                                    <strong class="pos-cart-item-name">{{ item.product?.name || 'Product' }}</strong>
                                    <small>{{ formatMoney(item.price) }} each</small>
                                    <div class="pos-quantity-control">
                                        <button type="button" aria-label="Decrease quantity"
                                            :disabled="changingCartItemId === item.id"
                                            @click="updateQuantity(item, 'decrease')">
                                            <i class="bi bi-dash" aria-hidden="true"></i>
                                        </button>
                                        <span>{{ item.quantity }}</span>
                                        <button type="button" aria-label="Increase quantity"
                                            :disabled="changingCartItemId === item.id"
                                            @click="updateQuantity(item, 'increase')">
                                            <i class="bi bi-plus" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="pos-cart-item-side">
                                    <strong>{{ formatMoney(lineTotal(item)) }}</strong>
                                    <button class="pos-remove-button" type="button"
                                        :aria-label="`Remove ${item.product?.name || 'product'} from cart`"
                                        :disabled="changingCartItemId === item.id"
                                        @click="updateQuantity(item, 'remove')">
                                        <i class="bi bi-trash3" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </li>
                        </TransitionGroup>
                    </div>

                    <footer class="pos-cart-footer">
                        <div class="pos-subtotal">
                            <span>Subtotal</span>
                            <strong>{{ formatMoney(pos.cart?.total) }}</strong>
                        </div>
                        <p class="pos-note">Tax and final total are calculated at checkout.</p>
                        <button class="pos-checkout-button" type="button"
                            :disabled="!cartItems.length || checkoutLoading" @click="openCheckout">
                            <span v-if="checkoutLoading" class="spinner-border spinner-border-sm"
                                aria-hidden="true"></span>
                            <i v-else class="bi bi-credit-card" aria-hidden="true"></i>
                            Checkout
                        </button>
                        <p class="pos-note pos-center">
                            Need to start a shift? <router-link to="/shifts">Open My Shift</router-link>
                        </p>
                    </footer>
                </div>
            </aside>
        </div>

        <Transition name="pos-modal">
            <div v-if="checkoutOpen" class="pos-modal-backdrop" role="presentation"
                @click.self="checkoutOpen = false">
                <section class="pos-modal" role="dialog" aria-modal="true" aria-labelledby="pos-checkout-title">
                    <header class="pos-modal-header">
                        <div>
                            <small>Review order</small>
                            <h2 id="pos-checkout-title">Checkout</h2>
                        </div>
                        <button class="btn-close" type="button" aria-label="Close"
                            @click="checkoutOpen = false"></button>
                    </header>

                    <div class="pos-modal-body">
                        <div v-if="checkoutError" class="pos-alert pos-alert-danger" role="alert">
                            <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
                            <span>{{ checkoutError }}</span>
                        </div>

                        <div v-if="pos.checkoutData" class="pos-summary">
                            <div v-for="item in pos.checkoutData.items" :key="item.product_id"
                                class="pos-summary-row">
                                <span>{{ item.product_name }} <small>× {{ item.quantity }}</small></span>
                                <span>{{ formatMoney(item.total_price) }}</span>
                            </div>
                            <dl class="pos-totals">
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
                            <h3>Payment method</h3>
                            <div class="pos-payment-methods" role="radiogroup" aria-label="Payment method">
                                <label v-for="method in supportedPaymentMethods" :key="method.id"
                                    class="pos-payment-option"
                                    :class="{ selected: selectedPaymentMethod === String(method.id) }">
                                    <input v-model="selectedPaymentMethod" type="radio" name="pos-payment-method"
                                        :value="String(method.id)" :disabled="checkoutLoading">
                                    <i class="bi" :class="paymentMethodIcon(method)" aria-hidden="true"></i>
                                    <span class="pos-payment-copy">
                                        <strong>{{ method.name }}</strong>
                                        <small>{{ paymentMethodDescription(method) }}</small>
                                    </span>
                                </label>
                            </div>
                            <p v-if="!supportedPaymentMethods.length" class="pos-note">
                                No supported active payment methods are available.
                            </p>
                        </div>
                    </div>

                    <footer class="pos-modal-footer">
                        <button class="pos-button-secondary" type="button" :disabled="checkoutLoading"
                            @click="checkoutOpen = false">Cancel</button>
                        <button class="pos-button-primary" type="button"
                            :disabled="checkoutLoading || !selectedPaymentMethod || !pos.checkoutData"
                            @click="confirmCheckout">
                            <span v-if="checkoutLoading" class="spinner-border spinner-border-sm"
                                aria-hidden="true"></span>
                            Confirm payment
                        </button>
                    </footer>
                </section>
            </div>
        </Transition>

        <Transition name="pos-modal">
            <div v-if="abaPaymentOpen && abaPayment" class="pos-modal-backdrop" role="presentation"
                @click.self="abaPaymentOpen = false">
                <section class="pos-modal pos-modal-narrow" role="dialog" aria-modal="true"
                    aria-labelledby="pos-aba-title">
                    <header class="pos-modal-header">
                        <div>
                            <small>Scan to pay</small>
                            <h2 id="pos-aba-title">ABA PayWay</h2>
                        </div>
                        <button class="btn-close" type="button" aria-label="Close"
                            @click="abaPaymentOpen = false"></button>
                    </header>

                    <div class="pos-modal-body">
                        <img class="pos-qr" :src="abaPayment.qrImage" alt="ABA PayWay QR Code">
                        <dl class="pos-totals">
                            <div>
                                <dt>Amount</dt>
                                <dd>{{ abaPayment.amount }} {{ abaPayment.currency }}</dd>
                            </div>
                            <div>
                                <dt>Payment ID</dt>
                                <dd>{{ abaPayment.paymentId }}</dd>
                            </div>
                            <div>
                                <dt>Order ID</dt>
                                <dd>{{ abaPayment.orderId }}</dd>
                            </div>
                            <div>
                                <dt>Transaction ID</dt>
                                <dd>{{ abaPayment.transactionId }}</dd>
                            </div>
                        </dl>
                    </div>

                    <footer class="pos-modal-footer">
                        <button class="pos-button-secondary" type="button" @click="abaPaymentOpen = false">
                            Close
                        </button>
                    </footer>
                </section>
            </div>
        </Transition>

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

.pos-alert {
    display: flex;
    align-items: center;
    gap: .65rem;
    margin-bottom: 1rem;
    padding: .75rem 1rem;
    border: 1px solid transparent;
    border-radius: .7rem;
    font-size: .88rem;
    animation: pos-drop .3s ease both;
}

.pos-alert > span {
    flex: 1;
}

.pos-alert-danger {
    border-color: color-mix(in srgb, var(--ui-danger, #d92d20) 28%, transparent);
    background: var(--ui-danger-soft, #fef3f2);
    color: var(--ui-danger, #b42318);
}

.pos-alert-success {
    border-color: color-mix(in srgb, var(--ui-success, #039855) 28%, transparent);
    background: var(--ui-success-soft, #f0fdf4);
    color: var(--ui-success, #027a48);
}

.pos-layout > section,
.pos-cart-column {
    min-width: 0;
}

.pos-cart-column {
    scroll-margin-top: 1rem;
}

.pos-panel {
    display: flex;
    overflow: hidden;
    flex-direction: column;
    border: 1px solid var(--ui-border, var(--bs-border-color));
    border-radius: 1rem;
    background: var(--ui-surface, var(--bs-body-bg));
    box-shadow: var(--ui-shadow-sm, 0 2px 8px rgb(16 24 40 / .05));
}

.pos-products-panel,
.pos-cart-card {
    height: calc(100vh - 7rem);
    height: calc(100dvh - 7rem);
    min-height: 420px;
}

.pos-cart-card {
    position: sticky;
    top: 1rem;
}

.pos-toolbar,
.pos-cart-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--ui-border, var(--bs-border-color));
}

.pos-toolbar-title h1,
.pos-toolbar-title h2 {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 700;
}

.pos-toolbar-title p {
    margin: .2rem 0 0;
    color: var(--ui-muted, var(--bs-secondary-color));
    font-size: .8rem;
}

.pos-toolbar-actions {
    display: flex;
    flex: 1;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-end;
    gap: .6rem;
}

.pos-search {
    display: flex;
    align-items: center;
    flex: 1 1 220px;
    max-width: 340px;
    height: 2.5rem;
    gap: .5rem;
    padding: 0 .3rem 0 .75rem;
    border: 1px solid var(--ui-border, var(--bs-border-color));
    border-radius: .65rem;
    background: var(--ui-control, var(--bs-body-bg));
    transition: border-color .15s, box-shadow .15s;
}

.pos-search:focus-within {
    border-color: var(--ui-primary, var(--bs-primary));
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--ui-primary, var(--bs-primary)) 16%, transparent);
}

.pos-search > i {
    color: var(--ui-muted, var(--bs-secondary-color));
}

.pos-search input {
    flex: 1;
    min-width: 0;
    border: 0;
    outline: 0;
    background: transparent;
    color: inherit;
    font-size: .86rem;
}

.pos-search input::placeholder {
    color: var(--ui-muted, var(--bs-secondary-color));
    opacity: 1;
}

.pos-search button {
    height: 1.9rem;
    padding: 0 .7rem;
    border: 0;
    border-radius: .5rem;
    background: var(--ui-primary-soft, var(--bs-primary-bg-subtle));
    color: var(--ui-primary, var(--bs-primary));
    font-size: .78rem;
    font-weight: 600;
}

.pos-search button:disabled {
    opacity: .6;
}

.pos-category-filter {
    flex: 0 0 170px;
    width: 170px;
    height: 2.5rem;
    border-radius: .65rem;
    font-size: .86rem;
}

.pos-icon-button {
    display: grid;
    width: 2.5rem;
    height: 2.5rem;
    place-items: center;
    border: 1px solid var(--ui-border, var(--bs-border-color));
    border-radius: .65rem;
    background: var(--ui-control, var(--bs-body-bg));
    color: var(--ui-text-secondary, var(--bs-secondary-color));
    transition: background .15s, color .15s, transform .15s;
}

.pos-icon-button:hover {
    background: var(--ui-surface-muted, var(--bs-tertiary-bg));
    color: var(--ui-text, var(--bs-body-color));
    transform: rotate(12deg);
}

.pos-cart-link {
    display: inline-flex;
    align-items: center;
    height: 2.5rem;
    gap: .5rem;
    padding: 0 .9rem;
    border-radius: .65rem;
    background: var(--ui-primary, var(--bs-primary));
    color: #fff;
    font-size: .86rem;
    font-weight: 600;
    text-decoration: none;
    transition: filter .15s, transform .15s;
}

.pos-cart-link:hover {
    color: #fff;
    filter: brightness(1.08);
    transform: translateY(-1px);
}

.pos-cart-badge {
    display: inline-grid;
    min-width: 1.35rem;
    height: 1.35rem;
    padding: 0 .3rem;
    place-items: center;
    border-radius: 999px;
    background: rgb(255 255 255 / .22);
    font-size: .74rem;
    animation: pos-bump .35s ease;
}

.pos-products-body {
    flex: 1;
    min-height: 0;
    padding: 1.1rem 1.25rem;
    overflow-y: auto;
    overscroll-behavior: contain;
}

.pos-product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(min(100%, 175px), 1fr));
    gap: 1rem;
}

.pos-product-card {
    display: flex;
    overflow: hidden;
    flex-direction: column;
    min-width: 0;
    border: 1px solid var(--ui-border, var(--bs-border-color));
    border-radius: .85rem;
    background: var(--ui-surface, var(--bs-body-bg));
    animation: pos-rise .45s cubic-bezier(.22, 1, .36, 1) both;
    animation-delay: calc(var(--i, 0) * 40ms);
    transition: border-color .2s, box-shadow .2s, transform .2s;
}

.pos-product-card:hover {
    transform: translateY(-3px);
    border-color: color-mix(in srgb, var(--ui-primary, var(--bs-primary)) 45%, var(--ui-border, var(--bs-border-color)));
    box-shadow: 0 10px 24px rgb(15 23 42 / .09);
}

.pos-product-card.is-out .pos-product-image img {
    opacity: .45;
    filter: grayscale(1);
}

.pos-product-image {
    position: relative;
    display: grid;
    aspect-ratio: 4 / 3;
    place-items: center;
    overflow: hidden;
    background: var(--ui-surface-muted, var(--bs-tertiary-bg));
    color: var(--ui-muted, var(--bs-secondary-color));
    font-size: 1.8rem;
}

.pos-product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform .4s ease;
}

.pos-product-card:hover .pos-product-image img {
    transform: scale(1.05);
}

.pos-stock-pill {
    position: absolute;
    top: .5rem;
    left: .5rem;
    padding: .2rem .55rem;
    border-radius: 999px;
    background: rgb(255 255 255 / .92);
    color: #027a48;
    font-size: .68rem;
    font-weight: 700;
    backdrop-filter: blur(4px);
}

.pos-stock-pill.empty {
    color: #b42318;
}

.pos-product-info {
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: .35rem;
    padding: .8rem;
}

.pos-product-name {
    display: -webkit-box;
    overflow: hidden;
    min-height: 2.4rem;
    margin: 0;
    font-size: .86rem;
    font-weight: 600;
    line-height: 1.2rem;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}

.pos-product-price {
    font-size: 1rem;
    font-weight: 700;
    color: var(--ui-primary, var(--bs-primary));
}

.pos-add-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .4rem;
    margin-top: auto;
    padding: .55rem .75rem;
    border: 0;
    border-radius: .6rem;
    background: var(--ui-primary-soft, var(--bs-primary-bg-subtle));
    color: var(--ui-primary, var(--bs-primary));
    font-size: .8rem;
    font-weight: 600;
    transition: background .15s, color .15s, transform .1s;
}

.pos-add-button:hover:not(:disabled) {
    background: var(--ui-primary, var(--bs-primary));
    color: #fff;
}

.pos-add-button:active:not(:disabled) {
    transform: scale(.97);
}

.pos-add-button:disabled {
    opacity: .55;
    cursor: not-allowed;
}

.pos-product-skeleton {
    height: 250px;
    border-radius: .85rem;
    background: linear-gradient(100deg, var(--ui-surface-muted, var(--bs-tertiary-bg)) 30%, var(--bs-secondary-bg) 50%, var(--ui-surface-muted, var(--bs-tertiary-bg)) 70%);
    background-size: 200% 100%;
    animation: pos-shimmer 1.3s ease-in-out infinite;
}

.pos-empty {
    display: flex;
    min-height: 260px;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: .4rem;
    padding: 2rem 1rem;
    color: var(--ui-muted, var(--bs-secondary-color));
    text-align: center;
}

.pos-empty > i {
    font-size: 2rem;
}

.pos-empty h2 {
    margin: .4rem 0 0;
    color: var(--ui-text, var(--bs-body-color));
    font-size: 1rem;
    font-weight: 700;
}

.pos-empty p {
    margin: 0;
    font-size: .82rem;
}

.pos-cart-count {
    padding: .35rem .75rem;
    border-radius: 999px;
    background: var(--ui-surface-muted, var(--bs-tertiary-bg));
    color: var(--ui-text-secondary, var(--bs-secondary-color));
    font-size: .76rem;
    font-weight: 600;
    white-space: nowrap;
}

.pos-cart-body {
    flex: 1;
    min-height: 0;
    padding: .25rem 1.25rem;
    overflow-y: auto;
}

.pos-cart-items {
    margin: 0;
    padding: 0;
    list-style: none;
}

.pos-cart-item {
    display: flex;
    justify-content: space-between;
    gap: .75rem;
    padding: .9rem 0;
    border-bottom: 1px solid var(--ui-border, var(--bs-border-color));
}

.pos-cart-item:last-child {
    border-bottom: 0;
}

.pos-cart-item-main {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: .15rem;
}

.pos-cart-item-name {
    overflow: hidden;
    font-size: .88rem;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.pos-cart-item-main small {
    color: var(--ui-muted, var(--bs-secondary-color));
    font-size: .74rem;
}

.pos-cart-item-side {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    justify-content: space-between;
    font-size: .9rem;
}

.pos-quantity-control {
    display: inline-flex;
    align-items: center;
    align-self: flex-start;
    gap: .15rem;
    margin-top: .45rem;
    padding: .15rem;
    border: 1px solid var(--ui-border, var(--bs-border-color));
    border-radius: .6rem;
    background: var(--ui-surface-muted, var(--bs-tertiary-bg));
}

.pos-quantity-control button {
    display: grid;
    width: 1.7rem;
    height: 1.7rem;
    padding: 0;
    place-items: center;
    border: 0;
    border-radius: .45rem;
    background: transparent;
    color: var(--ui-text, var(--bs-body-color));
    transition: background .15s, color .15s;
}

.pos-quantity-control button:hover:not(:disabled) {
    background: var(--ui-primary, var(--bs-primary));
    color: #fff;
}

.pos-quantity-control span {
    min-width: 1.7rem;
    font-size: .86rem;
    font-weight: 700;
    text-align: center;
}

.pos-remove-button {
    display: grid;
    width: 1.9rem;
    height: 1.9rem;
    padding: 0;
    place-items: center;
    border: 0;
    border-radius: .5rem;
    background: transparent;
    color: var(--ui-muted, var(--bs-secondary-color));
    transition: background .15s, color .15s;
}

.pos-remove-button:hover:not(:disabled) {
    background: var(--ui-danger-soft, #fef3f2);
    color: var(--ui-danger, #d92d20);
}

.pos-cart-footer {
    padding: 1rem 1.25rem 1.15rem;
    border-top: 1px solid var(--ui-border, var(--bs-border-color));
    background: var(--ui-surface-muted, var(--bs-tertiary-bg));
}

.pos-subtotal {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: .92rem;
}

.pos-subtotal strong {
    font-size: 1.2rem;
}

.pos-note {
    margin: .35rem 0 .9rem;
    color: var(--ui-muted, var(--bs-secondary-color));
    font-size: .74rem;
}

.pos-center {
    margin: .75rem 0 0;
    text-align: center;
}

.pos-checkout-button,
.pos-button-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    border: 0;
    border-radius: .7rem;
    background: var(--ui-primary, var(--bs-primary));
    color: #fff;
    font-weight: 600;
    transition: filter .15s, transform .1s;
}

.pos-checkout-button {
    width: 100%;
    min-height: 2.9rem;
    font-size: .95rem;
}

.pos-button-primary {
    min-height: 2.5rem;
    padding: 0 1.2rem;
    font-size: .9rem;
}

.pos-checkout-button:hover:not(:disabled),
.pos-button-primary:hover:not(:disabled) {
    filter: brightness(1.08);
}

.pos-checkout-button:active:not(:disabled),
.pos-button-primary:active:not(:disabled) {
    transform: scale(.98);
}

.pos-checkout-button:disabled,
.pos-button-primary:disabled {
    opacity: .55;
    cursor: not-allowed;
}

.pos-button-secondary {
    min-height: 2.5rem;
    padding: 0 1.1rem;
    border: 1px solid var(--ui-border, var(--bs-border-color));
    border-radius: .7rem;
    background: var(--ui-surface, var(--bs-body-bg));
    color: var(--ui-text, var(--bs-body-color));
    font-size: .9rem;
    font-weight: 600;
}

.pos-button-secondary:hover:not(:disabled) {
    background: var(--ui-surface-muted, var(--bs-tertiary-bg));
}

.pos-modal-backdrop {
    position: fixed;
    z-index: 1080;
    inset: 0;
    display: grid;
    place-items: center;
    overflow-y: auto;
    padding: 1rem;
    background: rgb(15 23 42 / .5);
    backdrop-filter: blur(3px);
}

.pos-modal {
    display: flex;
    width: min(100%, 540px);
    max-height: calc(100vh - 2rem);
    flex-direction: column;
    overflow: hidden;
    border: 1px solid var(--ui-border, var(--bs-border-color));
    border-radius: 1rem;
    background: var(--ui-surface, var(--bs-body-bg));
    box-shadow: 0 24px 60px rgb(15 23 42 / .3);
    color: var(--ui-text, var(--bs-body-color));
}

.pos-modal-narrow {
    width: min(100%, 400px);
}

.pos-modal-header,
.pos-modal-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    padding: 1rem 1.25rem;
    background: var(--ui-surface-muted, var(--bs-tertiary-bg));
}

.pos-modal-header {
    border-bottom: 1px solid var(--ui-border, var(--bs-border-color));
}

.pos-modal-header small {
    color: var(--ui-muted, var(--bs-secondary-color));
}

.pos-modal-header h2 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 700;
}

.pos-modal-footer {
    justify-content: flex-end;
    border-top: 1px solid var(--ui-border, var(--bs-border-color));
}

.pos-modal-body {
    flex: 1;
    padding: 1.25rem;
    overflow-y: auto;
}

.pos-summary-row {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    padding: .6rem 0;
    border-bottom: 1px solid var(--ui-border, var(--bs-border-color));
    font-size: .88rem;
}

.pos-summary-row small {
    color: var(--ui-muted, var(--bs-secondary-color));
}

.pos-totals {
    margin: .9rem 0 0;
}

.pos-totals > div {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: .45rem;
    font-size: .88rem;
}

.pos-totals dt,
.pos-totals dd {
    margin: 0;
}

.pos-totals dt {
    color: var(--ui-muted, var(--bs-secondary-color));
    font-weight: 500;
}

.pos-grand-total {
    padding-top: .75rem;
    border-top: 1px solid var(--ui-border, var(--bs-border-color));
    font-size: 1.1rem !important;
    font-weight: 700;
}

.pos-grand-total dt {
    color: inherit;
}

.pos-payment-section {
    margin-top: 1.4rem;
}

.pos-payment-section h3 {
    margin: 0 0 .65rem;
    font-size: .9rem;
    font-weight: 700;
}

.pos-payment-methods {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: .65rem;
}

.pos-payment-option {
    display: flex;
    min-width: 0;
    align-items: center;
    gap: .65rem;
    padding: .8rem;
    border: 1px solid var(--ui-border, var(--bs-border-color));
    border-radius: .7rem;
    background: var(--ui-surface, var(--bs-body-bg));
    cursor: pointer;
    transition: border-color .15s, background .15s;
}

.pos-payment-option:hover {
    border-color: var(--ui-primary, var(--bs-primary));
}

.pos-payment-option.selected {
    border-color: var(--ui-primary, var(--bs-primary));
    background: var(--ui-primary-soft, var(--bs-primary-bg-subtle));
}

.pos-payment-option > input {
    flex: 0 0 auto;
    accent-color: var(--ui-primary, var(--bs-primary));
}

.pos-payment-option > .bi {
    color: var(--ui-primary, var(--bs-primary));
    font-size: 1.15rem;
}

.pos-payment-copy {
    display: grid;
    min-width: 0;
    gap: .15rem;
}

.pos-payment-copy strong {
    font-size: .84rem;
}

.pos-payment-copy small {
    color: var(--ui-muted, var(--bs-secondary-color));
    font-size: .72rem;
}

.pos-qr {
    display: block;
    width: 240px;
    max-width: 100%;
    margin: 0 auto 1rem;
    border-radius: .75rem;
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
    padding: .7rem 1.1rem;
    border-radius: 999px;
    background: var(--ui-primary, var(--bs-primary));
    box-shadow: 0 6px 20px rgb(15 23 42 / .22);
    color: #fff;
    font-size: .85rem;
    font-weight: 600;
    text-decoration: none;
    transition: transform .15s, box-shadow .15s;
}

.pos-dashboard-fab:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 26px rgb(15 23 42 / .28);
    color: #fff;
}

.pos-dark-mode .pos-category-filter {
    border-color: var(--ui-border);
    background-color: var(--ui-control);
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23cbd5e1' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
    color: var(--ui-text);
}

.pos-dark-mode .pos-stock-pill {
    background: rgb(15 23 42 / .85);
    color: #86efac;
}

.pos-dark-mode .pos-stock-pill.empty {
    color: #fca5a5;
}

:global(.dark-theme) .pos-modal .btn-close {
    filter: invert(1) grayscale(100%) brightness(200%);
}

.pos-cart-enter-active,
.pos-cart-leave-active {
    transition: opacity .3s ease, transform .3s ease;
}

.pos-cart-enter-from {
    opacity: 0;
    transform: translateX(24px);
}

.pos-cart-leave-to {
    opacity: 0;
    transform: translateX(-24px);
}

.pos-cart-move {
    transition: transform .3s ease;
}

.pos-cart-leave-active {
    position: absolute;
    width: calc(100% - 2.5rem);
}

.pos-modal-enter-active,
.pos-modal-leave-active {
    transition: opacity .25s ease;
}

.pos-modal-enter-active .pos-modal,
.pos-modal-leave-active .pos-modal {
    transition: transform .3s cubic-bezier(.22, 1, .36, 1);
}

.pos-modal-enter-from,
.pos-modal-leave-to {
    opacity: 0;
}

.pos-modal-enter-from .pos-modal,
.pos-modal-leave-to .pos-modal {
    transform: translateY(18px) scale(.97);
}

@keyframes pos-rise {
    from {
        opacity: 0;
        transform: translateY(14px);
    }

    to {
        opacity: 1;
        transform: none;
    }
}

@keyframes pos-drop {
    from {
        opacity: 0;
        transform: translateY(-8px);
    }

    to {
        opacity: 1;
        transform: none;
    }
}

@keyframes pos-bump {
    0% {
        transform: scale(1);
    }

    50% {
        transform: scale(1.35);
    }

    100% {
        transform: scale(1);
    }
}

@keyframes pos-shimmer {
    to {
        background-position-x: -200%;
    }
}

@media (max-height: 700px) and (min-width: 1200px) {

    .pos-products-panel,
    .pos-cart-card {
        height: calc(100vh - 6rem);
        min-height: 320px;
    }
}

@media (max-width: 991.98px) {
    .pos-cart-card {
        position: static;
    }
}

@media (max-width: 575.98px) {

    .pos-toolbar {
        align-items: stretch;
        flex-direction: column;
    }

    .pos-toolbar-actions {
        justify-content: stretch;
    }

    .pos-search {
        max-width: none;
        flex-basis: 100%;
    }

    .pos-category-filter {
        flex: 1;
        width: auto;
    }

    .pos-cart-link {
        flex: 1;
        justify-content: center;
    }

    .pos-products-body {
        padding: .85rem;
    }

    .pos-product-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .65rem;
    }

    .pos-product-info {
        padding: .65rem;
    }

    .pos-dashboard-fab {
        right: 1rem;
        bottom: max(1rem, env(safe-area-inset-bottom));
    }
}
</style>