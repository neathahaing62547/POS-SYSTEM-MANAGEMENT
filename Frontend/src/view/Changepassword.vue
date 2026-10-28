<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const auth = useAuthStore()

const email = sessionStorage.getItem('recover_email') || ''
const otp = sessionStorage.getItem('recover_otp') || ''

const password = ref('')
const confirm = ref('')
const showPassword = ref(false)
const showConfirm = ref(false)
const error = ref('')
const loading = ref(false)

const strength = computed(() => {
    const p = password.value
    let s = 0
    if (p.length >= 8) s++
    if (/[A-Z]/.test(p) && /[a-z]/.test(p)) s++
    if (/\d/.test(p)) s++
    if (/[^A-Za-z0-9]/.test(p)) s++
    return s
})
const strengthLabel = computed(() => ['Too weak', 'Weak', 'Fair', 'Good', 'Strong'][strength.value])
const mismatch = computed(() => confirm.value && confirm.value !== password.value)

const goBack = () => router.push('/recoveremail')

const changePassword = async () => {
    error.value = ''
    if (password.value.length < 8) {
        error.value = 'Password must be at least 8 characters'
        return
    }
    if (password.value !== confirm.value) {
        error.value = 'Passwords do not match'
        return
    }
    loading.value = true
    try {
        await auth.resetPassword(email, otp, password.value)
        sessionStorage.removeItem('recover_email')
        sessionStorage.removeItem('recover_otp')
        router.push('/login')
    } catch (err) {
        error.value = auth.error || 'Could not change password'
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <div class="otp-page">
        <div class="otp-form">
            <div class="form-wrap">
                <button type="button" class="back-btn" @click="goBack">
                    <i class="bi bi-arrow-left"></i>
                    Back
                </button>

                <div class="form-content">
                    <div class="logo">
                        <svg width="52" height="46" viewBox="0 0 52 46" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M4 20L20 20L36 4L20 4L4 20Z" fill="#2563EB" />
                            <path d="M16 42L32 42L48 26L32 26L16 42Z" fill="#4338CA" />
                        </svg>
                    </div>

                    <div class="form-header">
                        <h2>Set a new password</h2>
                        <p>Choose a password you haven't used before.</p>
                    </div>

                    <div v-if="error" class="alert-error">
                        <i class="bi bi-exclamation-circle"></i>
                        <span>{{ error }}</span>
                    </div>

                    <form @submit.prevent="changePassword">
                        <div class="field">
                            <label for="password">New password</label>
                            <div class="input-wrap">
                                <input id="password" v-model="password" :type="showPassword ? 'text' : 'password'"
                                    autocomplete="new-password" placeholder="At least 8 characters" required />
                                <button type="button" class="eye" :aria-label="showPassword ? 'Hide password' : 'Show password'"
                                    @click="showPassword = !showPassword">
                                    <i :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                                </button>
                            </div>

                            <div v-if="password" class="strength">
                                <div class="bars">
                                    <span v-for="i in 4" :key="i" class="bar"
                                        :class="[i <= strength ? 'on' : '', 'lvl-' + strength]"></span>
                                </div>
                                <small>{{ strengthLabel }}</small>
                            </div>
                        </div>

                        <div class="field">
                            <label for="confirm">Confirm password</label>
                            <div class="input-wrap" :class="{ invalid: mismatch }">
                                <input id="confirm" v-model="confirm" :type="showConfirm ? 'text' : 'password'"
                                    autocomplete="new-password" placeholder="Re-enter your password" required />
                                <button type="button" class="eye" :aria-label="showConfirm ? 'Hide password' : 'Show password'"
                                    @click="showConfirm = !showConfirm">
                                    <i :class="showConfirm ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                                </button>
                            </div>
                            <small v-if="mismatch" class="hint-error">Passwords do not match</small>
                        </div>

                        <button type="submit" class="submit-btn" :disabled="loading">
                            <span v-if="loading">
                                <i class="bi bi-arrow-repeat spin"></i>
                                Saving...
                            </span>
                            <span v-else class="submit-label">
                                Change password
                                <i class="bi bi-arrow-right"></i>
                            </span>
                        </button>
                    </form>

                    <p class="security-text">
                        <i class="bi bi-shield-check"></i>
                        Your password is encrypted and never shared.
                    </p>
                </div>
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
                        <span class="dot"></span>
                        <span class="dot"></span>
                        <span class="dot active"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
* { box-sizing: border-box; }

.otp-page {
    min-height: 100vh;
    min-height: 100dvh;
    width: 100%;
    display: grid;
    grid-template-columns: 50% 50%;
    background: #fff;
}

/* ---------- LEFT ---------- */
.otp-form {
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
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 28px;
    padding: 36px 50px 42px;
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
    font-family: inherit;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: color 0.15s;
}
.back-btn:hover { color: #4338ca; }

.form-content { width: 100%; margin: auto 0; }

.logo { display: flex; justify-content: center; margin-bottom: 20px; }

.form-header { text-align: center; margin-bottom: 28px; }
.form-header h2 { font-size: 28px; font-weight: 700; margin: 0 0 8px; color: #111827; }
.form-header p { margin: 0; color: #6b7280; font-size: 15px; line-height: 1.5; }

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

.field { margin-bottom: 20px; }
.field label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #111827;
    margin-bottom: 8px;
}

/* password inputs */
.input-wrap { position: relative; }
.input-wrap input {
    width: 100%;
    height: 48px;
    padding: 0 46px 0 14px;
    border: 1px solid #d1d5db;
    border-radius: 9px;
    background: #fff;
    color: #111827;
    font-family: inherit;
    font-size: 15px;
    outline: none;
    transition: border-color 0.15s, box-shadow 0.15s;
}
.input-wrap input::placeholder { color: #9ca3af; }
.input-wrap input:focus {
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
}
.input-wrap.invalid input { border-color: #dc2626; }
.input-wrap.invalid input:focus { box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.12); }

.eye {
    position: absolute;
    top: 0;
    right: 0;
    width: 46px;
    height: 100%;
    border: none;
    background: transparent;
    color: #9ca3af;
    font-size: 17px;
    cursor: pointer;
    transition: color 0.15s;
}
.eye:hover { color: #4338ca; }

/* strength meter */
.strength { display: flex; align-items: center; gap: 10px; margin-top: 10px; }
.bars { flex: 1; display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; }
.bar { height: 4px; border-radius: 2px; background: #e5e7eb; transition: background 0.2s; }
.bar.on.lvl-1 { background: #ef4444; }
.bar.on.lvl-2 { background: #f59e0b; }
.bar.on.lvl-3 { background: #6366f1; }
.bar.on.lvl-4 { background: #10b981; }
.strength small { font-size: 12px; color: #6b7280; min-width: 54px; text-align: right; }

.hint-error { display: block; margin-top: 6px; font-size: 12px; color: #dc2626; }

.submit-btn {
    width: 100%;
    height: 48px;
    margin-top: 4px;
    border: none;
    border-radius: 9px;
    background: linear-gradient(135deg, #6366f1, #4338ca);
    color: #fff;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.15s;
}
.submit-btn:hover:not(:disabled) { opacity: 0.92; }
.submit-btn:disabled { opacity: 0.6; cursor: not-allowed; }
.submit-label { display: inline-flex; align-items: center; justify-content: center; gap: 8px; }

.security-text {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin: 24px 0 0;
    color: #9ca3af;
    font-size: 12px;
    text-align: center;
}
.security-text i { color: #6366f1; }

.spin { display: inline-block; margin-right: 6px; animation: spin 1s linear infinite; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

/* ---------- RIGHT ---------- */
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
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0));
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: 28px;
    padding: 36px 34px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    overflow: hidden;
}

.scene { position: relative; flex: 1; min-height: 330px; margin-bottom: 20px; }
.scene-bg { position: absolute; inset: 0; width: 100%; height: 100%; }
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

.showcase-copy { color: #fff; text-align: left; }
.showcase-copy h3 { margin: 0 0 10px; font-size: 24px; font-weight: 700; }
.showcase-copy p { margin: 0 0 22px; font-size: 14px; color: rgba(255, 255, 255, 0.85); line-height: 1.6; }
.dots { display: flex; gap: 6px; }
.dot { width: 8px; height: 8px; border-radius: 4px; background: rgba(255, 255, 255, 0.4); }
.dot.active { width: 22px; background: #fff; }

/* ---------- RESPONSIVE ---------- */
@media (min-width: 861px) and (max-width: 1250px) {
    .otp-form, .showcase { padding: 20px; }
    .form-wrap, .showcase-card { width: 100%; max-width: 520px; min-height: 650px; }
    .form-wrap { padding: 36px 35px 40px; }
    .showcase-card { padding: 34px 30px; }
    .scene { min-height: 380px; }
    .figure { width: 78%; }
}

@media (max-width: 860px) {
    .otp-page { grid-template-columns: 1fr; }
    .showcase { display: none; }
    .otp-form { min-height: 100dvh; padding: 16px; }
    .form-wrap { width: 100%; max-width: 500px; min-height: 600px; padding: 32px 28px 35px; }
}

@media (max-width: 500px) {
    .otp-form { padding: 10px; }
    .form-wrap { min-height: 560px; padding: 26px 18px 28px; border-radius: 20px; }
    .logo svg { width: 42px; height: 38px; }
    .form-header { margin-bottom: 22px; }
    .form-header h2 { font-size: 21px; }
    .form-header p { font-size: 12px; }
    .field label { font-size: 12px; }
    .input-wrap input { height: 44px; font-size: 14px; }
    .submit-btn { height: 42px; font-size: 13px; }
    .back-btn { font-size: 12px; }
    .security-text { font-size: 11px; }
}
</style>