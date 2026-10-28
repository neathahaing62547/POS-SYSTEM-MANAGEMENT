<script setup>
const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false
    },
    form: {
        type: Object,
        required: true
    },
    mode: {
        type: String,
        default: 'create'
    },
    saving: {
        type: Boolean,
        default: false
    },
    error: {
        type: String,
        default: ''
    }
})

const emit = defineEmits(['update:modelValue', 'submit'])
</script>

<template>
    <div
        v-if="modelValue"
        class="store-modal-backdrop"
        @click.self="emit('update:modelValue', false)"
    >
        <form class="store-modal" @submit.prevent="emit('submit')">
            <header class="store-modal-header">
                <div>
                    <p class="eyebrow">{{ mode === 'create' ? 'New store' : 'Store details' }}</p>
                    <h2>{{ mode === 'create' ? 'Add Store' : 'Edit Store' }}</h2>
                </div>
                <button
                    type="button"
                    class="btn-close"
                    aria-label="Close"
                    :disabled="saving"
                    @click="emit('update:modelValue', false)"
                ></button>
            </header>

            <div class="store-modal-body">
                <div v-if="error" class="alert alert-danger">
                    {{ error }}
                </div>

                <label class="form-label">
                    Store name
                    <input
                        v-model.trim="form.name"
                        class="form-control"
                        type="text"
                        maxlength="255"
                        required
                    >
                </label>

                <label class="form-label">
                    Phone
                    <input
                        v-model.trim="form.phone"
                        class="form-control"
                        type="tel"
                        maxlength="20"
                    >
                </label>

                <label class="form-label">
                    Address
                    <textarea
                        v-model.trim="form.address"
                        class="form-control"
                        rows="3"
                    ></textarea>
                </label>

                <label class="form-label">
                    Status
                    <select v-model="form.status" class="form-select" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </label>
            </div>

            <footer class="store-modal-footer">
                <button
                    type="button"
                    class="btn btn-light"
                    :disabled="saving"
                    @click="emit('update:modelValue', false)"
                >
                    Cancel
                </button>
                <button type="submit" class="btn btn-primary" :disabled="saving">
                    <span
                        v-if="saving"
                        class="spinner-border spinner-border-sm me-1"
                        aria-hidden="true"
                    ></span>
                    {{ saving ? 'Saving...' : mode === 'create' ? 'Create Store' : 'Save Changes' }}
                </button>
            </footer>
        </form>
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

.store-modal {
    width: min(100%, 560px);
    max-height: 92vh;
    overflow: auto;
    border: 1px solid var(--ui-border);
    border-radius: var(--ui-modal-radius);
    background: var(--ui-surface);
    color: var(--ui-text);
    box-shadow: var(--ui-shadow-lg);
}

.store-modal-header,
.store-modal-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.25rem 1.5rem;
    background: var(--ui-surface-muted);
}

.store-modal-header {
    border-bottom: 1px solid var(--ui-border);
}

.store-modal-header .eyebrow {
    margin: 0 0 0.25rem;
    color: var(--ui-muted);
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
}

.store-modal-header h2 {
    margin: 0;
    font-size: 1.25rem;
}

.store-modal-body {
    display: grid;
    gap: 1rem;
    padding: 1.5rem;
}

.store-modal-body .form-label {
    display: grid;
    gap: 0.4rem;
    margin: 0;
    font-weight: 600;
}

.store-modal-body .form-control,
.store-modal-body .form-select {
    border-color: var(--ui-border);
    background: var(--ui-control);
    color: var(--ui-text);
}

.store-modal-footer {
    justify-content: flex-end;
    border-top: 1px solid var(--ui-border);
}

@media (max-width: 575px) {
    .store-modal-header,
    .store-modal-body,
    .store-modal-footer {
        padding: 1rem;
    }
}
</style>
