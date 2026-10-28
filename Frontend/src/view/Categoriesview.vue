<script setup>
import { onMounted, ref } from 'vue'
import ExcelJS from 'exceljs'
import { useAuthStore } from '@/stores/auth'
import { PERMISSIONS } from '@/constants/permissions'
import { useCategoryStore } from '@/stores/Category'
import LoadingSkeleton from '@/Components/LoadingSkeleton.vue'
import CategorySummary from '@/Components/categories/CategorySummary.vue'
import CategoryToolbar from '@/Components/categories/CategoryToolbar.vue'
import CategoryTable from '@/Components/categories/CategoryTable.vue'
import CategoryAddModal from '@/Components/categories/CategoryAddModal.vue'
import CategoryEditModal from '@/Components/categories/CategoryEditModal.vue'
import CategoryDetailModal from '@/Components/categories/CategoryDetailModal.vue'
import CategoryDeleteModal from '@/Components/categories/CategoryDeleteModal.vue'

const authStore = useAuthStore()
const categoryStore = useCategoryStore()
const canCreateCategory = () => authStore.can(PERMISSIONS.CATEGORIES_CREATE)
const canUpdateCategory = () => authStore.can(PERMISSIONS.CATEGORIES_UPDATE)
const canDeleteCategory = () => authStore.can(PERMISSIONS.CATEGORIES_DELETE)

const search = ref('')
const status = ref('')

const modal = ref(null)
const selected = ref(null)

const categoryForm = ref({
    name: '',
    description: '',
    status: 'active',
    image: null
})

const successMessage = ref('')
const errorMessage = ref('')

const load = (page = 1) => {
    return categoryStore.getCategories(
        search.value,
        page,
        status.value
    )
}

const flash = (msg) => {
    successMessage.value = msg

    setTimeout(() => {
        successMessage.value = ''
    }, 3000)
}

const closeModal = () => {
    modal.value = null
    selected.value = null
}

const openAdd = () => {
    if (!canCreateCategory()) return

    categoryForm.value = {
        name: '',
        description: '',
        status: 'active',
        image: null
    }

    errorMessage.value = ''
    modal.value = 'add'
}

const handleImageChange = (file) => {
    categoryForm.value.image = file
}

onMounted(() => {
    load()
})

const applyFilters = (filter) => {
    status.value = filter.status ?? ''
    load()
}

const changePage = (page) => {
    if (
        !categoryStore.pagination ||
        page < 1 ||
        page > categoryStore.pagination.last_page
    ) {
        return
    }

    load(page)
}

const createCategory = async () => {
    if (!canCreateCategory()) return

    try {
        await categoryStore.createCategory(categoryForm.value)

        closeModal()

        flash('Category created successfully')

        load()
    } catch (e) {
        errorMessage.value =
            e.response?.data?.message ||
            categoryStore.error ||
            'Failed to create category'
    }
}

const openView = async (category) => {
    try {
        const id = typeof category === 'object'
            ? category.id
            : category

        await categoryStore.getDetailCategory(id)

        modal.value = 'view'
    } catch (e) {
        errorMessage.value =
            e.response?.data?.message ||
            'Failed to load category'
    }
}

const openEdit = (category) => {
    if (!canUpdateCategory()) return

    selected.value = category
    modal.value = 'edit'
}

const updateCategory = async (form) => {
    if (!canUpdateCategory()) return

    try {
        await categoryStore.updateCategory(form.id, form)

        closeModal()

        flash('Category updated successfully')

        load()
    } catch (e) {
        errorMessage.value =
            e.response?.data?.message ||
            categoryStore.error ||
            'Failed to update category'
    }
}

const openDelete = (category) => {
    if (!canDeleteCategory()) return

    selected.value = category
    modal.value = 'delete'
}

const deleteCategory = async () => {
    if (!canDeleteCategory()) return

    if (!selected.value) return

    const id = selected.value.id

    closeModal()

    try {
        await categoryStore.deleteCategory(id)

        flash('Category deleted successfully')

        load()
    } catch (e) {
        errorMessage.value =
            e.response?.data?.message ||
            categoryStore.error ||
            'Failed to delete category'
    }
}

const exportExcel = async () => {
    const wb = new ExcelJS.Workbook()
    const ws = wb.addWorksheet('Categories')

    ws.addRow([
        'ID',
        'Name',
        'Description',
        'Status',
        'Created Date'
    ])

    categoryStore.categories.forEach(category => {
        ws.addRow([
            category.id,
            category.name,
            category.description || '',
            category.status,
            category.created_at
        ])
    })

    ws.columns.forEach(column => {
        column.width = 20
    })

    const buffer = await wb.xlsx.writeBuffer()

    const url = URL.createObjectURL(
        new Blob([buffer])
    )

    const a = document.createElement('a')

    a.href = url
    a.download = `categories_${new Date().toISOString().split('T')[0]}.xlsx`

    a.click()

    URL.revokeObjectURL(url)
}
</script>

<template>
    <div class="container-fluid py-4 px-3 px-md-4">

        <CategorySummary :summary="categoryStore.summary" />

        <div class="card border-0 shadow-sm">

            <CategoryToolbar v-model:search="search" :is-admin="authStore.isAdmin()" :can-create="canCreateCategory()" @filter="applyFilters"
                @search="load()" @export="exportExcel" @add="openAdd" />

            <div class="card-body">

                <div v-if="errorMessage" class="alert alert-danger">
                    {{ errorMessage }}
                </div>

                <div v-if="successMessage" class="alert alert-success">
                    {{ successMessage }}
                </div>

                <LoadingSkeleton v-if="categoryStore.loading" variant="table" :columns="7" label="Loading categories" />

                <div v-else-if="categoryStore.error" class="alert alert-danger">
                    {{ categoryStore.error }}
                </div>

                <CategoryTable v-else :categories="categoryStore.categories" :pagination="categoryStore.pagination"
                    :loading="categoryStore.loading" :error="categoryStore.error" :can-update="canUpdateCategory()" :can-delete="canDeleteCategory()"
                    @view="openView" @edit="openEdit" @delete="openDelete" @page="changePage" />

            </div>
        </div>
    </div>

    <CategoryAddModal v-if="modal === 'add'" :model-value="modal === 'add'" :form="categoryForm"
        :loading="categoryStore.loading" :error="categoryStore.error || errorMessage" @submit="createCategory"
        @update:form="categoryForm = $event" @image-change="handleImageChange" @update:model-value="closeModal" />
    <CategoryEditModal v-if="modal === 'edit'" :category="selected" @save="updateCategory" @close="closeModal" />
    <CategoryDetailModal v-if="modal === 'view'" :category="categoryStore.category" @close="closeModal" />
    <CategoryDeleteModal v-if="modal === 'delete'" :name="selected?.name" :loading="categoryStore.loading"
        :error="categoryStore.error" @confirm="deleteCategory" @close="closeModal" />
</template>
