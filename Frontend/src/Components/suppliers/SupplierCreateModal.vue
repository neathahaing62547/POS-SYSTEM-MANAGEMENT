```vue
<script setup>
import { isFile } from '@/utils/isFile'
import ImageField from '@/Components/ImageField.vue'
import './SupplierModal.css'

defineProps({
    modelValue: {
        type: Boolean,
        default: false
    },

    form: {
        type: Object,
        required: true
    },
    saving: {
        type: Boolean,
        default: false
    },

    errorMessage: {
        type: String,
        default: ''
    }
})

const emit = defineEmits([
    'update:modelValue',
    'submit',
    'image-change'
])
</script>

<template>
    <div class="modal-backdrop-custom supplier-modal-backdrop">
        <div class="product-modal">

            <div class="modal-heading">
                <div>
                    <p class="eyebrow">
                        New supplier
                    </p>

                    <h2>
                        Add Supplier
                    </h2>
                </div>

                <button
                    class="close-button"
                    @click="emit('update:modelValue', false)"
                >
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="modal-content-body">

                <div
                    v-if="errorMessage"
                    class="alert alert-danger mb-3"
                >
                    {{ errorMessage }}
                </div>

                <div class="form-grid">

                    <label>
                        Company Name

                        <input
                            v-model="form.company_name"
                            type="text"
                            class="form-control"
                            placeholder="Enter company name"
                        >
                    </label>

                    <label>
                        Contact Person

                        <input
                            v-model="form.contact_person"
                            type="text"
                            class="form-control"
                            placeholder="Enter contact person"
                        >
                    </label>

                    <label>
                        Phone

                        <input
                            v-model="form.phone"
                            type="text"
                            class="form-control"
                            placeholder="Enter phone"
                        >
                    </label>

                    <label>
                        Email

                        <input
                            v-model="form.email"
                            type="email"
                            class="form-control"
                            placeholder="Enter email"
                        >
                    </label>

                    <label>
                        Status

                        <select
                            v-model="form.status"
                            class="form-select"
                        >
                            <option value="active">
                                Active
                            </option>

                            <option value="inactive">
                                Inactive
                            </option>
                        </select>
                    </label>

                    <ImageField
                        :model-value="isFile(form.image) ? form.image : null"
                        label="Supplier image"
                        @update:model-value="emit('image-change', $event, form)"
                    />

                    <label class="full-width">
                        Address

                        <textarea
                            v-model="form.address"
                            class="form-control"
                            rows="3"
                            placeholder="Enter address"
                        ></textarea>
                    </label>

                </div>
            </div>

            <div class="modal-actions">

                <button
                    class="btn btn-light"
                    @click="emit('update:modelValue', false)"
                >
                    Cancel
                </button>

                <button
                    class="btn btn-primary"
                    :disabled="saving"
                    @click="emit('submit')"
                >
                    <span
                        v-if="saving"
                        class="spinner-border spinner-border-sm"
                        aria-hidden="true"
                    ></span>
                    <i
                        v-else
                        class="bi bi-check2"
                    ></i>
                    {{ saving ? 'Saving...' : 'Save Supplier' }}
                </button>

            </div>
        </div>
    </div>
</template>

```
