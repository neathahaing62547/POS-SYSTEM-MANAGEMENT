<script setup>
defineProps({
    modelValue: { type: Boolean, default: false },
    title: { type: String, required: true },
    itemName: { type: String, default: '' },
    deleting: { type: Boolean, default: false },
    error: { type: String, default: '' },
    warning: { type: String, default: 'This action cannot be undone.' }
})

const emit = defineEmits(['update:modelValue', 'confirm'])
</script>

<template>
    <div v-if="modelValue" class="management-delete-backdrop" @click.self="emit('update:modelValue', false)">
        <section class="management-delete-modal" role="dialog" aria-modal="true" :aria-label="title">
            <header>
                <h2>{{ title }}</h2>
                <button type="button" class="btn-close" aria-label="Close" :disabled="deleting"
                    @click="emit('update:modelValue', false)"></button>
            </header>
            <div class="body">
                <div v-if="error" class="alert alert-danger">{{ error }}</div>
                <p>Delete <strong>{{ itemName }}</strong>?</p>
                <p class="text-muted small mb-0">{{ warning }}</p>
            </div>
            <footer>
                <button type="button" class="btn btn-light" :disabled="deleting"
                    @click="emit('update:modelValue', false)">Cancel</button>
                <button type="button" class="btn btn-danger" :disabled="deleting" @click="emit('confirm')">
                    <span v-if="deleting" class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                    {{ deleting ? 'Deleting...' : 'Delete' }}
                </button>
            </footer>
        </section>
    </div>
</template>

<style scoped>
.management-delete-backdrop {
    position: fixed;
    inset: 0;
    z-index: 1050;
    display: grid;
    place-items: center;
    padding: 1rem;
    background: rgb(15 23 42 / 60%);
}

.management-delete-modal {
    width: min(100%, 440px);
    border: 1px solid var(--ui-border);
    border-radius: var(--ui-modal-radius);
    background: var(--ui-surface);
    color: var(--ui-text);
    box-shadow: var(--ui-shadow-lg);
}

.management-delete-modal header,
.management-delete-modal footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.25rem;
    background: var(--ui-surface-muted);
}

.management-delete-modal header {
    border-bottom: 1px solid var(--ui-border);
}

.management-delete-modal h2 {
    margin: 0;
    font-size: 1.15rem;
}

.management-delete-modal .body {
    padding: 1.25rem;
}

.management-delete-modal footer {
    justify-content: flex-end;
    border-top: 1px solid var(--ui-border);
}
</style>
