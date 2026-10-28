<template>
    <div class="modal-backdrop-custom supplier-modal-backdrop">

        <div class="product-modal">

            <div class="modal-heading">

                <div>
                    <p class="eyebrow">
                        Edit supplier
                    </p>

                    <h2>
                        Update Supplier
                    </h2>
                </div>

                <button
                    class="close-button"
                    @click="$emit('update:modelValue', false)"
                >
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div
                class="modal-content-body"
                v-if="form"
            >
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
                        >
                    </label>

                    <label>
                        Contact Person
                        <input
                            v-model="form.contact_person"
                            type="text"
                            class="form-control"
                        >
                    </label>

                    <label>
                        Phone
                        <input
                            v-model="form.phone"
                            type="text"
                            class="form-control"
                        >
                    </label>
                    <label>
                        Email

                        <input
                            v-model="form.email"
                            type="email"
                            class="form-control"
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
                        :current-image="typeof form.image === 'string' ? form.image : ''"
                        label="Supplier image"
                        hint="Choose a new image to replace the current one"
                        @update:model-value="$emit('image-change', $event, form)"
                    />

                    <label class="full-width">
                        Address

                        <textarea
                            v-model="form.address"
                            class="form-control"
                            rows="3"
                        ></textarea>
                    </label>

                </div>

            </div>

            <div class="modal-actions">

                <button
                    class="btn btn-light"
                    @click="$emit('update:modelValue', false)"
                >
                    Cancel
                </button>

                <button
                    class="btn btn-primary"
                    @click="$emit('submit')"
                >
                    <i class="bi bi-check2"></i>
                    Update Supplier
                </button>

            </div>

        </div>

    </div>
</template>

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
        default: null
    },
    errorMessage: {
        type: String,
        default: ''
    }
})

defineEmits([
    'update:modelValue',
    'submit',
    'image-change'
])
</script>