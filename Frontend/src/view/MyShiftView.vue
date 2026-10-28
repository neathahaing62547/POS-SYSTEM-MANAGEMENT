
<script setup>
import { computed, onMounted, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useCashRegisterStore } from '@/stores/CashRegister'
import { useStoreStore } from '@/stores/Store'
import { PERMISSIONS } from '@/constants/permissions'
import LoadingSkeleton from '@/Components/LoadingSkeleton.vue'

const auth = useAuthStore()
const cashStore = useCashRegisterStore()
const storeStore = useStoreStore()

const canOpen = computed(() =>
    auth.can(PERMISSIONS.CASH_REGISTER_SHIFT_OPEN)
)
const canClose = computed(() =>
    auth.can(PERMISSIONS.CASH_REGISTER_SHIFT_CLOSE)
)
const canViewStores = computed(() =>
    auth.can(PERMISSIONS.STORES_VIEW)
)

const openForm = ref({ store_id: '', opening_cash: '' })
const closingCash = ref('')
const error = ref('')
const success = ref('')

const availableStores = computed(() =>
    (storeStore.stores || []).filter(
        store => store.status === 'active'
    )
)

const currentShift = computed(() => cashStore.currentShift)

const formatMoney = value =>
    new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(Number(value) || 0)

const formatDate = value => {
    if (!value) return '-'

    const date = new Date(value)
    return Number.isNaN(date.getTime())
        ? '-'
        : date.toLocaleString()
}

const getErrorMessage = (err) => {
    const errors = err.response?.data?.errors

    if (errors) {
        return Object.values(errors).flat().join(' ')
    }

    return cashStore.error || err.response?.data?.message ||
        err.message || 'Something went wrong. Please try again.'
}

const load = async () => {
    error.value = ''

    const tasks = [cashStore.getCurrentShift()]

    if (canViewStores.value) {
        tasks.push(storeStore.getStores())
    }

    const results = await Promise.allSettled(tasks)
    const failed = results.find(result => result.status === 'rejected')

    if (failed) {
        error.value = cashStore.error || storeStore.error ||
            failed.reason?.message || 'Failed to load shift information.'
    }

    if (currentShift.value?.cash_register?.store_id) {
        openForm.value.store_id =
            currentShift.value.cash_register.store_id
    } else if (availableStores.value.length === 1) {
        openForm.value.store_id = availableStores.value[0].id
    }
}

const openShift = async () => {
    if (
        !canOpen.value ||
        !canViewStores.value ||
        cashStore.saving ||
        !openForm.value.store_id ||
        openForm.value.opening_cash === '' ||
        Number(openForm.value.opening_cash) < 0
    ) return

    error.value = ''
    success.value = ''

    try {
        await cashStore.openShift({
            store_id: Number(openForm.value.store_id),
            opening_cash: Number(openForm.value.opening_cash)
        })

        success.value = 'Your cash register shift is now open.'
        openForm.value.opening_cash = ''

        await cashStore.getCurrentShift()
    } catch (err) {
        error.value = getErrorMessage(err)
    }
}

const closeShift = async () => {
    if (
        !canClose.value ||
        cashStore.saving ||
        closingCash.value === '' ||
        Number(closingCash.value) < 0
    ) return

    error.value = ''
    success.value = ''

    try {
        await cashStore.closeShift({
            closing_cash: Number(closingCash.value)
        })

        success.value = 'Your cash register shift has been closed.'
        closingCash.value = ''

        await cashStore.getCurrentShift()
    } catch (err) {
        error.value = getErrorMessage(err)
    }
}

onMounted(load)
</script>

<template>
    <main class="shift-page">
        <!-- Page heading -->
        <header class="shift-page-header">
            <div class="shift-heading">
                <div class="shift-heading-icon">
                    <i class="bi bi-cash-stack"></i>
                </div>

                <div>
                    <p class="shift-eyebrow">POINT OF SALE</p>
                    <h1>Cash Register</h1>
                    <p class="shift-subtitle">
                        Manage your register, opening cash and shift closing.
                    </p>
                </div>
            </div>

            <div class="shift-header-tag">
                <span class="shift-live-dot"></span>
                Shift Management
            </div>
        </header>

        <!-- Notifications -->
        <div v-if="error" class="shift-alert shift-alert-error" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>{{ error }}</span>
            <button
                type="button"
                class="shift-alert-dismiss"
                aria-label="Dismiss error"
                @click="error = ''"
            >×</button>
        </div>

        <div
            v-if="success"
            class="shift-alert shift-alert-success"
            role="status"
        >
            <i class="bi bi-check-circle-fill"></i>
            <span>{{ success }}</span>
            <button
                type="button"
                class="shift-alert-dismiss"
                aria-label="Dismiss message"
                @click="success = ''"
            >×</button>
        </div>

        <!-- Loading -->
        <section v-if="cashStore.shiftLoading" class="shift-panel shift-loading">
            <LoadingSkeleton
                variant="spinner"
                label="Loading your cash register..."
            />
        </section>

        <template v-else>
            <!-- OPEN SHIFT -->
            <template v-if="currentShift">
                <section class="shift-status-banner">
                    <div class="shift-status-icon">
                        <i class="bi bi-check-lg"></i>
                    </div>

                    <div class="shift-status-copy">
                        <span class="shift-eyebrow">CURRENT SESSION</span>
                        <h2>Your shift is active</h2>
                        <p>
                            Your cash register is open and ready for transactions.
                        </p>
                    </div>

                    <span class="shift-status-pill">
                        <span></span> OPEN
                    </span>
                </section>

                <div class="shift-info-grid">
                    <article class="shift-info-card">
                        <div class="shift-info-top">
                            <span>Cash Register</span>
                            <i class="bi bi-pc-display"></i>
                        </div>
                        <h3>
                            {{ currentShift.cash_register?.name || 'Cash register' }}
                        </h3>
                        <p>Active register</p>
                    </article>

                    <article class="shift-info-card">
                        <div class="shift-info-top">
                            <span>Store</span>
                            <i class="bi bi-shop"></i>
                        </div>
                        <h3>
                            {{ currentShift.cash_register?.store?.name || '-' }}
                        </h3>
                        <p>Assigned store</p>
                    </article>

                    <article class="shift-info-card shift-money-card">
                        <div class="shift-info-top">
                            <span>Opening Cash</span>
                            <i class="bi bi-wallet2"></i>
                        </div>
                        <h3>{{ formatMoney(currentShift.opening_cash) }}</h3>
                        <p>Starting cash balance</p>
                    </article>

                    <article class="shift-info-card">
                        <div class="shift-info-top">
                            <span>Opened At</span>
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <h3 class="shift-date-value">
                            {{ formatDate(currentShift.opened_at) }}
                        </h3>
                        <p>Session start time</p>
                    </article>
                </div>

                <!-- Close shift -->
                <section class="shift-panel shift-action-panel">
                    <div class="shift-panel-heading">
                        <div class="shift-panel-icon close-icon">
                            <i class="bi bi-lock"></i>
                        </div>
                        <div>
                            <h2>Close Register Shift</h2>
                            <p>
                                Count the cash currently in your drawer before closing.
                            </p>
                        </div>
                    </div>

                    <form
                        v-if="canClose"
                        class="shift-form"
                        @submit.prevent="closeShift"
                    >
                        <div class="shift-field">
                            <label for="closing-cash">
                                Counted Closing Cash
                            </label>

                            <div class="shift-input-wrap">
                                <i class="bi bi-cash-coin"></i>
                                <input
                                    id="closing-cash"
                                    v-model="closingCash"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    placeholder="0.00"
                                    required
                                    :disabled="cashStore.saving"
                                >
                                <span class="shift-input-currency">USD</span>
                            </div>

                            <small>
                                Enter the actual amount counted in the cash drawer.
                            </small>
                        </div>

                        <button
                            type="submit"
                            class="shift-button shift-button-danger"
                            :disabled="
                                cashStore.saving ||
                                closingCash === '' ||
                                Number(closingCash) < 0
                            "
                        >
                            <span
                                v-if="cashStore.saving"
                                class="shift-spinner"
                            ></span>
                            <i v-else class="bi bi-lock-fill"></i>
                            {{ cashStore.saving ? 'Closing shift...' : 'Close Shift' }}
                        </button>
                    </form>

                    <div v-else class="shift-permission-note">
                        <i class="bi bi-shield-lock"></i>
                        You do not have permission to close shifts.
                    </div>

                    <div class="shift-info-note">
                        <i class="bi bi-info-circle"></i>
                        Expected cash and cash variance are not currently
                        calculated by your backend.
                    </div>
                </section>
            </template>

            <!-- NO OPEN SHIFT -->
            <template v-else>
                <section class="shift-welcome-banner">
                    <div class="shift-welcome-copy">
                        <span class="shift-eyebrow">REGISTER CONTROL</span>
                        <h2>Start your shift</h2>
                        <p>
                            Select an active store and enter the starting cash
                            amount to prepare your register for sales.
                        </p>
                    </div>

                    <div class="shift-welcome-art">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                </section>

                <section class="shift-panel shift-action-panel">
                    <div class="shift-panel-heading">
                        <div class="shift-panel-icon open-icon">
                            <i class="bi bi-unlock"></i>
                        </div>
                        <div>
                            <h2>Open a New Shift</h2>
                            <p>Enter your register setup details below.</p>
                        </div>
                    </div>

                    <div v-if="!canOpen" class="shift-empty-state">
                        <div class="shift-empty-icon">
                            <i class="bi bi-shield-lock"></i>
                        </div>
                        <h3>Permission required</h3>
                        <p>
                            You do not have permission to open a cash register shift.
                        </p>
                    </div>

                    <form
                        v-else
                        class="shift-open-form"
                        @submit.prevent="openShift"
                    >
                        <div class="shift-field">
                            <label for="shift-store">Select Store</label>

                            <div class="shift-input-wrap">
                                <i class="bi bi-shop"></i>
                                <select
                                    id="shift-store"
                                    v-model="openForm.store_id"
                                    required
                                    :disabled="
                                        !canViewStores ||
                                        storeStore.loading ||
                                        cashStore.saving
                                    "
                                >
                                    <option value="" disabled>
                                        Choose a store
                                    </option>
                                    <option
                                        v-for="store in availableStores"
                                        :key="store.id"
                                        :value="store.id"
                                    >
                                        {{ store.name }}
                                    </option>
                                </select>
                                <i class="bi bi-chevron-down shift-select-arrow"></i>
                            </div>

                            <small>
                                Choose the store where you will operate.
                            </small>
                        </div>

                        <div class="shift-field">
                            <label for="opening-cash">Opening Cash</label>

                            <div class="shift-input-wrap">
                                <i class="bi bi-wallet2"></i>
                                <input
                                    id="opening-cash"
                                    v-model="openForm.opening_cash"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    placeholder="0.00"
                                    required
                                    :disabled="cashStore.saving"
                                >
                                <span class="shift-input-currency">USD</span>
                            </div>

                            <small>
                                Enter the initial cash available in your drawer.
                            </small>
                        </div>

                        <div class="shift-submit-row">
                            <p>
                                <i class="bi bi-shield-check"></i>
                                Verify your cash amount before opening.
                            </p>

                            <button
                                type="submit"
                                class="shift-button shift-button-primary"
                                :disabled="
                                    cashStore.saving ||
                                    storeStore.loading ||
                                    !canViewStores ||
                                    !availableStores.length ||
                                    !openForm.store_id ||
                                    openForm.opening_cash === '' ||
                                    Number(openForm.opening_cash) < 0
                                "
                            >
                                <span
                                    v-if="cashStore.saving"
                                    class="shift-spinner"
                                ></span>
                                <i v-else class="bi bi-unlock-fill"></i>
                                {{ cashStore.saving ? 'Opening shift...' : 'Open Shift' }}
                                <i
                                    v-if="!cashStore.saving"
                                    class="bi bi-arrow-right"
                                ></i>
                            </button>
                        </div>

                        <div
                            v-if="!canViewStores"
                            class="shift-inline-warning"
                        >
                            Store viewing permission is required to open a shift.
                        </div>

                        <div
                            v-else-if="
                                !storeStore.loading && !availableStores.length
                            "
                            class="shift-inline-warning"
                        >
                            No active stores found. Activate a store before
                            opening a shift.
                        </div>
                    </form>
                </section>
            </template>
        </template>
    </main>
</template>

<style scoped>
.shift-page {
    --shift-primary: #4f46e5;
    --shift-primary-dark: #3730a3;
    --shift-text: #172033;
    --shift-muted: #758096;
    --shift-border: #e7eaf1;
    --shift-surface: #ffffff;

    width: 100%;
    min-height: 100%;
    padding: 28px;
    color: var(--shift-text);
    background: #f6f8fc;
    font-family: inherit;
}

.shift-page,
.shift-page * {
    box-sizing: border-box;
}

.shift-page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 28px;
}

.shift-heading {
    display: flex;
    align-items: center;
    gap: 16px;
}

.shift-heading-icon {
    display: grid;
    place-items: center;
    width: 58px;
    height: 58px;
    flex-shrink: 0;
    border-radius: 17px;
    color: #fff;
    background: linear-gradient(145deg, #6366f1, #4338ca);
    box-shadow: 0 8px 22px rgb(79 70 229 / 20%);
    font-size: 25px;
}

.shift-eyebrow {
    display: block;
    margin-bottom: 6px;
    color: var(--shift-primary);
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 1.7px;
}

.shift-heading h1 {
    margin: 0;
    font-size: clamp(24px, 3vw, 31px);
    font-weight: 800;
    letter-spacing: -1px;
}

.shift-subtitle {
    margin: 6px 0 0;
    color: var(--shift-muted);
    font-size: 13px;
}

.shift-header-tag {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 11px 15px;
    border: 1px solid var(--shift-border);
    border-radius: 12px;
    background: #fff;
    color: #475467;
    font-size: 12px;
    font-weight: 700;
}

.shift-live-dot,
.shift-status-pill span {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #16a34a;
}

.shift-alert {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 15px 16px;
    margin-bottom: 18px;
    border: 1px solid transparent;
    border-radius: 13px;
    font-size: 13px;
    line-height: 1.6;
}

.shift-alert > span {
    flex: 1;
}

.shift-alert-error {
    color: #b42318;
    border-color: #fecaca;
    background: #fff1f2;
}

.shift-alert-success {
    color: #166534;
    border-color: #bbf7d0;
    background: #f0fdf4;
}

.shift-alert-dismiss {
    padding: 0 4px;
    border: 0;
    background: transparent;
    color: inherit;
    font-size: 22px;
    line-height: 1;
    cursor: pointer;
}

.shift-panel {
    border: 1px solid var(--shift-border);
    border-radius: 19px;
    background: var(--shift-surface);
    box-shadow: 0 5px 24px rgb(25 35 70 / 3%);
}

.shift-loading {
    display: grid;
    min-height: 200px;
    place-items: center;
    padding: 30px;
}

.shift-status-banner {
    display: flex;
    align-items: center;
    gap: 17px;
    padding: 23px;
    margin-bottom: 20px;
    border: 1px solid #bbf7d0;
    border-radius: 18px;
    background: linear-gradient(110deg, #f0fdf4, #fff 80%);
}

.shift-status-icon {
    display: grid;
    place-items: center;
    width: 48px;
    height: 48px;
    flex-shrink: 0;
    border-radius: 15px;
    background: #dcfce7;
    color: #15803d;
    font-size: 24px;
}

.shift-status-copy {
    flex: 1;
}

.shift-status-copy .shift-eyebrow {
    color: #15803d;
}

.shift-status-copy h2,
.shift-welcome-copy h2 {
    margin: 0;
    font-size: 22px;
    font-weight: 800;
    letter-spacing: -.5px;
}

.shift-status-copy p,
.shift-welcome-copy p {
    margin: 6px 0 0;
    color: var(--shift-muted);
    font-size: 13px;
    line-height: 1.6;
}

.shift-status-pill {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border: 1px solid #bbf7d0;
    border-radius: 30px;
    background: #fff;
    color: #15803d;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 1px;
}

.shift-info-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 22px;
}

.shift-info-card {
    min-width: 0;
    padding: 20px;
    border: 1px solid var(--shift-border);
    border-radius: 16px;
    background: #fff;
    box-shadow: 0 4px 15px rgb(25 35 70 / 2%);
}

.shift-info-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    color: var(--shift-muted);
    font-size: 12px;
    font-weight: 600;
}

.shift-info-top i {
    display: grid;
    place-items: center;
    width: 35px;
    height: 35px;
    flex-shrink: 0;
    border-radius: 11px;
    background: #eef2ff;
    color: var(--shift-primary);
    font-size: 17px;
}

.shift-info-card h3 {
    margin: 17px 0 6px;
    overflow-wrap: anywhere;
    font-size: 18px;
    font-weight: 800;
    letter-spacing: -.4px;
}

.shift-info-card > p {
    margin: 0;
    color: var(--shift-muted);
    font-size: 11px;
}

.shift-money-card {
    border-color: #c7d2fe;
    background: linear-gradient(150deg, #fff, #f5f5ff);
}

.shift-money-card h3 {
    color: var(--shift-primary-dark);
    font-variant-numeric: tabular-nums;
}

.shift-info-card .shift-date-value {
    font-size: 14px;
    line-height: 1.5;
}

.shift-action-panel {
    padding: 27px;
}

.shift-panel-heading {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 25px;
}

.shift-panel-icon {
    display: grid;
    place-items: center;
    width: 46px;
    height: 46px;
    flex-shrink: 0;
    border-radius: 14px;
    font-size: 20px;
}

.open-icon {
    color: #4338ca;
    background: #eef2ff;
}

.close-icon {
    color: #b91c1c;
    background: #fff1f2;
}

.shift-panel-heading h2 {
    margin: 0;
    font-size: 18px;
    font-weight: 800;
}

.shift-panel-heading p {
    margin: 5px 0 0;
    color: var(--shift-muted);
    font-size: 12px;
    line-height: 1.6;
}

.shift-welcome-banner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    overflow: hidden;
    padding: 30px;
    margin-bottom: 22px;
    border-radius: 19px;
    background: linear-gradient(115deg, #312e81, #4f46e5 70%, #6366f1);
    color: #fff;
}

.shift-welcome-copy {
    max-width: 580px;
}

.shift-welcome-copy .shift-eyebrow {
    color: #c7d2fe;
}

.shift-welcome-copy h2 {
    font-size: 27px;
}

.shift-welcome-copy p {
    color: #e0e7ff;
}

.shift-welcome-art {
    display: grid;
    place-items: center;
    width: 100px;
    height: 100px;
    flex-shrink: 0;
    border: 1px solid rgb(255 255 255 / 22%);
    border-radius: 28px;
    background: rgb(255 255 255 / 10%);
    color: #fff;
    font-size: 48px;
    transform: rotate(4deg);
}

.shift-open-form {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 24px;
}

.shift-form {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: end;
    gap: 20px;
}

.shift-field {
    display: grid;
    align-content: start;
    gap: 9px;
    min-width: 0;
}

.shift-field > label {
    color: #344054;
    font-size: 12px;
    font-weight: 700;
}

.shift-field > small {
    color: var(--shift-muted);
    font-size: 11px;
    line-height: 1.6;
}

.shift-input-wrap {
    display: flex;
    align-items: center;
    min-width: 0;
    min-height: 49px;
    gap: 11px;
    padding: 0 13px;
    border: 1px solid #dfe3ed;
    border-radius: 11px;
    background: #fff;
    transition: border-color .2s, box-shadow .2s;
}

.shift-input-wrap:focus-within {
    border-color: #818cf8;
    box-shadow: 0 0 0 3px rgb(99 102 241 / 11%);
}

.shift-input-wrap > i:first-child {
    flex-shrink: 0;
    color: #818cf8;
    font-size: 17px;
}

.shift-input-wrap input,
.shift-input-wrap select {
    width: 100%;
    min-width: 0;
    height: 46px;
    padding: 0;
    border: 0;
    outline: none;
    background: transparent;
    color: var(--shift-text);
    font: inherit;
    font-size: 13px;
    box-shadow: none;
}

.shift-input-wrap input:focus,
.shift-input-wrap select:focus {
    outline: none;
    box-shadow: none;
}

.shift-input-wrap input::placeholder {
    color: #a0a8b8;
}

.shift-input-wrap input:disabled,
.shift-input-wrap select:disabled {
    cursor: not-allowed;
    opacity: .65;
}

.shift-input-currency {
    flex-shrink: 0;
    padding: 5px 8px;
    border-radius: 6px;
    background: #f1f3f9;
    color: #667085;
    font-size: 10px;
    font-weight: 800;
}

.shift-select-arrow {
    flex-shrink: 0;
    color: #98a2b3;
    pointer-events: none;
}

.shift-input-wrap select {
    appearance: none;
    cursor: pointer;
}

.shift-submit-row {
    display: flex;
    grid-column: 1 / -1;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 15px;
    padding-top: 20px;
    margin-top: 1px;
    border-top: 1px solid #eef0f5;
}

.shift-submit-row p {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
    color: var(--shift-muted);
    font-size: 11px;
}

.shift-submit-row p i {
    color: #16a34a;
    font-size: 16px;
}

.shift-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    min-height: 47px;
    padding: 0 22px;
    border: 0;
    border-radius: 11px;
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: transform .2s, background .2s, box-shadow .2s;
}

.shift-button:not(:disabled):hover {
    transform: translateY(-1px);
}

.shift-button:focus-visible {
    outline: 3px solid #a5b4fc;
    outline-offset: 3px;
}

.shift-button:disabled {
    cursor: not-allowed;
    opacity: .55;
    box-shadow: none;
}

.shift-button-primary {
    background: linear-gradient(120deg, #4f46e5, #4338ca);
    box-shadow: 0 5px 13px rgb(79 70 229 / 18%);
}

.shift-button-primary:not(:disabled):hover {
    background: #3730a3;
    box-shadow: 0 7px 18px rgb(79 70 229 / 24%);
}

.shift-button-danger {
    background: #dc2626;
    box-shadow: 0 5px 13px rgb(220 38 38 / 14%);
}

.shift-button-danger:not(:disabled):hover {
    background: #b91c1c;
}

.shift-spinner {
    width: 15px;
    height: 15px;
    border: 2px solid rgb(255 255 255 / 45%);
    border-top-color: #fff;
    border-radius: 50%;
    animation: shift-spin .7s linear infinite;
}

.shift-info-note,
.shift-permission-note,
.shift-inline-warning {
    display: flex;
    align-items: flex-start;
    gap: 9px;
    padding: 13px 15px;
    border-radius: 10px;
    font-size: 11px;
    line-height: 1.7;
}

.shift-info-note {
    margin-top: 22px;
    border: 1px solid #dbeafe;
    background: #f5f9ff;
    color: #475569;
}

.shift-info-note i {
    color: #3b82f6;
    font-size: 15px;
}

.shift-permission-note {
    background: #fff7ed;
    color: #9a3412;
}

.shift-inline-warning {
    grid-column: 1 / -1;
    background: #fffbeb;
    color: #92400e;
}

.shift-empty-state {
    padding: 25px 12px;
    text-align: center;
}

.shift-empty-icon {
    display: grid;
    place-items: center;
    width: 58px;
    height: 58px;
    margin: 0 auto 14px;
    border-radius: 17px;
    background: #fff7ed;
    color: #c2410c;
    font-size: 24px;
}

.shift-empty-state h3 {
    margin: 0 0 8px;
    font-size: 16px;
    font-weight: 800;
}

.shift-empty-state p {
    margin: 0;
    color: var(--shift-muted);
    font-size: 12px;
}

.shift-footer {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: 9px;
    padding: 25px 10px 5px;
    color: #98a2b3;
    font-size: 10px;
}

.shift-footer i {
    color: #16a34a;
}

@keyframes shift-spin {
    to {
        transform: rotate(360deg);
    }
}

@media (max-width: 1100px) {
    .shift-info-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .shift-page {
        padding: 22px;
    }
}

@media (max-width: 650px) {
    .shift-page {
        padding: 16px 12px;
    }

    .shift-page-header {
        align-items: flex-start;
        margin-bottom: 20px;
    }

    .shift-heading {
        align-items: flex-start;
        gap: 12px;
    }

    .shift-heading-icon {
        width: 45px;
        height: 45px;
        border-radius: 13px;
        font-size: 20px;
    }

    .shift-heading h1 {
        font-size: 23px;
    }

    .shift-subtitle {
        max-width: 280px;
        line-height: 1.6;
    }

    .shift-header-tag {
        padding: 9px 11px;
    }

    .shift-status-banner {
        align-items: flex-start;
        flex-wrap: wrap;
        padding: 17px;
    }

    .shift-status-copy {
        min-width: 0;
    }

    .shift-status-copy h2 {
        font-size: 19px;
    }

    .shift-status-pill {
        margin-left: 64px;
    }

    .shift-info-grid {
        gap: 10px;
    }

    .shift-info-card {
        padding: 14px;
    }

    .shift-info-card h3 {
        margin-top: 12px;
        font-size: 16px;
    }

    .shift-info-top {
        font-size: 11px;
    }

    .shift-info-top i {
        width: 30px;
        height: 30px;
        font-size: 14px;
    }

    .shift-info-card .shift-date-value {
        font-size: 12px;
    }

    .shift-action-panel {
        padding: 19px 15px;
    }

    .shift-panel-heading {
        align-items: flex-start;
        gap: 11px;
    }

    .shift-panel-heading h2 {
        font-size: 16px;
    }

    .shift-welcome-banner {
        padding: 22px 18px;
    }

    .shift-welcome-copy h2 {
        font-size: 23px;
    }

    .shift-welcome-art {
        width: 58px;
        height: 58px;
        border-radius: 17px;
        font-size: 28px;
    }

    .shift-open-form,
    .shift-form {
        grid-template-columns: minmax(0, 1fr);
        gap: 20px;
    }

    .shift-submit-row {
        align-items: stretch;
        flex-direction: column;
    }

    .shift-submit-row .shift-button,
    .shift-form .shift-button {
        width: 100%;
    }

    .shift-footer {
        text-align: center;
        line-height: 1.8;
    }
}

@media (prefers-reduced-motion: reduce) {
    .shift-page *,
    .shift-page *::before,
    .shift-page *::after {
        animation-duration: .01ms !important;
        transition-duration: .01ms !important;
    }
}
</style>
