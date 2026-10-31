<script setup>
import { onMounted, ref } from 'vue'
import { useAuthStore } from '../stores/auth'
import router from '@/router'

const auth = useAuthStore()

const email = ref('')
const password = ref('')
const remember = ref(false)
const error = ref('')
const errors = ref({})
const loading = ref(false)
const success = ref(false)
const opening = ref(false)
const entering = ref(true)

onMounted(() => {
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            entering.value = false
        })
    })
})

const SUCCESS_PAUSE = 500
const DOOR_DURATION = 1100

const wait = ms => new Promise(resolve => setTimeout(resolve, ms))

const validateLogin = () => {
    errors.value = {}

    if (!email.value.trim()) {
        errors.value.email = 'Email is required'
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
        errors.value.email = 'Please enter a valid email'
    }

    if (!password.value) {
        errors.value.password = 'Password is required'
    } else if (password.value.length < 8) {
        errors.value.password = 'Password must be at least 8 characters'
    }

    return Object.keys(errors.value).length === 0
}

const login = async () => {
    if (success.value) return

    error.value = ''

    if (!validateLogin()) {
        return
    }
    loading.value = true
    try {
        await auth.login(
            email.value.trim(),
            password.value,
            remember.value
        )

        loading.value = false
        success.value = true
        await wait(SUCCESS_PAUSE)

        opening.value = true
        await wait(DOOR_DURATION)

        router.push('/otp')
    } catch (err) {
        error.value = auth.error || 'Invalid email or password'
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <div class="login-page" :class="{ success, opening, entering }">
        <div class="door-reveal" aria-hidden="true">
            <div class="reveal-content">
                <div class="reveal-check">
                    <i class="bi bi-check-lg"></i>
                </div>
                <h3>Login successful</h3>
                <p>Sending your verification code...</p>
                <div class="reveal-bar"><span></span></div>
            </div>
        </div>

        <div class="login-form">
            <div class="form-wrap">
                <div class="logo">
                    <svg width="52" height="46" viewBox="0 0 52 46" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4 20L20 20L36 4L20 4L4 20Z" fill="#2563EB" />
                        <path d="M16 42L32 42L48 26L32 26L16 42Z" fill="#4338CA" />
                    </svg>
                </div>
                <div class="form-header">
                    <h2>Welcome Back!</h2>
                    <p>Please enter your details</p>
                </div>
                <div v-if="error" class="alert-error">
                    <i class="bi bi-exclamation-circle"></i>
                    {{ error }}
                </div>

                <form novalidate @submit.prevent="login">
                    <div class="field">
                        <label for="email">Email Address</label>
                        <input id="email" v-model="email" type="email" placeholder="Input your email address"
                            :class="{ 'input-error': errors.email }" :disabled="success" @input="errors.email = ''">
                        <small v-if="errors.email" class="field-error text-danger">
                            {{ errors.email }}
                        </small>
                    </div>

                    <div class="field">
                        <label for="password">Password</label>
                        <input id="password" v-model="password" type="password" placeholder="Input your password"
                            :class="{ 'input-error': errors.password }" :disabled="success"
                            @input="errors.password = ''">
                        <small v-if="errors.password" class="field-error">
                            {{ errors.password }}
                        </small>
                    </div>

                    <div class="row-between">
                        <label class="remember">
                            <input type="checkbox" v-model="remember" :disabled="success">
                            <span>Remember me</span>
                        </label>

                        <a @click.prevent="router.push({ name: 'Recoveremailpage' })" class="link"
                            style="cursor: pointer;">
                            Forgot Password?
                        </a>
                    </div>
                    <button type="submit" class="submit-btn" :disabled="loading || success">
                        <span v-if="loading">
                            <i class="bi bi-arrow-repeat spin"></i>
                            Signing in...
                        </span>

                        <span v-else-if="success" class="submit-label">
                            <i class="bi bi-check-circle"></i>
                            Success
                        </span>

                        <span v-else class="submit-label">
                            Login
                            <i class="bi bi-arrow-right"></i>
                        </span>
                    </button>
                </form>

                <div class="social-divider">
                    <span>OR</span>
                </div>

                <div class="social-login">
                    <button type="button" class="social-btn google-btn">
                        <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="Google">
                        Continue with Google
                    </button>

                    <button type="button" class="social-btn telegram-btn">
                        <i class="bi bi-telegram"></i>
                        Continue with Telegram
                    </button>
                </div>

                <p class="terms">
                    By creating an account, you agree to our
                    <a href="#" class="link">Terms of Service</a> and
                    <a href="#" class="link">Privacy Policy</a>
                </p>
            </div>
        </div>
        <div class="showcase">
            <div class="showcase-card">
                <div class="scene">
                    <svg class="scene-bg" viewBox="0 0 400 320" preserveAspectRatio="none">
                        <line x1="0" y1="80" x2="400" y2="80" stroke="rgba(255,255,255,0.14)" stroke-width="1" />
                        <line x1="0" y1="170" x2="400" y2="170" stroke="rgba(255,255,255,0.14)" stroke-width="1" />
                        <line x1="130" y1="0" x2="130" y2="320" stroke="rgba(255,255,255,0.1)" stroke-width="1" />
                        <line x1="290" y1="0" x2="290" y2="320" stroke="rgba(255,255,255,0.1)" stroke-width="1" />
                        <ellipse cx="70" cy="55" rx="34" ry="14" fill="rgba(255,255,255,0.16)" />
                        <ellipse cx="330" cy="90" rx="42" ry="16" fill="rgba(255,255,255,0.14)" />
                    </svg>

                    <img class="figure" src="@/assets/remote-worker.png" alt="Person working on a laptop">
                </div>

                <div class="showcase-copy">
                    <h3>Seamless work experience</h3>
                    <p>Everything you need in an easily customizable dashboard</p>
                    <div class="dots">
                        <span class="dot active"></span>
                        <span class="dot"></span>
                        <span class="dot"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
* {
    box-sizing: border-box;
}

.login-page {
    position: relative;
    min-height: 100vh;
    min-height: 100dvh;
    width: 100%;
    display: grid;
    grid-template-columns: 50% 50%;
    background: #fff;
    overflow: hidden;
}

.login-form,
.showcase {
    position: relative;
    z-index: 1;
    transition:
        transform 1s cubic-bezier(0.76, 0, 0.24, 1),
        box-shadow 1s ease;
    will-change: transform;
}

.opening .login-form {
    transform: translateX(-100%);
    box-shadow: 16px 0 50px rgba(17, 24, 39, 0.18);
    pointer-events: none;
}

.opening .showcase {
    transform: translateX(100%);
    box-shadow: -16px 0 50px rgba(17, 24, 39, 0.25);
    pointer-events: none;
}

.entering .login-form {
    transform: translateX(-100%);
    box-shadow: 16px 0 50px rgba(17, 24, 39, 0.18);
}

.entering .showcase {
    transform: translateX(100%);
    box-shadow: -16px 0 50px rgba(17, 24, 39, 0.25);
}

.door-reveal {
    position: absolute;
    inset: 0;
    z-index: 0;
    display: grid;
    place-items: center;
    background: radial-gradient(circle at 50% 40%, #ffffff 0%, #eef2ff 60%, #e0e7ff 100%);
}

.reveal-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    text-align: center;
    opacity: 0;
    transform: scale(0.9);
}

.opening .reveal-content {
    opacity: 1;
    transform: none;
    transition:
        opacity 0.6s ease 0.35s,
        transform 0.7s cubic-bezier(0.22, 1, 0.36, 1) 0.35s;
}

.reveal-check {
    display: grid;
    place-items: center;
    width: 72px;
    height: 72px;
    margin-bottom: 8px;
    border-radius: 50%;
    background: #12b76a;
    color: #fff;
    font-size: 34px;
    box-shadow: 0 0 0 0 rgba(18, 183, 106, 0.45);
}

.opening .reveal-check {
    animation: check-pulse 1.4s ease-out infinite;
}

@keyframes check-pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(18, 183, 106, 0.45);
    }

    100% {
        box-shadow: 0 0 0 28px rgba(18, 183, 106, 0);
    }
}

.reveal-content h3 {
    margin: 0;
    color: #101828;
    font-size: 24px;
    font-weight: 700;
}

.reveal-content p {
    margin: 0;
    color: #667085;
    font-size: 14px;
}

.reveal-bar {
    width: 160px;
    height: 4px;
    margin-top: 14px;
    overflow: hidden;
    border-radius: 4px;
    background: #d9e0f5;
}

.reveal-bar span {
    display: block;
    width: 100%;
    height: 100%;
    border-radius: 4px;
    background: #4f46e5;
    transform: scaleX(0);
    transform-origin: left;
}

.opening .reveal-bar span {
    transform: scaleX(1);
    transition: transform 1s ease 0.5s;
}

.success .submit-btn {
    background: #12b76a;
    opacity: 1;
    animation: btn-pop 0.45s cubic-bezier(0.22, 1, 0.36, 1);
}

@keyframes btn-pop {
    0% {
        transform: scale(1);
    }

    50% {
        transform: scale(1.03);
    }

    100% {
        transform: scale(1);
    }
}

.field input.input-error {
    border: 1px solid #dc3545;
}

.field input.input-error:focus {
    border: 1px solid #dc3545;
    box-shadow: none;
}

.field-error {
    display: block;
    margin-top: 5px;
    color: #dc3545;
    font-size: 12px;
}

.google-btn img {
    width: 18px;
    height: 18px;
}

.login-form {
    min-width: 0;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px;
    background: #f9fafb;
}

.form-wrap {
    width: min(100%, 500px);
    min-height: 620px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 28px;
    padding: 42px 50px;
    box-shadow: 0 12px 40px rgba(17, 24, 39, 0.08);
}

.logo {
    display: flex;
    justify-content: center;
    margin-bottom: 20px;
}

.form-header {
    text-align: center;
    margin-bottom: 28px;
}

.form-header h2 {
    font-size: 28px;
    font-weight: 700;
    margin: 0 0 8px;
    color: #111827;
}

.form-header p {
    margin: 0;
    color: #6b7280;
    font-size: 15px;
}

.alert-error {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #fdf1ef;
    color: #b3401f;
    border-radius: 8px;
    padding: 12px 14px;
    font-size: 14px;
    margin-bottom: 20px;
}

.field {
    margin-bottom: 20px;
}

.field label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #111827;
    margin-bottom: 8px;
}

.field input {
    width: 100%;
    height: 48px;
    border: 1px solid #d1d5db;
    border-radius: 9px;
    padding: 0 14px;
    font-size: 15px;
    font-family: inherit;
    color: #111827;
    outline: none;
    background: #fff;
    transition: border-color 0.15s, box-shadow 0.15s;
}

.field input:focus {
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
}

.row-between {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 22px;
    flex-wrap: wrap;
}

.remember {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: #374151;
    cursor: pointer;
}

.remember input {
    width: 16px;
    height: 16px;
    accent-color: #4f46e5;
}

.link {
    color: #4338ca;
    text-decoration: none;
    font-size: 14px;
}

.link:hover {
    text-decoration: underline;
}

.submit-btn {
    width: 100%;
    height: 48px;
    border: none;
    border-radius: 9px;
    background: linear-gradient(135deg, #6366f1, #4338ca);
    color: #fff;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s;
}

.submit-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.submit-label {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.social-divider {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 22px 0 16px;
    color: #9ca3af;
    font-size: 12px;
}

.social-divider::before,
.social-divider::after {
    content: "";
    flex: 1;
    height: 1px;
    background: #e5e7eb;
}

.social-login {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.social-btn {
    width: 100%;
    height: 44px;
    border: 1px solid #e5e7eb;
    border-radius: 9px;
    background: #fff;
    font-size: 14px;
    font-weight: 500;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    cursor: pointer;
    transition: 0.2s;
}

.social-btn:hover {
    background: #f9fafb;
}

.google-btn i {
    color: #4285f4;
    font-size: 17px;
}

.telegram-btn i {
    color: #229ed9;
    font-size: 19px;
}

.terms {
    margin: 18px 0 0;
    font-size: 12px;
    line-height: 1.5;
    color: #6b7280;
    text-align: center;
}

.spin {
    display: inline-block;
    animation: spin 1s linear infinite;
    margin-right: 6px;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }

    to {
        transform: rotate(360deg);
    }
}

.showcase {
    min-width: 0;
    min-height: 100vh;
    background: linear-gradient(160deg, #8b7bf7, #5b4fe0);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px;
}

.showcase-card {
    width: min(100%, 500px);
    min-height: 620px;
    background: linear-gradient(180deg,
            rgba(255, 255, 255, 0.08),
            rgba(255, 255, 255, 0));
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: 28px;
    padding: 36px 34px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    overflow: hidden;
}

.scene {
    position: relative;
    flex: 1;
    min-height: 330px;
    margin-bottom: 20px;
}

.scene-bg {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
}

.figure {
    position: absolute;
    right: 0;
    bottom: 0;
    width: 72%;
    max-height: 100%;
    height: auto;
    object-fit: contain;
    filter: drop-shadow(0 16px 20px rgba(0, 0, 0, 0.18));
}

.showcase-copy {
    color: #fff;
    text-align: left;
}

.showcase-copy h3 {
    margin: 0 0 10px;
    font-size: 24px;
    font-weight: 700;
}

.showcase-copy p {
    margin: 0 0 22px;
    font-size: 14px;
    color: rgba(255, 255, 255, 0.85);
    line-height: 1.6;
}

.dots {
    display: flex;
    gap: 6px;
}

.dot {
    width: 8px;
    height: 8px;
    border-radius: 4px;
    background: rgba(255, 255, 255, 0.4);
}

.dot.active {
    width: 22px;
    background: #fff;
}

@media (min-width: 861px) and (max-width: 1250px) {

    .login-form,
    .showcase {
        padding: 20px;
    }

    .form-wrap,
    .showcase-card {
        width: 100%;
        max-width: 520px;
        min-height: 650px;
    }

    .form-wrap {
        padding: 40px 35px;
    }

    .showcase-card {
        padding: 34px 30px;
    }

    .scene {
        min-height: 380px;
    }

    .figure {
        width: 78%;
    }
}

@media (max-width: 860px) {
    .login-page {
        grid-template-columns: 1fr;
    }

    .showcase {
        display: none;
    }

    .login-form {
        min-height: 100dvh;
        padding: 16px;
    }

    .form-wrap {
        width: 100%;
        max-width: 500px;
        min-height: 600px;
        padding: 35px 28px;
    }
}

@media (max-width: 500px) {
    .login-form {
        padding: 10px;
    }

    .form-wrap {
        min-height: 560px;
        padding: 28px 18px;
        border-radius: 20px;
    }

    .logo svg {
        width: 42px;
        height: 38px;
    }

    .form-header {
        margin-bottom: 22px;
    }

    .form-header h2 {
        font-size: 21px;
    }

    .form-header p {
        font-size: 12px;
    }

    .field {
        margin-bottom: 16px;
    }

    .field label {
        font-size: 12px;
    }

    .field input,
    .submit-btn {
        height: 42px;
        font-size: 13px;
    }

    .remember,
    .link {
        font-size: 11px;
    }

    .social-btn {
        height: 42px;
        font-size: 12px;
    }

    .terms {
        font-size: 10px;
    }
}
</style>