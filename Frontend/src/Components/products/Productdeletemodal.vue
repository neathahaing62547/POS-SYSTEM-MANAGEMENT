<script setup>
defineProps({
    name: {
        type: String,
        default: ''
    }
})
defineEmits(['confirm', 'close'])
</script>

<template>
<div class="modal-backdrop-custom">
    <div class="product-modal">
        <div class="modal-heading">
            <h2>Delete Product</h2>
            <button class="close-button" @click="$emit('close')"><i class="bi bi-x-lg"></i></button>
        </div>

        <div class="modal-content-body">
            <p class="mb-2">Are you sure you want to delete this product?</p>
            <strong>{{ name }}</strong>
        </div>

        <div class="modal-actions">
            <button class="btn btn-light" @click="$emit('close')">Cancel</button>
            <button class="btn btn-danger" @click="$emit('confirm')">
                <i class="bi bi-trash"></i> Delete
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

.product-modal {
    background: #fff;
    border-radius: 16px;
    width: 100%;
    max-width: 420px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.modal-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 1.75rem;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
}

.modal-heading h2 {
    font-size: 1.3rem;
    font-weight: 700;
    margin: 0;
}

.close-button {
    border: none;
    background: transparent;
    font-size: 18px;
    color: #64748b;
    cursor: pointer;
}

.close-button:hover {
    color: #dc2626;
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
</style>
