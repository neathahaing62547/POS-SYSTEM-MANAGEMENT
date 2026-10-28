
import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/services/api'

export const useInventoryStore = defineStore('inventory', () => {

    const stocks = ref([])

    const pagination = ref({
        current_page: 1,
        last_page: 1,
        from: 0,
        to: 0,
        total: 0
    })

    const summary = ref({
        total_products: 0,
        low_stock: 0,
        out_of_stock: 0
    })

    const history = ref([])

    const loading = ref(false)

    const error = ref('')

    const getInventory = async (search = '', page = 1) => {
        loading.value = true
        error.value = ''
        try {
            const res = await api.get('/inventorys', {
                params: {
                    search,
                    page
                }
            })
            stocks.value = res.data.data.data
            pagination.value = {
                current_page: res.data.data.current_page,
                last_page: res.data.data.last_page,
                from: res.data.data.from,
                to: res.data.data.to,
                total: res.data.data.total
            }
            summary.value = res.data.summary

        } catch (e) {

            error.value = e.response?.data?.message || 'Failed to load inventory'

        } finally {

            loading.value = false

        }
    }

    const getHistory = async (productId = null, page = 1) => {

        loading.value = true
        error.value = ''
        try {
            const res = await api.get('/stockhistory', {
                params: {
                    product_id: productId,
                    page
                }
            })
            history.value = res.data.data.data

            return res.data.data

        } catch (e) {

            error.value = e.response?.data?.message || 'Failed to load stock history'

            throw e

        } finally {

            loading.value = false

        }
    }

    const getInventoryById = async (inventoryStock) => {

        loading.value = true
        error.value = ''

        try {

            const res = await api.get(`/inventorys/${inventoryStock}`)

            return res.data.data

        } catch (e) {

            error.value = e.response?.data?.message || 'Failed to load inventory'

            throw e

        } finally {

            loading.value = false

        }
    }

    return {
        stocks,
        pagination,
        summary,
        history,
        loading,
        error,
        getInventory,
        getHistory,
        getInventoryById
    }

})
