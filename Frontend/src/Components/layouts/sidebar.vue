
<template>
  <aside class="sidebar">

    <!-- LOGO -->
    <div class="logo">
      <div class="logo-mark">
        <i class="bi bi-shop"></i>
      </div>

      <div class="logo-text">
        POS <span>SYSTEM</span>
      </div>
    </div>


    <nav class="navigation">

      <!-- =========================
           MAIN
      ========================== -->

      <div class="section-title">
        MAIN
      </div>

      <RouterLink to="/dashboard" class="nav-link">
        <i class="bi bi-speedometer2"></i>
        <span>Dashboard</span>
      </RouterLink>

      <!-- =========================
           MANAGEMENT
      ========================== -->

      <div class="section-title">
        MANAGEMENT
      </div>


      <!-- USER MANAGEMENT -->

      <div
        v-if="auth.can(PERMISSIONS.USERS_VIEW) || auth.can(PERMISSIONS.ROLES_VIEW) || auth.can(PERMISSIONS.PERMISSIONS_VIEW)"
        class="module-title"
      >
        <i class="bi bi-people"></i>
        <span>User Management</span>
      </div>

      <RouterLink v-if="auth.can(PERMISSIONS.USERS_VIEW)" to="/users" class="submenu-link">
        <i class="bi bi-person"></i>
        <span>Users</span>
      </RouterLink>

      <RouterLink v-if="auth.can(PERMISSIONS.ROLES_VIEW)" to="/roles" class="submenu-link">
        <i class="bi bi-person-badge"></i>
        <span>Roles</span>
      </RouterLink>

      <RouterLink v-if="auth.can(PERMISSIONS.PERMISSIONS_VIEW)" to="/permissions" class="submenu-link">
        <i class="bi bi-shield-lock"></i>
        <span>Permissions</span>
      </RouterLink>


      <!-- PRODUCTS -->

      <div
        v-if="auth.can(PERMISSIONS.POS_VIEW) || auth.can(PERMISSIONS.PRODUCTS_VIEW) || auth.can(PERMISSIONS.CATEGORIES_VIEW) || auth.can(PERMISSIONS.BRANDS_VIEW) || auth.can(PERMISSIONS.INVENTORY_VIEW)"
        class="module-title"
      >
        <i class="bi bi-box-seam"></i>
        <span>Products & Inventory</span>
      </div>

      <RouterLink v-if="auth.can(PERMISSIONS.POS_VIEW)" to="/pos" class="submenu-link">
        <i class="bi bi-cart-check"></i>
        <span>POS</span>
      </RouterLink>

      <RouterLink v-if="auth.can(PERMISSIONS.PRODUCTS_VIEW)" to="/products" class="submenu-link">
        <i class="bi bi-box"></i>
        <span>Products</span>
      </RouterLink>

      <RouterLink v-if="auth.can(PERMISSIONS.CATEGORIES_VIEW)" to="/categories" class="submenu-link">
        <i class="bi bi-grid"></i>
        <span>Categories</span>
      </RouterLink>

      <RouterLink v-if="auth.can(PERMISSIONS.BRANDS_VIEW)" to="/brands" class="submenu-link">
        <i class="bi bi-tags"></i>
        <span>Brands</span>
      </RouterLink>

      <RouterLink v-if="auth.can(PERMISSIONS.INVENTORY_VIEW)" to="/inventory" class="submenu-link">
        <i class="bi bi-boxes"></i>
        <span>Inventory</span>
      </RouterLink>


      <!-- PURCHASES -->

      <div
        v-if="auth.can(PERMISSIONS.PURCHASES_VIEW) || auth.can(PERMISSIONS.SUPPLIERS_VIEW)"
        class="module-title"
      >
        <i class="bi bi-cart-plus"></i>
        <span>Purchases & Suppliers</span>
      </div>

      <RouterLink v-if="auth.can(PERMISSIONS.PURCHASES_VIEW)" to="/purchases" class="submenu-link">
        <i class="bi bi-cart-plus"></i>
        <span>Purchases</span>
      </RouterLink>

      <RouterLink v-if="auth.can(PERMISSIONS.SUPPLIERS_VIEW)" to="/suppliers" class="submenu-link">
        <i class="bi bi-truck"></i>
        <span>Suppliers</span>
      </RouterLink>


      <!-- SALES -->

      <div
        v-if="auth.can(PERMISSIONS.ORDERS_VIEW) || auth.can(PERMISSIONS.SALES_RETURNS_VIEW)"
        class="module-title"
      >
        <i class="bi bi-receipt"></i>
        <span>Sales & Orders</span>
      </div>

      <RouterLink v-if="auth.can(PERMISSIONS.ORDERS_VIEW)" to="/orders" class="submenu-link">
        <i class="bi bi-receipt"></i>
        <span>Orders</span>
      </RouterLink>

      <RouterLink v-if="auth.can(PERMISSIONS.SALES_RETURNS_VIEW)" to="/sales-returns" class="submenu-link">
        <i class="bi bi-arrow-return-left"></i>
        <span>Sales Returns</span>
      </RouterLink>


      <!-- PAYMENTS -->

      <div
        v-if="auth.can(PERMISSIONS.PAYMENT_METHODS_VIEW) || auth.can(PERMISSIONS.PAYMENTS_VIEW)"
        class="module-title"
      >
        <i class="bi bi-credit-card"></i>
        <span>Payments</span>
      </div>

      <RouterLink v-if="auth.can(PERMISSIONS.PAYMENT_METHODS_VIEW)" to="/payment-methods" class="submenu-link">
        <i class="bi bi-credit-card"></i>
        <span>Payment Methods</span>
      </RouterLink>

      <RouterLink v-if="auth.can(PERMISSIONS.PAYMENTS_VIEW)" to="/payments" class="submenu-link">
        <i class="bi bi-cash-stack"></i>
        <span>Payments</span>
      </RouterLink>


      <!-- CASH -->

      <div v-if="auth.can(PERMISSIONS.CASH_REGISTERS_VIEW) || auth.can(PERMISSIONS.EXPENSES_VIEW) || auth.can(PERMISSIONS.REPORTS_VIEW)" class="module-title">
        <i class="bi bi-wallet2"></i>
        <span>Cash & Expenses</span>
      </div>

      <RouterLink v-if="auth.can(PERMISSIONS.CASH_REGISTERS_VIEW)" to="/cash-registers" class="submenu-link">
        <i class="bi bi-safe"></i>
        <span>Cash Registers</span>
      </RouterLink>

      <RouterLink v-if="auth.can(PERMISSIONS.CASH_REGISTERS_VIEW)" to="/shifts" class="submenu-link">
        <i class="bi bi-clock-history"></i>
        <span>My Shift</span>
      </RouterLink>

      <RouterLink v-if="auth.can(PERMISSIONS.CASH_REGISTERS_VIEW)" to="/cash-transactions" class="submenu-link">
        <i class="bi bi-wallet2"></i>
        <span>Cash Transactions</span>
      </RouterLink>

      <RouterLink v-if="auth.can(PERMISSIONS.EXPENSES_VIEW)" to="/expenses" class="submenu-link">
        <i class="bi bi-receipt-cutoff"></i>
        <span>Expenses</span>
      </RouterLink>

      <RouterLink v-if="auth.can(PERMISSIONS.REPORTS_VIEW)" to="/reports" class="submenu-link">
        <i class="bi bi-bar-chart-line"></i>
        <span>Reports</span>
      </RouterLink>


      <!-- =========================
           SYSTEM
      ========================== -->

      <div class="section-title">
        SYSTEM
      </div>


      <!-- STORE -->

      <div v-if="auth.can(PERMISSIONS.STORES_VIEW)" class="module-title">
        <i class="bi bi-shop-window"></i>
        <span>Store & Branch</span>
      </div>

      <RouterLink v-if="auth.can(PERMISSIONS.STORES_VIEW)" to="/stores" class="submenu-link">
        <i class="bi bi-shop-window"></i>
        <span>Stores</span>
      </RouterLink>


      <!-- =========================
           THEME
      ========================== -->

      <div class="theme-switch">

        <div class="theme-info">

          <i
            class="bi"
            :class="
              isDarkMode
                ? 'bi-moon-stars'
                : 'bi-sun'
            "
          ></i>

          <span>
            {{ isDarkMode ? 'Dark mode' : 'Light mode' }}
          </span>

        </div>

        <button
          class="theme-toggle"
          :class="{ dark: isDarkMode }"
          @click="emit('toggle-theme')"
          :title="
            isDarkMode
              ? 'Switch to light mode'
              : 'Switch to dark mode'
          "
        >
          <span></span>
        </button>

      </div>


      <!-- =========================
           NOTIFICATIONS
      ========================== -->

    </nav>

  </aside>
</template>


<script setup>
import { toRef } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { PERMISSIONS } from '@/constants/permissions'

const auth = useAuthStore()

const props = defineProps({
  isDarkMode: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits([
  'toggle-theme'
])

const isDarkMode = toRef(
  props,
  'isDarkMode'
)
</script>


<style scoped>

/* =========================================================
   SIDEBAR
========================================================= */

.sidebar {
  position: fixed;

  top: 0;
  left: 0;

  width: 250px;
  height: 100vh;

  z-index: 1040;

  padding: 18px 12px;

  background: var(--sidebar-bg);
  color: var(--sidebar-text);

  border-right: 1px solid var(--sidebar-border);

  overflow-y: auto;

  transition: none !important;
  animation: none !important;
}


/* =========================================================
   SCROLLBAR
========================================================= */

.sidebar::-webkit-scrollbar {
  width: 5px;
}

.sidebar::-webkit-scrollbar-track {
  background: transparent;
}

.sidebar::-webkit-scrollbar-thumb {
  background: var(--border-color);
  border-radius: 10px;
}


/* =========================================================
   LOGO
========================================================= */

.logo {
  display: flex;
  align-items: center;

  gap: 10px;

  height: 44px;

  padding: 0 8px;

  margin-bottom: 14px;
}

.logo-mark {
  width: 34px;
  height: 34px;

  display: flex;
  align-items: center;
  justify-content: center;

  border-radius: 9px;

  background: #2563eb;
  color: #ffffff;

  font-size: 17px;
}

.logo-text {
  font-size: 17px;
  font-weight: 800;

  letter-spacing: -.02em;

  color: var(--text-color);
}

.logo-text span {
  font-weight: 500;
  color: var(--muted-text);
}


/* =========================================================
   NAVIGATION
========================================================= */

.navigation {
  display: flex;
  flex-direction: column;

  gap: 3px;
}


/* =========================================================
   SECTION TITLE
========================================================= */

.section-title {
  padding: 16px 12px 7px;

  color: var(--muted-text);

  font-size: 10px;
  font-weight: 700;

  letter-spacing: .08em;
}


/* =========================================================
   NORMAL LINKS
========================================================= */

.nav-link {
  display: flex;
  align-items: center;

  gap: 12px;

  min-height: 42px;

  padding: 9px 12px;

  border-radius: 8px;

  color: var(--sidebar-text);

  text-decoration: none;

  font-size: 14px;
  font-weight: 500;

  transition: none !important;
  animation: none !important;
}

.nav-link:hover {
  background: var(--sidebar-hover);
  color: var(--text-color);
}

.nav-link.router-link-active {
  background: var(--sidebar-hover);

  color: #2563eb;

  font-weight: 600;
}


/* =========================================================
   MODULE TITLE
========================================================= */

.module-title {
  display: flex;
  align-items: center;

  gap: 12px;

  min-height: 38px;

  padding: 9px 12px 5px;

  color: var(--muted-text);

  font-size: 12px;
  font-weight: 700;

  margin-top: 6px;
}

.module-title i {
  font-size: 13px;
}


/* =========================================================
   SUB MENU LINKS
========================================================= */

.submenu-link {
  display: flex;
  align-items: center;

  gap: 11px;

  min-height: 37px;

  margin-left: 17px;

  padding: 7px 10px 7px 15px;

  border-left: 1px solid var(--border-color);

  border-radius: 0 7px 7px 0;

  color: var(--muted-text);

  text-decoration: none;

  font-size: 13px;
  font-weight: 500;

  transition: none !important;
  animation: none !important;
}

.submenu-link:hover {
  background: var(--sidebar-hover);

  color: var(--text-color);
}

.submenu-link.router-link-active {
  background: var(--sidebar-hover);

  color: #2563eb;

  font-weight: 600;
}


/* =========================================================
   THEME SWITCH
========================================================= */

.theme-switch {
  display: flex;
  align-items: center;
  justify-content: space-between;

  margin-top: 16px;

  padding: 12px;

  border-top: 1px solid var(--border-color);
}

.theme-info {
  display: flex;
  align-items: center;

  gap: 10px;

  color: var(--muted-text);

  font-size: 13px;
}


/* =========================================================
   THEME TOGGLE
========================================================= */

.theme-toggle {
  width: 38px;
  height: 22px;

  padding: 2px;

  border: none;

  border-radius: 20px;

  background: #cbd5e1;

  cursor: pointer;

  transition: none !important;
}

.theme-toggle span {
  display: block;

  width: 18px;
  height: 18px;

  border-radius: 50%;

  background: #ffffff;

  transition: none !important;
}

.theme-toggle.dark {
  background: #2563eb;
}

.theme-toggle.dark span {
  transform: translateX(16px);
}


/* =========================================================
   NOTIFICATION
========================================================= */

.notification-link {
  position: relative;

  margin-top: 4px;
}

.notification-badge {
  margin-left: auto;

  min-width: 18px;
  height: 18px;

  display: flex;
  align-items: center;
  justify-content: center;

  padding: 0 5px;

  border-radius: 10px;

  background: #ef4444;

  color: #ffffff;

  font-size: 10px;
  font-weight: 700;
}


/* =========================================================
   DARK MODE
========================================================= */

:global(.dark-theme) .sidebar {
  background: var(--sidebar-bg);
  color: var(--sidebar-text);

  border-color: var(--sidebar-border);
}

:global(.dark-theme) .logo-text {
  color: #f8fafc;
}

:global(.dark-theme) .logo-text span {
  color: #64748b;
}

:global(.dark-theme) .section-title {
  color: #64748b;
}

:global(.dark-theme) .nav-link {
  color: #cbd5e1;
}

:global(.dark-theme) .nav-link i {
  color: #94a3b8;
}

:global(.dark-theme) .nav-link:hover {
  background: #1e293b;
  color: #ffffff;
}

:global(.dark-theme) .nav-link.router-link-active {
  background: #172554;
  color: #60a5fa;
}

:global(.dark-theme) .nav-link.router-link-active i {
  color: #60a5fa;
}

:global(.dark-theme) .module-title {
  color: #94a3b8;
}

:global(.dark-theme) .module-title i {
  color: #64748b;
}

:global(.dark-theme) .submenu-link {
  color: #94a3b8;

  border-color: #334155;
}

:global(.dark-theme) .submenu-link:hover {
  background: #1e293b;
  color: #60a5fa;
}

:global(.dark-theme) .submenu-link.router-link-active {
  background: #172554;
  color: #60a5fa;
}

:global(.dark-theme) .theme-switch {
  border-color: #1e293b;
}

:global(.dark-theme) .theme-info {
  color: #94a3b8;
}

:global(.dark-theme) .theme-toggle {
  background: #475569;
}

:global(.dark-theme) .theme-toggle.dark {
  background: #2563eb;
}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 768px) {

  .sidebar {
    width: 240px;
  }

}
</style>
