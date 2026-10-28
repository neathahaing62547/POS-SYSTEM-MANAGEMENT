<template>
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4">
            <div class="card summary-card total-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <small class="text-muted">Total Suppliers</small>
                            <h4 class="fw-bold mb-0 mt-2">
                                {{ summary.total || 0 }}
                            </h4>
                        </div>

                        <div class="icon-box">
                            <i class="bi bi-truck"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4">
            <div class="card summary-card active-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <small class="text-muted">Active</small>
                            <h4 class="fw-bold mb-0 mt-2">
                                {{ summary.active || 0 }}
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
            <div class="card summary-card inactive-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <small class="text-muted">Inactive</small>
                            <h4 class="fw-bold mb-0 mt-2">
                                {{ summary.inactive || 0 }}
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

<script setup>
defineProps({
    summary: {
        type: Object,
        default: () => ({
            total: 0,
            active: 0,
            inactive: 0
        })
    }
})
</script>