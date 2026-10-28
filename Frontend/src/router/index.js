import { createRouter, createWebHistory } from 'vue-router'

import Login from '@/view/login.vue'
import Otp from '@/view/otp.vue'
import Dashboard from '@/view/dashboard.vue'
import Products from '@/view/product.vue'
import BrandVue from '@/view/BrandVue.vue'
import Categoriesview from '@/view/Categoriesview.vue'
import SupplierView from '@/view/SupplierView.vue'
import OrderView from '@/view/Pos.vue'
import PurchaseView from '@/view/PurchaseView.vue'
import ModulePlaceholder from '@/view/ModulePlaceholder.vue'
import Recoveremailpage from '@/view/Recoveremailpage.vue'
import Recoverotppage from '@/view/Recoverotppage.vue'
import Changepassword from '@/view/Changepassword.vue'
import Inventory from '@/view/Inventory.vue'
import StoreView from '@/view/StoreView.vue'
import UserView from '@/view/UserView.vue'
import RoleView from '@/view/RoleView.vue'
import PermissionView from '@/view/PermissionView.vue'
import CashRegisterView from '@/view/CashRegisterView.vue'
import MyShiftView from '@/view/MyShiftView.vue'
import CashTransactionsView from '@/view/CashTransactionsView.vue'
import ExpenseView from '@/view/ExpenseView.vue'
import PaymentsView from '@/view/PaymentsView.vue'
import PaymentMethodsView from '@/view/PaymentMethodsView.vue'
import OrdersView from '@/view/OrdersView.vue'
import SalesReturnsView from '@/view/SalesReturnsView.vue'
import ReportsView from '@/view/ReportsView.vue'
import { useAuthStore } from '@/stores/auth'
import { PERMISSIONS } from '@/constants/permissions'


const routes = [
    {
        path: '/',
        redirect: '/dashboard'
    },

    {
        path: '/login',
        component: Login,
        meta: {
            guest: true
        }
    },
    {
        path: '/recoveremail',
        name: 'Recoveremailpage',
        component: Recoveremailpage,
        meta: {
            guest: true
        }
    },
    {
        path: '/recover-otp-verify',
        name: 'recover-otp-verify',
        component: Recoverotppage ,
        meta: {
            requiresRecoverySession: true
        }
    },
    {
        path: '/changepassword',
        name: 'changepassword',
        component : Changepassword ,
        meta : {
            guest : true
        }

    },
    {
        path: '/otp',
        component: Otp,
        meta: {
            guest: true
        }
    },

    {
        path: '/dashboard',
        component: Dashboard,
        meta: {
            auth: true
        }
    },
    {
        path: '/pos',
        component: OrderView,
        meta: {
            auth: true,
            permission: PERMISSIONS.POS_VIEW
        }
    },

    {
        path: '/products',
        component: Products,
        meta: {
            auth: true,
            permission: PERMISSIONS.PRODUCTS_VIEW
        }
    },
    {
        path: '/brands',
        component: BrandVue,
        meta: {
            auth: true,
            permission: PERMISSIONS.BRANDS_VIEW
        }
    },
    {
        path: '/categories',
        component: Categoriesview,
        meta: {
            auth: true,
            permission: PERMISSIONS.CATEGORIES_VIEW
        }
    },
    {
        path: '/suppliers',
        component: SupplierView,
        meta: {
            auth: true,
            permission: PERMISSIONS.SUPPLIERS_VIEW
        }
    },
    {
        path: '/purchases',
        component: PurchaseView,
        meta: {
            auth: true,
            permission: PERMISSIONS.PURCHASES_VIEW
        }
    },
      {
        path: '/inventory',
        name: 'inventory',
        component: Inventory,
        meta: {
            auth: true,
            permission: PERMISSIONS.INVENTORY_VIEW
        }
    },
    {
        path: '/stores',
        component: StoreView,
        meta: {
            auth: true,
            permission: PERMISSIONS.STORES_VIEW
        }
    },
    {
        path: '/users',
        component: UserView,
        meta: {
            auth: true,
            permission: PERMISSIONS.USERS_VIEW
        }
    },
    {
        path: '/roles',
        component: RoleView,
        meta: {
            auth: true,
            permission: PERMISSIONS.ROLES_VIEW
        }
    },
    {
        path: '/permissions',
        component: PermissionView,
        meta: {
            auth: true,
            permission: PERMISSIONS.PERMISSIONS_VIEW
        }
    },
    {
        path: '/cash-registers',
        component: CashRegisterView,
        meta: { auth: true, permission: PERMISSIONS.CASH_REGISTERS_VIEW }
    },
    {
        path: '/shifts',
        component: MyShiftView,
        meta: { auth: true, permission: PERMISSIONS.CASH_REGISTERS_VIEW }
    },
    {
        path: '/cash-transactions',
        component: CashTransactionsView,
        meta: { auth: true, permission: PERMISSIONS.CASH_REGISTERS_VIEW }
    },
    {
        path: '/expenses',
        component: ExpenseView,
        meta: { auth: true, permission: PERMISSIONS.EXPENSES_VIEW }
    },
    {
        path: '/payment-methods',
        component: PaymentMethodsView,
        meta: { auth: true, permission: PERMISSIONS.PAYMENT_METHODS_VIEW }
    },
    {
        path: '/payments',
        component: PaymentsView,
        meta: { auth: true, permission: PERMISSIONS.PAYMENTS_VIEW }
    },
    {
        path: '/orders',
        component: OrdersView,
        meta: { auth: true, permission: PERMISSIONS.ORDERS_VIEW }
    },
    {
        path: '/sales-returns',
        component: SalesReturnsView,
        meta: { auth: true, permission: PERMISSIONS.SALES_RETURNS_VIEW }
    },
    {
        path: '/reports',
        component: ReportsView,
        meta: { auth: true, permission: PERMISSIONS.REPORTS_VIEW }
    },
    ...[
        ['stock-history', 'Stock History', 'bi-clock-history', PERMISSIONS.INVENTORY_HISTORY_VIEW],
        ['purchase-receive', 'Purchase Receive', 'bi-arrow-down-square', PERMISSIONS.PURCHASES_RECEIVE]
    ].map(([path, title, icon, permission]) => ({
        path: `/${path}`,
        component: ModulePlaceholder,
        props: { title, icon },
        meta: { auth: true, permission }
    }))
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

router.beforeEach(async (to) => {
    const token = localStorage.getItem('token')
    const authStore = useAuthStore()

    if (to.meta.auth && !token) {
        return '/login'
    }

    if (
        to.meta.auth &&
        token &&
        !authStore.permissionsReady
    ) {
        try {
            await authStore.refreshUser()
        } catch (error) {
            console.error('Failed to load authenticated user permissions', error)
            return false
        }
    }

    if (to.meta.guest && token) {
        return '/dashboard'
    }
    if (to.meta.auth && to.meta.permission && token) {
        if (!authStore.can(to.meta.permission)) {
            return '/dashboard'
        }
    }
      if (to.meta.requiresRecoverySession && !sessionStorage.getItem('recover_email')) {
        return { name: 'Recoveremailpage' }
    }
    return true
})

export default router