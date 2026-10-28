
<script setup>
import { computed } from 'vue'

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

const cards = computed(() => {
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
            currency: true,
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
            currency: true,
        },
    ]
})
</script>

<template>
    <section class="metric-grid">
        <article
            v-for="card in cards"
            :key="card.label"
            class="metric-card"
        >
            <div class="metric-icon" :class="card.color">
                <i class="bi" :class="card.icon"></i>
            </div>

            <div class="metric-content">
                <span>{{ card.label }}</span>

                <strong v-if="loading">—</strong>

                <strong v-else-if="card.currency">
                    {{ Number(card.value ?? 0).toLocaleString('en-US', {
                        style: 'currency',
                        currency: 'USD',
                    }) }}
                </strong>

                <strong v-else>
                    {{ Number(card.value ?? 0).toLocaleString() }}
                </strong>

                <small v-if="card.growthAmount != null ||
                    card.growthPercentage != null"
                    :class="Number(card.growthAmount ?? 0) >= 0
                        ? 'growth-up'
                        : 'growth-down'"
                >
                    <b>
                        {{ Number(card.growthAmount ?? 0) > 0 ? '+' : '' }}{{ Number(card.growthAmount ?? 0).toLocaleString() }}
                    </b>
                    · {{ card.growthPercentage == null
                        ? 'N/A'
                        : `${Number(card.growthPercentage).toFixed(1)}%` }}
                    <span class="growth-label">vs previous period</span>
                </small>

                <small v-else-if="card.note">
                    {{ card.note }}
                </small>
            </div>
        </article>
    </section>
</template>

<style scoped>
.metric-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 20px;
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
    box-shadow: 0 2px 7px rgba(16, 24, 40, .035);
}

.metric-icon {
    width: 42px;
    height: 42px;
    flex-shrink: 0;
    display: grid;
    place-items: center;
    border-radius: 10px;
    font-size: 18px;
}

.metric-icon.blue {
    color: #1f5eff;
    background: #edf3ff;
}

.metric-icon.green {
    color: #039855;
    background: #ecfdf3;
}

.metric-icon.amber {
    color: #dc6803;
    background: #fffaeb;
}

.metric-icon.violet {
    color: #6941c6;
    background: #f4f3ff;
}

.metric-icon.red {
    color: #d92d20;
    background: #fef3f2;
}

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
    letter-spacing: -.03em;
    overflow-wrap: anywhere;
}

.growth-up {
    color: #039855 !important;
}

.growth-down {
    color: #d92d20 !important;
}

.growth-label {
    display: inline;
    color: #667085;
    font-size: 10px;
}

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
