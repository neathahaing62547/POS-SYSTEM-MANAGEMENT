<script setup>
import { computed } from 'vue'

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    form: { type: Object, required: true },
    mode: { type: String, default: 'create' },
    permissions: { type: Array, default: () => [] },
    canManagePermissions: { type: Boolean, default: false },
    saving: { type: Boolean, default: false },
    error: { type: String, default: '' }
})

const emit = defineEmits(['update:modelValue', 'submit'])

const permissionGroups = computed(() => {
    const groups = new Map()
    for (const permission of props.permissions) {
        const [module = 'Other'] = permission.name.split('.')
        if (!groups.has(module)) groups.set(module, [])
        groups.get(module).push(permission)
    }
    return [...groups.entries()].sort(([a], [b]) => a.localeCompare(b))
})
</script>

<template>
    <div v-if="modelValue" class="management-modal-backdrop" @click.self="emit('update:modelValue', false)">
        <form class="management-modal" @submit.prevent="emit('submit')">
            <header class="management-modal-header">
                <div>
                    <p class="eyebrow">{{ mode === 'create' ? 'New role' : 'Role details' }}</p>
                    <h2>{{ mode === 'create' ? 'Add Role' : 'Edit Role' }}</h2>
                </div>
                <button type="button" class="btn-close" aria-label="Close" :disabled="saving" @click="emit('update:modelValue', false)"></button>
            </header>

            <div class="management-modal-body">
                <div v-if="error" class="alert alert-danger">{{ error }}</div>
                <label class="form-label">
                    Role name
                    <input v-model.trim="form.name" class="form-control" type="text" maxlength="255" required>
                </label>
                <label class="form-label">
                    Description
                    <textarea v-model.trim="form.description" class="form-control" rows="2"></textarea>
                </label>

                <fieldset v-if="canManagePermissions" class="permission-selector">
                    <legend class="form-label">Permissions</legend>
                    <p class="text-muted small">Select the capabilities users assigned to this role should have.</p>
                    <section v-for="[group, permissions] in permissionGroups" :key="group" class="permission-group">
                        <h3>{{ group.replaceAll('_', ' ') }}</h3>
                        <label v-for="permission in permissions" :key="permission.id" class="permission-option">
                            <input v-model="form.permission_ids" type="checkbox" class="form-check-input" :value="permission.id">
                            <span>
                                <strong>{{ permission.name }}</strong>
                                <small v-if="permission.description">{{ permission.description }}</small>
                            </span>
                        </label>
                    </section>
                    <p v-if="!permissions.length" class="text-muted small mb-0">No permissions are available.</p>
                </fieldset>
                <div v-else class="alert alert-info mb-0">
                    Permission assignment requires the Permissions view permission. Existing assignments will remain unchanged.
                </div>
            </div>

            <footer class="management-modal-footer">
                <button type="button" class="btn btn-light" :disabled="saving" @click="emit('update:modelValue', false)">Cancel</button>
                <button type="submit" class="btn btn-primary" :disabled="saving">
                    <span v-if="saving" class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                    {{ saving ? 'Saving...' : mode === 'create' ? 'Create Role' : 'Save Changes' }}
                </button>
            </footer>
        </form>
    </div>
</template>

<style scoped>
.management-modal-backdrop { position: fixed; inset: 0; z-index: 1050; display: grid; place-items: center; padding: 1rem; background: rgb(15 23 42 / 60%); }
.management-modal { width: min(100%, 720px); max-height: 92vh; overflow: auto; border: 1px solid var(--ui-border); border-radius: var(--ui-modal-radius); background: var(--ui-surface); color: var(--ui-text); box-shadow: var(--ui-shadow-lg); }
.management-modal-header, .management-modal-footer { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.25rem 1.5rem; background: var(--ui-surface-muted); }
.management-modal-header { border-bottom: 1px solid var(--ui-border); }
.management-modal-header .eyebrow { margin: 0 0 .25rem; color: var(--ui-muted); font-size: .75rem; font-weight: 700; text-transform: uppercase; }
.management-modal-header h2 { margin: 0; font-size: 1.25rem; }
.management-modal-body { display: grid; gap: 1rem; padding: 1.5rem; }
.management-modal-body > .alert { margin: 0; }
.management-modal-body .form-label { display: grid; gap: .4rem; margin: 0; font-weight: 600; }
.permission-selector { display: grid; gap: 1rem; min-width: 0; margin: 0; padding: 1rem; border: 1px solid var(--ui-border); border-radius: var(--ui-radius-md); }
.permission-selector legend { float: none; width: auto; margin: 0; }
.permission-selector > p { margin: -.75rem 0 0; }
.permission-group { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: .5rem 1rem; padding-top: .75rem; border-top: 1px solid var(--ui-border); }
.permission-group h3 { grid-column: 1 / -1; margin: 0; font-size: .9rem; text-transform: capitalize; }
.permission-option { display: flex; align-items: flex-start; gap: .5rem; min-width: 0; font-size: .875rem; }
.permission-option .form-check-input { flex: none; margin-top: .2rem; }
.permission-option span { display: grid; overflow-wrap: anywhere; }
.permission-option small { color: var(--ui-muted); font-weight: 400; }
.management-modal-footer { justify-content: flex-end; border-top: 1px solid var(--ui-border); }
@media (max-width: 575px) { .management-modal-header, .management-modal-body, .management-modal-footer { padding: 1rem; } }
</style>
