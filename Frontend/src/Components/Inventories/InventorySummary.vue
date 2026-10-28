<script setup>
defineProps({
    summary: {
        type: Object,
        default: () => ({ total_products: 0, low_stock: 0, out_of_stock: 0 })
    }
})
</script>

<template>
    <div class="summary-grid">
        <div class="summary-card">
            <div class="summary-icon icon-blue"><i class="bi bi-box-seam"></i></div>
            <div>
                <div class="summary-value">{{ summary.total_products }}</div>
                <div class="summary-label">Total Products</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon icon-amber"><i class="bi bi-exclamation-triangle"></i></div>
            <div>
                <div class="summary-value">{{ summary.low_stock }}</div>
                <div class="summary-label">Low Stock</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon icon-red"><i class="bi bi-x-octagon"></i></div>
            <div>
                <div class="summary-value">{{ summary.out_of_stock }}</div>
                <div class="summary-label">Out of Stock</div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}

.summary-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 18px;
    border-radius: 12px;
    background: var(--surface-bg, #fff);
    border: 1px solid var(--border-color, #e2e8f0);
}

.summary-icon {
    flex: none;
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.icon-blue {
    background: #e0e7ff;
    color: #4338ca;
}

.icon-amber {
    background: #fef3c7;
    color: #b45309;
}

.icon-red {
    background: #fee2e2;
    color: #b91c1c;
}

.summary-value {
    font-size: 22px;
    font-weight: 700;
    color: var(--text-color, #0f172a);
}

.summary-label {
    font-size: 13px;
    color: var(--muted-text, #64748b);
}
</style>