<script setup>
import { onMounted, computed, ref } from 'vue'
import { useProductStore } from '@/stores/product'
import * as XLSX from 'xlsx'
import ExcelJS from 'exceljs'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const createError = ref('')
const productStore = useProductStore()
const search = ref('')
const page = ref('')
const products = computed(() => productStore.products)
const category = ref([])
const showModalProductDetail = ref(false)
const showModalAddProduct = ref(false)
const ShowModalEditProduct = ref(false)
const showModal = ref(false)
const selectedCategory = ref('')
const selectedBrand = ref('')
const selectedStatus = ref('')
const selectedStock = ref('')
const filltercategorys = ref('')
const filllterbrands = ref('')
const fillterstatus = ref('')
const fillterstock = ref('')
const brand = ref([])
const successMessage = ref('')
const showDeleteModal = ref(false)
const selectedProduct = ref(null)
const deleteProductId = ref()
const deleteProductName = ref()
const form = ref({
    category_id: '',
    brand_id: '',
    barcode: '',
    sku: '',
    name: '',
    description: '',
    cost_price: '',
    sale_price: '',
    unit: '',
    image: null,
    status: 'active'
})
const editForm = ref({
    id: null,
    category_id: '',
    brand_id: '',
    barcode: '',
    sku: '',
    name: '',
    description: '',
    cost_price: '',
    sale_price: '',
    unit: '',
    image: null,
    status: 'active'
})
const exportExcel = async () => {
    const workbook = new ExcelJS.Workbook()
    const worksheet = workbook.addWorksheet('Products')

    worksheet.addRow(['Product Summary'])
    worksheet.addRow(['Total Products', productStore.summary.total])
    worksheet.addRow(['Active Products', productStore.summary.active])
    worksheet.addRow(['Inactive Products', productStore.summary.inactive])
    worksheet.addRow(['Stock Alert', productStore.summary.stockAlert])
    worksheet.addRow([])

    worksheet.addRow([
        'ID',
        'Product',
        'SKU',
        'Barcode',
        'Category',
        'Brand',
        'Cost Price',
        'Sale Price',
        'Unit',
        'Stock',
        'Status',
        'Created Date'
    ])

    products.value.forEach(product => {
        worksheet.addRow([
            product.id,
            product.name,
            product.sku,
            product.barcode,
            product.category?.name || '',
            product.brand?.name || '',
            product.cost_price,
            product.sale_price,
            product.unit,
            product.inventory_stocks_sum_quantity ?? 0,
            product.status,
            product.created_at
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
        { width: 18 },
        { width: 20 },
        { width: 18 },
        { width: 18 },
        { width: 15 },
        { width: 15 },
        { width: 12 },
        { width: 12 },
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
    link.download = `products_${date}.xlsx`
    link.click()

    window.URL.revokeObjectURL(url)
}
const openDeleteModal = (product) => {
    deleteProductId.value = product.id
    deleteProductName.value = product.name
    showDeleteModal.value = true
}
const closeDeleteModal = () => {
    showDeleteModal.value = false
    deleteProductId.value = null
    deleteProductName.value = ''
}
const deleteProduct = async () => {
    const id = deleteProductId.value
    closeDeleteModal()
    try {
        await productStore.deleteProduct(id)
        successMessage.value = 'Product deleted successfully'
        setTimeout(() => {
            successMessage.value = ''
        }, 1000)
        await productStore.getProducts(
            search.value,
            1,
            filltercategorys.value,
            filllterbrands.value,
            fillterstatus.value,
            filllterstock.value
        )
        setTimeout(() => {
            successMessage.value = ''
        }, 1000)

    } catch (error) {
        createError.value =
            error.response?.data?.message ||
            'Failed to delete product'
    }
}
computed(() => {
    return products.value.fillter(product => {
        const keyword = search.value.trim.toLowerCase()
        if (!keyword) {
            return products
        }
        product.name.toLowerCase().includes(keyword)
        product.sku.toLowerCase().includes(keyword)
        product.barcode.toLowerCase().includes(keyword)
        product.cost_price.toLowerCase().includes(keyword)
        product.sale_price.toLowerCase().includes(keyword)
        product.description.toLowerCase().includes(keyword)
        product.category.toLowerCase().includes(keyword)
        product.name.toLowerCase().includes(keyword)
    })
})
onMounted(async () => {
    productStore.getProducts()
    category.value = await productStore.Getcategory()
    brand.value = await productStore.Getbrands()
})
const searchProduct = () => {
    productStore.getProducts(search.value)
}

const fillter = () => {
    filltercategorys.value = selectedCategory.value
    filllterbrands.value = selectedBrand.value
    fillterstatus.value = selectedStatus.value
    fillterstock.value = selectedStock.value
    productStore.getProducts(
        search.value, 1,
        filltercategorys.value,
        filllterbrands.value,
        fillterstatus.value,
        fillterstock.value
    )
}
const changePage = (page) => {
    if (page < 1 || page > productStore.pagination.last_page) {
        return
    }
    productStore.getProducts(
        search.value,
        page,
        filltercategorys.value,
        filllterbrands.value,
        fillterstatus.value,
        fillterstock.value
    )
}
const openAddProduct = async () => {
    showModalAddProduct.value = true
    brand.value = await productStore.Getbrands()
    category.value = await productStore.Getcategory()

}
const closeAddProduct = () => {
    showModalAddProduct.value = false
}
const viewProduct = async (id) => {
    showModalProductDetail.value = true
    await productStore.getDetailProduct(id)
}
const closeProductDetail = () => {
    showModalProductDetail.value = false
}
const Createproduct = async () => {
    try {
        await productStore.createProduct(form.value)

        showModalAddproduct.value = false
        successMessage.value = 'Product created successfully'
        setTimeout(() => {
            successMessage.value = ''
        }, 3000)

    } catch (error) {
        if (error.response?.status === 422) {
            const errors = error.response.data.errors
        } else {
            createError.value = error.response?.data?.message ||
                'Failed to create product'
        }
    }
}
const reloadProduct = () => {

    window.location.reload()
}
const showeditProduct = (product) => {
    editForm.value = {
        id: product.id,
        category_id: product.category_id || '',
        brand_id: product.brand_id || '',
        barcode: product.barcode || '',
        sku: product.sku || '',
        name: product.name || '',
        description: product.description || '',
        cost_price: product.cost_price || '',
        sale_price: product.sale_price || '',
        unit: product.unit || '',
        image: null,
        status: product.status || 'active'
    }
    ShowModalEditProduct.value = true
}
const closemodaleditproduct = () => {
    ShowModalEditProduct.value = false
}
const editProduct = async () => {
    try {
        await productStore.updateProduct(
            editForm.value.id,
            editForm.value
        )
        ShowModalEditProduct.value = false
        successMessage.value = 'Product updated successfully'

        await productStore.getProducts(
            search.value,
            1,
            filltercategorys.value,
            filllterbrands.value,
            filllterstatus.value,
            filllterstock.value
        )

        setTimeout(() => {
            successMessage.value = ''
        }, 3000)

    } catch (error) {
        createError.value =
            error.response?.data?.message ||
            'Failed to update product'
    }
}
</script>
<template>
    <div class="container-fluid py-4 px-3 px-md-4">
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card summary-card total-card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-muted">Total Products</small>
                                <h4 class="fw-bold mb-0 mt-2">
                                    {{ productStore.summary.total }}
                                </h4>
                            </div>
                            <div class="icon-box bg-primary-subtle text-primary">
                                <i class="bi bi-box-seam"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card summary-card active-card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-muted">Active</small>
                                <h4 class="fw-bold text-success mb-0 mt-2">
                                    {{ productStore.summary.active }}
                                </h4>
                            </div>
                            <div class="icon-box bg-success-subtle text-success">
                                <i class="bi bi-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card summary-card inactive-card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-muted">Inactive</small>
                                <h4 class="fw-bold text-secondary mb-0 mt-2">
                                    {{ productStore.summary.inactive }}
                                </h4>
                            </div>
                            <div class="icon-box bg-secondary-subtle text-secondary">
                                <i class="bi bi-x-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card summary-card stock-card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-muted">Stock Alert</small>
                                <h4 class="fw-bold text-danger mb-0 mt-2">
                                    {{ productStore.summary.stockAlert }}
                                </h4>
                            </div>
                            <div class="icon-box bg-danger-subtle text-danger">
                                <i class="bi bi-exclamation-triangle"></i>
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

                    <select v-model="selectedCategory" class="form-select filter-select">
                        <option value="">All Category</option>

                        <option v-for="item in category" :key="item.id" :value="item.id">
                            {{ item.name }}
                        </option>
                    </select>

                    <select v-model="selectedBrand" class="form-select filter-select">
                        <option value="">All Brands</option>

                        <option v-for="item in brand" :key="item.id" :value="item.id">
                            {{ item.name }}
                        </option>
                    </select>

                    <select v-model="selectedStatus" class="form-select filter-select">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <select v-model="selectedStock" class="form-select filter-select">
                        <option value="">All Stock</option>
                        <option value="low">Low Stock</option>
                        <option value="in">In Stock</option>
                    </select>
                    <button @click="fillter" class="btn btn-outline-secondary filter-btn text-nowrap flex-shrink-0">
                        <i class="bi bi-funnel me-1"></i>
                        Filter
                    </button>
                    <button v-if="authStore.isAdmin()" type="button" class="btn btn-success" @click="exportExcel">
                        <i class="bi bi-download me-1"></i>
                        Export Excel

                    </button>

                </div>
                <button v-if="authStore.isAdmin()" class="btn btn-primary" @click="showModalAddProduct = true">
                    <i class="bi bi-plus-lg"></i>
                    Add Product
                </button>

            </div>
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                    <div class="input-group search-input">
                        <span class="input-group-text bg-white">
                            <i class="bi bi-search"></i>
                        </span>

                        <input v-model="search" @input="searchProduct" type="text" class="form-control"
                            placeholder="Search products...">

                    </div>

                    <div class="d-flex gap-2">
                        <button @click="searchProduct" class="btn btn-outline-secondary flex-fill flex-md-grow-0">
                            <i class="bi bi-search"></i>
                            search
                        </button>

                        <button @click="reloadProduct" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>
                </div>

                <div v-if="productStore.loading" class="text-center py-5">
                    <div class="spinner-border text-primary"></div>
                    <p>waiting for response</p>
                </div>

                <div v-else-if="productStore.error" class="alert alert-danger">
                    {{ productStore.error }}
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
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Barcode</th>
                                <th>Category</th>
                                <th>Brand</th>
                                <th>Cost</th>
                                <th>Sale</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Created Date</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="product in products" :key="product.id">
                                <td>
                                    <span class="text-muted">
                                        #{{ product.id }}
                                    </span>
                                </td>
                                <td>
                                    <img v-if="product.image" :src="`http://127.0.0.1:8000/storage/${product.image}`"
                                        class="product-image" alt="Product">

                                    <div v-else class="no-image">
                                        No Image
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-truncate-cell" :title="product.name">
                                        {{ product.name }}
                                    </div>
                                    <small class="text-muted">
                                        {{ product.unit }}
                                    </small>
                                </td>
                                <td>{{ product.sku || '-' }}</td>

                                <td>{{ product.barcode || '-' }}</td>

                                <td>
                                    {{ product.category?.name || '-' }}
                                </td>
                                <td>
                                    {{ product.brand?.name || '-' }}
                                </td>
                                <td>
                                    ${{ product.cost_price }}
                                </td>
                                <td class="fw-semibold">
                                    ${{ product.sale_price }}
                                </td>
                                <td>
                                    <span :class="(product.inventory_stocks_sum_quantity ?? 0) <= 10
                                        ? 'text-danger fw-bold'
                                        : 'text-success fw-semibold'">
                                        {{ product.inventory_stocks_sum_quantity ?? 0 }}
                                    </span>
                                </td>

                                <td>
                                    <span class="badge" :class="product.status === 'active'
                                        ? 'text-bg-success'
                                        : 'text-bg-secondary'">
                                        {{ product.status }}
                                    </span>
                                </td>
                                <td>
                                    {{ new Date(product.created_at).toLocaleDateString('en-GB') }}
                                </td>

                                <td class="text-center">
                                    <button v-if="authStore.isAdmin()" class="btn btn-sm btn-outline-primary me-1"
                                        @click="showeditProduct(product)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-info me-1" @click="viewProduct(product.id)">
                                        <i class="bi bi-eye"></i>
                                    </button>

                                    <button v-if="authStore.isAdmin()" class="btn btn-sm btn-outline-danger"
                                        @click="openDeleteModal(product)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="products.length === 0">
                                <td colspan="13" class="text-center py-5">
                                    <div class="empty-products">
                                        <i class="bi bi-box-seam"></i>
                                        <h5>No Products Found</h5>
                                        <p>There are no products matching your search.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="productStore.pagination"
                    class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 mt-3">
                    <small class="text-muted">
                        Showing
                        {{ productStore.pagination.from || 0 }}
                        to
                        {{ productStore.pagination.to || 0 }}
                        of
                        {{ productStore.pagination.total || 0 }}
                        products
                    </small>

                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-sm btn-outline-secondary"
                            :disabled="productStore.pagination.current_page === 1"
                            @click="changePage(productStore.pagination.current_page - 1)">
                            Previous
                        </button>

                        <span class="text-muted small">
                            Page {{ productStore.pagination.current_page }}
                            of {{ productStore.pagination.last_page }}
                        </span>

                        <button class="btn btn-sm btn-outline-secondary" :disabled="productStore.pagination.current_page ===
                            productStore.pagination.last_page
                            " @click="changePage(productStore.pagination.current_page + 1)">
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div v-if="showModalAddProduct" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable product-modal">
            <div class="modal-content border-0 shadow">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        Add Product
                    </h5>

                    <button type="button" class="modal-close-btn" @click="closeAddProduct" aria-label="Close">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="row g-3 g-md-4">
                        <div v-if="productStore.error" class="alert alert-danger mb-3">
                            {{ productStore.error }}
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Product Name</label>
                            <input v-model="form.name" type="text" class="form-control"
                                placeholder="Enter product name">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">SKU</label>
                            <input v-model="form.sku" type="text" class="form-control" placeholder="Enter SKU">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Barcode</label>
                            <input v-model="form.barcode" type="text" class="form-control" placeholder="Enter barcode">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Unit</label>
                            <input v-model="form.unit" type="text" class="form-control" placeholder="pcs">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Category</label>

                            <select v-model="form.category_id" class="form-select">
                                <option value="">Select Category</option>

                                <option v-for="item in category" :key="item.id" :value="item.id">
                                    {{ item.name }}
                                </option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Brand</label>
                            <select v-model="form.brand_id" class="form-select">
                                <option value="">Select Brands</option>

                                <option v-for="item in brand" :key="item.id" :value="item.id">
                                    {{ item.name }}
                                </option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Cost Price</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input v-model="form.cost_price" type="number" class="form-control" placeholder="0.00">
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Sale Price</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input v-model="form.sale_price" type="number" class="form-control" placeholder="0.00">
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Status</label>
                            <select v-model="form.status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <input type="file" class="form-control" accept=".jpg,.jpeg,.png,.webp"
                            @change="form.image = $event.target.files[0]">

                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea v-model="form.description" class="form-control" rows="3"
                                placeholder="Enter description"></textarea>
                        </div>

                    </div>
                </div>
                <div class="modal-footer flex-column flex-sm-row modal-footer-actions">
                    <button type="button" class="btn btn-light footer-btn" @click="closeAddProduct">
                        Cancel
                    </button>

                    <button type="button" class="btn btn-primary footer-btn" @click="Createproduct">
                        <i class="bi bi-save me-1"></i>
                        Save Product
                    </button>
                </div>

            </div>
        </div>
    </div>

    <div v-if="showModalProductDetail" class="modal fade show d-block" tabindex="-1"
        style="background: rgba(0, 0, 0, 0.5);">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable product-modal">
            <div class="modal-content border-0 shadow">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        Product Details
                    </h5>

                    <button type="button" class="modal-close-btn" @click="closeProductDetail" aria-label="Close">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div class="modal-body" v-if="productStore.product">
                    <div class="row g-3 g-md-4">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Product Name</label>
                            <input class="form-control readonly-field" :value="productStore.product.name" readonly>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">SKU</label>
                            <input class="form-control readonly-field" :value="productStore.product.sku" readonly>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Barcode</label>
                            <input class="form-control readonly-field" :value="productStore.product.barcode" readonly>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Category</label>
                            <input class="form-control readonly-field"
                                :value="productStore.product.category?.name || '-'" readonly>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Brand</label>
                            <input class="form-control readonly-field" :value="productStore.product.brand?.name || '-'"
                                readonly>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Unit</label>
                            <input class="form-control readonly-field" :value="productStore.product.unit" readonly>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Cost Price</label>
                            <input class="form-control readonly-field" :value="productStore.product.cost_price"
                                readonly>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Sale Price</label>
                            <input class="form-control readonly-field" :value="productStore.product.sale_price"
                                readonly>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Stock</label>
                            <input class="form-control readonly-field"
                                :value="productStore.product.inventory_stocks_sum_quantity ?? 0" readonly>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Status</label>
                            <input class="form-control readonly-field" :value="productStore.product.status" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control readonly-field" rows="3"
                                readonly>{{ productStore.product.description || '-' }}</textarea>
                        </div>
                    </div>
                </div>

                <div v-else class="modal-body text-center py-5">
                    <div class="spinner-border text-primary"></div>
                    <p class="text-muted mt-2 mb-0">Loading product...</p>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="closeProductDetail">
                        Close
                    </button>
                </div>

            </div>
        </div>
    </div>
    <div v-if="ShowModalEditProduct" class="modal fade show d-block" tabindex="-1"
        style="background: rgba(0, 0, 0, 0.5);">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable product-modal">
            <div class="modal-content border-0 shadow">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        Product Details
                    </h5>

                    <button type="button" class="modal-close-btn" @click="closemodaleditproduct" aria-label="Close">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div class="modal-body" v-if="productStore.product">
                    <div class="row g-3 g-md-4">

                        <div class="col-12 col-md-6">
                            <label class="form-label">Product Name</label>
                            <input v-model="editForm.name" class="form-control">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">SKU</label>
                            <input v-model="editForm.sku" class="form-control">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Barcode</label>
                            <input v-model="editForm.barcode" class="form-control">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Category</label>
                            <select v-model="editForm.category_id" class="form-select">
                                <option value="">Select Category</option>
                                <option v-for="item in category" :key="item.id" :value="item.id">
                                    {{ item.name }}
                                </option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Brand</label>
                            <select v-model="editForm.brand_id" class="form-select">
                                <option value="">Select Brand</option>
                                <option v-for="item in brand" :key="item.id" :value="item.id">
                                    {{ item.name }}
                                </option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Unit</label>
                            <input v-model="editForm.unit" class="form-control">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Cost Price</label>
                            <input v-model="editForm.cost_price" type="number" class="form-control">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Sale Price</label>
                            <input v-model="editForm.sale_price" type="number" class="form-control">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Stock</label>
                            <input class="form-control readonly-field"
                                :value="productStore.product.inventory_stocks_sum_quantity ?? 0" readonly>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Status</label>
                            <select v-model="editForm.status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Product Image</label>
                            <input type="file" class="form-control" accept=".jpg,.jpeg,.png,.webp"
                                @change="editForm.image = $event.target.files[0]">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea v-model="editForm.description" class="form-control" rows="3"></textarea>
                        </div>

                    </div>
                </div>
                <div v-else class="modal-body text-center py-5">
                    <div class="spinner-border text-primary"></div>
                    <p class="text-muted mt-2 mb-0">Loading product...</p>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" @click="closemodaleditproduct">
                        Close
                    </button>
                    <button class="btn btn-sm btn-outline-primary me-1" @click="editProduct">
                        <i class="bi bi-pencil"></i>
                        Edit Product
                    </button>
                </div>

            </div>
        </div>
    </div>
    <div v-if="showDeleteModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0, 0, 0, 0.5);">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">
                        Delete Product
                    </h5>

                    <button type="button" class="btn-close" @click="closeDeleteModal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">
                        Are you sure you want to delete this product?
                    </p>

                    <strong>
                        {{ deleteProductName }}
                    </strong>
                </div>
                <div class="modal-footer">

                    <button type="button" class="btn btn-secondary" @click="closeDeleteModal">
                        Cancel
                    </button>

                    <button type="button" class="btn btn-danger" @click="deleteProduct()">
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

/* ---- Overflow safety: keep all text inside its box ---- */

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

.nav-link span,
.brand-text,
.topbar-title {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
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

.stock-card {
    background: #fef2f2;
    border-left-color: #dc2626 !important;
    color: #991b1b;
}

.stock-card small {
    color: #ef4444;
}

.stock-card .icon-box {
    background: #fee2e2;
    color: #dc2626;
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

/* ---- Product image / no-image (base sizing, all breakpoints) ---- */

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

/* ---- Responsive helpers ---- */

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

/* Only go full width below the row-layout breakpoint (matches flex-lg-row) */
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

/* ---- Modal clean-up ---- */

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

/* Mobile: near full-screen sheet, no awkward side gaps */
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

/* Tablet and below: let filters wrap in two even columns */
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

/* Mobile: stack everything, shrink paddings, keep table scrollable */
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