import { defineStore } from 'pinia'
import api from '@/services/api'

export const useProductStore = defineStore('product', {
    state: () => ({
        products: [],
        product: null,
        category: [],
        brand: [],
        summary: {
            total: 0,
            active: 0,
            inactive: 0,
            stockAlert: 0
        },
        pagination: null,
        error: null,
        loading: false,
        filltercategorys: '',
        fillterstock: '',
        fillterstatus: '',
        filllterbrands: '',
    }),
    actions: {
        async getProducts(
            search = '',
            page = 1,
            category_id = '',
            brand_id = '',
            status = '',
            stock = ''
        ) {
            this.loading = true
            try {
                const response = await api.get('/products', {
                    params: {
                        search,
                        page,
                        category_id,
                        brand_id,
                        status,
                        stock
                    }
                })
                this.products = response.data.data.data

                this.summary = {
                    total: response.data.summary.total,
                    active: response.data.summary.active,
                    inactive: response.data.summary.inactive,
                    stockAlert: response.data.summary['stock alert']
                }
                this.pagination = response.data.data
                return response.data.data
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to load products'
                throw error
            } finally {
                this.loading = false
            }
        },
        async fillterProduct() {
            this.loading = true
            try {
                const response = await api.get('/product', {
                    params: {

                    }
                })
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to load products'
                throw error
            } finally {

            }
        },
        async getDetailProduct(id) {
            try {
                const response = await api.get(`/products/${id}`)
                this.product = response.data.data
                return response.data.data
            }
            catch (error) {
                this.error = error.response?.data?.message || 'Failed to load product'
                throw error
            }
        },
        async Getcategory() {
            const response = await api.get('/categories')
            this.category = response.data.data?.data || response.data.data || response.data
            return this.category
        },
        async Getbrands() {
            try {
                const response = await api.get('/brands')
                this.brand = response.data.data?.data || response.data.data || response.data
                return this.brand

            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to load product'
                throw error
            }
        },
        async createProduct(form) {
            this.loading = true
            this.error = null

            try {
                const formData = new FormData()
                formData.append('category_id', form.category_id)
                formData.append('brand_id', form.brand_id)
                formData.append('barcode', form.barcode)
                formData.append('sku', form.sku)
                formData.append('name', form.name)
                formData.append('description', form.description)
                formData.append('cost_price', form.cost_price)
                formData.append('sale_price', form.sale_price)
                formData.append('unit', form.unit)
                formData.append('status', form.status)

                if (form.image) {
                    formData.append('image', form.image)
                }
                const response = await api.post('/products', formData)
                this.products.unshift(response.data.data || response.data)
                return response.data

            } catch (error) {
                if (error.response?.status === 422) {
                    this.error = Object.values(error.response.data.errors)
                        .flat()
                        .join('\n')
                } else {
                    this.error = error.response?.data?.message || 'Failed to create product'
                }

                throw error
            } finally {
                this.loading = false
            }
        },
        async deleteProduct(id) {
            this.loading = true
            this.error = null
            try {
                const response = await api.delete(`/products/${id}`)

                return response.data
            }
            catch (error) {
                this.error =
                    error.response?.data?.message ||
                    'Failed to delete product'
                throw error
            } finally {
                this.loading = false
            }
        },
        async updateProduct(id, form) {
            this.loading = true
            this.error = null
            try {
                const formData = new FormData()
                formData.append('_method', 'PUT')
                formData.append('category_id', form.category_id)
                formData.append('brand_id', form.brand_id)
                formData.append('barcode', form.barcode)
                formData.append('sku', form.sku)
                formData.append('name', form.name)
                formData.append('description', form.description)
                formData.append('cost_price', form.cost_price)
                formData.append('sale_price', form.sale_price)
                formData.append('unit', form.unit)
                formData.append('status', form.status)

                if (form.image) {
                    formData.append('image', form.image)
                }
                const response = await api.post(
                    `/products/${id}`,
                    formData
                )
                return response.data
            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    'Failed to update product'

                throw error
            } finally {
                this.loading = false
            }
        }
    }

})