<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const auth = useAuthStore()

const otp = ref('')
const error = ref('')

// ---- Animation state ----
const verified = ref(false) // step 1: boxes turn green
const opening = ref(false) // step 2: doors slide open


const SUCCESS_PAUSE = 600 
const DOOR_DURATION = 1100 

// Small helper: wait for some milliseconds
const wait = ms => new Promise(resolve => setTimeout(resolve, ms))

const goBack = () => {
    router.push('/login')
}

const verifyOtp = async () => {
    // Ignore clicks while the success animation is running
    if (verified.value) return

    error.value = ''

    // Basic validation: must be exactly 6 digits
    if (!otp.value || otp.value.length !== 6) {
        error.value = 'Please enter the 6-digit OTP'
        return
    }

    try {
        // Ask the server to verify the code
        await auth.verifyOtp(otp.value)

        // Step 1: show the success state (green boxes)
        verified.value = true
        await wait(SUCCESS_PAUSE)

        // Step 2: open the doors
        opening.value = true
        await wait(DOOR_DURATION)

        // Step 3: go to the dashboard when the doors are fully open
        router.push('/dashboard')
    } catch (err) {
        error.value = auth.error || 'Invalid OTP'
    }
}
</script>

<template>
    <!-- .verified / .opening classes drive the whole animation -->
    <div class="otp-page" :class="{ verified, opening }">
        <!-- ============ BEHIND THE DOORS (revealed when they open) ============ -->
        <div class="door-reveal" aria-hidden="true">
            <div class="reveal-content">
                <div class="reveal-check">
                    <i class="bi bi-check-lg"></i>
                </div>
                <h3>Verified successfully</h3>
                <p>Opening your dashboard...</p>
                <div class="reveal-bar"><span></span></div>
            </div>
        </div>

        <!-- ============ LEFT DOOR: form (slides to the left) ============ -->
        <div class="otp-form">
            <div class="form-wrap">
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
                        <h2>Verify your email</h2>
                        <p>Enter the 6-digit code sent to your email</p>
                    </div>

                    <!-- Error message -->
                    <div v-if="error" class="alert-error">
                        <i class="bi bi-exclamation-circle"></i>
                        <span>{{ error }}</span>
                    </div>

                    <form @submit.prevent="verifyOtp">
                        <div class="field">
                            <label for="otp">Verification Code</label>
                            <div class="otp-boxes">
                                <!-- 6 visual boxes. --i is used to stagger the green animation -->
                                <div v-for="i in 6" :key="i" class="otp-box" :style="{ '--i': i - 1 }" :class="{
                                    filled: otp[i - 1],
                                    active: otp.length === i - 1 || (otp.length === 6 && i === 6)
                                }">
                                    {{ otp[i - 1] || '' }}
                                </div>

                                <!-- One real input sits on top of the boxes (receives typing / paste / autofill) -->
                                <input id="otp" v-model="otp" type="text" inputmode="numeric"
                                    autocomplete="one-time-code" maxlength="6" pattern="[0-9]*" class="otp-input"
                                    required :disabled="verified" @input="otp = otp.replace(/\D/g, '')" />
                            </div>
                        </div>

                        <button type="submit" class="submit-btn" :disabled="auth.loading || verified">
                            <!-- Loading state -->
                            <span v-if="auth.loading">
                                <i class="bi bi-arrow-repeat spin"></i>
                                Verifying...
                            </span>

                            <!-- Success state -->
                            <span v-else-if="verified" class="submit-label">
                                <i class="bi bi-check-circle"></i>
                                Verified
                            </span>

                            <!-- Normal state -->
                            <span v-else class="submit-label">
                                Verify OTP
                                <i class="bi bi-arrow-right"></i>
                            </span>
                        </button>
                    </form>

                    <div class="resend">
                        <span>Didn't receive the code?</span>
                        <button type="button">Resend OTP</button>
                    </div>

                    <p class="security-text">
                        <i class="bi bi-shield-check"></i>
                        Your verification code is secure and private.
                    </p>
                </div>
            </div>
        </div>

        <!-- ============ RIGHT DOOR: image (slides to the right) ============ -->
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
                        <span class="dot active"></span>
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

.otp-page {
    position: relative;
    min-height: 100vh;
    min-height: 100dvh;
    width: 100%;
    display: grid;
    grid-template-columns: 50% 50%;
    background: #fff;
    overflow: hidden; /* hide the doors once they slide outside the screen */
}

/* ==============================================================
 * DOOR ANIMATION
 * ============================================================== */

/* The two panels are the "doors". They sit above the reveal layer. */
.otp-form,
.showcase {
    position: relative;
    z-index: 1;
    /* Slow start, fast middle, soft end: feels like a heavy door */
    transition:
        transform 1s cubic-bezier(0.76, 0, 0.24, 1),
        box-shadow 1s ease;
    will-change: transform;
}

/* Left door slides fully out to the LEFT */
.opening .otp-form {
    transform: translateX(-100%);
    box-shadow: 16px 0 50px rgba(17, 24, 39, 0.18);
    pointer-events: none;
}

/* Right door slides fully out to the RIGHT */
.opening .showcase {
    transform: translateX(100%);
    box-shadow: -16px 0 50px rgba(17, 24, 39, 0.25);
    pointer-events: none;
}

/* Layer behind the doors (visible when they open) */
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

/* Fade/zoom the message in while the doors are opening */
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

/* Small loading bar under the message */
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

/* Step 1: the six boxes turn green one after another */
.verified .otp-box {
    border-color: #12b76a;
    background: #ecfdf3;
    color: #027a48;
    animation: box-pop 0.45s cubic-bezier(0.22, 1, 0.36, 1) both;
    animation-delay: calc(var(--i, 0) * 70ms);
}

@keyframes box-pop {
    0% {
        transform: scale(1);
    }

    50% {
        transform: scale(1.12);
    }

    100% {
        transform: scale(1);
    }
}

.verified .submit-btn {
    background: #12b76a;
    opacity: 1;
}

/* ==============================================================
 * LEFT: form
 * ============================================================== */
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
    line-height: 1.5;
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
    margin-bottom: 22px;
}

.field label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #111827;
    margin-bottom: 8px;
}

.otp-boxes {
    position: relative;
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 10px;
}

.otp-box {
    height: 58px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #d1d5db;
    border-radius: 9px;
    background: #fff;
    color: #111827;
    font-size: 24px;
    font-weight: 600;
    transition: border-color 0.15s, box-shadow 0.15s, background 0.3s, color 0.3s;
}

.otp-box.filled {
    border-color: #6366f1;
}

/* Highlight the current box only while the input is focused */
.otp-boxes:focus-within .otp-box.active {
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
}

/* The real input covers all boxes: invisible but still receives typing/paste/autofill */
.otp-input {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    border: none;
    outline: none;
    cursor: text;
    font-size: 24px;
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
    transition: opacity 0.15s, background 0.3s;
}

.submit-btn:hover:not(:disabled) {
    opacity: 0.92;
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
    margin: 24px 0 0;
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

/* ==============================================================
 * RIGHT: showcase image
 * ============================================================== */
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

/* ==============================================================
 * RESPONSIVE
 * ============================================================== */
@media (min-width: 861px) and (max-width: 1250px) {

    .otp-form,
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
        padding: 36px 35px 40px;
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
    .otp-page {
        grid-template-columns: 1fr;
    }

    /* On small screens only the form exists, so only the left door opens */
    .showcase {
        display: none;
    }

    .otp-form {
        min-height: 100dvh;
        padding: 16px;
    }

    .form-wrap {
        width: 100%;
        max-width: 500px;
        min-height: 600px;
        padding: 32px 28px 35px;
    }
}

@media (max-width: 500px) {
    .otp-form {
        padding: 10px;
    }

    .form-wrap {
        min-height: 560px;
        padding: 26px 18px 28px;
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

    .field label {
        font-size: 12px;
    }

    .otp-boxes {
        gap: 6px;
    }

    .otp-box {
        height: 48px;
        font-size: 20px;
    }

    .submit-btn {
        height: 42px;
        font-size: 13px;
    }

    .back-btn,
    .resend,
    .resend button {
        font-size: 12px;
    }

    .security-text {
        font-size: 11px;
    }
}

@media (max-width: 380px) {
    .otp-boxes {
        gap: 4px;
    }

    .otp-box {
        height: 44px;
        font-size: 18px;
    }
}
</style>