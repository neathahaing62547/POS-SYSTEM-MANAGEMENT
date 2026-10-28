<script setup>
defineProps({
    modelValue: { type: Boolean, default: false },
    form: { type: Object, required: true },
    mode: { type: String, default: 'create' },
    saving: { type: Boolean, default: false },
    error: { type: String, default: '' }
})

const emit = defineEmits(['update:modelValue', 'submit'])
</script>

<template>
    <div v-if="modelValue" class="management-modal-backdrop" @click.self="emit('update:modelValue', false)">
        <form class="management-modal" @submit.prevent="emit('submit')">
            <header class="management-modal-header">
                <div>
                    <p class="eyebrow">{{ mode === 'create' ? 'New permission' : 'Permission details' }}</p>
                    <h2>{{ mode === 'create' ? 'Add Permission' : 'Edit Permission' }}</h2>
                </div>
                <button type="button" class="btn-close" aria-label="Close" :disabled="saving" @click="emit('update:modelValue', false)"></button>
            </header>
            <div class="management-modal-body">
                <div v-if="error" class="alert alert-danger">{{ error }}</div>
                <label class="form-label">
                    Permission name
                    <input v-model.trim="form.name" class="form-control" type="text" maxlength="255" placeholder="module.action" required>
                    <small class="text-muted">Use the existing module.action naming convention.</small>
                </label>
                <label class="form-label">
                    Description
                    <textarea v-model.trim="form.description" class="form-control" rows="3"></textarea>
                </label>
            </div>
            <footer class="management-modal-footer">
                <button type="button" class="btn btn-light" :disabled="saving" @click="emit('update:modelValue', false)">Cancel</button>
                <button type="submit" class="btn btn-primary" :disabled="saving">
                    <span v-if="saving" class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                    {{ saving ? 'Saving...' : mode === 'create' ? 'Create Permission' : 'Save Changes' }}
                </button>
            </footer>
        </form>
    </div>
</template>

<style scoped>
.management-modal-backdrop { position: fixed; inset: 0; z-index: 1050; display: grid; place-items: center; padding: 1rem; background: rgb(15 23 42 / 60%); }
.management-modal { width: min(100%, 560px); max-height: 92vh; overflow: auto; border: 1px solid var(--ui-border); border-radius: var(--ui-modal-radius); background: var(--ui-surface); color: var(--ui-text); box-shadow: var(--ui-shadow-lg); }
.management-modal-header, .management-modal-footer { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.25rem 1.5rem; background: var(--ui-surface-muted); }
.management-modal-header { border-bottom: 1px solid var(--ui-border); }
.management-modal-header .eyebrow { margin: 0 0 .25rem; color: var(--ui-muted); font-size: .75rem; font-weight: 700; text-transform: uppercase; }
.management-modal-header h2 { margin: 0; font-size: 1.25rem; }
.management-modal-body { display: grid; gap: 1rem; padding: 1.5rem; }
.management-modal-body > .alert { margin: 0; }
.management-modal-body .form-label { display: grid; gap: .4rem; margin: 0; font-weight: 600; }
.management-modal-footer { justify-content: flex-end; border-top: 1px solid var(--ui-border); }
@media (max-width: 575px) { .management-modal-header, .management-modal-body, .management-modal-footer { padding: 1rem; } }
</style>
