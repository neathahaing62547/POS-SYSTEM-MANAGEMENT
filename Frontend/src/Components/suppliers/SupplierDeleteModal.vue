<template>
    <div class="modal-backdrop-custom supplier-modal-backdrop">

        <div
            class="product-modal"
            style="max-width: 420px;"
        >

            <div class="modal-heading">

                <div>
                    <p class="eyebrow">
                        Danger zone
                    </p>

                    <h2>
                        Delete Supplier
                    </h2>
                </div>

                <button
                    class="close-button"
                    @click="$emit('update:modelValue', false)"
                >
                    <i class="bi bi-x-lg"></i>
                </button>

            </div>

            <div class="modal-content-body">

                <p class="mb-2 text-muted">
                    Are you sure you want to delete this supplier?
                </p>

                <strong>
                    {{ supplierName }}
                </strong>

            </div>

            <div class="modal-actions">

                <button
                    class="btn btn-light"
                    @click="$emit('update:modelValue', false)"
                >
                    Cancel
                </button>

                <button
                    class="btn btn-danger"
                    @click="$emit('delete')"
                >
                    <i class="bi bi-trash"></i>
                    Delete
                </button>

            </div>

        </div>

    </div>
</template>

<script setup>
import './SupplierModal.css'

defineProps({
    modelValue: {
        type: Boolean,
        default: false
    },
    supplierName: {
        type: String,
        default: ''
    }
})

defineEmits([
    'update:modelValue',
    'delete'
])
</script>