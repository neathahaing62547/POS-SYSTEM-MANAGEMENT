```vue
<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const auth = useAuthStore()

const otp = ref('')
const error = ref('')

const goBack = () => {
    router.push('/login')
}

const verifyOtp = async () => {
    error.value = ''

    if (!otp.value || otp.value.length !== 6) {
        error.value = 'Please enter the 6-digit OTP'
        return
    }

    try {
        const response = await auth.verifyOtp(otp.value)
        console.log(response)

        router.push('/dashboard')
    } catch (err) {
        error.value = auth.error || 'Invalid OTP'
    }
}
</script>

<template>
    <div class="otp-page">

        <div class="otp-card">

            <button type="button" class="back-btn" @click="goBack">
                <i class="bi bi-arrow-left"></i>
                Back to login
            </button>

            <div class="form-content">

                <div class="logo">
                    <svg width="52" height="46" viewBox="0 0 52 46" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4 20L20 20L36 4L20 4L4 20Z" fill="#2563EB" />
                        <path d="M16 42L32 42L48 26L32 26L16 42Z" fill="#4338CA" />
                    </svg>
                </div>

                <div class="form-header">
                    <h1>Verify your email</h1>
                    <p>
                        Enter the 6-digit code sent to your email
                    </p>
                </div>

                <div v-if="error" class="alert-error">
                    <i class="bi bi-exclamation-circle"></i>
                    <span>{{ error }}</span>
                </div>

                <form @submit.prevent="verifyOtp">

                    <div class="field">
                        <label for="otp">Verification Code</label>

                        <input id="otp" v-model="otp" type="text" inputmode="numeric" autocomplete="one-time-code"
                            maxlength="6" pattern="[0-9]*" class="otp-input" placeholder="000000" required
                            @input="otp = otp.replace(/\D/g, '')" />
                    </div>

                    <button type="submit" class="submit-btn" :disabled="auth.loading">
                        <span v-if="auth.loading">
                            <i class="bi bi-arrow-repeat spin"></i>
                            Verifying...
                        </span>

                        <span v-else class="submit-label">
                            Verify OTP
                            <i class="bi bi-arrow-right"></i>
                        </span>
                    </button>

                </form>

                <div class="resend">
                    <span>Didn't receive the code?</span>
                    <button type="button">
                        Resend OTP
                    </button>
                </div>

                <p class="security-text">
                    <i class="bi bi-shield-check"></i>
                    Your verification code is secure and private.
                </p>

            </div>

        </div>

    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

* {
    box-sizing: border-box;
}

.otp-page {
    min-height: 100vh;
    min-height: 100dvh;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px;
    font-family: 'Inter', sans-serif;
    background: #f9fafb;
}

.otp-card {
    width: min(100%, 500px);
    min-height: 620px;
    display: flex;
    flex-direction: column;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 28px;
    padding: 36px 50px;
    box-shadow: 0 12px 40px rgba(17, 24, 39, 0.08);
}

.back-btn {
    align-self: flex-start;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: none;
    background: transparent;
    padding: 0;
    color: #6b7280;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: color 0.15s;
}

.back-btn:hover {
    color: #4338ca;
}

.form-content {
    width: 100%;
    margin: auto 0;
}

.logo {
    display: flex;
    justify-content: center;
    margin-bottom: 24px;
}

.form-header {
    text-align: center;
    margin-bottom: 32px;
}

.form-header h1 {
    margin: 0 0 8px;
    color: #111827;
    font-size: 28px;
    font-weight: 700;
}

.form-header p {
    margin: 0;
    color: #6b7280;
    font-size: 15px;
    line-height: 1.5;
}

.alert-error {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-bottom: 20px;
    padding: 12px 14px;
    border-radius: 8px;
    background: #fdf1ef;
    color: #b3401f;
    font-size: 14px;
}

.field {
    margin-bottom: 22px;
}

.field label {
    display: block;
    margin-bottom: 8px;
    color: #111827;
    font-size: 14px;
    font-weight: 600;
}

.otp-input {
    width: 100%;
    height: 58px;
    border: 1px solid #d1d5db;
    border-radius: 9px;
    padding: 0 14px;
    background: #ffffff;
    color: #111827;
    font-family: inherit;
    font-size: 24px;
    font-weight: 600;
    letter-spacing: 10px;
    text-align: center;
    outline: none;
    transition: border-color 0.15s, box-shadow 0.15s;
}

.otp-input::placeholder {
    color: #d1d5db;
    letter-spacing: 10px;
}

.otp-input:focus {
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
}

.submit-btn {
    width: 100%;
    height: 48px;
    border: none;
    border-radius: 9px;
    background: linear-gradient(135deg, #6366f1, #4338ca);
    color: #ffffff;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.15s;
}

.submit-btn:hover:not(:disabled) {
    opacity: 0.92;
}

.submit-btn:active:not(:disabled) {
    opacity: 0.85;
}

.submit-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.submit-label {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.resend {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    margin-top: 22px;
    font-size: 13px;
    color: #6b7280;
}

.resend button {
    border: none;
    padding: 0;
    background: transparent;
    color: #4338ca;
    font-family: inherit;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}

.resend button:hover {
    text-decoration: underline;
}

.security-text {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin: 28px 0 0;
    color: #9ca3af;
    font-size: 12px;
    text-align: center;
}

.security-text i {
    color: #6366f1;
}

.spin {
    display: inline-block;
    margin-right: 6px;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }

    to {
        transform: rotate(360deg);
    }
}


@media (max-width: 600px) {
    .otp-page {
        min-height: 100dvh;
        padding: 20px 16px;
        background: #f9fafb;
    }

    .otp-card {
        width: 100%;
        max-width: 500px;
        min-height: 560px;
        padding: 32px 24px;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 24px;
        box-shadow: 0 8px 30px rgba(17, 24, 39, 0.08);
    }

    .form-header h1 {
        font-size: 24px;
    }

    .otp-input {
        height: 54px;
        font-size: 22px;
        letter-spacing: 8px;
    }
}

@media (max-width: 380px) {
    .otp-page {
        padding: 16px 12px;
    }

    .otp-card {
        min-height: 540px;
        padding: 28px 20px;
        border-radius: 20px;
    }

    .form-header h1 {
        font-size: 22px;
    }

    .otp-input {
        letter-spacing: 6px;
    }
}



@media (max-width: 380px) {
    .form-header h1 {
        font-size: 22px;
    }

    .otp-input {
        letter-spacing: 6px;
    }
}
</style>
