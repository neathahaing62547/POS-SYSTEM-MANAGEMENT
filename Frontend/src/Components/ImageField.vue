<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { resolveImageUrl } from '@/utils/imageUrl'
import ImageThumbnail from '@/Components/ImageThumbnail.vue'

const props = defineProps({
    modelValue: { type: Object, default: null },
    currentImage: { type: String, default: '' },
    label: { type: String, default: 'Image' },
    accept: { type: String, default: 'image/jpeg,image/png,image/webp' },
    hint: { type: String, default: 'JPG, PNG or WEBP' }
})

const emit = defineEmits(['update:modelValue'])

const objectUrl = ref('')
const originalImage = ref(props.currentImage)
const currentImageUrl = computed(() => resolveImageUrl(originalImage.value))

watch(() => props.currentImage, (image) => {
    if (image) originalImage.value = image
})

watch(() => props.modelValue, (file) => {
    if (objectUrl.value) URL.revokeObjectURL(objectUrl.value)
    objectUrl.value = file ? URL.createObjectURL(file) : ''
})

onBeforeUnmount(() => {
    if (objectUrl.value) URL.revokeObjectURL(objectUrl.value)
})

const selectImage = (event) => {
    const file = event.target.files?.[0] || null
    emit('update:modelValue', file)
}
</script>

<template>
    <div class="image-field">
        <label class="image-field-label">{{ label }}</label>
        <div class="image-field-content">
            <ImageThumbnail
                :src="objectUrl || currentImageUrl"
                :alt="`${label} preview`"
                class="image-field-preview"
            />
            <div class="image-field-input">
                <input
                    class="form-control"
                    type="file"
                    :accept="accept"
                    :aria-label="label"
                    @change="selectImage"
                >
                <small class="form-text">{{ modelValue?.name || hint }}</small>
            </div>
        </div>
    </div>
</template>
