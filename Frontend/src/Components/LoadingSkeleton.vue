<script setup>
defineProps({
    rows: { type: Number, default: 6 },
    columns: { type: Number, default: 6 },
    variant: { type: String, default: 'list' },
    label: { type: String, default: 'Loading records' }
})
</script>

<template>
    <div v-if="variant === 'table'" class="skeleton-table-scroll" role="status" :aria-label="label">
        <div
            class="skeleton-table"
            :style="{
                '--skeleton-columns': columns,
                '--skeleton-min-width': columns >= 9 ? '1050px' : '760px'
            }"
        >
            <div class="skeleton-table-head">
                <div v-for="column in columns" :key="column" class="skeleton-line"></div>
            </div>
            <div v-for="row in rows" :key="row" class="skeleton-table-row">
                <div v-for="column in columns" :key="column" class="skeleton-line"></div>
            </div>
        </div>
        <span class="visually-hidden">{{ label }}...</span>
    </div>
    <div v-else-if="variant === 'spinner'" class="loading-state" role="status" :aria-label="label">
        <span class="spinner-border" aria-hidden="true"></span>
        <span>{{ label }}...</span>
    </div>
    <div v-else class="skeleton-list" role="status" :aria-label="label">
        <div v-for="row in rows" :key="row" class="skeleton-row">
            <div class="skeleton-thumb"></div>
            <div class="skeleton-col skeleton-col-main">
                <div class="skeleton-line w-70"></div>
                <div class="skeleton-line w-40 skeleton-line-sm"></div>
            </div>
            <div class="skeleton-col">
                <div class="skeleton-line w-50"></div>
            </div>
            <div class="skeleton-col skeleton-col-end">
                <div class="skeleton-pill"></div>
            </div>
        </div>
        <span class="visually-hidden">{{ label }}...</span>
    </div>
</template>
