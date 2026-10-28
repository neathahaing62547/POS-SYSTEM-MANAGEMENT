```vue
<script setup>
defineProps({
    summary: {
        type: Object,
        default: () => ({})
    }
})
</script>

<template>

    <div class="row g-3 mb-4">

        <div class="col-6 col-md-3">
            <div class="card summary-card total-card border-0 shadow-sm h-100">
                <div class="card-body">

                    <div class="d-flex align-items-center justify-content-between">

                        <div>
                            <small class="text-muted">
                                Total Purchases
                            </small>

                            <h4 class="fw-bold mb-0 mt-2">
                                {{ summary.total || 0 }}
                            </h4>
                        </div>

                        <div class="icon-box">
                            <i class="bi bi-cart-check"></i>
                        </div>

                    </div>

                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card summary-card pending-card border-0 shadow-sm h-100">
                <div class="card-body">

                    <div class="d-flex align-items-center justify-content-between">

                        <div>
                            <small class="text-muted">
                                Pending
                            </small>

                            <h4 class="fw-bold mb-0 mt-2">
                                {{ summary.pending || 0 }}
                            </h4>
                        </div>

                        <div class="icon-box">
                            <i class="bi bi-hourglass-split"></i>
                        </div>

                    </div>

                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card summary-card received-card border-0 shadow-sm h-100">
                <div class="card-body">

                    <div class="d-flex align-items-center justify-content-between">

                        <div>
                            <small class="text-muted">
                                Received
                            </small>

                            <h4 class="fw-bold mb-0 mt-2">
                                {{ summary.received || 0 }}
                            </h4>
                        </div>

                        <div class="icon-box">
                            <i class="bi bi-check-circle"></i>
                        </div>

                    </div>

                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card summary-card cost-card border-0 shadow-sm h-100">
                <div class="card-body">

                    <div class="d-flex align-items-center justify-content-between">

                        <div>
                            <small class="text-muted">
                                Total Cost
                            </small>

                            <h4 class="fw-bold mb-0 mt-2">
                                {{ summary.total_cost || 0 }}
                            </h4>
                        </div>

                        <div class="icon-box">
                            <i class="bi bi-currency-dollar"></i>
                        </div>

                    </div>

                </div>
            </div>
        </div>

    </div>

</template>

<style scoped>
.summary-card {
    border-radius: 12px;
    border: 1px solid #e5e7eb !important;
    border-left: 4px solid transparent !important;
}

.summary-card small {
    font-size: 12px;
    font-weight: 600;
}

.summary-card h4 {
    font-size: 20px;
}

.icon-box {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.total-card {
    background: #eff6ff;
    border-left-color: #2563eb !important;
    color: #1e40af;
}

.total-card .icon-box {
    background: #dbeafe;
    color: #2563eb;
}

.pending-card {
    background: #fff7ed;
    border-left-color: #f59e0b !important;
    color: #b45309;
}

.pending-card .icon-box {
    background: #ffedd5;
    color: #f59e0b;
}

.received-card {
    background: #f0fdf4;
    border-left-color: #16a34a !important;
    color: #166534;
}

.received-card .icon-box {
    background: #dcfce7;
    color: #16a34a;
}

.cost-card {
    background: #faf5ff;
    border-left-color: #9333ea !important;
    color: #6b21a8;
}

.cost-card .icon-box {
    background: #f3e8ff;
    color: #9333ea;
}
</style>
```
