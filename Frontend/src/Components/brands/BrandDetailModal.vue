<script setup>
import ImageThumbnail from '@/Components/ImageThumbnail.vue'
import LoadingSkeleton from '@/Components/LoadingSkeleton.vue'

const show = defineModel()

defineProps({
    brand: {
        type: Object,
        default: null
    }
})

defineEmits(['close'])
</script>

<template>
    <div
        v-if="show"
        class="modal-backdrop-custom"
    >
        <div class="brand-modal">

            <div class="modal-heading">
                <div>
                    <p class="eyebrow">Brand details</p>
                    <h2>View Brand</h2>
                </div>

                <button
                    class="close-button"
                    @click="$emit('close')"
                >
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div
                v-if="brand"
                class="modal-content-body"
            >
                <div class="form-grid">
                    <div class="full-width brand-detail-logo">
                        <ImageThumbnail
                            :src="brand.logo"
                            class="detail-image"
                            :alt="`${brand.name} logo`"
                            fallback="bi-tag"
                        />
                    </div>

                    <label>
                        Brand Name

                        <input
                            class="form-control readonly-field"
                            :value="brand.name"
                            readonly
                        >
                    </label>

                    <label>
                        Status

                        <input
                            class="form-control readonly-field"
                            :value="brand.status"
                            readonly
                        >
                    </label>

                    <label class="full-width">
                        Description

                        <textarea
                            class="form-control readonly-field"
                            rows="3"
                            readonly
                        >{{ brand.description || '-' }}</textarea>
                    </label>

                </div>
            </div>

            <div
                v-else
                class="modal-content-body text-center py-5"
            >
                <LoadingSkeleton variant="spinner" label="Loading brand" />
            </div>

            <div class="modal-actions">
                <button
                    class="btn btn-light"
                    @click="$emit('close')"
                >
                    Close
                </button>
            </div>

        </div>
    </div>
</template>

<style scoped>
.modal-backdrop-custom {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, .55);
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
    overflow: hidden;
}

.modal-heading {
    display: flex;
    justify-content: space-between;
    padding: 1.5rem 1.75rem 1.25rem;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
}

.eyebrow {
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    color: #64748b;
    margin: 0 0 .25rem;
}

.modal-heading h2 {
    font-size: 1.35rem;
    font-weight: 700;
    margin: 0;
}

.close-button {
    width: 36px;
    height: 36px;
    border: none;
    background: transparent;
    border-radius: 50%;
}

.modal-content-body {
    padding: 1.5rem 1.75rem;
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
}

.full-width {
    grid-column: 1 / -1;
}

.readonly-field {
    background: #f1f5f9 !important;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    padding: 1.25rem 1.75rem;
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
}

.modal-actions .btn {
    min-width: 110px;
    height: 42px;
    border-radius: 10px;
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