<template>
    <div
        v-if="pagination"
        class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 mt-3"
    >

        <small class="text-muted">
            Showing {{ pagination.from || 0 }}
            to {{ pagination.to || 0 }}
            of {{ pagination.total || 0 }} suppliers
        </small>

        <div class="d-flex align-items-center gap-2">

            <button
                class="btn btn-sm btn-outline-secondary"
                :disabled="pagination.current_page === 1"
                @click="$emit('page', pagination.current_page - 1)"
            >
                Previous
            </button>

            <span class="text-muted small">
                Page {{ pagination.current_page }}
                of {{ pagination.last_page }}
            </span>

            <button
                class="btn btn-sm btn-outline-secondary"
                :disabled="
                    pagination.current_page === pagination.last_page
                "
                @click="$emit('page', pagination.current_page + 1)"
            >
                Next
            </button>

        </div>
    </div>
</template>

<script setup>
defineProps({
    pagination: {
        type: Object,
        default: null
    }
})

defineEmits([
    'page'
])
</script>