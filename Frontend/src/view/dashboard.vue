<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { Line } from 'vue-chartjs'
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Filler,
    Tooltip,
    Legend,
} from 'chart.js'

import { useProductStore } from '@/stores/product'
import { useDashboardStore } from '@/stores/dashboard'
import DashboardSummary from '@/Components/Dashboards/DashboardSummary.vue'

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Filler,
    Tooltip,
    Legend
)

const productStore = useProductStore()
const dashboardStore = useDashboardStore()

const selectedPeriod = ref('all')

const recentOrders = computed(
    () => dashboardStore.recentOrders ?? []
)

const topProducts = computed(
    () => dashboardStore.topProducts ?? []
)

const lowStockProducts = computed(
    () => dashboardStore.lowStockProducts ?? []
)

const outofstocks = computed(
    () => dashboardStore.outofstocks ?? [],
)
const ReactionPurchaseRecived = computed(
    () => dashboardStore.ReactionPurchaseRecived
)
const ReactionSaleReturn = computed(
    () => dashboardStore.ReactionSaleRuturn
)

const quickViewLoading = computed(
    () => dashboardStore.quickViewLoading
)

const quickViewError = computed(
    () => dashboardStore.quickViewError
)

const chartLabels = computed(
    () => dashboardStore.saleChart?.labels ?? []
)

const chartSales = computed(
    () => dashboardStore.saleChart?.sales ?? []
)

const chartOrders = computed(
    () => dashboardStore.saleChart?.orders ?? []
)

const chartTotal = computed(() =>
    chartSales.value.reduce(
        (total, value) => total + (Number(value) || 0),
        0
    )
)

const chartOrderTotal = computed(() =>
    chartOrders.value.reduce(
        (total, value) => total + (Number(value) || 0),
        0
    )
)

const formatMoney = value =>
    new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(Number(value) || 0)

const formatNumber = value =>
    new Intl.NumberFormat('en-US').format(Number(value) || 0)

const statusClass = status =>
    String(status ?? 'pending').toLowerCase()

const orderAmount = order =>
    order.total_amount ??
    order.grand_total ??
    order.total ??
    order.amount ??
    0

const productName = product =>
    product.name ??
    product.product_name ??
    product.product?.name ??
    'Unnamed product'

const soldQuantity = product =>
    product.total_sold ??
    product.quantity_sold ??
    product.total_quantity ??
    0

const stockQuantity = product =>
    product.quantity ??
    product.stock_quantity ??
    product.inventory_stocks_sum_quantity ??
    0

const stockAlert = product =>
    product.alert_quantity ?? 10

const lineChartData = computed(() => ({
    labels: chartLabels.value,
    datasets: [
        {
            label: 'Sales Revenue',
            data: chartSales.value.map(value => Number(value) || 0),
            borderColor: '#3975ff',
            backgroundColor: 'rgba(57, 117, 255, 0.10)',
            fill: true,
            tension: 0.4,
            borderWidth: 3,
            pointRadius: chartLabels.value.length > 20 ? 0 : 3,
            pointHoverRadius: 6,
            pointBackgroundColor: '#ffffff',
            pointBorderColor: '#3975ff',
            pointBorderWidth: 2,
        },
    ],
}))

const lineChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    animation: {
        duration: 900,
        easing: 'easeInOutQuart',
    },
    interaction: {
        mode: 'index',
        intersect: false,
    },
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: '#101828',
            titleColor: '#ffffff',
            bodyColor: '#ffffff',
            padding: 12,
            displayColors: false,
            callbacks: {
                label(context) {
                    const index = context.dataIndex
                    return [
                        `Sales: ${formatMoney(context.parsed.y)}`,
                        `Orders: ${formatNumber(chartOrders.value[index])}`,
                    ]
                },
            },
        },
    },
    scales: {
        x: {
            grid: { display: false },
            border: { display: false },
            ticks: {
                color: '#98a2b3',
                maxTicksLimit: 8,
                maxRotation: 0,
                autoSkip: true,
                font: { size: 10 },
            },
        },
        y: {
            beginAtZero: true,
            border: { display: false, dash: [4, 4] },
            grid: {
                color: 'rgba(152, 162, 179, 0.15)',
            },
            ticks: {
                color: '#98a2b3',
                maxTicksLimit: 5,
                font: { size: 10 },
                callback(value) {
                    const amount = Number(value) || 0
                    return amount >= 1000
                        ? `$${(amount / 1000).toFixed(1)}k`
                        : `$${amount}`
                },
            },
        },
    },
}

const loadDashboard = async period => {
    await Promise.allSettled([
        dashboardStore.fetchSummary(period),
        dashboardStore.fetchSaleChart(period),
    ])
}

const fetchDashboardQuickView = async () => {
    try {
        await dashboardStore.fetchDashboardQuickView()

    } catch (error) {
        console.error('Dashboard quick view failed:', error)
    }
}

watch(selectedPeriod, period => {
    loadDashboard(period)
})

onMounted(() => {
    productStore.getProducts().catch(() => undefined)
    loadDashboard(selectedPeriod.value)
    fetchDashboardQuickView()
})
// Real-time greeting
const currentTime = ref(new Date())
let clockInterval = null

const greeting = computed(() => {
    const hour = currentTime.value.getHours()

    if (hour >= 5 && hour < 12) {
        return 'Good morning'
    }

    if (hour >= 12 && hour < 17) {
        return 'Good afternoon'
    }

    return 'Good evening'
})
const currentUser = computed(() => {
    try {
        return JSON.parse(localStorage.getItem('user') || '{}')
    } catch {
        return {}
    }
})

const userName = computed(() => {
    const user = currentUser.value

    return (
        user.name ||
        user.username ||
        user.full_name ||
        user.fullName ||
        'there'
    )
})
const currentDateTime = computed(() => {
    return new Intl.DateTimeFormat('en-US', {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
    }).format(currentTime.value)
})

onMounted(() => {
    clockInterval = setInterval(() => {
        currentTime.value = new Date()
    }, 1000)
})

onUnmounted(() => {
    if (clockInterval) {
        clearInterval(clockInterval)
    }
})
</script>

<template>
    <main class="dashboard-page">
        <section class="page-heading">

            <div class="heading-copy">
                <p class="eyebrow">STORE DASHBOARD</p>
                <h1>
                    {{ greeting }}, {{ userName }}!
                </h1>
                <p class="heading-subtitle">
                    Monitor your store performance and daily operations.
                </p>
                <p class="dashboard-date">
                    <i class="bi bi-calendar3"></i>
                    {{ currentDateTime }}
                </p>
            </div>
            <div class="heading-actions">
                <label class="period-filter">
                    <i class="bi bi-calendar3"></i>
                    <select v-model="selectedPeriod" class="dashboard-period-select"
                        aria-label="Filter dashboard period">
                        <option value="all">All time</option>
                        <option value="today">Today</option>
                        <option value="week">This week</option>
                        <option value="month">This month</option>
                        <option value="year">This year</option>
                    </select>
                    <i class="bi bi-chevron-down period-arrow"></i>
                </label>

                <RouterLink to="/pos" class="primary-button">
                    <i class="bi bi-plus-lg"></i>
                    New sale
                </RouterLink>
            </div>
        </section>

        <!-- Existing Laravel summary -->
        <DashboardSummary :summary="dashboardStore.summary" :loading="dashboardStore.loading" />

        <!-- Sales chart and quick actions -->
        <section class="main-grid">
            <article class="panel sales-panel">
                <div class="panel-heading">
                    <div>
                        <h2>Sales overview</h2>
                        <p>Revenue trend for the selected period</p>
                    </div>
                    <span class="panel-tag">
                        <span class="live-dot"></span>
                        Sales report
                    </span>
                </div>

                <div class="sales-summary">
                    <div>
                        <span class="muted-label">Total sales in chart</span>
                        <strong>{{ formatMoney(chartTotal) }}</strong>
                    </div>
                    <span class="orders-badge">
                        <i class="bi bi-receipt"></i>
                        {{ formatNumber(chartOrderTotal) }} orders
                    </span>
                </div>

                <div v-if="dashboardStore.chartLoading" class="chart-message">
                    <span class="spinner-border spinner-border-sm"></span>
                    Loading sales chart...
                </div>

                <div v-else-if="dashboardStore.chartError" class="chart-message error-message">
                    {{ dashboardStore.chartError }}
                </div>

                <div v-else-if="chartLabels.length" class="line-chart-wrapper">
                    <Line :key="selectedPeriod" :data="lineChartData" :options="lineChartOptions" />
                </div>

                <div v-else class="chart-message">
                    <i class="bi bi-graph-up"></i>
                    No sales data available for this period.
                </div>

                <div class="chart-footer">
                    <span>
                        <span class="legend-line"></span>
                        Sales revenue (USD)
                    </span>
                    <span>{{ chartLabels.length }} data points</span>
                </div>
            </article>

            <!-- Quick actions -->
            <article class="panel quick-panel">
                <div class="panel-heading">
                    <div>
                        <h2>Quick actions</h2>
                        <p>Access your daily store operations</p>
                    </div>
                    <span class="quick-count">12 shortcuts</span>
                </div>

                <div class="quick-actions-grid">
                    <RouterLink to="/pos" class="quick-action-card">
                        <span class="quick-icon blue">
                            <i class="bi bi-cart-plus"></i>
                        </span>
                        <span class="quick-copy">
                            <strong>New sale</strong>
                            <small>Process a checkout</small>
                        </span>
                        <i class="bi bi-arrow-up-right quick-arrow"></i>
                    </RouterLink>

                    <RouterLink to="/products" class="quick-action-card">
                        <span class="quick-icon violet">
                            <i class="bi bi-box-seam"></i>
                        </span>
                        <span class="quick-copy">
                            <strong>Products</strong>
                            <small>Manage your catalog</small>
                        </span>
                        <i class="bi bi-arrow-up-right quick-arrow"></i>
                    </RouterLink>
                    <RouterLink to="/categories" class="quick-action-card">
                        <span class="quick-icon red">
                            <i class="bi bi-grid"></i>
                        </span>
                        <span class="quick-copy">
                            <strong>Category</strong>
                            <small>Manage your category</small>
                        </span>
                        <i class="bi bi-arrow-up-right quick-arrow"></i>
                    </RouterLink>
                    <RouterLink to="/brands" class="quick-action-card">
                        <span class="quick-icon green">
                            <i class="bi bi-tag"></i>
                        </span>
                        <span class="quick-copy">
                            <strong>Brands</strong>
                            <small>Manage your brands</small>
                        </span>
                        <i class="bi bi-arrow-up-right quick-arrow"></i>
                    </RouterLink>

                    <RouterLink to="/inventory" class="quick-action-card">
                        <span class="quick-icon orange">
                            <i class="bi bi-clipboard2-check"></i>
                        </span>
                        <span class="quick-copy">
                            <strong>Inventory</strong>
                            <small>Check stock levels</small>
                        </span>
                        <i class="bi bi-arrow-up-right quick-arrow"></i>
                    </RouterLink>

                    <RouterLink to="/orders" class="quick-action-card">
                        <span class="quick-icon green">
                            <i class="bi bi-receipt"></i>
                        </span>
                        <span class="quick-copy">
                            <strong>Orders</strong>
                            <small>View transactions</small>
                        </span>
                        <i class="bi bi-arrow-up-right quick-arrow"></i>
                    </RouterLink>

                    <RouterLink to="/suppliers" class="quick-action-card">
                        <span class="quick-icon cyan">
                            <i class="bi bi-people"></i>
                        </span>
                        <span class="quick-copy">
                            <strong>Suplliers</strong>
                            <small>Manage </small>
                        </span>
                        <i class="bi bi-arrow-up-right quick-arrow"></i>
                    </RouterLink>

                    <RouterLink to="/purchases" class="quick-action-card">
                        <span class="quick-icon pink">
                            <i class="bi bi-truck"></i>
                        </span>
                        <span class="quick-copy">
                            <strong>Purchases</strong>
                            <small>Restock products</small>
                        </span>
                        <i class="bi bi-arrow-up-right quick-arrow"></i>
                    </RouterLink>

                    <RouterLink to="/expenses" class="quick-action-card">
                        <span class="quick-icon red">
                            <i class="bi bi-wallet2"></i>
                        </span>
                        <span class="quick-copy">
                            <strong>Expenses</strong>
                            <small>Track store expenses</small>
                        </span>
                        <i class="bi bi-arrow-up-right quick-arrow"></i>
                    </RouterLink>
                    <RouterLink to="/cash-registers" class="quick-action-card">
                        <span class="quick-icon green">
                            <i class="bi bi-arrow-left-right"></i>
                        </span>
                        <span class="quick-copy">
                            <strong>Cash Transaction</strong>
                            <small>Manage cash in and cash out</small>
                        </span>
                        <i class="bi bi-arrow-up-right quick-arrow"></i>
                    </RouterLink>
                    <RouterLink to="/shifts" class="quick-action-card">
                        <span class="quick-icon pink">
                            <i class="bi bi-clock-history"></i>
                        </span>
                        <span class="quick-copy">
                            <strong>My Shift</strong>
                            <small>Manage Shift in Myshift</small>
                        </span>
                        <i class="bi bi-arrow-up-right quick-arrow"></i>
                    </RouterLink>
                    <RouterLink to="/stores" class="quick-action-card">
                        <span class="quick-icon orange">
                            <i class="bi bi-shop"></i>
                        </span>
                        <span class="quick-copy">
                            <strong>My Stores</strong>
                            <small>Manage Stores in and store </small>
                        </span>
                        <i class="bi bi-arrow-up-right quick-arrow"></i>
                    </RouterLink>
                </div>
            </article>
        </section>

        <!-- Live database quick view -->
        <section class="quick-view-section">
            <div class="quick-view-heading">
                <div>
                    <p class="eyebrow">STORE ACTIVITY</p>
                    <h2>Quick view</h2>
                    <p class="heading-subtitle">
                        Recent transactions, best sellers and stock alerts.
                    </p>
                </div>

                <button type="button" class="refresh-button" :disabled="quickViewLoading"
                    @click="fetchDashboardQuickView">
                    <i class="bi bi-arrow-clockwise" :class="{ spinning: quickViewLoading }"></i>
                    Refresh
                </button>
            </div>

            <div v-if="quickViewError" class="quick-view-error">
                <i class="bi bi-exclamation-circle"></i>
                <span>{{ quickViewError }}</span>
                <button type="button" class="retry-button" @click="fetchDashboardQuickView">
                    Retry
                </button>
            </div>

            <div v-else class="quick-view-grid">
                <!-- Recent Orders -->
                <article class="panel quick-view-card">
                    <div class="panel-heading">
                        <div class="section-title">
                            <span class="section-icon blue-soft">
                                <i class="bi bi-receipt"></i>
                            </span>
                            <div>
                                <h3>Recent orders</h3>
                                <p>Latest customer transactions</p>
                            </div>
                        </div>
                        <RouterLink to="/orders" class="view-link">
                            View all
                        </RouterLink>
                    </div>

                    <div v-if="quickViewLoading" class="loading-state">
                        <span class="spinner-border spinner-border-sm"></span>
                        Loading orders...
                    </div>

                    <div v-else-if="recentOrders.length" class="quick-list">
                        <div v-for="(order, index) in recentOrders" :key="order.id ?? index" class="quick-list-row">
                            <span class="list-icon order-icon">
                                <i class="bi bi-receipt-cutoff"></i>
                            </span>

                            <div class="list-main">
                                <strong>
                                    Order #{{ order.order_number ?? order.id }}
                                </strong>
                                <small>
                                    {{ order.customer_name ?? order.customer?.name ?? 'Walk-in customer' }}
                                </small>
                            </div>

                            <div class="list-end">
                                <strong class="list-amount">
                                    {{ formatMoney(orderAmount(order)) }}
                                </strong>
                                <span class="status-pill" :class="statusClass(order.status)">
                                    {{ order.status ?? 'Unknown' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div v-else class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <strong>No recent orders</strong>
                        <p>Orders will appear here when available.</p>
                    </div>
                </article>

                <article class="panel quick-view-card">
                    <div class="panel-heading">
                        <div class="section-title">
                            <span class="section-icon purple-soft">
                                <i class="bi bi-trophy"></i>
                            </span>
                            <div>
                                <h3>Top products</h3>
                                <p>Best-selling products</p>
                            </div>
                        </div>
                        <RouterLink to="/products" class="view-link">
                            Products
                        </RouterLink>
                    </div>

                    <div v-if="quickViewLoading" class="loading-state">
                        <span class="spinner-border spinner-border-sm"></span>
                        Loading products...
                    </div>

                    <div v-else-if="topProducts.length" class="quick-list">
                        <div v-for="(product, index) in topProducts" :key="product.id ?? index" class="quick-list-row">
                            <span class="rank-number">
                                {{ String(index + 1).padStart(2, '0') }}
                            </span>

                            <div class="list-main">
                                <strong>{{ productName(product) }}</strong>
                                <small>
                                    {{ formatNumber(soldQuantity(product)) }} units sold
                                </small>
                            </div>

                            <span class="top-product-badge">
                                <i class="bi bi-graph-up-arrow"></i>
                                Top
                            </span>
                        </div>
                    </div>

                    <div v-else class="empty-state">
                        <i class="bi bi-bar-chart"></i>
                        <strong>No sales data</strong>
                        <p>Best sellers will appear here.</p>
                    </div>
                </article>

                <article class="panel quick-view-card">
                    <div class="panel-heading">
                        <div class="section-title">
                            <span class="section-icon red-soft">
                                <i class="bi bi-exclamation-triangle"></i>
                            </span>
                            <div>
                                <h3>Low stock</h3>
                                <p>Products needing attention</p>
                            </div>
                        </div>
                        <RouterLink to="/products" class="view-link">
                            products
                        </RouterLink>
                    </div>

                    <div v-if="quickViewLoading" class="loading-state">
                        <span class="spinner-border spinner-border-sm"></span>
                        Checking stock...
                    </div>

                    <div v-else-if="lowStockProducts.length" class="quick-list">
                        <div v-for="(product, index) in lowStockProducts" :key="product.id ?? index"
                            class="quick-list-row">
                            <span class="list-icon stock-icon">
                                <i class="bi bi-box-seam"></i>
                            </span>

                            <div class="list-main">
                                <strong>{{ productName(product) }}</strong>
                                <small>
                                    Alert at {{ formatNumber(stockAlert(product)) }} units
                                </small>
                            </div>

                            <div class="list-end">
                                <strong class="lowstock-value" :class="{
                                    'stock-empty': Number(stockQuantity(product)) <= 0
                                }">
                                    {{ formatNumber(stockQuantity(product)) }}
                                </strong>
                                <span class="stock-caption">in stock</span>
                            </div>
                        </div>
                    </div>

                    <div v-else class="empty-state">
                        <i class="bi bi-check-circle"></i>
                        <strong>Stock looks good</strong>
                        <p>No low-stock products were returned.</p>
                    </div>
                </article>
                <article class="panel quick-view-card ">
                    <div class="panel-heading">
                        <div class="section-title">
                            <span class="section-icon red-soft">
                                <i class="bi bi-box-seam"></i>
                            </span>
                            <div>
                                <h3>Out Of Stocks</h3>
                                <p>Products needing attention</p>
                            </div>
                        </div>
                        <RouterLink to="/inventory" class="view-link">
                            Inventory
                        </RouterLink>
                    </div>

                    <div v-if="quickViewLoading" class="loading-state">
                        <span class="spinner-border spinner-border-sm"></span>
                        Checking stock...
                    </div>

                    <div v-else-if="outofstocks.length" class="quick-list mt-1">
                        <div v-for="(product, index) in outofstocks" :key="product.id ?? index" class="quick-list-row">
                            <span class="list-icon stock-icon">
                                <i class="bi bi-box-seam"></i>
                            </span>
                            <div class="list-main">

                                <strong>{{ product.name }}</strong>
                                <small>
                                    Alert at {{ formatNumber(stockAlert(product)) }} units
                                </small>
                            </div>

                            <div class="list-end">
                                <strong class="stock-value" :class="{
                                    'stock-empty': Number(stockQuantity(product)) <= 0
                                }">
                                    {{ formatNumber(stockQuantity(product)) }}
                                </strong>
                                <span class="stock-caption">Out stock</span>
                            </div>
                        </div>
                    </div>

                    <div v-else class="empty-state">
                        <i class="bi bi-check-circle"></i>
                        <strong>Stock looks good</strong>
                        <p>all item dont have out stocs</p>
                    </div>
                </article>
                <article class="panel quick-view-card ">
                    <div class="panel-heading">
                        <div class="section-title">
                            <span class="section-icon   green-soft">
                                <i class="bi bi-truck"></i>
                            </span>
                            <div>
                                <h3> Received Purchases</h3>
                                <p>Reaction purchase Received</p>
                            </div>
                        </div>
                        <RouterLink to="/purchases" class="view-link">
                            purchase
                        </RouterLink>
                    </div>
                    <div v-if="quickViewLoading" class="loading-state">
                        <span class="spinner-border spinner-border-sm"></span>
                        Checking stock...
                    </div>
                    <div v-else-if="ReactionPurchaseRecived" class="quick-list mt-1">
                        <div v-for="(product, index) in ReactionPurchaseRecived" :key="product.id ?? index"
                            class="quick-list-row">
                            <span class="list-icon  purchase-icon  ">
                                <i class="bi bi-truck"></i>
                            </span>
                            <div class="list-main">

                                <strong>{{ product.status }}</strong>
                                <small>
                                    purchase from company : {{ product.supplier.company_name }} to: {{
                                        product.store_id.name }}
                                </small>
                            </div>
                            <div class="list-end">
                                <strong class="stock-value" :class="{
                                    'stock-empty': product.items.length <= 0
                                }">
                                    {{ formatNumber(product.items.length) }}
                                </strong>
                                <span class="stock-caption">{{ product.received_at }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-else class="empty-state">
                        <i class="bi bi-check-circle"></i>
                        <strong>Stock looks good</strong>
                        <p>all item dont have out stocs</p>
                    </div>
                </article>
                <article class="panel quick-view-card ">
                    <div class="panel-heading">
                        <div class="section-title">
                            <span class="section-icon   green-soft">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </span>
                            <div>
                                <h3> Reaction Refunds</h3>
                                <p>Reaction Refunds On Refunds</p>
                            </div>
                        </div>
                        <RouterLink to="/sales-returns" class="view-link">
                            SaleReturn
                        </RouterLink>
                    </div>
                    <div v-if="quickViewLoading" class="loading-state">
                        <span class="spinner-border spinner-border-sm"></span>
                        Checking stock...
                    </div>
                    <div v-else-if="ReactionSaleReturn?.length" class="quick-list mt-1">
                        <div v-for="(product, index) in ReactionSaleReturn" :key="product.id ?? index"
                            class="quick-list-row">
                            <span class="list-icon purchase-icon">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </span>

                            <div class="list-main">
                                <strong>{{ product.status }}</strong>
                                <small>
                                    Sale Return by: {{ product.user.name }} role: {{ product.user.roles[0].name }} On: {{ product.order }}
                                </small>
                            </div>

                            <div class="list-end">
                                <strong class="return-value" :class="{ 'stock-empty': !product.items?.length }">
                                    {{ formatNumber(product.items[0].quantity ?? 0) }}
                                </strong>
                                <span class="stock-caption">{{ product.received_at }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-else class="empty-state">
                        <i class="bi bi-check-circle"></i>
                        <strong>Stock looks good</strong>
                        <p>all item dont have out stocs</p>
                    </div>
                </article>
            </div>
        </section>
    </main>
</template>

<style scoped>
.dashboard-page {
    --text: #101828;
    --muted: #667085;
    --subtle: #98a2b3;
    --border: #eaecf0;
    --surface: #ffffff;
    --blue: #245bdb;

    width: 100%;
    min-width: 0;
    margin: 0;
    padding: 24px;
    box-sizing: border-box;
    color: var(--text);
}

.page-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    margin-bottom: 28px;
}

.heading-copy {
    min-width: 0;
}

.eyebrow {
    margin: 0 0 8px;
    color: var(--blue);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.12em;
}

.page-heading h1 {
    max-width: 720px;
    margin: 0;
    color: var(--text);
    font-size: clamp(23px, 2vw, 30px);
    font-weight: 750;
    line-height: 1.3;
    letter-spacing: -0.7px;
}

.heading-subtitle {
    margin: 8px 0 0;
    color: var(--muted);
    font-size: 13px;
    line-height: 1.6;
}

.heading-actions {
    display: flex;
    flex-shrink: 0;
    align-items: center;
    gap: 12px;
}

.period-filter {
    position: relative;
    display: flex;
    align-items: center;
    gap: 10px;
    width: 175px;
    height: 44px;
    padding: 0 13px;
    box-sizing: border-box;
    border: 1px solid #e2e8f0;
    border-radius: 11px;
    background: var(--surface);
    color: #64748b;
}

.dashboard-period-select {
    width: 100%;
    min-width: 0;
    height: 100%;
    padding-right: 16px;
    appearance: none;
    border: 0;
    outline: 0;
    background: transparent;
    color: #344054;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
}

.period-arrow {
    position: absolute;
    right: 12px;
    pointer-events: none;
    font-size: 10px;
}

.primary-button,
.refresh-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 42px;
    padding: 0 16px;
    border: 1px solid transparent;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    transition: background 0.2s, border-color 0.2s;
}

.primary-button {
    background: var(--blue);
    color: white;
    box-shadow: 0 3px 8px #245bdb20;
}

.primary-button:hover {
    background: #1948b5;
    color: white;
}

.main-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.55fr) minmax(330px, 1fr);
    align-items: stretch;
    gap: 20px;
    margin-top: 24px;
}

.panel {
    min-width: 0;
    padding: 22px;
    border: 1px solid var(--border);
    border-radius: 16px;
    background: var(--surface);
    box-shadow: 0 2px 8px #10182805;
    box-sizing: border-box;
}

.panel-heading {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
}

.panel-heading h2,
.panel-heading h3 {
    margin: 0;
    color: var(--text);
    font-size: 15px;
    font-weight: 750;
    line-height: 1.4;
}

.panel-heading h3 {
    font-size: 14px;
}

.panel-heading p {
    margin: 5px 0 0;
    color: var(--muted);
    font-size: 11px;
    line-height: 1.5;
}

.panel-tag {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 7px 9px;
    border: 1px solid var(--border);
    border-radius: 8px;
    color: #475467;
    font-size: 10px;
    white-space: nowrap;
}

.live-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #12b76a;
}

.sales-summary {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 12px;
    margin: 25px 0 20px;
}

.sales-summary>div {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.muted-label {
    color: var(--muted);
    font-size: 11px;
}

.sales-summary strong {
    color: var(--text);
    font-size: 27px;
    font-weight: 800;
    letter-spacing: -0.7px;
}

.orders-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 3px;
    padding: 7px 10px;
    border-radius: 8px;
    background: #edf3ff;
    color: var(--blue);
    font-size: 11px;
    font-weight: 750;
}

.line-chart-wrapper {
    position: relative;
    width: 100%;
    height: 260px;
}

.line-chart-wrapper canvas {
    width: 100% !important;
    height: 100% !important;
}

.chart-message {
    display: flex;
    min-height: 220px;
    align-items: center;
    justify-content: center;
    gap: 10px;
    color: var(--muted);
    font-size: 12px;
    text-align: center;
}

.error-message {
    color: #b42318;
}

.chart-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-top: 14px;
    color: var(--subtle);
    font-size: 10px;
}

.chart-footer>span:first-child {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.legend-line {
    width: 20px;
    height: 3px;
    border-radius: 3px;
    background: #3975ff;
}

.quick-panel {
    display: flex;
    flex-direction: column;
}

.quick-count {
    flex-shrink: 0;
    padding: 7px 9px;
    border-radius: 8px;
    background: #f2f4f7;
    color: #475467;
    font-size: 10px;
    font-weight: 700;
}

.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    align-content: start;
    gap: 11px;
    margin-top: 20px;
}

.quick-action-card {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
    min-height: 76px;
    padding: 12px;
    border: 1px solid var(--border);
    border-radius: 12px;
    background: #fff;
    color: #344054;
    text-decoration: none;
    box-sizing: border-box;
    transition: border-color 0.2s, background 0.2s, transform 0.2s;
}

.quick-action-card:hover {
    transform: translateY(-2px);
    border-color: #c8d7ff;
    background: #f9fbff;
}

.quick-icon,
.section-icon,
.list-icon {
    display: grid;
    flex-shrink: 0;
    place-items: center;
}

.quick-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    font-size: 16px;
}

.blue {
    background: #edf3ff;
    color: #245bdb;
}

.violet {
    background: #f4f0ff;
    color: #6941c6;
}

.orange {
    background: #fff4e5;
    color: #c77700;
}

.green {
    background: hsl(152, 70%, 95%);
    color: #039855;
}

.cyan {
    background: #e8faff;
    color: #087eab;
}

.pink {
    background: #fff0f6;
    color: #c11574;
}

.red {
    background: #fff0ee;
    color: #d92d20;
}

.quick-copy {
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
}

.quick-copy strong {
    overflow: hidden;
    color: #344054;
    font-size: 11px;
    font-weight: 750;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.quick-copy small {
    overflow: hidden;
    color: var(--subtle);
    font-size: 10px;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.quick-arrow {
    flex-shrink: 0;
    color: #98a2b3;
    font-size: 11px;
}

/* Quick view section */
.quick-view-section {
    margin-top: 30px;
}

.quick-view-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 18px;
}

.quick-view-heading h2 {
    margin: 0;
    color: var(--text);
    font-size: 22px;
    font-weight: 800;
    letter-spacing: -0.4px;
}

.refresh-button {
    flex-shrink: 0;
    border-color: #d0d5dd;
    background: white;
    color: #344054;
}

.refresh-button:hover:not(:disabled) {
    border-color: #b7c8ef;
    background: #f8faff;
}

.refresh-button:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

.spinning {
    animation: spin 1s linear infinite;
}

.quick-view-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    align-items: stretch;
    gap: 18px;
}

.quick-view-card {
    display: flex;
    flex-direction: column;
    min-height: 300px;
    padding: 20px;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}

.section-icon {
    width: 38px;
    height: 38px;
    border-radius: 11px;
    font-size: 16px;
}

.blue-soft {
    background: #edf3ff;
    color: #245bdb;
}

.purple-soft {
    background: #f4f0ff;
    color: #6941c6;
}

.red-soft {
    background: #fff0ee;
    color: #d92d20;
}

.green-soft {
    background: hsl(152, 70%, 95%);
    color: #039855;
}


.view-link {
    flex-shrink: 0;
    padding-top: 4px;
    color: var(--blue);
    font-size: 11px;
    font-weight: 750;
    text-decoration: none;
}

.view-link:hover {
    color: #1948b5;
}

.quick-list {
    margin-top: 17px;
}

.quick-list-row {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
    padding: 13px 0;
    border-bottom: 1px solid #f2f4f7;
}

.quick-list-row:last-child {
    border-bottom: 0;
}

.list-icon {
    width: 34px;
    height: 34px;
    border-radius: 9px;
    background: #f2f4f7;
    color: #475467;
    font-size: 14px;
}

.order-icon {
    background: #f0f5ff;
    color: #245bdb;
}

.stock-icon {
    background: #fff4f2;
    color: #d92d20;
}

.purchase-icon {
    background: hsl(152, 70%, 95%);
    color: #039855
}

.list-main {
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: 5px;
    min-width: 0;
}

.list-main strong {
    overflow: hidden;
    color: #344054;
    font-size: 11px;
    font-weight: 750;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.list-main small {
    overflow: hidden;
    color: var(--subtle);
    font-size: 10px;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.list-end {
    display: flex;
    flex-shrink: 0;
    flex-direction: column;
    align-items: flex-end;
    gap: 5px;
}

.list-amount {
    color: var(--text);
    font-size: 11px;
    font-weight: 800;
}

.status-pill {
    display: inline-block;
    max-width: 100px;
    padding: 4px 7px;
    overflow: hidden;
    border-radius: 20px;
    background: #f2f4f7;
    color: #475467;
    font-size: 9px;
    font-weight: 750;
    text-overflow: ellipsis;
    text-transform: capitalize;
    white-space: nowrap;
}

.status-pill.completed {
    background: #ecfdf3;
    color: #027a48;
}

.status-pill.processing {
    background: #eff8ff;
    color: #175cd3;
}

.status-pill.pending {
    background: #fffaeb;
    color: #b54708;
}

.status-pill.cancelled {
    background: #fef3f2;
    color: #b42318;
}

.rank-number {
    display: grid;
    width: 32px;
    height: 32px;
    flex-shrink: 0;
    place-items: center;
    border-radius: 9px;
    background: #f4f0ff;
    color: #6941c6;
    font-size: 11px;
    font-weight: 800;
}

.top-product-badge {
    display: inline-flex;
    flex-shrink: 0;
    align-items: center;
    gap: 4px;
    padding: 5px 7px;
    border-radius: 7px;
    background: #ecfdf3;
    color: #027a48;
    font-size: 9px;
    font-weight: 750;
}

.stock-value {
    color: #1908b5;
    font-size: 14px;
    font-weight: 800;
}
.return-value {
    color: #c5b313;
    font-size: 14px;
    font-weight: 800;
}

.lowstock-value {
    color: #d92d20;
    font-size: 14px;
    font-weight: 800;
}

.stock-value.stock-empty {
    color: #d92d20;
}

.stock-caption {
    color: var(--subtle);
    font-size: 9px;
}

.loading-state,
.empty-state {
    display: flex;
    flex: 1;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 9px;
    padding: 30px 12px;
    color: var(--muted);
    text-align: center;
}

.loading-state {
    flex-direction: row;
    font-size: 11px;
}

.empty-state>i {
    color: #98a2b3;
    font-size: 25px;
}

.empty-state strong {
    color: #344054;
    font-size: 12px;
}

.empty-state p {
    margin: 0;
    color: var(--subtle);
    font-size: 10px;
    line-height: 1.5;
}

.quick-view-error {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px;
    border: 1px solid #fecdca;
    border-radius: 12px;
    background: #fff6f5;
    color: #b42318;
    font-size: 12px;
}

.quick-view-error span {
    flex: 1;
}

.retry-button {
    padding: 7px 10px;
    border: 1px solid #fecdca;
    border-radius: 8px;
    background: #fff;
    color: #b42318;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

@media (max-width: 1250px) {
    .main-grid {
        grid-template-columns: minmax(0, 1fr);
    }

    .quick-view-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 850px) {
    .dashboard-page {
        padding: 18px;
    }

    .page-heading {
        align-items: flex-start;
        flex-direction: column;
        gap: 18px;
    }

    .heading-actions {
        width: 100%;
    }

    .period-filter {
        flex: 1;
        width: auto;
    }

    .quick-view-grid {
        grid-template-columns: minmax(0, 1fr);
    }

    .quick-view-card {
        min-height: 250px;
    }
}

@media (max-width: 480px) {
    .dashboard-page {
        padding: 12px;
    }

    .page-heading h1 {
        font-size: 23px;
    }

    .heading-actions {
        align-items: stretch;
        flex-direction: column;
    }

    .period-filter,
    .primary-button {
        width: 100%;
        min-height: 42px;
    }

    .panel {
        padding: 16px;
        border-radius: 13px;
    }

    .quick-actions-grid {
        grid-template-columns: minmax(0, 1fr);
    }

    .quick-action-card {
        min-height: 68px;
    }

    .sales-summary strong {
        font-size: 23px;
    }

    .line-chart-wrapper {
        height: 220px;
    }

    .quick-view-heading {
        align-items: flex-start;
    }

    .quick-view-heading h2 {
        font-size: 20px;
    }

    .refresh-button {
        padding: 0 11px;
    }

    .quick-view-error {
        align-items: flex-start;
        flex-wrap: wrap;
    }
}
</style>
