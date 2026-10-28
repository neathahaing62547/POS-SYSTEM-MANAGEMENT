import { defineStore } from 'pinia'
import api from '@/services/api'

export const useBrandStore = defineStore('brand', {
    state: () => ({
        brands: [],
        brand: null,
        summary: {
            total: 0,
            active: 0,
            inactive: 0
        },
        pagination: null,
        error: null,
        loading: false
    }),
    actions: {
        async getBrands(search = '', page = 1, status = '') {
            this.loading = true
            this.error = null
            try {
                const response = await api.get('/brands', {
                    params: { search, page, status }
                })
                this.brands = response.data.data.data
                this.pagination = response.data.data
                this.summary = {
                    total: response.data.summary.total_brands,
                    active: response.data.summary.active_brands,
                    inactive: response.data.summary.inactive_brands
                }
                return response.data.data
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to load brands'
                throw error
            } finally {
                this.loading = false
            }
        },
        async getDetailBrand(id) {
            this.error = null
            try {
                const response = await api.get(`/brands/${id}`)
                this.brand = response.data.data
                return response.data.data
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to load brand'
                throw error
            }
        },
        async createBrand(form) {
            this.loading = true
            this.error = null
            try {
                const formData = new FormData()
                formData.append('name', form.name)
                formData.append('description', form.description ?? '')
                formData.append('status', form.status)

                if (form.logo) {
                    formData.append('logo', form.logo)
                }
                const response = await api.post('/brands', formData)
                this.brands.unshift(response.data.data || response.data)
                return response.data
            } catch (error) {
                if (error.response?.status === 422) {
                    this.error = Object.values(error.response.data.errors)
                        .flat()
                        .join('\n')
                } else {
                    this.error = error.response?.data?.message || 'Failed to create brand'
                }
                throw error
            } finally {
                this.loading = false
            }
        },

        async updateBrand(id, form) {
            this.loading = true
            this.error = null
            try {
                const formData = new FormData()
                formData.append('_method', 'PUT')
                formData.append('name', form.name)
                formData.append('description', form.description ?? '')
                formData.append('status', form.status)

                if (form.logo) {
                    formData.append('logo', form.logo)
                }

                const response = await api.post(`/brands/${id}`, formData)
                return response.data
            } catch (error) {
                if (error.response?.status === 422) {
                    this.error = Object.values(error.response.data.errors)
                        .flat()
                        .join('\n')
                } else {
                    this.error = error.response?.data?.message || 'Failed to update brand'
                }
                throw error
            } finally {
                this.loading = false
            }
        },

        async deleteBrand(id) {
            this.loading = true
            this.error = null
            try {
                const response = await api.delete(`/brands/${id}`)
                return response.data
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to delete brand'
                throw error
            } finally {
                this.loading = false
            }
        }
    }
})