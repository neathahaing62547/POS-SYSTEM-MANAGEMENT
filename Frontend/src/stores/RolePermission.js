import { defineStore } from 'pinia'
import api from '@/services/api'

export const useRolePermissionStore = defineStore('role-permissions', {
    state: () => ({
        roles: [],
        permissions: [],
        rolesLoading: false,
        permissionsLoading: false,
        error: ''
    }),
    actions: {
        async getRoles() {
            this.rolesLoading = true
            this.error = ''
            try {
                const response = await api.get('/roles')
                this.roles = response.data
                return this.roles
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to load roles.'
                throw error
            } finally {
                this.rolesLoading = false
            }
        },
        async createRole(payload) {
            this.error = ''
            try {
                await api.post('/roles', payload)
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to create role.'
                throw error
            }
        },
        async updateRole(id, payload) {
            this.error = ''
            try {
                await api.put(`/roles/${id}`, payload)
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to update role.'
                throw error
            }
        },
        async deleteRole(id) {
            this.error = ''
            try {
                await api.delete(`/roles/${id}`)
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to delete role.'
                throw error
            }
        },
        async getPermissions() {
            this.permissionsLoading = true
            this.error = ''
            try {
                const response = await api.get('/permissions')
                this.permissions = response.data
                return this.permissions
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to load permissions.'
                throw error
            } finally {
                this.permissionsLoading = false
            }
        },
        async createPermission(payload) {
            this.error = ''
            try {
                await api.post('/permissions', payload)
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to create permission.'
                throw error
            }
        },
        async updatePermission(id, payload) {
            this.error = ''
            try {
                await api.put(`/permissions/${id}`, payload)
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to update permission.'
                throw error
            }
        },
        async deletePermission(id) {
            this.error = ''
            try {
                await api.delete(`/permissions/${id}`)
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to delete permission.'
                throw error
            }
        }
    }
})
