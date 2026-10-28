<script setup>
import {  computed} from 'vue';
 import { defineProps } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    title: {
        type: String,
        default: 'Are you sure?'
    },
    message: {
        type: String,
        default: ''
    },
    confirmText: {
        type: String,
        default: 'Confirm'
    },
    cancelText: {
        type: String,
        default: 'Cancel'
    },
    variant: {
        type: String,
        default: 'primary'
    },
    showCancel: {
        type: Boolean,
        default: true
    },
    loading: {
        type: Boolean,
        default: false
    }
})

defineEmits(['confirm', 'close'])

const icon = computed(() => ({
    success: 'bi bi-box-arrow-in-down',
    danger: 'bi bi-exclamation-triangle',
    warning: 'bi bi-exclamation-circle',
    primary: 'bi bi-info-circle'
} [props.variant] || 'bi bi-info-circle'))
</script>
<template>
<div v-if="show" class="confirm-backdrop" @click.self="$emit('close')">
    <div class="confirm-dialog" role="dialog" aria-modal="true">

        <div class="confirm-icon" :class="variant">
            <i :class="icon"></i>
        </div>

        <h5 class="confirm-title">{{ title }}</h5>
        <p class="confirm-message">{{ message }}</p>

        <div class="confirm-actions">
            <button v-if="showCancel" type="button" class="btn btn-light" :disabled="loading" @click="$emit('close')">
                {{ cancelText }}
            </button>

            <button type="button" class="btn" :class="'btn-' + variant" :disabled="loading" @click="$emit('confirm')">
                <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
                {{ confirmText }}
            </button>
        </div>

    </div>
</div>
</template>

<style scoped>
.confirm-backdrop {
    position: fixed;
    inset: 0;
    z-index: 1300;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    background: rgba(0, 0, 0, 0.55);
}

.confirm-dialog {
    width: 100%;
    max-width: 420px;
    padding: 28px 24px 22px;
    border: 1px solid var(--border-color);
    border-radius: 14px;
    background: var(--surface-bg);
    color: var(--text-color);
    text-align: center;
}

.confirm-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 56px;
    height: 56px;
    margin-bottom: 14px;
    border-radius: 50%;
    font-size: 26px;
}

.confirm-icon.primary {
    background: #dbeafe;
    color: #1d4ed8;
}

.confirm-icon.success {
    background: #d1e7dd;
    color: #0f5132;
}

.confirm-icon.danger {
    background: #f8d7da;
    color: #842029;
}

.confirm-icon.warning {
    background: #fff3cd;
    color: #856404;
}

.confirm-title {
    margin-bottom: 8px;
    font-size: 17px;
    font-weight: 700;
    color: var(--text-color);
}

.confirm-message {
    margin-bottom: 22px;
    font-size: 13px;
    line-height: 1.5;
    color: var(--muted-text);
    white-space: pre-line;
}

.confirm-actions {
    display: flex;
    justify-content: center;
    gap: 10px;
}

.confirm-actions .btn {
    min-width: 110px;
}
</style>
