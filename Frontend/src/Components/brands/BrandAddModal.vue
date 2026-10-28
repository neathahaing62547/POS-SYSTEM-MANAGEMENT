<script setup>
import ImageField from '@/Components/ImageField.vue'

const show = defineModel()
const form = defineModel('form')

defineProps({
    error: {
        type: String,
        default: ''
    }
})
defineEmits([
    'save',
    'close'
])
</script>
<template>
    <div
        v-if="show"
        class="modal-backdrop-custom"
    >
        <div class="brand-modal">

            <div class="modal-heading">
                <div>
                    <p class="eyebrow">New brand</p>
                    <h2>Add Brand</h2>
                </div>

                <button
                    class="close-button"
                    @click="$emit('close')"
                >
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="modal-content-body">

                <div
                    v-if="error"
                    class="alert alert-danger mb-3"
                >
                    {{ error }}
                </div>

                <div class="form-grid">

                    <label>
                        Brand Name

                        <input
                            v-model="form.name"
                            type="text"
                            class="form-control"
                            placeholder="Enter brand name"
                        >
                    </label>

                    <label>
                        Status

                        <select
                            v-model="form.status"
                            class="form-select"
                        >
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </label>

                    <ImageField class="full-width" v-model="form.logo" label="Brand logo" />

                    <label class="full-width">
                        Description

                        <textarea
                            v-model="form.description"
                            class="form-control"
                            rows="3"
                            placeholder="Enter description"
                        ></textarea>
                    </label>

                </div>

            </div>

            <div class="modal-actions">

                <button
                    class="btn btn-light"
                    @click="$emit('close')"
                >
                    Cancel
                </button>

                <button
                    class="btn btn-primary"
                    @click="$emit('save')"
                >
                    <i class="bi bi-check2"></i>
                    Save Brand
                </button>

            </div>

        </div>
    </div>
</template>

<style scoped>
.modal-backdrop-custom {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.55);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1050;
    padding: 1rem;
}

.brand-modal {
    background: #fff;
    border-radius: 16px;
    width: 100%;
    max-width: 640px;
    max-height: 92vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    overflow: hidden;
}

.modal-heading {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding: 1.5rem 1.75rem 1.25rem;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
}

.eyebrow {
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #64748b;
    margin: 0 0 .25rem;
}

.modal-heading h2 {
    font-size: 1.35rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
}

.close-button {
    width: 36px;
    height: 36px;
    border: none;
    background: transparent;
    border-radius: 50%;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.close-button:hover {
    background: #fee2e2;
    color: #dc2626;
}

.modal-content-body {
    padding: 1.5rem 1.75rem;
    overflow-y: auto;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.25rem;
}

.form-grid label {
    display: flex;
    flex-direction: column;
    gap: .4rem;
    font-size: 13px;
    font-weight: 600;
    color: #334155;
}

.full-width {
    grid-column: 1 / -1;
}

.form-control,
.form-select {
    height: 42px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    padding: .5rem .85rem;
    font-size: 14px;
}

textarea.form-control {
    height: auto;
    min-height: 90px;
    resize: vertical;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: .75rem;
    padding: 1.25rem 1.75rem;
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
}

.modal-actions .btn {
    min-width: 110px;
    height: 42px;
    border-radius: 10px;
    font-weight: 600;
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }

    .full-width {
        grid-column: auto;
    }
}
</style>