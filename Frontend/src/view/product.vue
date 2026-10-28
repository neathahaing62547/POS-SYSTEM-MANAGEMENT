<script setup>
import { onMounted, ref } from 'vue'
import ExcelJS from 'exceljs'
import { useAuthStore } from '@/stores/auth'
import { PERMISSIONS } from '@/constants/permissions'
import { useProductStore } from '@/stores/product'
import LoadingSkeleton from '@/Components/LoadingSkeleton.vue'
import ProductSummary from '@/Components/products/Productsummary.vue'
import ProductToolbar from '@/Components/products/Producttoolbar.vue'
import ProductTable from '@/Components/products/Producttable.vue'
import ProductAddModal from '@/Components/products/Productaddmodal.vue'
import ProductEditModal from '@/Components/products/Producteditmodal.vue'
import ProductDetailModal from '@/Components/products/Productdetailmodal.vue'
import ProductDeleteModal from '@/Components/products/Productdeletemodal.vue'

const authStore = useAuthStore()
const productStore = useProductStore()
const canCreateProduct = () => authStore.can(PERMISSIONS.PRODUCTS_CREATE)
const canUpdateProduct = () => authStore.can(PERMISSIONS.PRODUCTS_UPDATE)
const canDeleteProduct = () => authStore.can(PERMISSIONS.PRODUCTS_DELETE)

const search = ref('')
const filters = ref({
    category: '',
    brand: '',
    status: '',
    stock: ''
})
const category = ref([])
const brand = ref([])

const modal = ref(null)
const selected = ref(null)
const successMessage = ref('')
const errorMessage = ref('')

const load = (page = 1) =>
    productStore.getProducts(search.value, page, filters.value.category,
        filters.value.brand, filters.value.status, filters.value.stock)

const flash = (msg) => {
    successMessage.value = msg
    setTimeout(() => (successMessage.value = ''), 3000)
}

const closeModal = () => {
    modal.value = null
    selected.value = null
}
onMounted(async () => {
    load()
    category.value = await productStore.Getcategory()
    brand.value = await productStore.Getbrands()
})
const applyFilters = (f) => {
    filters.value = f
    load()
}
const changePage = (page) => {
    if (page < 1 || page > productStore.pagination.last_page) return
    load(page)
}


const createProduct = async (form) => {
    if (!canCreateProduct()) return

    try {
        await productStore.createProduct(form)
        closeModal()
        flash('Product created successfully')
        load()
    } catch (e) {
        errorMessage.value = e.response?.data?.message || 'Failed to create product'
    }
}

const openView = async (id) => {
    await productStore.getDetailProduct(id)
    modal.value = 'view'
}
const openEdit = (product) => {
    if (!canUpdateProduct()) return

    selected.value = product
    modal.value = 'edit'
}
const updateProduct = async (form) => {
    if (!canUpdateProduct()) return

    try {
        await productStore.updateProduct(form.id, form)
        closeModal()
        flash('Product updated successfully')
        load()
    } catch (e) {
        errorMessage.value = e.response?.data?.message || 'Failed to update product'
    }
}
const openDelete = (product) => {
    if (!canDeleteProduct()) return

    selected.value = product
    modal.value = 'delete'
}
const deleteProduct = async () => {
    if (!canDeleteProduct()) return

    const id = selected.value.id
    closeModal()
    try {
        await productStore.deleteProduct(id)
        flash('Product deleted successfully')
        load()
    } catch (e) {
        errorMessage.value = e.response?.data?.message || 'Failed to delete product'
    }
}
const exportExcel = async () => {
    const wb = new ExcelJS.Workbook()
    const ws = wb.addWorksheet('Products')
    ws.addRow(['ID', 'Product', 'SKU', 'Barcode', 'Category', 'Brand',
        'Cost Price', 'Sale Price', 'Unit', 'Stock', 'Status', 'Created Date'
    ])
    productStore.products.forEach(p => ws.addRow([
        p.id, p.name, p.sku, p.barcode, p.category?.name || '', p.brand?.name || '',
        p.cost_price, p.sale_price, p.unit, p.inventory_stocks_sum_quantity ?? 0,
        p.status, p.created_at
    ]))
    ws.columns.forEach(c => (c.width = 18))

    const buffer = await wb.xlsx.writeBuffer()
    const url = URL.createObjectURL(new Blob([buffer]))
    const a = document.createElement('a')
    a.href = url
    a.download = `products_${new Date().toISOString().split('T')[0]}.xlsx`
    a.click()
    URL.revokeObjectURL(url)
}
</script>

<template>
    <div class="container-fluid py-4 px-3 px-md-4">
        <ProductSummary :summary="productStore.summary" />

        <div class="card border-0 shadow-sm">
            <ProductToolbar v-model:search="search" :categories="category" :brands="brand"
                :is-admin="authStore.isAdmin()" :can-create="canCreateProduct()" @filter="applyFilters" @search="load()" @export="exportExcel"
                @add="modal = 'add'" />

            <div class="card-body">
                <div v-if="errorMessage" class="alert alert-danger">{{ errorMessage }}</div>
                <div v-if="successMessage" class="alert alert-success">{{ successMessage }}</div>

                <LoadingSkeleton v-if="productStore.loading" variant="table" :columns="13" label="Loading products" />
                <div v-else-if="productStore.error" class="alert alert-danger">{{ productStore.error }}</div>

                <ProductTable v-else :products="productStore.products" :pagination="productStore.pagination"
                    :can-update="canUpdateProduct()" :can-delete="canDeleteProduct()" @view="openView" @edit="openEdit" @delete="openDelete"
                    @page="changePage" />
            </div>
        </div>
    </div>

    <ProductAddModal v-if="modal === 'add'" :categories="category" :brands="brand" @save="createProduct"
        @close="closeModal" />

    <ProductEditModal v-if="modal === 'edit'" :product="selected" :categories="category" :brands="brand"
        @save="updateProduct" @close="closeModal" />

    <ProductDetailModal v-if="modal === 'view'" :product="productStore.product" @close="closeModal" />

    <ProductDeleteModal v-if="modal === 'delete'" :name="selected?.name" @confirm="deleteProduct" @close="closeModal" />
</template>