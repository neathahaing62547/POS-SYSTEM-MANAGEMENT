<script setup>
import { onMounted, computed, ref } from 'vue'
import { useBrandStore } from '@/stores/Brand'
import ExcelJS from 'exceljs'
import { useAuthStore } from '@/stores/auth'

const brandStore = useBrandStore()
const authStore = useAuthStore()
const search = ref('')
const selectedStatus = ref('')
const appliedStatus = ref('')

const successMessage = ref('')
const createError = ref('')

const brands = computed(() => brandStore.brands)

const showModalAddBrand = ref(false)
const showModalEditBrand = ref(false)
const showModalBrandDetail = ref(false)
const showDeleteModal = ref(false)

const deleteBrandId = ref(null)
const deleteBrandName = ref('')

const form = ref({
    name: '',
    description: '',
    logo: null,
    status: 'active'
})

const editForm = ref({
    id: null,
    name: '',
    description: '',
    logo: null,
    status: 'active'
})

const logoUrl = (logo) => `http://127.0.0.1:8000/storage/${logo}`

const loadBrands = (page = 1) => {
    brandStore.getBrands(search.value, page, appliedStatus.value)
}

onMounted(() => {
    loadBrands()
})

const searchBrand = () => {
    loadBrands(1)
}

const filterBrand = () => {
    appliedStatus.value = selectedStatus.value
    loadBrands(1)
}

const reloadBrand = () => {
    window.location.reload()
}

const changePage = (page) => {
    if (page < 1 || page > brandStore.pagination.last_page) {
        return
    }
    loadBrands(page)
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
        logo: null,
        status: 'active'
    }
}

const openAddBrand = () => {
    resetForm()
    createError.value = ''
    showModalAddBrand.value = true
}

const closeAddBrand = () => {
    showModalAddBrand.value = false
}

const createBrand = async () => {
    try {
        await brandStore.createBrand(form.value)
        showModalAddBrand.value = false
        showSuccess('Brand created successfully')
        await loadBrands(1)
    } catch (error) {
        createError.value = error.response?.data?.message || 'Failed to create brand'
    }
}

const viewBrand = async (id) => {
    showModalBrandDetail.value = true
    await brandStore.getDetailBrand(id)
}

const closeBrandDetail = () => {
    showModalBrandDetail.value = false
}

const showEditBrand = (brand) => {
    editForm.value = {
        id: brand.id,
        name: brand.name || '',
        description: brand.description || '',
        logo: null,
        status: brand.status || 'active'
    }
    showModalEditBrand.value = true
}

const closeEditBrand = () => {
    showModalEditBrand.value = false
}

const editBrand = async () => {
    try {
        await brandStore.updateBrand(editForm.value.id, editForm.value)
        showModalEditBrand.value = false
        showSuccess('Brand updated successfully')
        await loadBrands(brandStore.pagination?.current_page || 1)
    } catch (error) {
        createError.value = error.response?.data?.message || 'Failed to update brand'
    }
}

const openDeleteModal = (brand) => {
    deleteBrandId.value = brand.id
    deleteBrandName.value = brand.name
    showDeleteModal.value = true
}

const closeDeleteModal = () => {
    showDeleteModal.value = false
    deleteBrandId.value = null
    deleteBrandName.value = ''
}

const deleteBrand = async () => {
    const id = deleteBrandId.value
    closeDeleteModal()
    try {
        await brandStore.deleteBrand(id)
        showSuccess('Brand deleted successfully')
        await loadBrands(brandStore.pagination?.current_page || 1)
    } catch (error) {
        createError.value = error.response?.data?.message || 'Failed to delete brand'
    }
}

const exportExcel = async () => {
    const workbook = new ExcelJS.Workbook()
    const worksheet = workbook.addWorksheet('Brands')

    worksheet.addRow(['Brand Summary'])
    worksheet.addRow(['Total Brands', brandStore.summary.total])
    worksheet.addRow(['Active Brands', brandStore.summary.active])
    worksheet.addRow(['Inactive Brands', brandStore.summary.inactive])
    worksheet.addRow([])

    worksheet.addRow(['ID', 'Name', 'Description', 'Status', 'Created Date'])

    brands.value.forEach(brand => {
        worksheet.addRow([
            brand.id,
            brand.name,
            brand.description,
            brand.status,
            brand.created_at
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
    link.download = `brands_${date}.xlsx`
    link.click()

    window.URL.revokeObjectURL(url)
}
</script>

<template>
    <div class="container-fluid py-4 px-3 px-md-4">
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4">
                <div class="card summary-card total-card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-muted">Total Brands</small>
                                <h4 class="fw-bold mb-0 mt-2">
                                    {{ brandStore.summary.total }}
                                </h4>
                            </div>
                            <div class="icon-box bg-primary-subtle text-primary">
                                <i class="bi bi-tags"></i>
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
                                    {{ brandStore.summary.active }}
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
                                    {{ brandStore.summary.inactive }}
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

                    <button @click="filterBrand" class="btn btn-outline-secondary filter-btn text-nowrap flex-shrink-0">
                        <i class="bi bi-funnel me-1"></i>
                        Filter
                    </button>

                    <button v-if="authStore.isAdmin()" type="button" class="btn btn-success" @click="exportExcel">
                        <i class="bi bi-download me-1"></i>
                        Export Excel
                    </button>
                </div>

                <button v-if="authStore.isAdmin()" @click="openAddBrand" class="btn btn-primary add-btn flex-shrink-0">
                    <i class="bi bi-plus-lg me-2"></i>
                    Add Brand
                </button>
            </div>

            <div class="card-body">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                    <div class="input-group search-input">
                        <span class="input-group-text bg-white">
                            <i class="bi bi-search"></i>
                        </span>
                        <input v-model="search" @input="searchBrand" type="text" class="form-control"
                            placeholder="Search brands...">
                    </div>

                    <div class="d-flex gap-2">
                        <button @click="searchBrand" class="btn btn-outline-secondary flex-fill flex-md-grow-0">
                            <i class="bi bi-search"></i>
                            search
                        </button>

                        <button @click="reloadBrand" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>
                </div>

                <div v-if="brandStore.loading" class="text-center py-5">
                    <div class="spinner-border text-primary"></div>
                    <p>waiting for response</p>
                </div>

                <div v-else-if="brandStore.error" class="alert alert-danger">
                    {{ brandStore.error }}
                </div>

                <div v-else-if="successMessage" class="alert alert-success">
                    {{ successMessage }}
                </div>

                <div v-else class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Logo</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Created Date</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="brand in brands" :key="brand.id">
                                <td>
                                    <span class="text-muted">
                                        #{{ brand.id }}
                                    </span>
                                </td>
                                <td>
                                    <img v-if="brand.logo" :src="logoUrl(brand.logo)" class="product-image" alt="Brand">
                                    <div v-else class="no-image">
                                        No Logo
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-truncate-cell" :title="brand.name">
                                        {{ brand.name }}
                                    </div>
                                </td>
                                <td>
                                    <div class="text-truncate-cell" :title="brand.description">
                                        {{ brand.description || '-' }}
                                    </div>
                                </td>
                                <td>
                                    <span class="badge" :class="brand.status === 'active'
                                        ? 'text-bg-success'
                                        : 'text-bg-secondary'">
                                        {{ brand.status }}
                                    </span>
                                </td>
                                <td>
                                    {{ new Date(brand.created_at).toLocaleDateString('en-GB') }}
                                </td>
                                <td class="text-center">
                                    <button v-if="authStore.isAdmin()" class="btn btn-sm btn-outline-primary me-1"
                                        @click="showEditBrand(brand)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-info me-1" @click="viewBrand(brand.id)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button v-if="authStore.isAdmin()" class="btn btn-sm btn-outline-danger"
                                        @click="openDeleteModal(brand)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="brands.length === 0">
                                <td colspan="7" class="text-center py-5">
                                    <div class="empty-products">
                                        <i class="bi bi-tags"></i>
                                        <h5>No Brands Found</h5>
                                        <p>There are no brands matching your search.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="brandStore.pagination"
                    class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 mt-3">
                    <small class="text-muted">
                        Showing
                        {{ brandStore.pagination.from || 0 }}
                        to
                        {{ brandStore.pagination.to || 0 }}
                        of
                        {{ brandStore.pagination.total || 0 }}
                        brands
                    </small>

                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-sm btn-outline-secondary"
                            :disabled="brandStore.pagination.current_page === 1"
                            @click="changePage(brandStore.pagination.current_page - 1)">
                            Previous
                        </button>

                        <span class="text-muted small">
                            Page {{ brandStore.pagination.current_page }}
                            of {{ brandStore.pagination.last_page }}
                        </span>

                        <button class="btn btn-sm btn-outline-secondary" :disabled="brandStore.pagination.current_page ===
                            brandStore.pagination.last_page
                            " @click="changePage(brandStore.pagination.current_page + 1)">
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Brand -->
    <div v-if="showModalAddBrand" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable product-modal">
            <div class="modal-content border-0 shadow">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        Add Brand
                    </h5>
                    <button type="button" class="modal-close-btn" @click="closeAddBrand" aria-label="Close">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="row g-3 g-md-4">
                        <div v-if="createError" class="alert alert-danger mb-3">
                            {{ createError }}
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Brand Name</label>
                            <input v-model="form.name" type="text" class="form-control" placeholder="Enter brand name">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Status</label>
                            <select v-model="form.status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Logo</label>
                            <input type="file" class="form-control" accept=".jpg,.jpeg,.png,.webp"
                                @change="form.logo = $event.target.files[0]">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea v-model="form.description" class="form-control" rows="3"
                                placeholder="Enter description"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer flex-column flex-sm-row modal-footer-actions">
                    <button type="button" class="btn btn-light footer-btn" @click="closeAddBrand">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-primary footer-btn" @click="createBrand">
                        <i class="bi bi-save me-1"></i>
                        Save Brand
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Brand Detail -->
    <div v-if="showModalBrandDetail" class="modal fade show d-block" tabindex="-1"
        style="background: rgba(0, 0, 0, 0.5);">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable product-modal">
            <div class="modal-content border-0 shadow">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        Brand Details
                    </h5>
                    <button type="button" class="modal-close-btn" @click="closeBrandDetail" aria-label="Close">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div class="modal-body" v-if="brandStore.brand">
                    <div class="row g-3 g-md-4">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Brand Name</label>
                            <input class="form-control readonly-field" :value="brandStore.brand.name" readonly>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Status</label>
                            <input class="form-control readonly-field" :value="brandStore.brand.status" readonly>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control readonly-field" rows="3"
                                readonly>{{ brandStore.brand.description || '-' }}</textarea>
                        </div>
                    </div>
                </div>

                <div v-else class="modal-body text-center py-5">
                    <div class="spinner-border text-primary"></div>
                    <p class="text-muted mt-2 mb-0">Loading brand...</p>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="closeBrandDetail">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Brand -->
    <div v-if="showModalEditBrand" class="modal fade show d-block" tabindex="-1"
        style="background: rgba(0, 0, 0, 0.5);">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable product-modal">
            <div class="modal-content border-0 shadow">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        Edit Brand
                    </h5>
                    <button type="button" class="modal-close-btn" @click="closeEditBrand" aria-label="Close">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="row g-3 g-md-4">
                        <div v-if="createError" class="alert alert-danger mb-3">
                            {{ createError }}
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Brand Name</label>
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
                            <label class="form-label">Logo</label>
                            <input type="file" class="form-control" accept=".jpg,.jpeg,.png,.webp"
                                @change="editForm.logo = $event.target.files[0]">
                            <small class="text-muted">Leave empty to keep the current logo.</small>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea v-model="editForm.description" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="closeEditBrand">
                        Close
                    </button>
                    <button class="btn btn-sm btn-outline-primary me-1" @click="editBrand">
                        <i class="bi bi-pencil"></i>
                        Update Brand
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
                        Delete Brand
                    </h5>
                    <button type="button" class="btn-close" @click="closeDeleteModal"></button>
                </div>

                <div class="modal-body">
                    <p class="mb-2">
                        Are you sure you want to delete this brand?
                    </p>
                    <strong>
                        {{ deleteBrandName }}
                    </strong>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="closeDeleteModal">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-danger" @click="deleteBrand()">
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
    color: #080808;
    white-space: nowrap;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.table td {
    font-size: 14px;
    white-space: nowrap;
}

.summary-card {
    border-radius: 12px;
    border: 1px solid #e5e7eb !important;
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
</style>