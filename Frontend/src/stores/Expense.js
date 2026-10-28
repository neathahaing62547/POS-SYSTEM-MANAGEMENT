import { defineStore } from 'pinia'
import api from '@/services/api'

export const useExpenseStore = defineStore('expenses', {
    state: () => ({
        expenses: [],
        pagination: null,
        summary: { count: 0, total: 0 },
        loading: false,
        saving: false,
        error: ''
    }),
    actions: {
        async getExpenses(params = {}) {
            this.loading = true
            this.error = ''
            try {
                const response = await api.get('/expenses', { params })
                this.expenses = response.data.data.data
                this.pagination = response.data.data
                this.summary = response.data.summary
                return this.expenses
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to load expenses.'
                throw error
            } finally {
                this.loading = false
            }
        },
        async createExpense(payload) {
            this.saving = true
            this.error = ''
            try {
                return (await api.post('/expenses', payload)).data.data
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to create expense.'
                throw error
            } finally {
                this.saving = false
            }
        },
        async updateExpense(id, payload) {
            this.saving = true
            this.error = ''
            try {
                return (await api.put(`/expenses/${id}`, payload)).data.data
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to update expense.'
                throw error
            } finally {
                this.saving = false
            }
        },
        async deleteExpense(id) {
            this.saving = true
            this.error = ''
            try {
                await api.delete(`/expenses/${id}`)
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to delete expense.'
                throw error
            } finally {
                this.saving = false
            }
        }
    }
})
