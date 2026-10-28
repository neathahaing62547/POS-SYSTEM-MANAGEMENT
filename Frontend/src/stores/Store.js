import { defineStore } from 'pinia'
import api from '@/services/api'

export const useStoreStore = defineStore('stores', {
    state: () => ({
        stores: [],
        store: null,
        pagination: null,
        loading: false,
        error: ''
    }),
    actions: {
        async getStores(page = 1) {
            this.loading = true
            this.error = ''
            try {
                const response = await api.get('/stores', {
                    params: { page }
                })
                const paginated = response.data.data
                this.stores = paginated.data || []
                this.pagination = paginated
                return this.stores
            } catch (error) {
                this.error =
                    error.response?.data?.message || 'Failed to load stores.'
                throw error
            } finally {
                this.loading = false
            }
        },
        async getStore(id) {
            this.error = ''
            try {
                const response = await api.get(`/stores/${id}`)
                this.store = response.data.data
                return this.store
            } catch (error) {
                this.error =
                    error.response?.data?.message || 'Failed to load store.'
                throw error
            }
        },
        async createStore(payload) {
            this.error = ''
            try {
                const response = await api.post('/stores', payload)
                return response.data.data
            } catch (error) {
                this.error =
                    error.response?.data?.message || 'Failed to create store.'
                throw error
            }
        },
        async updateStore(id, payload) {
            this.error = ''

            try {
                const response = await api.put(`/stores/${id}`, payload)
                return response.data.data
            } catch (error) {
                this.error =
                    error.response?.data?.message || 'Failed to update store.'
                throw error
            }
        },
        async updateStoreStatus(id, status) {
            this.error = ''

            try {
                const response = await api.patch(`/stores/${id}/status`, { status })
                return response.data.data
            } catch (error) {
                this.error =
                    error.response?.data?.message || 'Failed to update store status.'
                throw error
            }
        },
        async deleteStore(id) {
            this.error = ''

            try {
                await api.delete(`/stores/${id}`)
            } catch (error) {
                this.error =
                    error.response?.data?.message || 'Failed to delete store.'
                throw error
            }
        }
    }
})
