<script setup>
import { onMounted, computed, ref } from 'vue'
import { useCategoryStore } from '@/stores/Category '
import { useAuthStore } from '@/stores/auth'
import ExcelJS from 'exceljs'

const categoryStore = useCategoryStore()
const authStore = useAuthStore()

const search = ref('')
const selectedStatus = ref('')
const appliedStatus = ref('')

const successMessage = ref('')
const createError = ref('')

const categories = computed(() => categoryStore.categories)

const showModalAddCategory = ref(false)
const showModalEditCategory = ref(false)
const showModalCategoryDetail = ref(false)
const showDeleteModal = ref(false)

const deleteCategoryId = ref(null)
const deleteCategoryName = ref('')

const form = ref({
    name: '',
    description: '',
    image: null,
    status: 'active'
})

const editForm = ref({
    id: null,
    name: '',
    description: '',
    image: null,
    status: 'active'
})

const imageUrl = (image) => `http://127.0.0.1:8000/storage/${image}`

const loadCategories = (page = 1) => {
    categoryStore.getCategories(search.value, page, appliedStatus.value)
}

onMounted(() => {
    loadCategories()
})

const searchCategory = () => {
    loadCategories(1)
}

const filterCategory = () => {
    appliedStatus.value = selectedStatus.value
    loadCategories(1)
}

const reloadCategory = () => {
    window.location.reload()
}

const changePage = (page) => {
    if (page < 1 || page > categoryStore.pagination.last_page) {
        return
    }
    loadCategories(page)
}

const showSuccess = (message) => {
    successMessage.value = message
    setTimeout(() => {
        successMessage.value = ''
    }, 3000)
}

const resetForm = () => {
    form.value = {
        name: '',
        description: '',
        image: null,
        status: 'active'
    }
}

const openAddCategory = () => {
    resetForm()
    createError.value = ''
    showModalAddCategory.value = true
}

const closeAddCategory = () => {
    showModalAddCategory.value = false
}

const createCategory = async () => {
    try {
        await categoryStore.createCategory(form.value)
        showModalAddCategory.value = false
        showSuccess('Category created successfully')
        await loadCategories(1)
    } catch (error) {
        createError.value = error.response?.data?.message || 'Failed to create category'
    }
}

const viewCategory = async (id) => {
    showModalCategoryDetail.value = true
    await categoryStore.getDetailCategory(id)
}

const closeCategoryDetail = () => {
    showModalCategoryDetail.value = false
}

const showEditCategory = (category) => {
    editForm.value = {
        id: category.id,
        name: category.name || '',
        description: category.description || '',
        image: null,
        status: category.status || 'active'
    }
    showModalEditCategory.value = true
}

const closeEditCategory = () => {
    showModalEditCategory.value = false
}

const editCategory = async () => {
    try {
        await categoryStore.updateCategory(editForm.value.id, editForm.value)
        showModalEditCategory.value = false
        showSuccess('Category updated successfully')
        await loadCategories(categoryStore.pagination?.current_page || 1)
    } catch (error) {
        createError.value = error.response?.data?.message || 'Failed to update category'
    }
}

const openDeleteModal = (category) => {
    deleteCategoryId.value = category.id
    deleteCategoryName.value = category.name
    showDeleteModal.value = true
}

const closeDeleteModal = () => {
    showDeleteModal.value = false
    deleteCategoryId.value = null
    deleteCategoryName.value = ''
}

const deleteCategory = async () => {
    const id = deleteCategoryId.value
    closeDeleteModal()
    try {
        await categoryStore.deleteCategory(id)
        showSuccess('Category deleted successfully')
        await loadCategories(categoryStore.pagination?.current_page || 1)
    } catch (error) {
        createError.value = error.response?.data?.message || 'Failed to delete category'
    }
}

const exportExcel = async () => {
    const workbook = new ExcelJS.Workbook()
    const worksheet = workbook.addWorksheet('Categories')

    worksheet.addRow(['Category Summary'])
    worksheet.addRow(['Total Categories', categoryStore.summary.total])
    worksheet.addRow(['Active Categories', categoryStore.summary.active])
    worksheet.addRow(['Inactive Categories', categoryStore.summary.inactive])
    worksheet.addRow([])

    worksheet.addRow(['ID', 'Name', 'Description', 'Status', 'Created Date'])

    categories.value.forEach(category => {
        worksheet.addRow([
            category.id,
            category.name,
            category.description,
            category.status,
            category.created_at
        ])
    })

    worksheet.eachRow(row => {
        row.eachCell(cell => {
            cell.alignment = {
                horizontal: 'center',
                vertical: 'middle'
            }
        })
    })

    worksheet.columns = [
        { width: 10 },
        { width: 25 },
        { width: 35 },
        { width: 12 },
        { width: 22 }
    ]

    const date = new Date().toISOString().split('T')[0]
    const buffer = await workbook.xlsx.writeBuffer()
    const blob = new Blob([buffer], {
        type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    })

    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `categories_${date}.xlsx`
    link.click()

    window.URL.revokeObjectURL(url)
}
</script>

<template>
    <div class="container-fluid categories-page py-4 px-3 px-md-4">
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4">
                <div class="card summary-card total-card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-muted">Total Categories</small>
                                <h4 class="fw-bold mb-0 mt-2">
                                    {{ categoryStore.summary.total }}
                                </h4>
                            </div>
                            <div class="icon-box bg-primary-subtle text-primary">
                                <i class="bi bi-grid"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-4">
                <div class="card summary-card active-card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-muted">Active</small>
                                <h4 class="fw-bold text-success mb-0 mt-2">
                                    {{ categoryStore.summary.active }}
                                </h4>
                            </div>
                            <div class="icon-box bg-success-subtle text-success">
                                <i class="bi bi-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-4">
                <div class="card summary-card inactive-card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-muted">Inactive</small>
                                <h4 class="fw-bold text-secondary mb-0 mt-2">
                                    {{ categoryStore.summary.inactive }}
                                </h4>
                            </div>
                            <div class="icon-box bg-secondary-subtle text-secondary">
                                <i class="bi bi-x-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div
                class="toolbar d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 p-3 border-bottom">
                <div class="d-flex flex-wrap gap-2 align-items-center filter-bar">

                    <select v-model="selectedStatus" class="form-select filter-select">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>

                    <button @click="filterCategory"
                        class="btn btn-outline-secondary filter-btn text-nowrap flex-shrink-0">
                        <i class="bi bi-funnel me-1"></i>
                        Filter
                    </button>

                    <button v-if="authStore.isAdmin()" type="button" class="btn btn-success" @click="exportExcel">
                        <i class="bi bi-download me-1"></i>
                        Export Excel
                    </button>
                </div>

                <button v-if="authStore.isAdmin()" @click="openAddCategory"
                    class="btn btn-primary add-btn flex-shrink-0">
                    <i class="bi bi-plus-lg me-2"></i>
                    Add Category
                </button>
            </div>

            <div class="card-body">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                    <div class="input-group search-input">
                        <span class="input-group-text bg-white">
                            <i class="bi bi-search"></i>
                        </span>
                        <input v-model="search" @input="searchCategory" type="text" class="form-control"
                            placeholder="Search categories...">
                    </div>

                    <div class="d-flex gap-2">
                        <button @click="searchCategory" class="btn btn-outline-secondary flex-fill flex-md-grow-0">
                            <i class="bi bi-search"></i>
                            search
                        </button>

                        <button @click="reloadCategory" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>
                </div>

                <div v-if="categoryStore.loading" class="text-center py-5">
                    <div class="spinner-border text-primary"></div>
                    <p>waiting for response</p>
                </div>

                <div v-else-if="categoryStore.error" class="alert alert-danger">
                    {{ categoryStore.error }}
                </div>

                <div v-else-if="successMessage" class="alert alert-success">
                    {{ successMessage }}
                </div>

                <div v-else class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Created Date</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="category in categories" :key="category.id">
                                <td>
                                    <span class="text-muted">
                                        #{{ category.id }}
                                    </span>
                                </td>
                                <td>
                                    <img v-if="category.image" :src="imageUrl(category.image)" class="product-image"
                                        alt="Category">
                                    <div v-else class="no-image">
                                        No Image
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-truncate-cell" :title="category.name">
                                        {{ category.name }}
                                    </div>
                                </td>
                                <td>
                                    <div class="text-truncate-cell" :title="category.description">
                                        {{ category.description || '-' }}
                                    </div>
                                </td>
                                <td>
                                    <span class="badge" :class="category.status === 'active'
                                        ? 'text-bg-success'
                                        : 'text-bg-secondary'">
                                        {{ category.status }}
                                    </span>
                                </td>
                                <td>
                                    {{ new Date(category.created_at).toLocaleDateString('en-GB') }}
                                </td>
                                <td class="text-center">
                                    <button v-if="authStore.isAdmin()" class="btn btn-sm btn-outline-primary me-1"
                                        @click="showEditCategory(category)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-info me-1" @click="viewCategory(category.id)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button v-if="authStore.isAdmin()" class="btn btn-sm btn-outline-danger"
                                        @click="openDeleteModal(category)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="categories.length === 0">
                                <td colspan="7" class="text-center py-5">
                                    <div class="empty-products">
                                        <i class="bi bi-grid"></i>
                                        <h5>No Categories Found</h5>
                                        <p>There are no categories matching your search.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="categoryStore.pagination"
                    class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 mt-3">
                    <small class="text-muted">
                        Showing
                        {{ categoryStore.pagination.from || 0 }}
                        to
                        {{ categoryStore.pagination.to || 0 }}
                        of
                        {{ categoryStore.pagination.total || 0 }}
                        categories
                    </small>

                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-sm btn-outline-secondary"
                            :disabled="categoryStore.pagination.current_page === 1"
                            @click="changePage(categoryStore.pagination.current_page - 1)">
                            Previous
                        </button>

                        <span class="text-muted small">
                            Page {{ categoryStore.pagination.current_page }}
                            of {{ categoryStore.pagination.last_page }}
                        </span>

                        <button class="btn btn-sm btn-outline-secondary" :disabled="categoryStore.pagination.current_page ===
                            categoryStore.pagination.last_page
                            " @click="changePage(categoryStore.pagination.current_page + 1)">
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Category -->
    <div v-if="showModalAddCategory" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable product-modal">
            <div class="modal-content border-0 shadow">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        Add Category
                    </h5>
                    <button type="button" class="modal-close-btn" @click="closeAddCategory" aria-label="Close">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="row g-3 g-md-4">
                        <div v-if="createError" class="alert alert-danger mb-3">
                            {{ createError }}
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Category Name</label>
                            <input v-model="form.name" type="text" class="form-control"
                                placeholder="Enter category name">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Status</label>
                            <select v-model="form.status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Image</label>
                            <input type="file" class="form-control" accept=".jpg,.jpeg,.png,.webp"
                                @change="form.image = $event.target.files[0]">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea v-model="form.description" class="form-control" rows="3"
                                placeholder="Enter description"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer flex-column flex-sm-row modal-footer-actions">
                    <button type="button" class="btn btn-light footer-btn" @click="closeAddCategory">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-primary footer-btn" @click="createCategory">
                        <i class="bi bi-save me-1"></i>
                        Save Category
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Detail -->
    <div v-if="showModalCategoryDetail" class="modal fade show d-block" tabindex="-1"
        style="background: rgba(0, 0, 0, 0.5);">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable product-modal">
            <div class="modal-content border-0 shadow">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        Category Details
                    </h5>
                    <button type="button" class="modal-close-btn" @click="closeCategoryDetail" aria-label="Close">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div class="modal-body" v-if="categoryStore.category">
                    <div class="row g-3 g-md-4">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Category Name</label>
                            <input class="form-control readonly-field" :value="categoryStore.category.name" readonly>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Status</label>
                            <input class="form-control readonly-field" :value="categoryStore.category.status" readonly>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control readonly-field" rows="3"
                                readonly>{{ categoryStore.category.description || '-' }}</textarea>
                        </div>
                    </div>
                </div>

                <div v-else class="modal-body text-center py-5">
                    <div class="spinner-border text-primary"></div>
                    <p class="text-muted mt-2 mb-0">Loading category...</p>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="closeCategoryDetail">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Category -->
    <div v-if="showModalEditCategory" class="modal fade show d-block" tabindex="-1"
        style="background: rgba(0, 0, 0, 0.5);">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable product-modal">
            <div class="modal-content border-0 shadow">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        Edit Category
                    </h5>
                    <button type="button" class="modal-close-btn" @click="closeEditCategory" aria-label="Close">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="row g-3 g-md-4">
                        <div v-if="createError" class="alert alert-danger mb-3">
                            {{ createError }}
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Category Name</label>
                            <input v-model="editForm.name" class="form-control">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Status</label>
                            <select v-model="editForm.status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Image</label>
                            <input type="file" class="form-control" accept=".jpg,.jpeg,.png,.webp"
                                @change="editForm.image = $event.target.files[0]">
                            <small class="text-muted">Leave empty to keep the current image.</small>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea v-model="editForm.description" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="closeEditCategory">
                        Close
                    </button>
                    <button class="btn btn-sm btn-outline-primary me-1" @click="editCategory">
                        <i class="bi bi-pencil"></i>
                        Update Category
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirm -->
    <div v-if="showDeleteModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0, 0, 0, 0.5);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">
                        Delete Category
                    </h5>
                    <button type="button" class="btn-close" @click="closeDeleteModal"></button>
                </div>

                <div class="modal-body">
                    <p class="mb-2">
                        Are you sure you want to delete this category?
                    </p>
                    <strong>
                        {{ deleteCategoryName }}
                    </strong>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="closeDeleteModal">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-danger" @click="deleteCategory()">
                        <i class="bi bi-trash me-1"></i>
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.icon-box {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}

* {
    box-sizing: border-box;
}

.card,
.card-body,
.summary-card,
.toolbar {
    min-width: 0;
}

.categories-page .card,
.categories-page .table {
    background: var(--surface-bg, #ffffff);
    color: var(--text-color, #172033);
    border-color: var(--border-color, #dbe3ee);
}

.categories-page .toolbar,
.categories-page .table-light,
.categories-page .table-light th {
    background: var(--surface-muted, #eef2f7) !important;
    color: var(--text-color, #172033);
    border-color: var(--border-color, #dbe3ee);
}

.categories-page .table> :not(caption)>*>* {
    background-color: transparent;
    color: var(--text-color, #172033);
    border-bottom-color: var(--border-color, #dbe3ee);
}

.categories-page .table-hover>tbody>tr:hover>* {
    background-color: var(--surface-muted, #eef2f7);
    color: var(--text-color, #172033);
}

.summary-card h4,
.summary-card small {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.text-truncate-cell {
    max-width: 220px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.table td,
.table th {
    max-width: 240px;
    overflow: hidden;
    text-overflow: ellipsis;
}

.badge {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
    display: inline-block;
}

.product-modal .form-control,
.product-modal .form-select,
.product-modal .readonly-field {
    overflow-wrap: break-word;
    text-overflow: ellipsis;
}

.product-modal textarea.form-control,
.product-modal textarea.readonly-field {
    word-break: break-word;
    overflow-wrap: break-word;
    resize: vertical;
}

.table th {
    font-size: 14px;
    font-weight: 700;
    color: var(--text-color, #172033);
    white-space: nowrap;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.table td {
    font-size: 14px;
    white-space: nowrap;
}

.summary-card {
    border-radius: 12px;
    border: 1px solid var(--border-color, #dbe3ee) !important;
    border-left: 4px solid transparent !important;
}

.summary-card small {
    font-size: 12px;
    font-weight: 600;
}

.summary-card h4 {
    font-size: 20px;
}

.total-card {
    background: #eff6ff;
    border-left-color: #2563eb !important;
    color: #1e40af;
}

.total-card small {
    color: #3b82f6;
}

.total-card .icon-box {
    background: #dbeafe;
    color: #2563eb;
}

.active-card {
    background: #f0fdf4;
    border-left-color: #16a34a !important;
    color: #166534;
}

.active-card small {
    color: #22c55e;
}

.active-card .icon-box {
    background: #dcfce7;
    color: #16a34a;
}

.inactive-card {
    background: #f8fafc;
    border-left-color: #64748b !important;
    color: #334155;
}

.inactive-card small {
    color: #64748b;
}

.inactive-card .icon-box {
    background: #e2e8f0;
    color: #64748b;
}

.badge {
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
}

.text-bg-success {
    background-color: #dcfce7 !important;
    color: #15803d !important;
}

.text-bg-secondary {
    background-color: #e5e7eb !important;
    color: #4b5563 !important;
}

.empty-products {
    padding: 50px 20px;
}

.empty-products i {
    font-size: 60px;
    color: #adb5bd;
    display: block;
    margin-bottom: 20px;
}

.empty-products h5 {
    font-size: 22px;
    font-weight: 700;
    color: #495057;
    margin-bottom: 8px;
}

.empty-products p {
    font-size: 15px;
    color: #6c757d;
    margin: 0;
}

.form-select option {
    color: rgb(15, 15, 15) !important;
}

.product-image {
    width: 45px;
    height: 45px;
    object-fit: cover;
    border-radius: 6px;
}

.no-image {
    width: 45px;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f1f3f5;
    color: #888;
    border-radius: 6px;
    font-size: 9px;
    text-align: center;
}

.toolbar {
    background: var(--surface-muted, #eef2f7);
    border-top-left-radius: 12px;
    border-top-right-radius: 12px;
}

.filter-bar {
    flex: 1 1 auto;
    min-width: 0;
}

.filter-select,
.filter-btn,
.add-btn {
    height: 40px;
    border-radius: 8px;
    font-size: 14px;
}

.filter-select {
    width: 150px;
    max-width: 150px;
    flex: 0 1 150px;
    border-color: var(--border-color, #dbe3ee);
    color: var(--text-color, #172033);
}

.filter-select:focus {
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
}

.filter-btn {
    padding: 0 16px;
    display: inline-flex;
    align-items: center;
    background: var(--surface-bg, #ffffff);
}

.add-btn {
    padding: 0 20px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08);
    width: 100%;
}

@media (min-width: 992px) {
    .add-btn {
        width: auto;
    }
}

.modal-footer-actions .footer-btn {
    width: 100%;
}

@media (min-width: 576px) {
    .modal-footer-actions .footer-btn {
        width: auto;
    }
}

.product-modal .modal-content {
    border-radius: 14px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    max-height: 90vh;
    max-height: 90dvh;
}

.product-modal .modal-header {
    padding: 1.1rem 1.5rem;
    border-bottom: 1px solid var(--border-color, #dbe3ee);
    background: var(--surface-muted, #eef2f7);
}

.product-modal .modal-title {
    font-size: 17px;
}

.modal-close-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border: none;
    background: transparent;
    border-radius: 50%;
    color: #6c757d;
    font-size: 16px;
    flex-shrink: 0;
    transition: background-color 0.15s ease, color 0.15s ease;
}

.modal-close-btn:hover,
.modal-close-btn:focus {
    background-color: #fdecec;
    color: #dc3545;
}

.product-modal .modal-body {
    padding: 1.5rem;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
}

.product-modal .form-label {
    font-size: 13px;
    font-weight: 600;
    color: var(--text-color, #172033);
    margin-bottom: 0.4rem;
}

.product-modal .form-control,
.product-modal .form-select {
    border-radius: 8px;
    border-color: var(--border-color, #dbe3ee);
    font-size: 14px;
}

.product-modal .form-control:focus,
.product-modal .form-select:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
}

.product-modal .readonly-field {
    background-color: var(--surface-muted, #eef2f7);
    color: var(--text-color, #172033);
}

.product-modal .modal-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid var(--border-color, #dbe3ee);
    background: var(--surface-muted, #eef2f7);
    gap: 0.5rem;
}

@media (max-width: 575.98px) {
    .product-modal {
        margin: 0.6rem;
        max-width: calc(100% - 1.2rem);
    }

    .product-modal .modal-content {
        border-radius: 12px;
        max-height: 92vh;
        max-height: 92dvh;
    }

    .product-modal .modal-header,
    .product-modal .modal-body,
    .product-modal .modal-footer {
        padding: 1rem;
    }

    .product-modal .modal-header,
    .product-modal .modal-footer {
        flex-shrink: 0;
    }
}

.search-input {
    max-width: 350px;
    width: 100%;
}

.search-input .form-control,
.search-input .input-group-text {
    height: 40px;
    border-radius: 8px;
}

.search-input .input-group-text {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
}

.search-input .form-control {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
}

@media (max-width: 991.98px) {
    .filter-select {
        flex: 1 1 calc(50% - 0.5rem);
        max-width: none;
        width: auto;
    }

    .filter-btn {
        flex: 1 1 100%;
        justify-content: center;
    }
}

@media (max-width: 575.98px) {
    .container-fluid {
        padding-left: 12px;
        padding-right: 12px;
    }

    .summary-card h4 {
        font-size: 18px;
    }

    .summary-card .card-body {
        padding: 0.85rem;
    }

    .icon-box {
        width: 36px;
        height: 36px;
        font-size: 16px;
    }

    .filter-select {
        flex: 1 1 100%;
        width: 100%;
    }

    .filter-bar .btn {
        width: 100%;
    }

    .search-input {
        max-width: 100%;
    }

    .table th,
    .table td {
        font-size: 13px;
    }

    .modal-dialog {
        margin: 0.5rem;
    }

    .product-image {
        width: 36px;
        height: 36px;
    }

    .no-image {
        width: 36px;
        height: 36px;
        font-size: 8px;
    }
}
</style>