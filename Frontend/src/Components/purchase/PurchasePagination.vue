<script setup>
defineProps({
    pagination: {
        type: Object,
        default: () => ({})
    }
})

defineEmits(['change'])
</script>
<template>
    <div
        class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 mt-3"
    >
        <small class="text-muted">
         Showing
            {{ pagination?.from || 0 }}
            to
            {{ pagination?.to || 0 }}
            of
            {{ pagination?.total || 0 }}
            purchases
        </small>

        <div class="d-flex align-items-center gap-1">
            <button
                class="btn btn-sm btn-outline-secondary"
                @click="$emit('change', pagination.current_page - 1)"
            >
                Previous
            </button>

            <button
                v-for="page in pagination?.last_page"
                :key="page"
                class="btn btn-sm"
                :class="
                    page === pagination.current_page
                        ? 'btn-primary'
                        : 'btn-outline-secondary'
                "
                @click="$emit('change', page)"
            >
                {{ page }}
            </button>
            <button
                class="btn btn-sm btn-outline-secondary"
                @click="$emit('change', pagination.current_page + 1)"
            >
                Next
            </button>
        </div>

    </div>

</template>

