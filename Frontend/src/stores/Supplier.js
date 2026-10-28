import { defineStore } from 'pinia'
import api from '@/services/api'
import { isFile } from '@/utils/isFile'

export const useSupplierStore = defineStore('supplier', {
    state: () => ({
        suppliers: [],
        supplier: null,
        loading: false,
        error: null,
        pagination: null,
        summary: {
            total: 0,
            active: 0,
            inactive: 0
        }
    }),
    actions: {
        buildFormData(form) {
            const formData = new FormData()

            Object.entries(form).forEach(([key, value]) => {
                if (key === 'image') {
                    if (isFile(value)) formData.append('image', value)
                    return
                }

                formData.append(key, value ?? '')
            })

            return formData
        },

        async getSuppliers(search = '', page = 1, status = '') {
            this.loading = true
            this.error = null

            try {
                const response = await api.get('/suppliers', {
                    params: { search, page, status }
                })

                this.suppliers = response.data.data.data || []
                this.pagination = response.data.data
                this.summary = {
                    total: response.data.data.total || 0,
                    active: response.data.data.data?.filter(item => item.status === 'active').length || 0,
                    inactive: response.data.data.data?.filter(item => item.status === 'inactive').length || 0
                }

                return response.data.data
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to load suppliers'
                throw error
            } finally {
                this.loading = false
            }
        },
        async getSupplierById(id) {
            this.loading = true
            this.error = null

            try {
                const response = await api.get(`/suppliers/${id}`)
                this.supplier = response.data.data
                return response.data.data
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to load supplier'
                throw error
            } finally {
                this.loading = false
            }
        },

        async createSupplier(form) {
            this.loading = true
            this.error = null

            try {
                const response = await api.post('/suppliers', this.buildFormData(form))
                this.suppliers.unshift(response.data.data)
                return response.data
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to create supplier'
                throw error
            } finally {
                this.loading = false
            }
        },
        async updateSupplier(id, form) {
            this.loading = true
            this.error = null
            try {
                const formData = this.buildFormData(form)
                formData.append('_method', 'PUT')
                const response = await api.post(`/suppliers/${id}`, formData)
                const index = this.suppliers.findIndex(item => item.id === id)
                if (index >= 0) this.suppliers[index] = response.data.data
                return response.data
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to update supplier'
                throw error
            } finally {
                this.loading = false
            }
        },

        async deleteSupplier(id) {
            this.loading = true
            this.error = null

            try {
                const response = await api.delete(`/suppliers/${id}`)
                this.suppliers = this.suppliers.filter(item => item.id !== id)
                return response.data
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to delete supplier'
                throw error
            } finally {
                this.loading = false
            }
        }
    }
})
