<script setup>
defineProps({
    summary: {
        type: Object,
        required: true
    }
})
</script>

<template>
    <div class="row g-3 mb-4">

        <div class="col-6 col-md-4">
            <div class="card summary-card total-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <small class="text-muted">Total Brands</small>
                            <h4 class="fw-bold mb-0 mt-2">
                                {{ summary.total }}
                            </h4>
                        </div>

                        <div class="icon-box">
                            <i class="bi bi-tags"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4">
            <div class="card summary-card active-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <small class="text-muted">Active</small>
                            <h4 class="fw-bold mb-0 mt-2">
                                {{ summary.active }}
                            </h4>
                        </div>

                        <div class="icon-box">
                            <i class="bi bi-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4">
            <div class="card summary-card inactive-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <small class="text-muted">Inactive</small>
                            <h4 class="fw-bold mb-0 mt-2">
                                {{ summary.inactive }}
                            </h4>
                        </div>

                        <div class="icon-box">
                            <i class="bi bi-x-circle"></i>
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

.active-card {
    background: #f0fdf4;
    border-left-color: #16a34a !important;
    color: #166534;
}

.active-card .icon-box {
    background: #dcfce7;
    color: #16a34a;
}

.inactive-card {
    background: #f8fafc;
    border-left-color: #64748b !important;
    color: #334155;
}

.inactive-card .icon-box {
    background: #e2e8f0;
    color: #64748b;
}
</style>