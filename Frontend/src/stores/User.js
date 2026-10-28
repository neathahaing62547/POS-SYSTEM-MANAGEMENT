import { defineStore } from 'pinia'
import api from '@/services/api'

export const useUserStore = defineStore('users', {
    state: () => ({
        users: [],
        roles: [],
        loading: false,
        rolesLoading: false,
        error: '',
        rolesError: ''
    }),
    actions: {
        async getUsers() {
            this.loading = true
            this.error = ''
            try {
                const response = await api.get('/users')
                this.users = response.data
                return this.users
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to load users.'
                throw error
            } finally {
                this.loading = false
            }
        },
        async getRoles() {
            this.rolesLoading = true
            this.rolesError = ''
            try {
                const response = await api.get('/roles')
                this.roles = response.data
                return this.roles
            } catch (error) {
                this.rolesError = error.response?.data?.message || 'Failed to load roles.'
                throw error
            } finally {
                this.rolesLoading = false
            }
        },
        async createUser(payload) {
            this.error = ''
            try {
                await api.post('/users', payload)
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to create user.'
                throw error
            }
        },
        async updateUser(id, payload) {
            this.error = ''
            try {
                await api.put(`/users/${id}`, payload)
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to update user.'
                throw error
            }
        },
        async deleteUser(id) {
            this.error = ''
            try {
                await api.delete(`/users/${id}`)
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to delete user.'
                throw error
            }
        }
    }
})
