
<script setup>
import ImageField from '@/Components/ImageField.vue'

defineProps({
    modelValue: {
        type: Boolean,
        default: false
    },

    form: {
        type: Object,
        default: () => ({
            name: '',
            description: '',
            status: 'active',
            image: null
        })
    },

    loading: {
        type: Boolean,
        default: false
    },

    error: {
        type: String,
        default: ''
    }
})

const emit = defineEmits([
    'update:modelValue',
    'update:form',
    'submit',
    'image-change'
])

const close = () => {
    emit('update:modelValue', false)
}

const updateField = (field, value) => {
    emit('update:form', {
        ...arguments,
        [field]: value
    })
}

const handleImageChange = (event) => {
    const file = event.target.files?.[0] || null
    emit('image-change', file)
}
</script>

<template>
    <div
        v-if="modelValue"
        class="modal-backdrop"
        @click.self="close"
    >
        <div class="modal-box">

            <!-- Header -->
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">
                        Add Category
                    </h5>

                    <small class="modal-subtitle">
                        Create a new product category
                    </small>
                </div>

                <button
                    type="button"
                    class="close-btn"
                    @click="close"
                >
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <!-- Form -->
            <form @submit.prevent="emit('submit')">

                <div class="modal-body">

                    <!-- Name -->
                    <div class="form-group">
                        <label>
                            Category Name
                            <span class="required">*</span>
                        </label>

                        <input
                            :value="form.name"
                            type="text"
                            class="form-control"
                            placeholder="Enter category name"
                            required
                            @input="
                                emit(
                                    'update:form',
                                    {
                                        ...form,
                                        name: $event.target.value
                                    }
                                )
                            "
                        />
                    </div>

                    <!-- Description -->
                    <div class="form-group">
                        <label>
                            Description
                        </label>

                        <textarea
                            :value="form.description"
                            class="form-control"
                            rows="4"
                            placeholder="Enter category description"
                            @input="
                                emit(
                                    'update:form',
                                    {
                                        ...form,
                                        description: $event.target.value
                                    }
                                )
                            "
                        ></textarea>
                    </div>

                    <!-- Status -->
                    <div class="form-group">
                        <label>
                            Status
                        </label>

                        <select
                            :value="form.status"
                            class="form-select"
                            @change="
                                emit(
                                    'update:form',
                                    {
                                        ...form,
                                        status: $event.target.value
                                    }
                                )
                            "
                        >
                            <option value="active">
                                Active
                            </option>

                            <option value="inactive">
                                Inactive
                            </option>
                        </select>
                    </div>

                    <ImageField
                        class="form-group"
                        :model-value="form.image"
                        label="Category image"
                        @update:model-value="handleImageChange"
                    />

                    <!-- Error -->
                    <div
                        v-if="error"
                        class="form-error"
                    >
                        <i class="bi bi-exclamation-circle me-2"></i>
                        {{ error }}
                    </div>

                </div>

                <!-- Footer -->
                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-light"
                        @click="close"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary"
                        :disabled="loading"
                    >
                        <span v-if="loading">
                            <span
                                class="spinner-border spinner-border-sm me-2"
                            ></span>
                            Saving...
                        </span>

                        <span v-else>
                            <i class="bi bi-check-lg me-1"></i>
                            Save Category
                        </span>
                    </button>

                </div>

            </form>

        </div>
    </div>
</template>

<style scoped>
.modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 1050;

    display: flex;
    align-items: center;
    justify-content: center;

    padding: 20px;

    background: rgba(15, 23, 42, 0.5);
    backdrop-filter: blur(2px);
}

.modal-box {
    width: 100%;
    max-width: 520px;

    background: #fff;
    border-radius: 12px;
    overflow: hidden;

    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);

    animation: modalShow 0.18s ease-out;
}

@keyframes modalShow {
    from {
        opacity: 0;
        transform: translateY(-8px) scale(0.98);
    }

    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* Header */

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;

    padding: 18px 20px;

    border-bottom: 1px solid #e5e7eb;
}

.modal-title {
    margin: 0;

    font-size: 17px;
    font-weight: 600;

    color: #111827;
}

.modal-subtitle {
    display: block;

    margin-top: 3px;

    font-size: 12px;
    color: #6b7280;
}

.close-btn {
    width: 34px;
    height: 34px;

    display: flex;
    align-items: center;
    justify-content: center;

    border: 0;
    border-radius: 7px;

    background: transparent;
    color: #64748b;

    cursor: pointer;

    transition: 0.15s ease;
}

.close-btn:hover {
    background: #f1f5f9;
    color: #111827;
}

/* Body */

.modal-body {
    padding: 20px;
}

.form-group {
    margin-bottom: 17px;
}

.form-group:last-child {
    margin-bottom: 0;
}

.form-group label {
    display: block;

    margin-bottom: 7px;

    font-size: 13px;
    font-weight: 600;

    color: #374151;
}

.required {
    color: #dc2626;
}

/* Inputs */

.form-control,
.form-select {
    width: 100%;

    min-height: 40px;

    border: 1px solid #d1d5db;
    border-radius: 8px;

    font-size: 14px;

    color: #111827;
    background: #fff;

    transition:
        border-color 0.15s ease,
        box-shadow 0.15s ease;
}

.form-control {
    padding: 9px 12px;
}

.form-select {
    padding: 8px 36px 8px 12px;
}

textarea.form-control {
    min-height: 95px;
    resize: vertical;
}

.form-control::placeholder {
    color: #9ca3af;
}

.form-control:focus,
.form-select:focus {
    border-color: #2563eb;

    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);

    outline: none;
}

.form-hint {
    display: block;

    margin-top: 6px;

    font-size: 11px;
    color: #94a3b8;
}

/* Error */

.form-error {
    display: flex;
    align-items: flex-start;

    margin-top: 4px;
    padding: 10px 12px;

    border: 1px solid #fecaca;
    border-radius: 8px;

    background: #fef2f2;
    color: #991b1b;

    font-size: 13px;
    white-space: pre-line;
}

/* Footer */

.modal-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;

    gap: 10px;

    padding: 16px 20px;

    border-top: 1px solid #e5e7eb;

    background: #fafafa;
}

.modal-footer .btn {
    min-height: 40px;

    padding: 8px 16px;

    border-radius: 8px;

    font-size: 13px;
    font-weight: 500;
}

.btn-light {
    border: 1px solid #d1d5db;

    background: #fff;
    color: #374151;
}

.btn-light:hover {
    background: #f8fafc;
}

.btn-primary {
    background: #2563eb;
    border-color: #2563eb;
}

.btn-primary:hover {
    background: #1d4ed8;
    border-color: #1d4ed8;
}

.btn-primary:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

/* Mobile */

@media (max-width: 576px) {
    .modal-backdrop {
        padding: 12px;
        align-items: flex-end;
    }

    .modal-box {
        max-width: 100%;
        border-radius: 12px;
    }

    .modal-body {
        padding: 16px;
    }

    .modal-header {
        padding: 16px;
    }

    .modal-footer {
        padding: 14px 16px;
    }

    .modal-footer .btn {
        flex: 1;
    }
}
</style>
