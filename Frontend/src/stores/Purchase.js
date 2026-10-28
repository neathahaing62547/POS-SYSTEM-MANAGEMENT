import { defineStore } from 'pinia'
import api from '@/services/api'

export const usePurchaseStore = defineStore('Purchase', {

    state: () => ({
        purchases: [],
        purchaseDetail: null,
        stores: [],
        suppliers: [],
        products: [],
        summary: {
            total: 0,
            pending: 0,
            received: 0,
            cancelled: 0,
            total_cost: 0
        },
        pagination: null,
        loading: false,
        error: null
    }),
    actions: {
        async getPurchases(
            search = '',
            page = 1,
            status = '',
            store_id = '',
            supplier_id = ''
        ) {
            this.loading = true
            this.error = null
            try {
                const response = await api.get('/purchases', {
                    params: {
                        search,
                        page,
                        status,
                        store_id,
                        supplier_id
                    }
                })
                const data = response.data

                const paginated = data.data?.data
                    ? data.data
                    : data.data || data

                this.purchases = paginated.data || []
                this.pagination = paginated
                this.summary = data.summary || this.summary

            } catch (error) {

                this.error =
                    error.response?.data?.message ||
                    'Failed to load purchases'

                this.purchases = []

            } finally {
                this.loading = false
            }
        },
        async getPurchaseDetail(id) {
            this.error = null
            try {
                const response = await api.get(`/purchases/${id}`)
                const data = response.data

                this.purchaseDetail =
                    data.data || data

                return this.purchaseDetail

            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    'Failed to load purchase detail'
                throw error
            }
        },
        async updatePurchase(id, payload) {
            this.error = null

            try {
                const response = await api.put(`/purchases/${id}`, payload)

                return response.data.data

            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    'Failed to update purchase'

                throw error
            }
        },
        async receivePurchase(id) {
            this.error = null
            try {
                const response = await api.post(`/purchases/${id}/receive`)
                if (response.data?.purchase?.status !== 'received') {
                    throw new Error(
                        response.data?.message ||
                        response.data?.Message ||
                        'Purchase was not received.'
                    )
                }
                return response.data
            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    error.message ||
                    'Failed to receive purchase'

                throw error
            }
        },

        async getStore() {
            this.error = null

            try {
                const response = await api.get('/stores')

                const data = response.data

                this.stores =
                    data.data?.data ||
                    data.data ||
                    []

                return this.stores

            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    'Failed to load stores'

                throw error
            }
        },

        async getSubpllier() {
            this.error = null
            try {
                const response = await api.get('/suppliers')
                const data = response.data
                this.suppliers =
                    data.data?.data ||
                    data.data ||
                    []
                return this.suppliers

            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    'Failed to load suppliers'

                throw error
            }
        },

        async searchProduct(barcode) {
            this.error = null

            try {
                const response = await api.get('/products', {
                    params: {
                        search: barcode
                    }
                })

                const data = response.data

                this.products =
                    data.data?.data ||
                    data.data ||
                    []

                return this.products

            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    'Failed to search products'

                throw error
            }
        },

        async createPurchase(
            store_id,
            supplier_id,
            ordered_at,
            items
        ) {
            this.error = null

            try {
                const response = await api.post('/purchases', {
                    store_id,
                    supplier_id,
                    ordered_at,
                    items
                })

                return response.data

            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    'Failed to create purchase'

                throw error
            }
        }

    }

})