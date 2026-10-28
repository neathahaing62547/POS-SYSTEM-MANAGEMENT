<script setup>
import { computed, ref, watch } from 'vue'
import { resolveImageUrl } from '@/utils/imageUrl'

defineOptions({ inheritAttrs: false })

const props = defineProps({
    src: { type: String, default: '' },
    alt: { type: String, default: '' },
    fallback: { type: String, default: 'bi-image' }
})

const imageFailed = ref(false)
const resolvedSrc = computed(() => resolveImageUrl(props.src))

watch(resolvedSrc, () => {
    imageFailed.value = false
})
</script>

<template>
    <img
        v-if="resolvedSrc && !imageFailed"
        :src="resolvedSrc"
        :alt="alt"
        class="image-thumbnail"
        v-bind="$attrs"
        @error="imageFailed = true"
    >
    <div v-else class="image-thumbnail image-placeholder" role="img" :aria-label="alt || 'No image'" v-bind="$attrs">
        <i class="bi" :class="fallback" aria-hidden="true"></i>
    </div>
</template>
