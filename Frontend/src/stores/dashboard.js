
import { defineStore } from 'pinia'
import api from '@/services/api'

export const useDashboardStore = defineStore('dashboard', {
    state: () => ({
        summary: {},
        filter: {},

        saleChart: {
            labels: [],
            sales: [],
            orders: [],
        },

        loading: false,
        chartLoading: false,
        error: null,
        chartError: null,
    }),

    actions: {
        async fetchSummary(period = 'all') {
            this.loading = true
            this.error = null

            try {
                const response = await api.get('/dashboard', {
                    params: { period },
                })

                const data = response.data?.Data ?? {}
                const summary = data.Summary ?? {}

                this.filter = data.Filter ?? {}

                this.summary = {
                    SummaryProducts: summary.SummaryProducts ?? {},
                    SummaryBrand: summary.SummaryBrand ?? {},
                    SummaryCategories: summary.SummaryCategories ?? {},
                    SummaryOrder: summary.SummaryOrder ?? {},
                    SummaryExpenses: summary.SummaryExpenses ?? {},
                    InventoryStockSummary:
                        summary.InventoryStockSummary ?? {},
                    SaleSummary: summary.SaleSummary ?? {},
                }

                return this.summary
            } catch (error) {
                this.error =
                    error.response?.data?.message ??
                    'Unable to load dashboard summary.'

                throw error
            } finally {
                this.loading = false
            }
        },

        async fetchSaleChart(period = 'all') {
            this.chartLoading = true
            this.chartError = null

            try {
                const response = await api.get('/dashboard/sale-chart', {
                    params: { period },
                })

                const chart = response.data?.Data?.SaleChart ?? {}

                this.saleChart = {
                    labels: chart.labels ?? [],
                    sales: chart.sales ?? [],
                    orders: chart.orders ?? [],
                }

                return this.saleChart
            } catch (error) {
                this.chartError =
                    error.response?.data?.message ??
                    'Unable to load sales chart.'

                throw error
            } finally {
                this.chartLoading = false
            }
        },

        async fetchDashboardQuickView() {
            this.quickViewLoading = true
            this.quickViewError = null
            try {
                const response = await api.get('/dashboard/quick-view')
                const data = response.data?.Data ?? response.data ?? {}
                this.recentOrders = data.recent_orders ?? []
                this.topProducts = data.top_products ?? []
                this.lowStockProducts = data.low_stock_products ?? []
                this.outofstocks = data.Outofstocks ?? []
                this.ReactionPurchaseRecived = data.ReactionpercahseRecived ?? []
                this.ReactionSaleRuturn = data.ReactionSaleRuturn ?? []
                console.log('Quick View API response:', this.ReactionSaleRuturn)
                return data
            } catch (error) {
                this.quickViewError =
                    error.response?.data?.message ??
                    'Unable to load dashboard quick view.'

                throw error
            } finally {
                this.quickViewLoading = false
            }
        },

    },
})
