<template>
    <aside class="sidebar">
        <div class="logo">
            <i class="bi bi-shop me-2"></i>
            POS SYSTEM
        </div>
        <nav>
            <RouterLink to="/dashboard">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </RouterLink>

            <RouterLink to="/analysis">
                <i class="bi bi-graph-up"></i>
                <span>Analysis</span>
            </RouterLink>
            <RouterLink to="/users">
                <i class="bi bi-people"></i>
                <span>Users</span>
            </RouterLink>

            <RouterLink to="/roles">
                <i class="bi bi-person-badge"></i>
                <span>Roles</span>
            </RouterLink>

            <RouterLink to="/permissions">
                <i class="bi bi-shield-lock"></i>
                <span>Permissions</span>
            </RouterLink>

            <RouterLink to="/pos">
                <i class="bi bi-cart-check"></i>
                <span>POS</span>
            </RouterLink>

            <RouterLink to="/products">
                <i class="bi bi-box-seam"></i>
                <span>Products</span>
            </RouterLink>

            <RouterLink to="/categories">
                <i class="bi bi-grid"></i>
                <span>Categories</span>
            </RouterLink>

            <RouterLink to="/brands">
                <i class="bi bi-tags"></i>
                <span>Brands</span>
            </RouterLink>

            <RouterLink to="/purchases">
                <i class="bi bi-cart-plus"></i>
                <span>Purchases</span>
            </RouterLink>

            <RouterLink to="/suppliers">
                <i class="bi bi-truck"></i>
                <span>Suppliers</span>
            </RouterLink>

            <RouterLink to="/orders">
                <i class="bi bi-receipt"></i>
                <span>Orders</span>
            </RouterLink>
            <RouterLink to="/payments">
                <i class="bi bi-cash-stack"></i>
                <span>Payments Method</span>
            </RouterLink>

            <RouterLink to="/payments">
                <i class="bi bi-cash-stack"></i>
                <span>Payments</span>
            </RouterLink>

            <RouterLink to="/sales-returns">
                <i class="bi bi-arrow-return-left"></i>
                <span>Sales Returns</span>
            </RouterLink>

            <RouterLink to="/inventory">
                <i class="bi bi-boxes"></i>
                <span>Inventory</span>
            </RouterLink>

            <RouterLink to="/cash-registers">
                <i class="bi bi-safe"></i>
                <span>Cash Registers</span>
            </RouterLink>

            <RouterLink to="/shifts">
                <i class="bi bi-clock-history"></i>
                <span>My Shift</span>
            </RouterLink>

            <RouterLink to="/cash-transactions">
                <i class="bi bi-wallet2"></i>
                <span>Cash Transactions</span>
            </RouterLink>

            <RouterLink to="/expenses">
                <i class="bi bi-receipt-cutoff"></i>
                <span>Expenses</span>
            </RouterLink>


            <RouterLink to="/stores">
                <i class="bi bi-shop-window"></i>
                <span>Stores</span>
            </RouterLink>

            <RouterLink to="/reports">
                <i class="bi bi-bar-chart"></i>
                <span>Reports</span>
            </RouterLink>

            <RouterLink to="/notifications">
                <i class="bi bi-bell"></i>
                <span>Notifications</span>
            </RouterLink>

            <RouterLink to="/audit-logs">
                <i class="bi bi-journal-text"></i>
                <span>Audit Logs</span>
            </RouterLink>

            <RouterLink to="/settings">
                <i class="bi bi-gear"></i>
                <span>Settings</span>
            </RouterLink>
        </nav>
    </aside>
</template>

<style scoped>
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    z-index: 1040;
    width: 250px;
    height: 100vh;
    background: var(--sidebar-bg, #111827);
    color: var(--sidebar-text, #ffffff);
    padding: 20px 15px;
    overflow-y: auto;
    border-right: 1px solid var(--sidebar-border, #1f2937);
}

.sidebar::-webkit-scrollbar {
    width: 6px;
}

.sidebar::-webkit-scrollbar-track {
    background: var(--sidebar-bg, #111827);
}

.sidebar::-webkit-scrollbar-thumb {
    background: var(--sidebar-border, #374151);
    border-radius: 10px;
}

.logo {
    font-size: 21px;
    font-weight: 800;
    color: var(--text-color, #ffffff);
    padding: 12px 15px 25px;
    letter-spacing: 0.5px;
}

.logo i {
    font-size: 22px;
    color: #60a5fa;
}

nav {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

nav a {
    display: flex;
    align-items: center;
    gap: 13px;
    color: var(--sidebar-text, #d1d5db);
    text-decoration: none;
    padding: 12px 15px;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    transition: all 0.2s ease;
}

nav a i {
    width: 22px;
    font-size: 18px;
    text-align: center;
    color: var(--nav-icon-color, #9ca3af);
    transition: color 0.2s ease, transform 0.2s ease;
}

nav a:nth-child(1) {
    --nav-icon-color: #60a5fa;
}

nav a:nth-child(2) {
    --nav-icon-color: #a78bfa;
}

nav a:nth-child(3) {
    --nav-icon-color: #34d399;
}

nav a:nth-child(4) {
    --nav-icon-color: #fbbf24;
}

nav a:nth-child(5) {
    --nav-icon-color: #f87171;
}

nav a:nth-child(6) {
    --nav-icon-color: #fb923c;
}

nav a:nth-child(7) {
    --nav-icon-color: #38bdf8;
}

nav a:nth-child(8) {
    --nav-icon-color: #c084fc;
}

nav a:nth-child(9) {
    --nav-icon-color: #f472b6;
}

nav a:nth-child(10) {
    --nav-icon-color: #2dd4bf;
}

nav a:nth-child(11) {
    --nav-icon-color: #4ade80;
}

nav a:nth-child(12) {
    --nav-icon-color: #facc15;
}

nav a:nth-child(13) {
    --nav-icon-color: #fb7185;
}

nav a:nth-child(14) {
    --nav-icon-color: #818cf8;
}

nav a:nth-child(15) {
    --nav-icon-color: #22d3ee;
}

nav a:nth-child(16) {
    --nav-icon-color: #f59e0b;
}

nav a:nth-child(17) {
    --nav-icon-color: #a3e635;
}

nav a:nth-child(18) {
    --nav-icon-color: #e879f9;
}

nav a:nth-child(19) {
    --nav-icon-color: #14b8a6;
}

nav a:nth-child(20) {
    --nav-icon-color: #f97316;
}

nav a:nth-child(21) {
    --nav-icon-color: #06b6d4;
}

nav a:nth-child(22) {
    --nav-icon-color: #8b5cf6;
}

nav a:nth-child(23) {
    --nav-icon-color: #ef4444;
}

nav a:nth-child(24) {
    --nav-icon-color: #60a5fa;
}

nav a:nth-child(25) {
    --nav-icon-color: #f472b6;
}

nav a:hover i {
    color: var(--nav-icon-color, #ffffff);
    transform: scale(1.08);
}

nav a:hover {
    background: var(--sidebar-hover, #1f2937);
    color: var(--text-color, #ffffff);
}

nav a.router-link-active {
    background: #2563eb;
    color: #ffffff;
    font-weight: 700;
    box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);
}

nav a.router-link-active i {
    color: #ffffff;
}

nav a:active {
    transform: scale(0.98);
}
</style>