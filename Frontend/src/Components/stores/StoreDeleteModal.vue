<script setup>
defineProps({
    modelValue: {
        type: Boolean,
        default: false
    },
    storeName: {
        type: String,
        default: ''
    },
    deleting: {
        type: Boolean,
        default: false
    },
    error: {
        type: String,
        default: ''
    }
})

const emit = defineEmits(['update:modelValue', 'confirm'])
</script>

<template>
    <div
        v-if="modelValue"
        class="store-modal-backdrop"
        @click.self="emit('update:modelValue', false)"
    >
        <section class="store-delete-modal">
            <header>
                <h2>Delete Store</h2>
                <button
                    type="button"
                    class="btn-close"
                    aria-label="Close"
                    :disabled="deleting"
                    @click="emit('update:modelValue', false)"
                ></button>
            </header>
            <div class="body">
                <div v-if="error" class="alert alert-danger">{{ error }}</div>
                <p>Delete <strong>{{ storeName }}</strong>?</p>
                <p class="text-muted small mb-0">
                    Stores with inventory or purchase history cannot be deleted. Deactivate those stores instead.
                </p>
            </div>
            <footer>
                <button
                    type="button"
                    class="btn btn-light"
                    :disabled="deleting"
                    @click="emit('update:modelValue', false)"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    class="btn btn-danger"
                    :disabled="deleting"
                    @click="emit('confirm')"
                >
                    {{ deleting ? 'Deleting...' : 'Delete Store' }}
                </button>
            </footer>
        </section>
    </div>
</template>

<style scoped>
.store-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 1050;
    display: grid;
    place-items: center;
    padding: 1rem;
    background: rgb(15 23 42 / 60%);
}

.store-delete-modal {
    width: min(100%, 440px);
    border: 1px solid var(--ui-border);
    border-radius: var(--ui-modal-radius);
    background: var(--ui-surface);
    color: var(--ui-text);
    box-shadow: var(--ui-shadow-lg);
}

.store-delete-modal header,
.store-delete-modal footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.25rem;
    background: var(--ui-surface-muted);
}

.store-delete-modal header {
    border-bottom: 1px solid var(--ui-border);
}

.store-delete-modal h2 {
    margin: 0;
    font-size: 1.15rem;
}

.store-delete-modal .body {
    padding: 1.25rem;
}

.store-delete-modal footer {
    justify-content: flex-end;
    border-top: 1px solid var(--ui-border);
}
</style>
