<script setup>
defineProps({
    isAdmin: {
        type: Boolean,
        default: false
    },
    canCreate: {
        type: Boolean,
        default: false
    }
})

const search = defineModel('search', {
    type: String,
    default: ''
})

const statusFilter = defineModel('statusFilter', {
    type: String,
    default: ''
})

const emit = defineEmits([
    'filter',
    'search',
    'export',
    'refresh',
    'create'
])
</script>

<template>
    <div>
        <div
            class="toolbar d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 p-3 border-bottom"
        >
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <select
                    v-model="statusFilter"
                    class="form-select filter-select"
                    aria-label="Filter suppliers by status"
                >
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>

                <button
                    type="button"
                    class="btn btn-outline-secondary filter-btn"
                    @click="emit('filter')"
                >
                    <i class="bi bi-funnel me-1"></i>
                    Filter
                </button>

                <button
                    v-if="isAdmin"
                    type="button"
                    class="btn btn-success"
                    @click="emit('export')"
                >
                    <i class="bi bi-download me-1"></i>
                    Export Excel
                </button>
            </div>

            <button
                v-if="canCreate"
                type="button"
                class="btn btn-primary"
                @click="emit('create')"
            >
                <i class="bi bi-plus-lg"></i>
                Add Supplier
            </button>
        </div>

        <div
            class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 p-3 pb-0"
        >
            <div class="input-group search-input">
                <span class="input-group-text">
                    <i class="bi bi-search"></i>
                </span>
                <input
                    v-model="search"
                    type="search"
                    class="form-control"
                    placeholder="Search suppliers..."
                    aria-label="Search suppliers"
                    @input="emit('search')"
                >
            </div>

            <div class="d-flex gap-2">
                <button
                    type="button"
                    class="btn btn-outline-secondary"
                    @click="emit('search')"
                >
                    <i class="bi bi-search"></i>
                    Search
                </button>
                <button
                    type="button"
                    class="btn btn-outline-secondary"
                    aria-label="Refresh suppliers"
                    title="Refresh suppliers"
                    @click="emit('refresh')"
                >
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.toolbar {
    background: var(--surface-muted);
    border-top-left-radius: 12px;
    border-top-right-radius: 12px;
    border-bottom: 1px solid var(--border-color) !important;
}

.filter-select,
.filter-btn {
    height: 40px;
    border-radius: 8px;
    font-size: 14px;
}

.filter-select {
    width: 150px;
    max-width: 150px;
}

.search-input {
    max-width: 350px;
    width: 100%;
}

.search-input .form-control,
.search-input .input-group-text {
    height: 40px;
    border-radius: 8px;
}

@media (max-width: 768px) {
    .filter-select {
        width: 100%;
        max-width: none;
    }
}
</style>
