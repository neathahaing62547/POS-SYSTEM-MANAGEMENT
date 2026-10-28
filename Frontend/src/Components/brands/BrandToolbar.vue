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

const search = defineModel('search')
const selectedStatus = defineModel('selectedStatus')

defineEmits([
    'search',
    'filter',
    'reload',
    'add',
    'export'
])
</script>

<template>
    <div class="card border-0 shadow-sm">

        <div class="toolbar d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 p-3">

            <div class="d-flex flex-wrap gap-2 align-items-center filter-bar">

                <select
                    v-model="selectedStatus"
                    class="form-select filter-select"
                >
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>

                <button
                    class="btn btn-outline-secondary filter-btn"
                    @click="$emit('filter')"
                >
                    <i class="bi bi-funnel me-1"></i>
                    Filter
                </button>

                <button
                    v-if="isAdmin"
                    class="btn btn-success"
                    @click="$emit('export')"
                >
                    <i class="bi bi-download me-1"></i>
                    Export Excel
                </button>

            </div>

            <button
                v-if="canCreate"
                class="btn btn-primary"
                @click="$emit('add')"
            >
                <i class="bi bi-plus-lg"></i>
                Add Brand
            </button>

        </div>

        <div class="card-body">

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">

                <div class="input-group search-input">
                    <span class="input-group-text bg-white">
                        <i class="bi bi-search"></i>
                    </span>

                    <input
                        v-model="search"
                        @input="$emit('search')"
                        type="text"
                        class="form-control"
                        placeholder="Search brands..."
                    >
                </div>

                <div class="d-flex gap-2">

                    <button
                        class="btn btn-outline-secondary"
                        @click="$emit('search')"
                    >
                        <i class="bi bi-search"></i>
                        Search
                    </button>

                    <button
                        class="btn btn-outline-secondary"
                        @click="$emit('reload')"
                    >
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>

                </div>

            </div>

        </div>

    </div>
</template>

<style scoped>
.toolbar {
    background: var(--surface-muted);
    border-bottom: 1px solid var(--border-color) !important;
    border-radius: 12px 12px 0 0;
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