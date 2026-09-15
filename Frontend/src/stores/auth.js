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

    const isAdmin = () => {
        return user.value?.roles?.[0]?.name === 'admin'
    }

    const isStaff = () => {
        return user.value?.roles?.[0]?.name === 'staff'
    }

    const can = (permission) => {
        if (isAdmin()) {
            return true
        }

        return user.value?.permissions?.includes(permission) || false
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

    const logout = () => {
        userId.value = null
        user.value = null
        token.value = null
        error.value = ''

        localStorage.removeItem('token')
        localStorage.removeItem('user')
    }

    return {
        userId,
        user,
        token,
        loading,
        error,
        login,
        verifyOtp,
        logout,
        isAdmin,
        isStaff,
        can
    }
})