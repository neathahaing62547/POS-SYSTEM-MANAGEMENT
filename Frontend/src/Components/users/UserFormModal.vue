<script setup>
defineProps({
    modelValue: { type: Boolean, default: false },
    form: { type: Object, required: true },
    mode: { type: String, default: 'create' },
    roles: { type: Array, default: () => [] },
    rolesLoading: { type: Boolean, default: false },
    canAssignRoles: { type: Boolean, default: false },
    saving: { type: Boolean, default: false },
    error: { type: String, default: '' }
})

const emit = defineEmits(['update:modelValue', 'submit'])
</script>

<template>
    <div v-if="modelValue" class="user-modal-backdrop" @click.self="emit('update:modelValue', false)">
        <form class="user-modal" @submit.prevent="emit('submit')">
            <header class="user-modal-header">
                <div>
                    <p class="eyebrow">{{ mode === 'create' ? 'New account' : 'Account details' }}</p>
                    <h2>{{ mode === 'create' ? 'Add User' : 'Edit User' }}</h2>
                </div>
                <button type="button" class="btn-close" aria-label="Close" :disabled="saving" @click="emit('update:modelValue', false)"></button>
            </header>

            <div class="user-modal-body">
                <div v-if="error" class="alert alert-danger">{{ error }}</div>

                <label class="form-label">
                    Username
                    <input v-model.trim="form.username" class="form-control" type="text" maxlength="255" required autocomplete="username">
                </label>

                <label class="form-label">
                    Full name
                    <input v-model.trim="form.full_name" class="form-control" type="text" maxlength="255" autocomplete="name">
                </label>

                <label class="form-label">
                    Email
                    <input v-model.trim="form.email" class="form-control" type="email" maxlength="255" required autocomplete="email">
                </label>

                <label class="form-label">
                    Phone
                    <input v-model.trim="form.phone" class="form-control" type="tel" maxlength="255" autocomplete="tel">
                </label>

                <label class="form-label">
                    {{ mode === 'create' ? 'Password' : 'New password' }}
                    <input
                        v-model="form.password"
                        class="form-control"
                        type="password"
                        minlength="8"
                        :required="mode === 'create'"
                        :placeholder="mode === 'edit' ? 'Leave blank to keep current password' : ''"
                        :autocomplete="mode === 'create' ? 'new-password' : 'new-password'"
                    >
                    <small v-if="mode === 'edit'" class="text-muted">Leave blank to keep the current password.</small>
                </label>

                <label class="form-label">
                    Status
                    <select v-model="form.status" class="form-select" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </label>

                <fieldset v-if="canAssignRoles" class="user-role-fieldset">
                    <legend class="form-label">Roles</legend>
                    <div v-if="rolesLoading" class="text-muted small">Loading roles...</div>
                    <div v-else-if="roles.length" class="user-role-list">
                        <label v-for="role in roles" :key="role.id" class="user-role-option">
                            <input v-model="form.role_ids" type="checkbox" class="form-check-input" :value="role.id">
                            <span>{{ role.name }}</span>
                        </label>
                    </div>
                    <p v-else class="text-muted small mb-0">No roles are available.</p>
                </fieldset>
                <div v-else class="alert alert-info mb-0">
                    Role assignment requires the Roles view permission. Existing roles remain unchanged when editing.
                </div>
            </div>

            <footer class="user-modal-footer">
                <button type="button" class="btn btn-light" :disabled="saving" @click="emit('update:modelValue', false)">Cancel</button>
                <button type="submit" class="btn btn-primary" :disabled="saving || rolesLoading">
                    <span v-if="saving" class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                    {{ saving ? 'Saving...' : mode === 'create' ? 'Create User' : 'Save Changes' }}
                </button>
            </footer>
        </form>
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

.user-modal {
    width: min(100%, 600px);
    max-height: 92vh;
    overflow: auto;
    border: 1px solid var(--ui-border);
    border-radius: var(--ui-modal-radius);
    background: var(--ui-surface);
    color: var(--ui-text);
    box-shadow: var(--ui-shadow-lg);
}

.user-modal-header,
.user-modal-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.25rem 1.5rem;
    background: var(--ui-surface-muted);
}

.user-modal-header {
    border-bottom: 1px solid var(--ui-border);
}

.user-modal-header .eyebrow {
    margin: 0 0 0.25rem;
    color: var(--ui-muted);
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
}

.user-modal-header h2 {
    margin: 0;
    font-size: 1.25rem;
}

.user-modal-body {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
    padding: 1.5rem;
}

.user-modal-body > .alert,
.user-modal-body > .form-label:nth-of-type(3),
.user-role-fieldset {
    grid-column: 1 / -1;
}

.user-modal-body .form-label {
    display: grid;
    gap: 0.4rem;
    margin: 0;
    font-weight: 600;
}

.user-role-fieldset {
    min-width: 0;
    margin: 0;
    padding: 1rem;
    border: 1px solid var(--ui-border);
    border-radius: var(--ui-radius-md);
}

.user-role-fieldset legend {
    float: none;
    width: auto;
    margin: 0 0 0.5rem;
}

.user-role-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 0.5rem 1rem;
}

.user-role-option {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.user-modal-footer {
    justify-content: flex-end;
    border-top: 1px solid var(--ui-border);
}

@media (max-width: 575px) {
    .user-modal-header,
    .user-modal-body,
    .user-modal-footer {
        padding: 1rem;
    }

    .user-modal-body {
        grid-template-columns: 1fr;
    }
}
</style>
