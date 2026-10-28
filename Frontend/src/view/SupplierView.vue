<script setup>
import { computed, ref, onMounted } from 'vue'
import { useSupplierStore } from '@/stores/Supplier'
import { useAuthStore } from '@/stores/auth'
import { PERMISSIONS } from '@/constants/permissions'
import ExcelJS from 'exceljs'

import SupplierSummary from '@/Components/suppliers/SupplierSummary.vue'
import SupplierToolbar from '@/Components/suppliers/SupplierToolbar.vue'
import SupplierTable from '@/Components/suppliers/SupplierTable.vue'
import SupplierPagination from '@/Components/suppliers/SupplierPagination.vue'
import SupplierCreateModal from '@/Components/suppliers/SupplierCreateModal.vue'
import SupplierEditModal from '@/Components/suppliers/SupplierEditModal.vue'
import SupplierDeleteModal from '@/Components/suppliers/SupplierDeleteModal.vue'

const supplierStore = useSupplierStore()
const authStore = useAuthStore()

const search = ref('')
const statusFilter = ref('')

const suppliers = computed(() => supplierStore.suppliers)
const canCreateSupplier = computed(() => authStore.can(PERMISSIONS.SUPPLIERS_CREATE))
const canUpdateSupplier = computed(() => authStore.can(PERMISSIONS.SUPPLIERS_UPDATE))
const canDeleteSupplier = computed(() => authStore.can(PERMISSIONS.SUPPLIERS_DELETE))

const page = ref(1)

const form = ref({
    company_name: '',
    contact_person: '',
    phone: '',
    email: '',
    address: '',
    image: null,
    status: 'active'
})

const editForm = ref(null)

const showModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const savingSupplier = ref(false)

const deleteSupplierId = ref(null)
const deleteSupplierName = ref('')

const errorMessage = ref('')

const loadSuppliers = async (currentPage = 1) => {
    page.value = currentPage

    await supplierStore.getSuppliers(
        search.value,
        currentPage,
        statusFilter.value
    )
}

const searchSuppliers = () => {
    loadSuppliers(1)
}

const applyFilter = () => {
    loadSuppliers(1)
}

const exportExcel = async () => {
    const workbook = new ExcelJS.Workbook()

    const worksheet = workbook.addWorksheet('Suppliers')

    worksheet.addRow(['Supplier Summary'])

    worksheet.addRow([
        'Total Suppliers',
        supplierStore.summary.total || 0
    ])

    worksheet.addRow([
        'Active Suppliers',
        supplierStore.summary.active || 0
    ])

    worksheet.addRow([
        'Inactive Suppliers',
        supplierStore.summary.inactive || 0
    ])

    worksheet.addRow([])

    worksheet.addRow([
        'ID',
        'Company',
        'Contact',
        'Phone',
        'Email',
        'Status'
    ])

    suppliers.value.forEach((supplier) => {
        worksheet.addRow([
            supplier.id,
            supplier.company_name || '',
            supplier.contact_person || '',
            supplier.phone || '',
            supplier.email || '',
            supplier.status || 'active'
        ])
    })

    worksheet.columns = [
        { width: 10 },
        { width: 28 },
        { width: 22 },
        { width: 18 },
        { width: 26 },
        { width: 14 }
    ]

    const date = new Date()
        .toISOString()
        .split('T')[0]

    const buffer = await workbook.xlsx.writeBuffer()

    const blob = new Blob([buffer], {
        type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    })

    const url = window.URL.createObjectURL(blob)

    const link = document.createElement('a')

    link.href = url
    link.download = `suppliers_${date}.xlsx`

    link.click()

    window.URL.revokeObjectURL(url)
}

const resetForm = () => {
    form.value = {
        company_name: '',
        contact_person: '',
        phone: '',
        email: '',
        address: '',
        image: null,
        status: 'active'
    }
}

const submitSupplier = async () => {
    if (!canCreateSupplier.value || savingSupplier.value) return

    errorMessage.value = ''

    if (!form.value.company_name.trim()) {
        errorMessage.value = 'Company name is required.'
        return
    }

    savingSupplier.value = true

    try {
        await supplierStore.createSupplier(form.value)
        resetForm()
        showModal.value = false
    } catch (error) {
        errorMessage.value =
            supplierStore.error || 'Failed to save supplier.'
        return
    } finally {
        savingSupplier.value = false
    }

    try {
        await loadSuppliers(1)
    } catch (error) {
        errorMessage.value =
            supplierStore.error || 'Supplier was created, but the list could not be refreshed.'
    }
}

const setImage = (file, target) => {
    target.image = file
}

const openEdit = (supplier) => {
    if (!canUpdateSupplier.value) return

    editForm.value = {
        ...supplier
    }

    showEditModal.value = true
}

const submitEdit = async () => {
    if (!canUpdateSupplier.value) return

    if (!editForm.value?.company_name?.trim()) {
        errorMessage.value = 'Company name is required.'
        return
    }

    try {
        await supplierStore.updateSupplier(
            editForm.value.id,
            editForm.value
        )

        showEditModal.value = false

        await loadSuppliers(page.value)
    } catch (error) {
        errorMessage.value =
            supplierStore.error || 'Failed to update supplier.'
    }
}

const openDeleteModal = (supplier) => {
    if (!canDeleteSupplier.value) return

    deleteSupplierId.value = supplier.id
    deleteSupplierName.value = supplier.company_name
    showDeleteModal.value = true
}

const closeDeleteModal = () => {
    showDeleteModal.value = false
    deleteSupplierId.value = null
    deleteSupplierName.value = ''
}

const deleteSupplier = async () => {
    if (!canDeleteSupplier.value) return

    const supplierId = deleteSupplierId.value

    closeDeleteModal()

    try {
        await supplierStore.deleteSupplier(supplierId)

        await loadSuppliers(page.value)
    } catch (error) {
        errorMessage.value =
            supplierStore.error || 'Delete failed.'
    }
}

onMounted(() => {
    loadSuppliers(1)
})
</script>

<template>
    <div class="container-fluid py-4 px-3 px-md-4">

        <SupplierSummary
            :summary="supplierStore.summary"
        />

        <div class="card main-card">

            <SupplierToolbar
                v-model:search="search"
                v-model:statusFilter="statusFilter"
                :is-admin="authStore.isAdmin()"
                :can-create="canCreateSupplier"
                @search="searchSuppliers"
                @filter="applyFilter"
                @refresh="loadSuppliers(1)"
                @export="exportExcel"
                @create="showModal = true"
            />

            <div class="card-body">

                <div
                    v-if="errorMessage"
                    class="alert alert-danger"
                >
                    {{ errorMessage }}
                </div>

                <SupplierTable
                    :suppliers="suppliers"
                    :loading="supplierStore.loading"
                    :can-update="canUpdateSupplier"
                    :can-delete="canDeleteSupplier"
                    @edit="openEdit"
                    @delete="openDeleteModal"
                />

                <SupplierPagination
                    :pagination="supplierStore.pagination"
                    @page="loadSuppliers"
                />

            </div>
        </div>
    </div>

    <SupplierCreateModal
        v-if="showModal"
        v-model="showModal"
        :form="form"
        :error-message="errorMessage"
        :saving="savingSupplier"
        @submit="submitSupplier"
        @image-change="setImage"
    />

    <SupplierEditModal
        v-if="showEditModal"
        v-model="showEditModal"
        :form="editForm"
        :error-message="errorMessage"
        @submit="submitEdit"
        @image-change="setImage"
    />

    <SupplierDeleteModal
        v-if="showDeleteModal"
        v-model="showDeleteModal"
        :supplier-name="deleteSupplierName"
        @delete="deleteSupplier"
    />
</template>

<style scoped>
.summary-card {
    border-radius: 12px;
    border: 1px solid var(--border-color) !important;
    background: var(--surface-bg);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.summary-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow);
}

.summary-card small {
    font-size: 12px;
    font-weight: 600;
    color: var(--muted-text);
}

.summary-card h4 {
    font-size: 22px;
    color: var(--text-color);
}

.icon-box {
    width: 44px;
    height: 44px;
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.total-card .icon-box {
    background: rgba(37, 99, 235, 0.12);
    color: #2563eb;
}

.active-card .icon-box {
    background: rgba(16, 185, 129, 0.12);
    color: #10b981;
}

.inactive-card .icon-box {
    background: rgba(100, 116, 139, 0.15);
    color: #64748b;
}

.main-card {
    border: 1px solid var(--border-color);
    border-radius: 12px;
    background: var(--surface-bg);
    overflow: hidden;
}

.toolbar {
    background: var(--surface-muted);
    border-bottom: 1px solid var(--border-color) !important;
}

.filter-select,
.filter-btn {
    height: 40px;
    border-radius: 8px;
    font-size: 14px;
}

.filter-select {
    width: 150px;
    max-width: 150px;
    background: var(--surface-bg);
    border-color: var(--border-color);
    color: var(--text-color);
}

.search-input {
    max-width: 350px;
    width: 100%;
}

.search-input .form-control,
.search-input .input-group-text {
    height: 40px;
    border-radius: 8px;
    background: var(--surface-bg);
    border-color: var(--border-color);
    color: var(--text-color);
}

.search-input .input-group-text {
    color: var(--muted-text);
}

.table {
    --bs-table-bg: transparent;
    --bs-table-color: var(--text-color);
    --bs-table-border-color: var(--border-color);
    --bs-table-hover-bg: var(--surface-muted);
    color: var(--text-color);
}

.table th {
    font-size: 12.5px;
    font-weight: 700;
    color: var(--muted-text);
    background: var(--surface-muted) !important;
    border-bottom: 1px solid var(--border-color);
    white-space: nowrap;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.table td {
    font-size: 14px;
    border-color: var(--border-color);
    vertical-align: middle;
}

.product-image {
    width: 42px;
    height: 42px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid var(--border-color);
}

.no-image {
    width: 42px;
    height: 42px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--surface-muted);
    color: var(--muted-text);
    border-radius: 8px;
    font-size: 16px;
    border: 1px solid var(--border-color);
}

.badge {
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    text-transform: capitalize;
}

.badge-active {
    color: green;
}

.badge-inactive {
    color: var(--muted-text);
}

.table-action {
    width: 34px;
    height: 34px;
    padding: 0;
    border-radius: 8px;
    color: var(--muted-text);
    border: 1px solid transparent;
    background: transparent;
}

.table-action:hover {
    background: var(--surface-muted);
    color: var(--text-color);
}

.action-danger:hover {
    background: rgba(239, 68, 68, 0.12);
    color: #ef4444;
}

.text-truncate-cell {
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.empty-state {
    padding: 50px 20px;
    text-align: center;
}

.empty-state i {
    font-size: 56px;
    color: var(--muted-text);
    opacity: 0.5;
    display: block;
    margin-bottom: 16px;
}

.empty-state h5 {
    font-size: 18px;
    font-weight: 700;
    color: var(--text-color);
}

.empty-state p {
    color: var(--muted-text);
    margin: 0;
}

@media (max-width: 768px) {
    .filter-select {
        width: 100%;
        max-width: none;
    }
}
</style>