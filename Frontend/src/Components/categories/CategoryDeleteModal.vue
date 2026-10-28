```vue
<script setup>
defineProps({
    name: {
        type: String,
        default: ''
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
    'confirm',
    'close'
])

const close = () => {
    emit('close')
}

const confirmDelete = () => {
    emit('confirm')
}
</script>

<template>
    <div
        class="modal-backdrop"
        @click.self="close"
    >
        <div class="modal-box">

            <div class="delete-icon">
                <i class="bi bi-trash"></i>
            </div>

            <h5>
                Delete Category
            </h5>

            <p>
                Are you sure you want to delete
                <strong>{{ name }}</strong>?
            </p>

            <div
                v-if="error"
                class="form-error"
            >
                <i class="bi bi-exclamation-circle me-2"></i>
                {{ error }}
            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    :disabled="loading"
                    @click="close"
                >
                    Cancel
                </button>

                <button
                    type="button"
                    class="btn btn-danger"
                    :disabled="loading"
                    @click="confirmDelete"
                >
                    <span v-if="loading">
                        <span
                            class="spinner-border spinner-border-sm me-2"
                        ></span>
                        Deleting...
                    </span>

                    <span v-else>
                        <i class="bi bi-trash me-1"></i>
                        Delete
                    </span>
                </button>

            </div>

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
    max-width: 420px;
    padding: 25px;
    background: #fff;
    border-radius: 12px;
    text-align: center;
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

.delete-icon {
    width: 55px;
    height: 55px;
    margin: 0 auto 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #fee2e2;
    color: #dc2626;
    font-size: 23px;
}

.modal-box h5 {
    margin-bottom: 10px;
    font-size: 17px;
    font-weight: 600;
    color: #111827;
}

.modal-box p {
    margin-bottom: 0;
    color: #6b7280;
    font-size: 14px;
}

.modal-box strong {
    color: #111827;
}

.form-error {
    display: flex;
    align-items: flex-start;
    margin-top: 18px;
    padding: 10px 12px;
    border: 1px solid #fecaca;
    border-radius: 8px;
    background: #fef2f2;
    color: #991b1b;
    font-size: 13px;
    text-align: left;
    white-space: pre-line;
}

.modal-footer {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 22px;
}

.btn {
    min-height: 40px;
    padding: 8px 18px;
    border: 0;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
}

.btn-secondary {
    border: 1px solid #d1d5db;
    background: #fff;
    color: #374151;
}

.btn-secondary:hover {
    background: #f8fafc;
}

.btn-danger {
    background: #dc2626;
    color: #fff;
}

.btn-danger:hover {
    background: #b91c1c;
}

.btn:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

@media (max-width: 576px) {
    .modal-backdrop {
        padding: 12px;
    }

    .modal-box {
        max-width: 100%;
        padding: 20px;
    }

    .modal-footer {
        flex-direction: column-reverse;
    }

    .modal-footer .btn {
        width: 100%;
    }
}
</style>

