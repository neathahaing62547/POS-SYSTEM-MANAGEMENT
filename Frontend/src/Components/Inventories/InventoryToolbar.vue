<script setup>
const search = defineModel('search', { type: String, default: '' })
const emit = defineEmits(['search'])

const resetAll = () => {
    search.value = ''
    emit('search')
}
</script>

<template>
    <div>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 p-3 pb-0">
            <div class="input-group search-input">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input v-model="search" class="form-control" placeholder="Search quantity or alert quantity..."
                    @input="emit('search')">
            </div>
            <button class="btn btn-outline-secondary" @click="resetAll" title="Reset filters">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
        </div>
    </div>
</template>

<style scoped>
.search-input {
    max-width: 350px;
    width: 100%;
}

.search-input .form-control,
.search-input .input-group-text {
    height: 40px;
    border-radius: 8px;
}

</style>