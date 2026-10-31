<script setup>

import { computed, onBeforeUnmount, ref, watch } from 'vue'

const props = defineProps({
   
    summary: {
        type: Object,
        default: () => ({}),
    },
   
    loading: {
        type: Boolean,
        default: false,
    },
})

const progress = ref(0)
let frame = 0

const play = () => {
    cancelAnimationFrame(frame)
    const startTime = performance.now()
    const duration = 1300 // milliseconds
    progress.value = 0

    const tick = now => {
        const t = Math.min((now - startTime) / duration, 1)
        progress.value = 1 - Math.pow(1 - t, 4) // ease-out: fast start, slow end
        if (t < 1) frame = requestAnimationFrame(tick)
    }
    frame = requestAnimationFrame(tick)
}


watch(
    () => [props.loading, props.summary],
    ([isLoading]) => {
        if (!isLoading) play()
    },
    { immediate: true }
)

// Stop the animation if the component is removed
onBeforeUnmount(() => cancelAnimationFrame(frame))

// Returns the number to display right now (partial value while counting)
const animated = (value, isCurrency = false) => {
    const number = Number(value ?? 0)
    if (progress.value >= 1) return number // finished: show the exact value
    return isCurrency ? number * progress.value : Math.round(number * progress.value)
}

const cards = computed(() => {
    // Read each section of the API response (empty object if missing)
    const products = props.summary.SummaryProducts ?? {}
    const brands = props.summary.SummaryBrand ?? {}
    const categories = props.summary.SummaryCategories ?? {}
    const orders = props.summary.SummaryOrder ?? {}
    const expenses = props.summary.SummaryExpenses ?? {}
    const inventory = props.summary.InventoryStockSummary ?? {}
    const sales = props.summary.SaleSummary ?? {}

    return [
        {
            label: 'Total products',
            value: products.AllProducts,
            growthAmount: products.GrowAmountProduct,
            growthPercentage: products.growPercentageProducts,
            icon: 'bi-box-seam',
            color: 'blue',
        },
        {
            label: 'Active products',
            value: products.ActiveProducts,
            note: `${products.InactiveProducts ?? 0} inactive`,
            icon: 'bi-check2-circle',
            color: 'green',
        },
        {
            label: 'Low stock products',
            value: products.LowstockProducts,
            note: 'Needs attention',
            icon: 'bi-exclamation-circle',
            color: 'amber',
        },
        {
            label: 'Out of stock',
            value: products.OutStockProduct,
            note: 'Products with no stock',
            icon: 'bi-box',
            color: 'red',
        },
        {
            label: 'Sold quantity',
            value: products.SoldQuantity,
            note: 'Units sold',
            icon: 'bi-cart-check',
            color: 'blue',
        },
        {
            label: 'Total brands',
            value: brands.AllBrands,
            note: `${brands.ActiveBrands ?? 0} active`,
            icon: 'bi-tags',
            color: 'violet',
        },
        {
            label: 'Total categories',
            value: categories.AllCategories,
            note: `${categories.ActiveCategories ?? 0} active`,
            icon: 'bi-grid',
            color: 'green',
        },
        {
            label: 'Total orders',
            value: orders.AllOrders,
            note: `${orders.PendingOrders ?? 0} pending`,
            icon: 'bi-receipt',
            color: 'blue',
        },
        {
            label: 'Completed orders',
            value: orders.CompletedOrders,
            note: 'Successfully completed',
            icon: 'bi-check-circle',
            color: 'green',
        },
        {
            label: 'Total expenses',
            value: expenses.TotalExpenseAmount,
            icon: 'bi-wallet2',
            color: 'amber',
            currency: true, // show as money
        },
        {
            label: 'Stock balance',
            value: inventory.current_stock,
            growthAmount: inventory.StockgrowAmount,
            growthPercentage: inventory.stock_growth_percentage,
            icon: 'bi-boxes',
            color: 'violet',
        },
        {
            label: 'Total sales',
            value: sales.current_sale,
            growthAmount: sales.sale_growth_amount,
            growthPercentage: sales.sale_growth_percentage,
            icon: 'bi-currency-dollar',
            color: 'green',
            currency: true, // show as money
        },
    ]
})
</script>

<template>
    <section class="metric-grid">
        <!-- --i = card position, used to stagger the entrance animation -->
        <article
            v-for="(card, index) in cards"
            :key="card.label"
            class="metric-card"
            :style="{ '--i': index }"
        >
            <!-- Icon -->
            <div class="metric-icon" :class="card.color">
                <i class="bi" :class="card.icon"></i>
            </div>

            <div class="metric-content">
                <span>{{ card.label }}</span>

                <!-- While loading, show a dash -->
                <strong v-if="loading">—</strong>

                <!-- Money value (counts up) -->
                <strong v-else-if="card.currency">
                    {{
                        animated(card.value, true).toLocaleString('en-US', {
                            style: 'currency',
                            currency: 'USD',
                        })
                    }}
                </strong>

                <!-- Normal number (counts up) -->
                <strong v-else>
                    {{ animated(card.value).toLocaleString() }}
                </strong>

                <!-- Growth line (amount + percentage vs previous period) -->
                <small
                    v-if="card.growthAmount != null || card.growthPercentage != null"
                    :class="Number(card.growthAmount ?? 0) >= 0 ? 'growth-up' : 'growth-down'"
                >
                    <b>
                        {{ Number(card.growthAmount ?? 0) > 0 ? '+' : ''
                        }}{{ Number(card.growthAmount ?? 0).toLocaleString() }}
                    </b>
                    ·
                    {{
                        card.growthPercentage == null
                            ? 'N/A'
                            : `${Number(card.growthPercentage).toFixed(1)}%`
                    }}
                    <span class="growth-label">vs previous period</span>
                </small>

                <!-- Otherwise show the small note -->
                <small v-else-if="card.note">
                    {{ card.note }}
                </small>
            </div>
        </article>
    </section>
</template>

<style scoped>
/* ---------- Grid ---------- */
.metric-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}

/* ---------- Card + entrance animation ---------- */
@keyframes card-in {
    from {
        opacity: 0;
        transform: translateY(16px) scale(0.96);
    }
    to {
        opacity: 1;
        transform: none;
    }
}

.metric-card {
    display: flex;
    align-items: center;
    gap: 13px;
    min-width: 0;
    padding: 18px;
    border: 1px solid #e4e7ec;
    border-radius: 12px;
    background: #fff;
    box-shadow: 0 2px 7px rgba(16, 24, 40, 0.035);

    /* Each card starts 60ms after the previous one */
    animation: card-in 0.6s cubic-bezier(0.22, 1, 0.36, 1) both;
    animation-delay: calc(0.15s + var(--i, 0) * 0.06s);

    /* Hover: lift the card slightly */
    transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
}

.metric-card:hover {
    transform: translateY(-3px);
    border-color: #d0d9ee;
    box-shadow: 0 8px 20px rgba(16, 24, 40, 0.07);
}

/* ---------- Icon ---------- */
.metric-icon {
    width: 42px;
    height: 42px;
    flex-shrink: 0;
    display: grid;
    place-items: center;
    border-radius: 10px;
    font-size: 18px;
    transition: transform 0.25s;
}

/* Icon wiggles up when the card is hovered */
.metric-card:hover .metric-icon {
    transform: scale(1.1) rotate(-4deg);
}

.metric-icon.blue { color: #1f5eff; background: #edf3ff; }
.metric-icon.green { color: #039855; background: #ecfdf3; }
.metric-icon.amber { color: #dc6803; background: #fffaeb; }
.metric-icon.violet { color: #6941c6; background: #f4f3ff; }
.metric-icon.red { color: #d92d20; background: #fef3f2; }

/* ---------- Text ---------- */
.metric-content {
    min-width: 0;
}

.metric-content > span,
.metric-content > small {
    display: block;
    color: #667085;
    font-size: 11px;
}

.metric-content > strong {
    display: block;
    margin: 5px 0 3px;
    color: #101828;
    font-size: 23px;
    letter-spacing: -0.03em;
    overflow-wrap: anywhere;
    font-variant-numeric: tabular-nums; /* digits keep the same width while counting */
}

/* Growth colours */
.growth-up { color: #039855 !important; }
.growth-down { color: #d92d20 !important; }

.growth-label {
    display: inline;
    color: #667085;
    font-size: 10px;
}

/* ---------- Responsive ---------- */
@media (max-width: 1000px) {
    .metric-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 575px) {
    .metric-card {
        padding: 12px;
        gap: 9px;
    }

    .metric-icon {
        width: 34px;
        height: 34px;
        font-size: 15px;
    }

    .metric-content > strong {
        font-size: 18px;
    }
}
</style>