<template>
    <div class="modal-backdrop" @click.self="close">
        <div class="modal-box">
            <div class="modal-header">
                <h5>Edit Category</h5>
                <button @click="close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <form @submit.prevent="handleSubmit">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Name</label>
                        <input v-model="form.name" type="text" class="form-control" />
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea v-model="form.description" class="form-control" rows="4"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select v-model="form.status" class="form-control">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <ImageField
                        class="form-group"
                        :model-value="form.image"
                        :current-image="category?.image || ''"
                        label="Category image"
                        @update:model-value="form.image = $event"
                    />

                    <div v-if="error" class="form-error">{{ error }}</div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="close">Cancel</button>
                    <button type="submit" class="btn btn-primary" :disabled="loading">
                        {{ loading ? 'Updating...' : 'Update Category' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import ImageField from '@/Components/ImageField.vue'
import { ref, watch } from 'vue'

const props = defineProps({
    category: {
        type: Object,
        default: () => ({})
    },
    loading: Boolean,
    error: {
        type: String,
        default: ''
    }
})

const emit = defineEmits(['save', 'close'])

const form = ref({
    id: props.category?.id ?? null,
    name: props.category?.name ?? '',
    description: props.category?.description ?? '',
    status: props.category?.status ?? 'active',
    image: null
})

watch(() => props.category, (val) => {
    if (!val) return
    form.value = {
        id: val.id,
        name: val.name ?? '',
        description: val.description ?? '',
        status: val.status ?? 'active',
        image: null
    }
}, { immediate: true })

const handleSubmit = () => {
    emit('save', { ...form.value })
}

const close = () => {
    emit('close')
}
</script>

<style scoped>
.modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, .45);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1050;
    padding: 20px;
}
.modal-box {
    width: 100%;
    max-width: 520px;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
}
.modal-header {
    display: flex;
    justify-content: space-between;
    padding: 18px 20px;
    border-bottom: 1px solid #e5e7eb;
}
.modal-header h5 { margin: 0; }
.modal-header button { border: 0; background: transparent; }
.modal-body { padding: 20px; }
.form-group { margin-bottom: 16px; }
.form-group label { display: block; margin-bottom: 7px; font-weight: 600; }
.form-control {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 7px;
    outline: none;
}
.form-control:focus { border-color: #6366f1; }
.form-error {
    padding: 10px;
    background: #fee2e2;
    color: #991b1b;
    border-radius: 7px;
}
.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 16px 20px;
    border-top: 1px solid #e5e7eb;
}
.btn { padding: 10px 16px; border: 0; border-radius: 7px; cursor: pointer; }
.btn-primary { background: #2563eb; color: #fff; }
.btn-secondary { background: #f3f4f6; }
</style>