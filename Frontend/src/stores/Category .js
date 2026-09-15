import { defineStore } from 'pinia'
import api from '@/services/api'

export const useCategoryStore = defineStore('category', {
    state: () => ({
        categories: [],
        category: null,
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
        async getCategories(search = '', page = 1, status = '') {
            this.loading = true
            this.error = null
            try {
                const response = await api.get('/categories', {
                    params: { search, page, status }
                })

                this.categories = response.data.data.data
                this.pagination = response.data.data
                this.summary = {
                    total: response.data.summary.total_categories,
                    active: response.data.summary.active_categories,
                    inactive: response.data.summary.inactive_categories
                }

                return response.data.data
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to load categories'
                throw error
            } finally {
                this.loading = false
            }
        },
        async getDetailCategory(id) {
            this.error = null
            try {
                const response = await api.get(`/categories/${id}`)
                this.category = response.data.data
                return response.data.data
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to load category'
                throw error
            }
        },
        async createCategory(form) {
            this.loading = true
            this.error = null
            try {
                const formData = new FormData()
                formData.append('name', form.name)
                formData.append('description', form.description ?? '')
                formData.append('status', form.status)

                if (form.image) {
                    formData.append('image', form.image)
                }

                const response = await api.post('/categories', formData)
                this.categories.unshift(response.data.data || response.data)

                return response.data
            } catch (error) {
                if (error.response?.status === 422) {
                    this.error = Object.values(error.response.data.errors)
                        .flat()
                        .join('\n')
                } else {
                    this.error = error.response?.data?.message || 'Failed to create category'
                }
                throw error
            } finally {
                this.loading = false
            }
        },
        async updateCategory(id, form) {
            this.loading = true
            this.error = null
            try {
                const formData = new FormData()
                formData.append('_method', 'PUT')
                formData.append('name', form.name)
                formData.append('description', form.description ?? '')
                formData.append('status', form.status)

                if (form.image) {
                    formData.append('image', form.image)
                }

                const response = await api.post(`/categories/${id}`, formData)
                return response.data
            } catch (error) {
                if (error.response?.status === 422) {
                    this.error = Object.values(error.response.data.errors)
                        .flat()
                        .join('\n')
                } else {
                    this.error = error.response?.data?.message || 'Failed to update category'
                }
                throw error
            } finally {
                this.loading = false
            }
        },
        async deleteCategory(id) {
            this.loading = true
            this.error = null
            try {
                const response = await api.delete(`/categories/${id}`)
                return response.data
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to delete category'
                throw error
            } finally {
                this.loading = false
            }
        }
    }
})