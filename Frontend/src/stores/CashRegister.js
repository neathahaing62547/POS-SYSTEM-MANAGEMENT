import { defineStore } from 'pinia'
import api from '@/services/api'

export const useCashRegisterStore = defineStore('cash-registers', {
    state: () => ({
        registers: [],
        selectedRegister: null,
        currentShift: null,
        loading: false,
        detailLoading: false,
        shiftLoading: false,
        transactions: [],
        transactionPagination: null,
        transactionSummary: null,
        transactionsLoading: false,
        saving: false,
        error: ''
    }),
    actions: {
        async getRegisters() {
            this.loading = true
            this.error = ''
            try {
                const response = await api.get('/cash-registers')
                this.registers = response.data
                return this.registers
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to load cash registers.'
                throw error
            } finally {
                this.loading = false
            }
        },
        async getRegister(id) {
            this.detailLoading = true
            this.error = ''
            try {
                const response = await api.get(`/cash-registers/${id}`)
                this.selectedRegister = response.data
                return this.selectedRegister
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to load register details.'
                throw error
            } finally {
                this.detailLoading = false
            }
        },
        async createRegister(payload) {
            this.saving = true
            this.error = ''
            try {
                const response = await api.post('/cash-registers', payload)
                return response.data.cash_register
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to create cash register.'
                throw error
            } finally {
                this.saving = false
            }
        },
        async getCurrentShift() {
            this.shiftLoading = true
            this.error = ''
            try {
                const response = await api.get('/cash-registers/current-shift')
                const shift = response.data
                this.currentShift = shift?.id && shift.status === 'open' ? shift : null
                return this.currentShift
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to load current shift.'
                throw error
            } finally {
                this.shiftLoading = false
            }
        },
        async openShift(payload) {
            this.saving = true
            this.error = ''
            try {
                const response = await api.post('/cash-registers/open-shift', payload)
                this.currentShift = response.data.shift
                return this.currentShift
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to open shift.'
                throw error
            } finally {
                this.saving = false
            }
        },
        async closeShift(payload) {
            this.saving = true
            this.error = ''
            try {
                const response = await api.post('/cash-registers/close-shift', payload)
                this.currentShift = null
                return response.data.shift
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to close shift.'
                throw error
            } finally {
                this.saving = false
            }
        },
        async getTransactions(params = {}) {
            this.transactionsLoading = true
            this.error = ''
            try {
                const response = await api.get('/cash-transactions', { params })
                const paginated = response.data.data
                this.transactions = paginated.data || []
                this.transactionPagination = paginated
                this.transactionSummary = response.data.summary
                return this.transactions
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to load cash transactions.'
                throw error
            } finally {
                this.transactionsLoading = false
            }
        },
        async recordCashIn(payload) {
            this.saving = true
            this.error = ''
            try {
                const response = await api.post('/cash-transactions/cash-in', payload)
                return response.data.data
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to record cash-in.'
                throw error
            } finally {
                this.saving = false
            }
        },
        async recordCashOut(payload) {
            this.saving = true
            this.error = ''
            try {
                const response = await api.post('/cash-transactions/cash-out', payload)
                return response.data.data
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to record cash-out.'
                throw error
            } finally {
                this.saving = false
            }
        }
    }
})
