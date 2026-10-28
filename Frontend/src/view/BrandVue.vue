<script setup>
import { onMounted, computed, ref } from 'vue'
import { useBrandStore } from '@/stores/Brand'
import { useAuthStore } from '@/stores/auth'
import { PERMISSIONS } from '@/constants/permissions'
import ExcelJS from 'exceljs'

import BrandSummary from '@/Components/brands/BrandSummary.vue'
import BrandToolbar from '@/Components/brands/BrandToolbar.vue'
import BrandTable from '@/Components/brands/BrandTable.vue'
import BrandAddModal from '@/Components/brands/BrandAddModal.vue'
import BrandDetailModal from '@/Components/brands/BrandDetailModal.vue'
import BrandEditModal from '@/Components/brands/BrandEditModal.vue'
import BrandDeleteModal from '@/Components/brands/BrandDeleteModal.vue'

const brandStore = useBrandStore()
const authStore = useAuthStore()
const canCreateBrand = () => authStore.can(PERMISSIONS.BRANDS_CREATE)
const canUpdateBrand = () => authStore.can(PERMISSIONS.BRANDS_UPDATE)
const canDeleteBrand = () => authStore.can(PERMISSIONS.BRANDS_DELETE)

const search = ref('')
const selectedStatus = ref('')
const appliedStatus = ref('')
const successMessage = ref('')
const createError = ref('')

const showModalAddBrand = ref(false)
const showModalEditBrand = ref(false)
const showModalBrandDetail = ref(false)
const showDeleteModal = ref(false)

const deleteBrandId = ref(null)
const deleteBrandName = ref('')

const brands = computed(() => brandStore.brands)

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
    current_logo: '',
    status: 'active'
})

const loadBrands = async (page = 1) => {
    await brandStore.getBrands(
        search.value,
        page,
        appliedStatus.value
    )
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
    if (
        page < 1 ||
        page > brandStore.pagination.last_page
    ) {
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
    if (!canCreateBrand()) return

    resetForm()
    createError.value = ''
    showModalAddBrand.value = true
}
const closeAddBrand = () => {
    showModalAddBrand.value = false
}
const createBrand = async () => {
    if (!canCreateBrand()) return

    try {
        await brandStore.createBrand(form.value)
        closeAddBrand()
        showSuccess('Brand created successfully')
        await loadBrands(1)
    } catch (error) {
        createError.value =
            error.response?.data?.message ||
            'Failed to create brand'
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
    if (!canUpdateBrand()) return

    createError.value = ''

    editForm.value = {
        id: brand.id,
        name: brand.name || '',
        description: brand.description || '',
        logo: null,
        current_logo: brand.logo || '',
        status: brand.status || 'active'
    }
    showModalEditBrand.value = true
}
const closeEditBrand = () => {
    showModalEditBrand.value = false
}
const editBrand = async () => {
    if (!canUpdateBrand()) return

    try {
        await brandStore.updateBrand(
            editForm.value.id,
            editForm.value
        )
      closeEditBrand()

        showSuccess('Brand updated successfully')

        await loadBrands(
            brandStore.pagination?.current_page || 1
        )
    } catch (error) {
        createError.value =
            error.response?.data?.message ||
            'Failed to update brand'
    }
}
const openDeleteModal = (brand) => {
    if (!canDeleteBrand()) return

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
    if (!canDeleteBrand()) return

    const id = deleteBrandId.value
    closeDeleteModal()
    try {
        await brandStore.deleteBrand(id)
        showSuccess('Brand deleted successfully')
        await loadBrands(
            brandStore.pagination?.current_page || 1
        )
    } catch (error) {
        createError.value =
            error.response?.data?.message ||
            'Failed to delete brand'
    }
}
const exportExcel = async () => {
    const workbook = new ExcelJS.Workbook()
    const worksheet = workbook.addWorksheet('Brands')

    worksheet.addRow(['Brand Summary'])
    worksheet.addRow([
        'Total Brands',
        brandStore.summary.total
    ])
    worksheet.addRow([
        'Active Brands',
        brandStore.summary.active
    ])
    worksheet.addRow([
        'Inactive Brands',
        brandStore.summary.inactive
    ])
    worksheet.addRow([])

    worksheet.addRow([
        'ID',
        'Name',
        'Description',
        'Status',
        'Created Date'
    ])
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
    link.download = `brands_${date}.xlsx`
    link.click()
    window.URL.revokeObjectURL(url)
}
</script>

<template>
    <div class="container-fluid py-4 px-3 px-md-4">

        <BrandSummary
            :summary="brandStore.summary"
        />

        <BrandToolbar
            v-model:search="search"
            v-model:selected-status="selectedStatus"
            :is-admin="authStore.isAdmin()"
            :can-create="canCreateBrand()"
            @search="searchBrand"
            @filter="filterBrand"
            @reload="reloadBrand"
            @add="openAddBrand"
            @export="exportExcel"
        />

        <BrandTable
            :brands="brands"
            :loading="brandStore.loading"
            :error="brandStore.error"
            :success-message="successMessage"
            :pagination="brandStore.pagination"
            :can-update="canUpdateBrand()"
            :can-delete="canDeleteBrand()"
            @view="viewBrand"
            @edit="showEditBrand"
            @delete="openDeleteModal"
            @page="changePage"
        />

    </div>

    <BrandAddModal
        v-model="showModalAddBrand"
        v-model:form="form"
        :error="createError"
        @save="createBrand"
        @close="closeAddBrand"
    />

    <BrandDetailModal
        v-model="showModalBrandDetail"
        :brand="brandStore.brand"
        @close="closeBrandDetail"
    />

    <BrandEditModal
        v-model="showModalEditBrand"
        v-model:form="editForm"
        :error="createError"
        @save="editBrand"
        @close="closeEditBrand"
    />

    <BrandDeleteModal
        v-model="showDeleteModal"
        :brand-name="deleteBrandName"
        @confirm="deleteBrand"
        @close="closeDeleteModal"
    />
</template>

<style scoped>
.container-fluid {
    width: 100%;
}
</style>