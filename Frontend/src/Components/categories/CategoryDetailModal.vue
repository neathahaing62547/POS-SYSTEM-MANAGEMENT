<template>
    <div class="modal-backdrop" @click.self="close">
        <div class="modal-box">
            <div class="modal-header">
                <h5>Category Details</h5>
                <button @click="close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="modal-body">
                <LoadingSkeleton v-if="loading" variant="spinner" label="Loading category" />

                <div v-else-if="category">
                    <div class="detail-image-wrapper">
                        <ImageThumbnail
                            :src="category.image"
                            class="detail-image"
                            :alt="`${category.name} image`"
                            fallback="bi-grid"
                        />
                    </div>

                    <div class="detail-row">
                        <span>ID</span>
                        <strong>{{ category.id }}</strong>
                    </div>
                    <div class="detail-row">
                        <span>Name</span>
                        <strong>{{ category.name }}</strong>
                    </div>
                    <div class="detail-row">
                        <span>Description</span>
                        <strong>{{ category.description || '-' }}</strong>
                    </div>
                    <div class="detail-row">
                        <span>Status</span>
                        <strong class="text-capitalize">{{ category.status }}</strong>
                    </div>
                    <div class="detail-row">
                        <span>Created</span>
                        <strong>{{ formatDate(category.created_at) }}</strong>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" @click="close">Close</button>
            </div>
        </div>
    </div>
</template>

<script setup>
import ImageThumbnail from '@/Components/ImageThumbnail.vue'
import LoadingSkeleton from '@/Components/LoadingSkeleton.vue'

defineProps({
    category: {
        type: Object,
        default: null
    },
    loading: Boolean
})

const emit = defineEmits(['close'])

const close = () => {
    emit('close')
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    })
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
.modal-header, .modal-footer {
    padding: 18px 20px;
    border-bottom: 1px solid #e5e7eb;
}
.modal-header { display: flex; justify-content: space-between; }
.modal-header h5 { margin: 0; }
.modal-header button { border: 0; background: transparent; }
.modal-body { padding: 20px; }
.detail-image-wrapper { display: flex; justify-content: center; margin-bottom: 20px; }
.detail-image, .no-image { width: 120px; height: 120px; border-radius: 10px; }
.detail-image { object-fit: cover; }
.no-image {
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f3f4f6;
    color: #9ca3af;
    font-size: 30px;
}
.detail-row {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    padding: 12px 0;
    border-bottom: 1px solid #f1f5f9;
}
.detail-row span { color: #6b7280; }
.text-capitalize { text-transform: capitalize; }
.loading { text-align: center; padding: 30px; }
.modal-footer {
    display: flex;
    justify-content: flex-end;
    border-top: 1px solid #e5e7eb;
    border-bottom: 0;
}
.btn { padding: 9px 15px; border: 0; border-radius: 7px; }
.btn-secondary { background: #f3f4f6; }
</style>