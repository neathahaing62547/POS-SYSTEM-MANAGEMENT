<script setup>
defineProps({
    modelValue: { type: Boolean, default: false },
    userName: { type: String, default: '' },
    deleting: { type: Boolean, default: false },
    error: { type: String, default: '' }
})

const emit = defineEmits(['update:modelValue', 'confirm'])
</script>

<template>
    <div v-if="modelValue" class="user-modal-backdrop" @click.self="emit('update:modelValue', false)">
        <section class="user-delete-modal" role="dialog" aria-modal="true" aria-labelledby="delete-user-title">
            <header>
                <h2 id="delete-user-title">Delete User</h2>
                <button type="button" class="btn-close" aria-label="Close" :disabled="deleting" @click="emit('update:modelValue', false)"></button>
            </header>
            <div class="body">
                <div v-if="error" class="alert alert-danger">{{ error }}</div>
                <p>Delete <strong>{{ userName }}</strong>?</p>
                <p class="text-muted small mb-0">This action cannot be undone. You cannot delete your own account.</p>
            </div>
            <footer>
                <button type="button" class="btn btn-light" :disabled="deleting" @click="emit('update:modelValue', false)">Cancel</button>
                <button type="button" class="btn btn-danger" :disabled="deleting" @click="emit('confirm')">
                    <span v-if="deleting" class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                    {{ deleting ? 'Deleting...' : 'Delete User' }}
                </button>
            </footer>
        </section>
    </div>
</template>

<style scoped>
.user-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 1050;
    display: grid;
    place-items: center;
    padding: 1rem;
    background: rgb(15 23 42 / 60%);
}

.user-delete-modal {
    width: min(100%, 440px);
    border: 1px solid var(--ui-border);
    border-radius: var(--ui-modal-radius);
    background: var(--ui-surface);
    color: var(--ui-text);
    box-shadow: var(--ui-shadow-lg);
}

.user-delete-modal header,
.user-delete-modal footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.25rem;
    background: var(--ui-surface-muted);
}

.user-delete-modal header {
    border-bottom: 1px solid var(--ui-border);
}

.user-delete-modal h2 {
    margin: 0;
    font-size: 1.15rem;
}

.user-delete-modal .body {
    padding: 1.25rem;
}

.user-delete-modal footer {
    justify-content: flex-end;
    border-top: 1px solid var(--ui-border);
}
</style>
