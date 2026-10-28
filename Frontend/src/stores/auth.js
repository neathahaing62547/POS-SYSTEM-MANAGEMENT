import { ref } from 'vue'
import { defineStore } from 'pinia'
import api from '../services/api'

export const useAuthStore = defineStore('auth', () => {
    const userId = ref(null)
    const savedUser = localStorage.getItem('user')
    const user = ref(
        savedUser ? JSON.parse(savedUser) : null
    )
    const token = ref(
        localStorage.getItem('token') || null
    )
    const loading = ref(false)
    const error = ref('')
    const permissionsReady = ref(false)
    const isAdmin = () => {
        return user.value?.roles?.some(role => role.name === 'admin') || false
    }
    const isStaff = () => {
        return user.value?.roles?.some(role => role.name === 'staff') || false
    }
    const can = (permission) => {
        if (!permission) {
            return Boolean(user.value)
        }
        if (isAdmin()) {
            return true
        }

        const rolePermissions = user.value?.roles?.flatMap(role =>
            role.permissions?.map(item =>
                typeof item === 'string' ? item : item?.name
            ) || []
        ) || []

        return user.value?.permissions?.includes(permission) === true ||
            rolePermissions.includes(permission)
    }
    const login = async (email, password) => {
        error.value = ''
        loading.value = true
        try {
            const response = await api.post('/login', {
                email,
                password
            })
            userId.value = response.data.user_id

            return response.data
        } catch (err) {
            error.value =
                err.response?.data?.message ||
                'Invalid email or password'

            throw err
        } finally {
            loading.value = false
        }
    }
    const verifyOtp = async (otp) => {
        error.value = ''
        loading.value = true
        try {
            const response = await api.post('/login/verify-otp', {
                user_id: userId.value,
                otp
            })
            token.value = response.data.token
            user.value = response.data.user

            localStorage.setItem('token', token.value)
            localStorage.setItem(
                'user',
                JSON.stringify(user.value)
            )
            permissionsReady.value = true
            if (isAdmin()) {
                return '/dashboard'
            }
            if (isStaff()) {
                return '/pos'
            }
            return response.data
        } catch (err) {
            error.value =
                err.response?.data?.message ||
                'Invalid OTP'
            throw err
        } finally {
            loading.value = false
        }
    }
    const refreshUser = async () => {
        if (!token.value) {
            return null
        }

        error.value = ''

        try {
            const response = await api.get('/user')
            user.value = response.data
            permissionsReady.value = true
            localStorage.setItem('user', JSON.stringify(user.value))
            return user.value
        } catch (err) {
            error.value =
                err.response?.data?.message ||
                'Failed to refresh user permissions'
            throw err
        }
    }
    const recoverEmail = async (email) => {
        error.value = ''
        try {
            const response = await api.post('/recoveremail', {
                email
            })
            return response.data
        } catch (err) {
            console.error('Recover email error:', err)
            error.value =
                err.response?.data?.message ||
                'Something went wrong.'
            throw err
        }
    }
    const verifyRecoverOtp = async (email, otp) => {
        error.value = ''
        try {
            const response = await api.post('/recoveremail/otpverify', {
                email,
                otp
            })
            return response.data
        } catch (err) {
            console.error('Recover Email OTP:', err)
            error.value =
                err.response?.data?.message ||
                'Invalid or expired OTP.'
            throw err
        }
    }
    const resetPassword = async (email, otp, password) =>  {
        error.value = ''
        try {
            const res = await api.post('/changepassword', { email, otp, password })
            return res.data
        } catch (err) {
            error.value = err.response?.data?.message || 'Could not change password'
            throw err 
        }
    }
    const logout = () => {
        userId.value = null
        user.value = null
        token.value = null
        permissionsReady.value = false
        error.value = ''
        localStorage.removeItem('token')
        localStorage.removeItem('user')
    }
    return {
        userId,
        user,
        token,
        permissionsReady,
        loading,
        error,
        login,
        verifyOtp,
        refreshUser,
        logout,
        isAdmin,
        isStaff,
        can,
        recoverEmail,
        verifyRecoverOtp,
        resetPassword
    }
})