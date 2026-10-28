<template>
    <div
        class="offcanvas offcanvas-end purchase-detail-offcanvas"
        tabindex="-1"
        id="purchaseDetail"
        aria-labelledby="purchaseDetailLabel"
    >
        <div class="offcanvas-header">
            <div>
                <h5 class="offcanvas-title" id="purchaseDetailLabel">
                    Purchase Detail ID {{ purchaseDetail?.id || '-' }}
                </h5>
                <span class="detail-po-number">
                    #{{ purchaseDetail?.po_number || '-' }}
                </span>
            </div>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="offcanvas"
                aria-label="Close"
            ></button>
        </div>

        <div class="offcanvas-body">

            <div class="detail-info">
                <div class="detail-info-item">
                    <span>Supplier</span>
                    <strong>{{ purchaseDetail?.supplier?.company_name || '-' }}</strong>
                </div>

                <div class="detail-info-item">
                    <span>Store</span>
                    <strong>{{ purchaseDetail?.store?.name || '-' }}</strong>
                </div>

                <div class="detail-info-item">
                    <span>Status</span>
                    <strong
                        :class="{
                            'badge-pending': purchaseDetail?.status === 'pending',
                            'badge-received': purchaseDetail?.status === 'received',
                            'badge-cancelled': purchaseDetail?.status === 'cancelled'
                        }"
                    >
                        {{ purchaseDetail?.status || '-' }}
                    </strong>
                </div>

                <div class="detail-info-item">
                    <span>Ordered At</span>
                    <strong>{{ purchaseDetail?.ordered_at || '-' }}</strong>
                </div>
            </div>

            <div class="detail-section-title">Products</div>

            <div class="detail-products">
                <div
                    v-for="item in purchaseDetail?.items || []"
                    :key="item.id"
                    class="detail-product"
                >
                    <div class="detail-product-main">
                        <div>
                            <strong>{{ item.product?.name || '-' }}</strong>
                            <span>{{ item.product?.sku || '-' }}</span>
                        </div>

                        <strong class="detail-product-total">
                            {{ money(item.subtotal) }}
                        </strong>
                    </div>

                    <div class="detail-product-meta">
                        <span>Qty: {{ item.quantity || 0 }}</span>
                        <span>{{ money(item.cost_price) }}</span>
                    </div>
                </div>

                <div v-if="!purchaseDetail?.items?.length" class="detail-product">
                    <span class="text-muted">No products found.</span>
                </div>
            </div>

            <div class="detail-summary">
                <div class="summary-row">
                    <span>Total Items</span>
                    <strong>{{ purchaseDetail?.total_items || 0 }}</strong>
                </div>

                <div class="summary-row">
                    <span>Total Quantity</span>
                    <strong>{{ purchaseDetail?.total_quantity || 0 }}</strong>
                </div>

                <div class="summary-row total">
                    <span>Total Cost</span>
                    <strong>{{ money(purchaseDetail?.total_cost) }}</strong>
                </div>
            </div>

        </div>
    </div>
</template>

<script setup>
defineProps({
    purchaseDetail: {
        type: Object,
        default: () => ({})
    }
})

const money = (value) =>
    '$' + Number(value || 0).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })
</script>

<style scoped>
.purchase-detail-offcanvas {
    width: 780px !important;
    max-width: 100vw;
    z-index: 1100;
    background: var(--surface-bg) !important;
    color: var(--text-color) !important;
}

.purchase-detail-offcanvas .offcanvas-header {
    margin-top: 80px;
    background: var(--surface-bg) !important;
    color: var(--text-color) !important;
    border-bottom: 1px solid var(--border-color);
}

.purchase-detail-offcanvas .offcanvas-body {
    background: var(--surface-bg) !important;
    color: var(--text-color) !important;
}

.purchase-detail-offcanvas .offcanvas-title {
    color: var(--text-color) !important;
}

.purchase-detail-offcanvas .btn-close {
    opacity: 0.8;
}

.detail-po-number {
    display: block;
    margin-top: 4px;
    font-size: 12px;
    color: var(--muted-text) !important;
}

.detail-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    margin-bottom: 24px;
}

.detail-info-item {
    padding: 14px;
    border: 1px solid var(--border-color);
    border-radius: 10px;
    background: var(--surface-muted);
}

.detail-info-item span {
    display: block;
    margin-bottom: 5px;
    font-size: 11px;
    color: var(--muted-text) !important;
}

.detail-info-item strong {
    font-size: 13px;
    color: var(--text-color) !important;
}

.detail-info-item strong.badge-pending,
.detail-info-item strong.badge-received,
.detail-info-item strong.badge-cancelled {
    display: inline-block;
    padding: 4px 9px;
    border-radius: 6px;
    font-size: 11px;
    text-transform: capitalize;
}

.detail-info-item strong.badge-pending {
    color: #856404 !important;
    background: #fff3cd !important;
}

.detail-info-item strong.badge-received {
    color: #0f5132 !important;
    background: #d1e7dd !important;
}

.detail-info-item strong.badge-cancelled {
    color: #842029 !important;
    background: #f8d7da !important;
}

.detail-section-title {
    margin-bottom: 12px;
    font-size: 14px;
    font-weight: 700;
    color: var(--text-color) !important;
}

.detail-products {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.detail-product {
    padding: 14px;
    border: 1px solid var(--border-color);
    border-radius: 10px;
    background: var(--surface-bg);
}

.detail-product-main {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
}

.detail-product-main strong {
    display: block;
    font-size: 13px;
    color: var(--text-color) !important;
}

.detail-product-main span {
    display: block;
    margin-top: 3px;
    font-size: 11px;
    color: var(--muted-text) !important;
}

.detail-product-total {
    white-space: nowrap;
}

.detail-product-meta {
    display: flex;
    gap: 20px;
    margin-top: 10px;
    font-size: 11px;
    color: var(--muted-text) !important;
}

.detail-product-meta span,
.detail-product .text-muted {
    color: var(--muted-text) !important;
}

.detail-summary {
    margin-top: 24px;
    padding: 16px;
    border: 1px solid var(--border-color);
    border-radius: 10px;
    background: var(--surface-muted);
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 7px 0;
    font-size: 13px;
    color: var(--muted-text) !important;
}

.summary-row strong {
    color: var(--text-color) !important;
}

.summary-row.total {
    margin-top: 8px;
    padding-top: 14px;
    border-top: 1px solid var(--border-color);
    font-weight: 700;
}

.summary-row.total span {
    color: var(--text-color) !important;
}

.summary-row.total strong {
    font-size: 18px;
}

@media (max-width: 768px) {
    .purchase-detail-offcanvas {
        width: 100% !important;
    }

    .purchase-detail-offcanvas .offcanvas-header {
        margin-top: 30px;
        padding: 16px;
        justify-content: space-between;
        min-height: 70px;
    }

    .purchase-detail-offcanvas .btn-close {
        display: block !important;
        width: 32px;
        height: 32px;
        padding: 8px;
        margin: 0;
        opacity: 1;
        flex-shrink: 0;
    }

    .detail-info {
        grid-template-columns: 1fr;
    }

    .detail-product-main {
        align-items: flex-start;
    }

    .detail-product-meta {
        flex-wrap: wrap;
        gap: 10px 20px;
    }
}
</style>