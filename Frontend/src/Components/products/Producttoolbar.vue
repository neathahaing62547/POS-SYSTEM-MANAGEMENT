<script setup>
import {
    reactive
} from 'vue'

defineProps({
    categories: {
        type: Array,
        default: () => []
    },
    brands: {
        type: Array,
        default: () => []
    },
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
const emit = defineEmits(['filter', 'search', 'export', 'add'])

const selected = reactive({
    category: '',
    brand: '',
    status: '',
    stock: ''
})
</script>

<template>
    <div>
        <div
            class="toolbar d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 p-3 border-bottom">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <select v-model="selected.category" class="form-select filter-select">
                    <option value="">All Category</option>
                    <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
                <select v-model="selected.brand" class="form-select filter-select">
                    <option value="">All Brands</option>
                    <option v-for="b in brands" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
                <select v-model="selected.status" class="form-select filter-select">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <select v-model="selected.stock" class="form-select filter-select">
                    <option value="">All Stock</option>
                    <option value="low">Low Stock</option>
                    <option value="in">In Stock</option>
                </select>
                <button class="btn btn-outline-secondary filter-btn" @click="emit('filter', { ...selected })">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <button v-if="isAdmin" class="btn btn-success" @click="emit('export')">
                    <i class="bi bi-download me-1"></i> Export Excel
                </button>
            </div>

            <button v-if="canCreate" class="btn btn-primary" @click="emit('add')">
                <i class="bi bi-plus-lg"></i> Add Product
            </button>
        </div>

        <!-- Search -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 p-3 pb-0">
            <div class="input-group search-input">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input v-model="search" class="form-control" placeholder="Search products..." @input="emit('search')">
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary" @click="emit('search')">
                    <i class="bi bi-search"></i> Search
                </button>
                <button class="btn btn-outline-secondary" @click="emit('search')">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.toolbar {
    background: var(--surface-muted, #eef2f7);
    border-top-left-radius: 12px;
    border-top-right-radius: 12px;
    border-bottom: 1px solid var(--border-color, #e2e8f0) !important;
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
