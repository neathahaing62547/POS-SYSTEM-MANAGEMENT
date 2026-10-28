import { defineStore } from 'pinia'
import api from '@/services/api'

export const usePosStore = defineStore('pos', {
    state: () => ({
        products: [],
        storeName: '',
        categories: [],
        cart: { items: [], total: 0 },
        paymentMethods: [],
        checkoutData: null,
        productsLoading: false,
        cartLoading: false,
        error: '',
        pageError: ''
    }),
    actions: {
        async getProducts({ search = '', categoryId = '' } = {}) {
            this.productsLoading = true
            this.error = ''
            try {
                const response = await api.get('/pos/products', {
                    params: {
                        search,
                        category_id: categoryId || undefined
                    }
                })
                this.products = response.data.data
                this.storeName = response.data.store?.name || ''
                return this.products
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to load POS products.'
                throw error
            } finally {
                this.productsLoading = false
            }
        },
        async getCategories() {
            this.error = ''
            try {
                const response = await api.get('/pos/categories')
                this.categories = response.data.data
                return this.categories
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to load POS categories.'
                throw error
            }
        },
        async getCart() {
            this.cartLoading = true
            this.error = ''
            try {
                const response = await api.get('/pos/cart')
                this.cart = response.data.data
                this.cart.total = response.data.total
                return this.cart
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to load the cart.'
                throw error
            } finally {
                this.cartLoading = false
            }
        },
        async addToCart(productId, quantity = 1) {
            try {
                return (await api.post('/pos/cart/items', {
                    product_id: productId,
                    quantity
                })).data
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to add product to cart.'
                throw error
            }
        },
        async removeCartItem(itemId) {
            try {
                return (await api.delete(`/pos/cart/items/${itemId}`)).data
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to remove cart item.'
                throw error
            }
        },
        async changeCartQuantity(itemId, action) {
            const endpoint = action === 'increase' ? 'increase' : 'decrease'
            try {
                return (await api.patch(`/pos/cart/items/${itemId}/${endpoint}`)).data
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to update cart quantity.'
                throw error
            }
        },
        async getCheckout() {
            try {
                return (await api.get('/pos/checkout')).data.data
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to prepare checkout.'
                throw error
            }
        },
        async getPaymentMethods() {
            try {
                const response = await api.get('/pos/payment-methods')
                this.paymentMethods = response.data.data
                return this.paymentMethods
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to load payment methods.'
                throw error
            }
        },
      
async confirmCheckout(paymentMethodId) {
    try {
        const response = await api.post('/pos/checkout/confirm', {
            payment_method: paymentMethodId
        })
        try {
            await this.getCart()
        } catch (cartError) {
            this.cart = {
                items: [],
                total: 0
            }
        }
        return response.data
    } catch (error) {
        this.error =
            error.response?.data?.message ||
            'Checkout failed.'
        throw error
    }
},
     async createAbaPayWayPayment(payload) {
    try {
        return (
            await api.post('/pos/aba-payway', payload)
        ).data
    } catch (error) {
        this.error =
            error.response?.data?.message ||
            'Failed to generate ABA PayWay QR.'
        throw error
    }
}
    }
})
