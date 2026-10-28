<script setup>
const show = defineModel()

defineProps({
    brandName: {
        type: String,
        default: ''
    }
})

defineEmits([
    'confirm',
    'close'
])
</script>

<template>
    <div
        v-if="show"
        class="modal-backdrop-custom"
    >
        <div class="brand-modal delete-modal">

            <div class="modal-heading">
                <div>
                    <p class="eyebrow">Danger zone</p>
                    <h2>Delete Brand</h2>
                </div>

                <button
                    class="close-button"
                    @click="$emit('close')"
                >
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="modal-content-body">
                <p class="mb-2">
                    Are you sure you want to delete this brand?
                </p>

                <strong>{{ brandName }}</strong>
            </div>

            <div class="modal-actions">

                <button
                    class="btn btn-light"
                    @click="$emit('close')"
                >
                    Cancel
                </button>

                <button
                    class="btn btn-danger"
                    @click="$emit('confirm')"
                >
                    <i class="bi bi-trash"></i>
                    Delete
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
    overflow: hidden;
}

.delete-modal {
    max-width: 420px;
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
}

.btn-danger {
    background: #dc2626;
    border-color: #dc2626;
    color: white;
}
</style>