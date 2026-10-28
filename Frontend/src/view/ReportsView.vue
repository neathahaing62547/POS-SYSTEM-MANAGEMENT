<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { PERMISSIONS } from '@/constants/permissions'

const auth = useAuthStore()

/* ---------- helpers ---------- */
const toDateInput = (date) =>
    new Date(date.getTime() - date.getTimezoneOffset() * 60000).toISOString().slice(0, 10)

const moneyFormatter = new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' })
const formatMoney = (value) => moneyFormatter.format(Number(value || 0))

const formatDate = (value) =>
    new Date(`${value}T00:00:00`).toLocaleDateString(undefined, { month: 'short', day: 'numeric' })

function statusTone(status) {
    const text = String(status || '').toLowerCase()
    if (/(complete|paid|received|deliver)/.test(text)) return 'is-good'
    if (/(cancel|refund|fail|void|reject)/.test(text)) return 'is-bad'
    return 'is-neutral'
}

/* ---------- state ---------- */
const today = new Date()
const start = new Date(today)
start.setDate(start.getDate() - 29)

const filters = ref({ from: toDateInput(start), to: toDateInput(today), store_id: '' })
const reports = ref(null)
const stores = ref([])
const loading = ref(false)
const error = ref('')
const analysisOpen = ref(false)
const exporting = ref(false)
const summaryCopied = ref(false)
const lastAnalysisKey = ref('')

/* ---------- date range picker ---------- */
const parseDate = (value) => new Date(`${value}T00:00:00`)
const todayStr = toDateInput(today)
const daysAgo = (n) => {
    const d = new Date(today)
    d.setDate(d.getDate() - n)
    return toDateInput(d)
}
const presets = [
    { key: 'today', label: 'Today', from: todayStr, to: todayStr },
    { key: 'yesterday', label: 'Yesterday', from: daysAgo(1), to: daysAgo(1) },
    { key: '7d', label: 'Last 7 days', from: daysAgo(6), to: todayStr },
    { key: '30d', label: 'Last 30 days', from: daysAgo(29), to: todayStr },
    { key: 'month', label: 'This month', from: toDateInput(new Date(today.getFullYear(), today.getMonth(), 1)), to: todayStr },
    {
        key: 'lastMonth',
        label: 'Last month',
        from: toDateInput(new Date(today.getFullYear(), today.getMonth() - 1, 1)),
        to: toDateInput(new Date(today.getFullYear(), today.getMonth(), 0))
    }
]
const weekdays = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa']

const rangeRef = ref(null)
const rangeOpen = ref(false)
const draft = ref({ from: filters.value.from, to: filters.value.to })
const anchor = ref('')
const hoverDate = ref('')
const viewMonth = ref(new Date(today.getFullYear(), today.getMonth(), 1))

function formatRange(from, to) {
    const withYear = { month: 'short', day: 'numeric', year: 'numeric' }
    const noYear = { month: 'short', day: 'numeric' }
    const a = parseDate(from)
    const b = parseDate(to)
    if (from === to) return a.toLocaleDateString(undefined, withYear)
    const sameYear = a.getFullYear() === b.getFullYear()
    return `${a.toLocaleDateString(undefined, sameYear ? noYear : withYear)} – ${b.toLocaleDateString(undefined, withYear)}`
}

const rangeLabel = computed(() => formatRange(filters.value.from, filters.value.to))
const draftLabel = computed(() => formatRange(draft.value.from, draft.value.to))
const activePreset = computed(() =>
    presets.find((p) => p.from === filters.value.from && p.to === filters.value.to)
)
const draftPreset = computed(() =>
    presets.find((p) => p.from === draft.value.from && p.to === draft.value.to)
)
const monthTitle = computed(() =>
    viewMonth.value.toLocaleDateString(undefined, { month: 'long', year: 'numeric' })
)
const canGoNext = computed(
    () => viewMonth.value < new Date(today.getFullYear(), today.getMonth(), 1)
)

const calendarCells = computed(() => {
    const year = viewMonth.value.getFullYear()
    const month = viewMonth.value.getMonth()
    const lead = new Date(year, month, 1).getDay()
    const count = new Date(year, month + 1, 0).getDate()

    let lo = draft.value.from
    let hi = draft.value.to
    if (anchor.value && hoverDate.value) {
        lo = anchor.value < hoverDate.value ? anchor.value : hoverDate.value
        hi = anchor.value < hoverDate.value ? hoverDate.value : anchor.value
    }

    const cells = Array.from({ length: lead }, () => null)
    for (let day = 1; day <= count; day++) {
        const date = toDateInput(new Date(year, month, day))
        cells.push({
            date,
            day,
            isStart: date === lo,
            isEnd: date === hi,
            inRange: date > lo && date < hi,
            isToday: date === todayStr,
            disabled: date > todayStr
        })
    }
    return cells
})

function openRange() {
    draft.value = { from: filters.value.from, to: filters.value.to }
    anchor.value = ''
    hoverDate.value = ''
    const end = parseDate(filters.value.to)
    viewMonth.value = new Date(end.getFullYear(), end.getMonth(), 1)
    rangeOpen.value = true
}

function toggleRange() {
    if (rangeOpen.value) rangeOpen.value = false
    else openRange()
}

function shiftMonth(step) {
    viewMonth.value = new Date(viewMonth.value.getFullYear(), viewMonth.value.getMonth() + step, 1)
}

function pickDay(date) {
    if (!anchor.value) {
        anchor.value = date
        draft.value = { from: date, to: date }
        return
    }
    const [from, to] = anchor.value <= date ? [anchor.value, date] : [date, anchor.value]
    draft.value = { from, to }
    anchor.value = ''
    hoverDate.value = ''
}

function applyRange(range = draft.value) {
    filters.value.from = range.from
    filters.value.to = range.to
    rangeOpen.value = false
    loadReports()
}

function onOutsidePointer(event) {
    if (rangeOpen.value && rangeRef.value && !rangeRef.value.contains(event.target)) {
        rangeOpen.value = false
    }
}

const canViewStores = computed(() => auth.can(PERMISSIONS.STORES_VIEW))
const summary = computed(() => reports.value?.summary || {})

/* ---------- derived data ---------- */
const comparison = computed(() => {
    const sales = Number(summary.value.net_sales || 0)
    const expenses = Number(summary.value.expense_total || 0)
    const purchases = Number(summary.value.purchase_cost || 0)
    const outgoings = expenses + purchases
    const remaining = sales - outgoings
    const storeId = String(reports.value?.filters?.store_id ?? '')

    let verdict = 'Sales equal recorded spending'
    if (remaining > 0) verdict = 'Sales exceed recorded spending'
    else if (remaining < 0) verdict = 'Recorded spending exceeds sales'

    return {
        sales,
        expenses,
        purchases,
        outgoings,
        remaining,
        ahead: remaining > 0,
        behind: remaining < 0,
        verdict,
        spendShare: sales > 0 ? Math.min(100, (outgoings / sales) * 100) : 0,
        storeName: stores.value.find((s) => String(s.id) === storeId)?.name || 'All stores'
    }
})

const kpis = computed(() => {
    const d = summary.value
    const orders = Number(d.sales_orders || 0)
    const average = orders > 0 ? comparison.value.sales / orders : 0
    return [
        {
            label: 'Net sales',
            value: formatMoney(d.net_sales),
            note: `Gross ${formatMoney(d.gross_sales)} · Returns ${formatMoney(d.refunded)}`,
            accent: 'blue',
            spark: true
        },
        {
            label: 'Sales orders',
            value: orders,
            note: `Average order ${formatMoney(average)}`,
            accent: 'orange',
            icon: 'bi-receipt'
        },
        {
            label: 'Purchases + expenses',
            value: formatMoney(comparison.value.outgoings),
            note: `Purchases ${formatMoney(comparison.value.purchases)} · Expenses ${formatMoney(comparison.value.expenses)}`,
            accent: 'red',
            icon: 'bi-wallet2'
        },
        {
            label: 'Payments received',
            value: formatMoney(d.paid_amount),
            note: `${reports.value?.sales_by_payment_method?.length ?? 0} payment methods`,
            accent: 'purple',
            icon: 'bi-credit-card'
        }
    ]
})

const keyFigures = computed(() => {
    const d = summary.value
    return [
        { label: 'Returns', value: formatMoney(d.refunded) },
        { label: 'Inventory value', value: formatMoney(d.stock_value) },
        { label: 'Low stock items', value: d.low_stock_items ?? 0 },
        { label: 'Pending purchases', value: formatMoney(d.pending_purchase_cost) },
        { label: 'Expense records', value: d.expenses ?? 0 }
    ]
})

const summaryLine = computed(() => {
    const c = comparison.value
    if (c.ahead) return `Sales were ${formatMoney(c.remaining)} above recorded spending.`
    if (c.behind) return `Recorded spending was ${formatMoney(Math.abs(c.remaining))} above sales.`
    return 'Sales equalled recorded spending.'
})

const summaryItems = computed(() => {
    const d = summary.value
    return [
        { label: 'Net sales', value: formatMoney(d.net_sales) },
        { label: 'Gross sales', value: formatMoney(d.gross_sales) },
        { label: 'Returns', value: formatMoney(d.refunded) },
        { label: 'Purchases', value: formatMoney(d.purchase_cost) },
        { label: 'Expenses', value: formatMoney(d.expense_total) },
        { label: 'Purchases + expenses', value: formatMoney(comparison.value.outgoings) },
        { label: 'Payments received', value: formatMoney(d.paid_amount) },
        { label: 'Inventory value', value: formatMoney(d.stock_value) }
    ]
})

const analysisMetrics = computed(() => [
    { label: 'Net sales', value: comparison.value.sales },
    { label: 'Purchases', value: comparison.value.purchases },
    { label: 'Expenses', value: comparison.value.expenses },
    { label: 'Total purchases + expenses', value: comparison.value.outgoings }
])

const salesByDay = computed(() => reports.value?.sales_by_day || [])
const hasSales = computed(() => salesByDay.value.some((d) => d.sales > 0))
const maxDailySales = computed(() => Math.max(1, ...salesByDay.value.map((d) => Number(d.sales || 0))))

/* Sparkline for the net sales card */
function smoothPath(points) {
    if (points.length < 2) return ''
    let path = `M${points[0][0]},${points[0][1]}`
    for (let i = 1; i < points.length; i++) {
        const [x0, y0] = points[i - 1]
        const [x1, y1] = points[i]
        const mx = (x0 + x1) / 2
        path += ` C${mx},${y0} ${mx},${y1} ${x1},${y1}`
    }
    return path
}

const sparkline = computed(() => {
    const days = salesByDay.value
    if (days.length < 2) return { line: '', area: '' }
    const points = days.map((d, i) => [
        (i / (days.length - 1)) * 100,
        28 - (Number(d.sales || 0) / maxDailySales.value) * 24
    ])
    const line = smoothPath(points)
    return { line, area: `${line} L100,32 L0,32 Z` }
})

/* Daily sales chart */
const compactMoney = new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: 'USD',
    notation: 'compact',
    maximumFractionDigits: 1
})

function niceCeil(value) {
    const exp = 10 ** Math.floor(Math.log10(value))
    const f = value / exp
    return (f <= 1 ? 1 : f <= 2 ? 2 : f <= 5 ? 5 : 10) * exp
}

const axisMax = computed(() => niceCeil(maxDailySales.value))

const axisTicks = computed(() =>
    [0, 1, 2, 3, 4].map((i) => ({
        pct: i * 25,
        label: i === 0 ? '$0' : compactMoney.format((axisMax.value / 4) * i)
    }))
)

const bestDay = computed(() => {
    const best = salesByDay.value.reduce((top, d) => (d.sales > (top?.sales ?? 0) ? d : top), null)
    return best ? { label: formatDate(best.date), amount: formatMoney(best.sales), date: best.date } : null
})

const dailyBars = computed(() => {
    const days = salesByDay.value
    const step = Math.max(1, Math.ceil(days.length / 7))
    return days.map((d, i) => ({
        date: d.date,
        label: i % step === 0 ? formatDate(d.date) : '',
        title: `${formatDate(d.date)}: ${formatMoney(d.sales)}`,
        height: d.sales > 0 ? Math.max(1.5, (d.sales / axisMax.value) * 100) : 0,
        best: bestDay.value?.date === d.date,
        align: i < days.length * 0.2 ? 'left' : i > days.length * 0.8 ? 'right' : 'center'
    }))
})

/* ---------- data loading ---------- */
function maybeOpenAnalysis(responseFilters) {
    if (!comparison.value.ahead) return
    const key = [
        responseFilters.from,
        responseFilters.to,
        responseFilters.store_id || 'all',
        comparison.value.sales,
        comparison.value.outgoings
    ].join('|')
    if (key === lastAnalysisKey.value) return
    lastAnalysisKey.value = key
    analysisOpen.value = true
}

async function loadReports() {
    error.value = ''
    loading.value = true
    analysisOpen.value = false
    try {
        const { data } = await api.get('/reports', {
            params: {
                from: filters.value.from,
                to: filters.value.to,
                store_id: filters.value.store_id || undefined
            }
        })
        reports.value = data
        maybeOpenAnalysis(data.filters)
    } catch (e) {
        const validation = e.response?.data?.errors
        error.value = validation
            ? Object.values(validation).flat().join(' ')
            : e.response?.data?.message || 'Could not load reports.'
    } finally {
        loading.value = false
    }
}

async function loadStores() {
    if (!canViewStores.value) return
    try {
        const { data } = await api.get('/stores')
        stores.value = data.data?.data || []
    } catch (e) {
        error.value = e.response?.data?.message || 'Could not load stores for report filters.'
    }
}

/* ---------- Excel export ---------- */
const MONEY_FORMAT = '"$"#,##0.00'
const HEADER_FILL = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF2F5BEA' } }
const THIN_BORDER = { style: 'thin', color: { argb: 'FFD9DEE7' } }

function styleHeader(row) {
    row.font = { bold: true, color: { argb: 'FFFFFFFF' } }
    row.fill = HEADER_FILL
    row.height = 22
    row.alignment = { vertical: 'middle' }
    row.eachCell((cell) => {
        cell.alignment = { vertical: 'middle', horizontal: cell.col > 1 ? 'right' : 'left' }
    })
}

function addTableSheet(workbook, name, columns, rows, { total = false } = {}) {
    const sheet = workbook.addWorksheet(name, { views: [{ state: 'frozen', ySplit: 1 }] })
    sheet.columns = columns.map(({ header, key, width }) => ({ header, key, width }))
    styleHeader(sheet.getRow(1))

    rows.forEach((row) => sheet.addRow(row))

    columns.forEach((col, index) => {
        if (index === 0) return
        const column = sheet.getColumn(col.key)
        column.alignment = { horizontal: 'right' }
        if (col.money) column.numFmt = MONEY_FORMAT
        if (col.date) column.numFmt = 'dd mmm yyyy'
    })
    if (columns[0].date) sheet.getColumn(columns[0].key).numFmt = 'dd mmm yyyy'
    styleHeader(sheet.getRow(1))

    sheet.eachRow((row, rowNumber) => {
        if (rowNumber === 1) return
        row.eachCell((cell) => {
            cell.border = { bottom: THIN_BORDER }
        })
    })

    if (total && rows.length) {
        const totalRow = sheet.addRow({ [columns[0].key]: 'Total' })
        columns.forEach((col, index) => {
            if (index === 0 || !col.sum) return
            totalRow.getCell(col.key).value = rows.reduce((sum, row) => sum + Number(row[col.key] || 0), 0)
        })
        totalRow.font = { bold: true }
        totalRow.eachCell({ includeEmpty: true }, (cell) => {
            cell.border = { top: { style: 'thin', color: { argb: 'FF2F5BEA' } } }
        })
    }
    return sheet
}

async function exportExcel() {
    const r = reports.value
    if (!r || exporting.value) return
    exporting.value = true
    try {
        const { default: ExcelJS } = await import('exceljs')
        const workbook = new ExcelJS.Workbook()
        workbook.created = new Date()

        const d = r.summary
        const c = comparison.value

        // Summary sheet
        const sheet = workbook.addWorksheet('Summary')
        sheet.columns = [{ width: 36 }, { width: 22 }]
        sheet.addRow(['Sales report']).font = { bold: true, size: 16 }
        sheet.addRow([`Period: ${r.filters.from} to ${r.filters.to}`])
        sheet.addRow([`Store: ${c.storeName}`])
        sheet.addRow([])
        styleHeader(sheet.addRow(['Metric', 'Value']))

        const money = (value) => ({ value: Number(value || 0), money: true })
        const lines = [
            ['Net sales', money(d.net_sales)],
            ['Gross sales', money(d.gross_sales)],
            ['Returns', money(d.refunded)],
            ['Sales orders', { value: Number(d.sales_orders || 0) }],
            ['Purchases', money(d.purchase_cost)],
            ['  Received', money(d.received_purchase_cost)],
            ['  Pending', money(d.pending_purchase_cost)],
            ['Expenses', money(d.expense_total)],
            ['Expense records', { value: Number(d.expenses || 0) }],
            ['Purchases + expenses', money(c.outgoings)],
            [c.verdict, { ...money(Math.abs(c.remaining)), bold: true }],
            ['Payments received', money(d.paid_amount)],
            ['Inventory value', money(d.stock_value)],
            ['Low stock items', { value: Number(d.low_stock_items || 0) }]
        ]
        lines.forEach(([label, cell]) => {
            const row = sheet.addRow([label, cell.value])
            row.getCell(2).alignment = { horizontal: 'right' }
            if (cell.money) row.getCell(2).numFmt = MONEY_FORMAT
            if (cell.bold) row.font = { bold: true }
            row.eachCell((x) => { x.border = { bottom: THIN_BORDER } })
        })
        sheet.addRow([])
        sheet.addRow(['Not accounting profit: taxes, payment fees and unrecorded costs are not deducted.']).font = {
            italic: true,
            color: { argb: 'FF6B7280' }
        }

        addTableSheet(
            workbook,
            'Daily sales',
            [
                { header: 'Date', key: 'date', width: 16, date: true },
                { header: 'Sales', key: 'sales', width: 18, money: true, sum: true }
            ],
            r.sales_by_day.map((i) => ({ date: new Date(`${i.date}T00:00:00`), sales: Number(i.sales || 0) })),
            { total: true }
        )

        addTableSheet(
            workbook,
            'Top products',
            [
                { header: 'Product', key: 'product', width: 34 },
                { header: 'Qty sold', key: 'quantity', width: 14, sum: true },
                { header: 'Sales', key: 'sales', width: 18, money: true, sum: true }
            ],
            r.top_products.map((i) => ({ product: i.product_name, quantity: Number(i.quantity || 0), sales: Number(i.sales || 0) })),
            { total: true }
        )

        addTableSheet(
            workbook,
            'Payments',
            [
                { header: 'Method', key: 'method', width: 24 },
                { header: 'Payments', key: 'count', width: 14, sum: true },
                { header: 'Amount', key: 'total', width: 18, money: true, sum: true }
            ],
            r.sales_by_payment_method.map((i) => ({ method: i.method, count: Number(i.payment_count || 0), total: Number(i.total || 0) })),
            { total: true }
        )

        addTableSheet(
            workbook,
            'Expenses',
            [
                { header: 'Category', key: 'category', width: 28 },
                { header: 'Records', key: 'count', width: 14, sum: true },
                { header: 'Amount', key: 'total', width: 18, money: true, sum: true }
            ],
            r.expenses_by_category.map((i) => ({ category: i.category, count: Number(i.expense_count || 0), total: Number(i.total || 0) })),
            { total: true }
        )

        addTableSheet(
            workbook,
            'Order status',
            [
                { header: 'Status', key: 'status', width: 24 },
                { header: 'Orders', key: 'count', width: 14, sum: true }
            ],
            r.order_statuses.map((i) => ({ status: i.status, count: Number(i.count || 0) })),
            { total: true }
        )

        const buffer = await workbook.xlsx.writeBuffer()
        const url = URL.createObjectURL(
            new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' })
        )
        const link = document.createElement('a')
        link.href = url
        link.download = `pos-report-${filters.value.from}-to-${filters.value.to}.xlsx`
        link.click()
        URL.revokeObjectURL(url)
    } catch {
        error.value = 'Could not create the Excel file.'
    } finally {
        exporting.value = false
    }
}

/* ---------- report summary text ---------- */
const listLines = (rows, label, value) =>
    rows.length ? rows.map((r) => `- ${label(r)}: ${value(r)}`).join('\n') : '- None'

const reportSummary = computed(() => {
    const r = reports.value
    if (!r) return ''
    const d = r.summary
    const c = comparison.value
    const days = r.sales_by_day.filter((i) => i.sales > 0)
    return [
        'Sales report summary',
        c.ahead
            ? `Sales were ${formatMoney(c.remaining)} above recorded spending.`
            : c.behind
                ? `Recorded spending was ${formatMoney(Math.abs(c.remaining))} above sales.`
                : 'Sales equalled recorded spending.',
        '',
        `Period: ${r.filters.from} to ${r.filters.to}`,
        `Store: ${c.storeName}`,
        '',
        'Totals',
        `- Net sales: ${formatMoney(d.net_sales)} (gross ${formatMoney(d.gross_sales)}, returns ${formatMoney(d.refunded)})`,
        `- Sales orders: ${d.sales_orders ?? 0}`,
        `- Purchases: ${formatMoney(d.purchase_cost)} (received ${formatMoney(d.received_purchase_cost)}, pending ${formatMoney(d.pending_purchase_cost)})`,
        `- Expenses: ${formatMoney(d.expense_total)} across ${d.expenses ?? 0} records`,
        `- Purchases + expenses: ${formatMoney(c.outgoings)} (${c.spendShare.toFixed(1)}% of net sales)`,
        `- Sales minus recorded spending: ${formatMoney(c.remaining)}`,
        `- Payments received: ${formatMoney(d.paid_amount)}`,
        `- Inventory value: ${formatMoney(d.stock_value)}`,
        `- Low stock items: ${d.low_stock_items ?? 0}`,
        '',
        'Top products (quantity sold, sales)',
        listLines(r.top_products, (i) => i.product_name, (i) => `${i.quantity}, ${formatMoney(i.sales)}`),
        '',
        'Payments by method',
        listLines(r.sales_by_payment_method, (i) => i.method, (i) => `${formatMoney(i.total)} (${i.payment_count} payments)`),
        '',
        'Expenses by category',
        listLines(r.expenses_by_category, (i) => i.category, (i) => `${formatMoney(i.total)} (${i.expense_count} records)`),
        '',
        'Order statuses',
        listLines(r.order_statuses, (i) => i.status, (i) => i.count),
        '',
        'Daily sales (days with sales only)',
        listLines(days, (i) => i.date, (i) => formatMoney(i.sales)),
        '',
        'Note: This is not accounting profit. Taxes, payment fees and unrecorded costs are not deducted, and purchases may still be in inventory.'
    ].join('\n')
})

async function copySummary() {
    try {
        await navigator.clipboard.writeText(reportSummary.value)
        summaryCopied.value = true
        setTimeout(() => (summaryCopied.value = false), 2000)
    } catch {
        summaryCopied.value = false
    }
}

/* ---------- modal behaviour ---------- */
const onKeydown = (event) => {
    if (event.key === 'Escape') {
        analysisOpen.value = false
        rangeOpen.value = false
    }
}
watch(() => filters.value.store_id, loadReports)
watch(analysisOpen, (open) => {
    document.body.style.overflow = open ? 'hidden' : ''
})

onMounted(() => {
    window.addEventListener('keydown', onKeydown)
    document.addEventListener('pointerdown', onOutsidePointer)
    Promise.all([loadStores(), loadReports()])
})
onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKeydown)
    document.removeEventListener('pointerdown', onOutsidePointer)
    document.body.style.overflow = ''
})
</script>

<template>
    <main class="reports-page container-fluid py-4 px-3 px-md-4">
        <!-- Filters -->
        <form class="panel filter-bar mb-4" @submit.prevent>
            <div ref="rangeRef" class="range">
                <button
                    type="button"
                    class="range-btn"
                    aria-haspopup="dialog"
                    :aria-expanded="rangeOpen"
                    @click="toggleRange"
                >
                    <i class="bi bi-calendar3" aria-hidden="true"></i>
                    <span class="range-text">{{ rangeLabel }}</span>
                    <span v-if="activePreset" class="range-tag">{{ activePreset.label }}</span>
                    <i class="bi bi-chevron-down range-caret" aria-hidden="true"></i>
                </button>

                <div v-if="rangeOpen" class="range-pop" role="dialog" aria-label="Choose date range">
                    <ul class="presets">
                        <li v-for="preset in presets" :key="preset.key">
                            <button
                                type="button"
                                :class="{ active: draftPreset?.key === preset.key }"
                                @click="applyRange(preset)"
                            >{{ preset.label }}</button>
                        </li>
                    </ul>

                    <div class="cal">
                        <div class="cal-head">
                            <button type="button" class="cal-nav" aria-label="Previous month" @click="shiftMonth(-1)">
                                <i class="bi bi-chevron-left" aria-hidden="true"></i>
                            </button>
                            <span>{{ monthTitle }}</span>
                            <button type="button" class="cal-nav" aria-label="Next month" :disabled="!canGoNext" @click="shiftMonth(1)">
                                <i class="bi bi-chevron-right" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div class="cal-week" aria-hidden="true">
                            <span v-for="day in weekdays" :key="day">{{ day }}</span>
                        </div>
                        <div class="cal-grid" @mouseleave="hoverDate = ''">
                            <template v-for="(cell, index) in calendarCells" :key="index">
                                <span v-if="!cell" class="cal-blank"></span>
                                <button
                                    v-else
                                    type="button"
                                    class="cal-day"
                                    :class="{
                                        'in-range': cell.inRange,
                                        'is-start': cell.isStart,
                                        'is-end': cell.isEnd,
                                        'is-today': cell.isToday
                                    }"
                                    :disabled="cell.disabled"
                                    :aria-label="cell.date"
                                    :aria-pressed="cell.isStart || cell.isEnd"
                                    @click="pickDay(cell.date)"
                                    @mouseenter="hoverDate = cell.date"
                                >{{ cell.day }}</button>
                            </template>
                        </div>
                        <div class="cal-foot">
                            <span class="cal-summary">{{ anchor ? 'Pick the end date' : draftLabel }}</span>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm" @click="rangeOpen = false">Cancel</button>
                                <button type="button" class="btn btn-primary btn-sm" @click="applyRange()">Apply</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <select
                v-if="canViewStores"
                v-model="filters.store_id"
                class="form-select store-select"
                aria-label="Store"
            >
                <option value="">All stores</option>
                <option v-for="store in stores" :key="store.id" :value="store.id">{{ store.name }}</option>
            </select>

            <span v-if="loading" class="spinner-border spinner-border-sm text-secondary" role="status" aria-label="Loading"></span>

            <button class="btn btn-outline-secondary ms-auto export-btn" type="button" :disabled="!reports || exporting" @click="exportExcel">
                <i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i>{{ exporting ? 'Exporting…' : 'Export Excel' }}
            </button>
        </form>

        <div v-if="error" class="alert alert-danger" role="alert">{{ error }}</div>
        <div v-else-if="loading && !reports" class="report-loading" role="status">
            <span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Loading reports…
        </div>

        <template v-if="reports">
            <!-- Row 1: KPI cards -->
            <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3 mb-3">
                <div v-for="card in kpis" :key="card.label" class="col">
                    <section class="panel kpi" :class="`accent-${card.accent}`">
                        <p class="kpi-label">{{ card.label }}</p>
                        <div class="kpi-main">
                            <strong class="kpi-value">{{ card.value }}</strong>
                            <span v-if="card.icon" class="kpi-icon" aria-hidden="true"><i class="bi" :class="card.icon"></i></span>
                        </div>
                        <p class="kpi-note">{{ card.note }}</p>
                        <svg v-if="card.spark && sparkline.line" class="kpi-spark" viewBox="0 0 100 32" preserveAspectRatio="none" aria-hidden="true">
                            <path :d="sparkline.area" class="spark-area" />
                            <path :d="sparkline.line" class="spark-line" vector-effect="non-scaling-stroke" />
                        </svg>
                    </section>
                </div>
            </div>

            <!-- Summary card -->
            <section class="panel summary-card mb-3" aria-labelledby="summary-heading">
                <header class="panel-head">
                    <div>
                        <h2 id="summary-heading">Summary</h2>
                        <span class="panel-meta">{{ reports.filters.from }} to {{ reports.filters.to }} · {{ comparison.storeName }}</span>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm" @click="copySummary">
                        <i class="bi me-1" :class="summaryCopied ? 'bi-check2' : 'bi-clipboard'" aria-hidden="true"></i>{{ summaryCopied ? 'Copied' : 'Copy summary' }}
                    </button>
                </header>
                <p class="summary-line" :class="{ 'is-negative': comparison.behind }">{{ summaryLine }}</p>
                <dl class="summary-grid">
                    <div v-for="item in summaryItems" :key="item.label">
                        <dt>{{ item.label }}</dt>
                        <dd>{{ item.value }}</dd>
                    </div>
                </dl>
            </section>

            <!-- Row 2: daily sales + top products -->
            <div class="row g-3 mb-3">
                <div class="col-12 col-xl-5">
                    <section class="panel h-100">
                        <header class="panel-head">
                            <h2>Daily sales</h2>
                            <span v-if="bestDay" class="panel-meta">Best day: {{ bestDay.label }} · {{ bestDay.amount }}</span>
                        </header>
                        <p v-if="!hasSales" class="report-empty">No completed or paid sales in this date range.</p>
                        <div v-else class="chart" role="group" :aria-label="`Daily sales from ${reports.filters.from} to ${reports.filters.to}`">
                            <div class="chart-y" aria-hidden="true">
                                <span v-for="tick in axisTicks" :key="tick.pct" :style="{ bottom: `${tick.pct}%` }">{{ tick.label }}</span>
                            </div>
                            <div class="chart-plot">
                                <i v-for="tick in axisTicks" :key="tick.pct" class="chart-line" :style="{ bottom: `${tick.pct}%` }" aria-hidden="true"></i>
                                <div class="chart-bars">
                                    <div
                                        v-for="bar in dailyBars"
                                        :key="bar.date"
                                        class="chart-bar"
                                        :class="{ 'is-best': bar.best }"
                                        tabindex="0"
                                        :aria-label="bar.title"
                                    >
                                        <span class="chart-fill" :style="{ height: `${bar.height}%` }">
                                            <span class="chart-tip" :class="`tip-${bar.align}`">{{ bar.title }}</span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="chart-x" aria-hidden="true">
                                <span v-for="bar in dailyBars" :key="bar.date">{{ bar.label }}</span>
                            </div>
                        </div>
                    </section>
                </div>
                <div class="col-12 col-xl-7">
                    <section class="panel h-100">
                        <header class="panel-head">
                            <h2>Top selling products</h2>
                            <span class="panel-meta">Top 10</span>
                        </header>
                        <p v-if="!reports.top_products.length" class="report-empty">No product sales in this date range.</p>
                        <div v-else class="table-responsive">
                            <table class="table clean-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col" class="num-col">No</th>
                                        <th scope="col">Product</th>
                                        <th scope="col" class="text-end">Qty sold</th>
                                        <th scope="col" class="text-end">Sales</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in reports.top_products" :key="item.product_id">
                                        <td class="num-col">{{ index + 1 }}</td>
                                        <td>{{ item.product_name }}</td>
                                        <td class="text-end">{{ item.quantity }}</td>
                                        <td class="text-end fw-semibold">{{ formatMoney(item.sales) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>

            <!-- Row 3: performance + key figures + breakdown -->
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6 col-xl-3">
                    <section class="performance h-100">
                        <h2>Performance</h2>
                        <div
                            class="donut"
                            :style="{ '--share': `${comparison.spendShare}%` }"
                            role="img"
                            :aria-label="`Spending is ${comparison.spendShare.toFixed(1)}% of net sales`"
                        >
                            <span>{{ comparison.spendShare.toFixed(0) }}%</span>
                        </div>
                        <p class="performance-text">
                            Purchases and expenses are {{ comparison.spendShare.toFixed(1) }}% of net sales.
                            <strong>{{ comparison.verdict }}: {{ formatMoney(Math.abs(comparison.remaining)) }}.</strong>
                        </p>
                        <button type="button" class="btn performance-btn" @click="analysisOpen = true">See full analysis</button>
                    </section>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <section class="panel h-100">
                        <header class="panel-head"><h2>Key figures</h2></header>
                        <dl class="figure-list">
                            <div v-for="item in keyFigures" :key="item.label">
                                <dt>{{ item.label }}</dt>
                                <dd>{{ item.value }}</dd>
                            </div>
                        </dl>
                    </section>
                </div>

                <div class="col-12 col-xl-6">
                    <section class="panel h-100">
                        <header class="panel-head"><h2>Breakdown</h2></header>
                        <div class="breakdown">
                            <div>
                                <h3>Payments by method</h3>
                                <p v-if="!reports.sales_by_payment_method.length" class="report-empty small">No payment records.</p>
                                <ul v-else class="line-list">
                                    <li v-for="item in reports.sales_by_payment_method" :key="item.method">
                                        <span class="line-main">{{ item.method }}<small>{{ item.payment_count }} payments</small></span>
                                        <strong>{{ formatMoney(item.total) }}</strong>
                                    </li>
                                </ul>
                            </div>
                            <div>
                                <h3>Expenses by category</h3>
                                <p v-if="!reports.expenses_by_category.length" class="report-empty small">No expenses.</p>
                                <ul v-else class="line-list">
                                    <li v-for="item in reports.expenses_by_category" :key="item.category">
                                        <span class="line-main">{{ item.category }}<small>{{ item.expense_count }} records</small></span>
                                        <strong>{{ formatMoney(item.total) }}</strong>
                                    </li>
                                </ul>
                            </div>
                            <div>
                                <h3>Order status</h3>
                                <p v-if="!reports.order_statuses.length" class="report-empty small">No orders.</p>
                                <ul v-else class="line-list">
                                    <li v-for="item in reports.order_statuses" :key="item.status">
                                        <span class="line-main text-capitalize status" :class="statusTone(item.status)">{{ item.status }}</span>
                                        <strong>{{ item.count }}</strong>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <p class="report-footnote text-muted small">
                Net sales are gross paid/completed sales less recorded sales returns. Inventory value uses current on-hand stock multiplied by product cost.
            </p>
        </template>

        <!-- Analysis modal -->
        <div v-if="analysisOpen && reports" class="modal-backdrop-custom" @click.self="analysisOpen = false">
            <section class="modal-card" role="dialog" aria-modal="true" aria-labelledby="sales-analysis-title">
                <header class="modal-head">
                    <div>
                        <p class="small text-muted mb-1">Sales performance analysis</p>
                        <h2 id="sales-analysis-title" class="h5 mb-0">{{ comparison.verdict }}</h2>
                    </div>
                    <button type="button" class="btn-close" aria-label="Close analysis" @click="analysisOpen = false"></button>
                </header>

                <div class="modal-body-custom">
                    <p class="text-muted small mb-3">
                        {{ reports.filters.from }} to {{ reports.filters.to }} · {{ comparison.storeName }}
                    </p>

                    <dl class="analysis-metrics">
                        <div v-for="metric in analysisMetrics" :key="metric.label">
                            <dt>{{ metric.label }}</dt>
                            <dd>{{ formatMoney(metric.value) }}</dd>
                        </div>
                        <div class="analysis-result" :class="{ 'is-negative': comparison.behind }">
                            <dt>{{ comparison.behind ? 'Spending above sales' : 'Sales above recorded spending' }}</dt>
                            <dd>{{ formatMoney(Math.abs(comparison.remaining)) }}</dd>
                        </div>
                    </dl>

                    <div class="my-4">
                        <div class="d-flex justify-content-between gap-3 small mb-2">
                            <span>Recorded spending as a share of net sales</span>
                            <strong>{{ comparison.spendShare.toFixed(1) }}%</strong>
                        </div>
                        <div class="share-track" role="img" :aria-label="`${comparison.spendShare.toFixed(1)}% of net sales`">
                            <div class="share-fill" :style="{ width: `${comparison.spendShare}%` }"></div>
                        </div>
                    </div>

                    <p class="note small text-muted mb-0">
                        This compares sales with purchases and recorded expenses. It is not accounting profit: purchases may remain in inventory, and product costs, taxes, payment fees and other unrecorded costs are not deducted.
                    </p>
                </div>

                <footer class="modal-foot">
                    <button type="button" class="btn btn-primary" @click="analysisOpen = false">Close</button>
                </footer>
            </section>
        </div>
    </main>
</template>

<style scoped>
.reports-page {
    --acc-blue: #2f5bea;
    --acc-orange: #f59e0b;
    --acc-red: #ef4444;
    --acc-purple: #8b5cf6;
    --good: #16a34a;
    --bad: #dc2626;
    --card-radius: .75rem;
    color: var(--ui-text, var(--bs-body-color));
}

/* Base panel */
.panel {
    position: relative;
    overflow: hidden;
    border: 1px solid var(--ui-border, var(--bs-border-color));
    border-radius: var(--card-radius);
    background: var(--ui-surface, var(--bs-body-bg));
    color: var(--ui-text, var(--bs-body-color));
}

/* Filter bar */
.filter-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .75rem;
    padding: .75rem 1rem;
    overflow: visible;
}

.store-select {
    width: auto;
    min-width: 11rem;
    border-color: var(--ui-border, var(--bs-border-color));
    background-color: var(--ui-control, var(--bs-body-bg));
    color: var(--ui-text, var(--bs-body-color));
}

.range {
    position: relative;
}

.range-btn {
    display: inline-flex;
    align-items: center;
    gap: .6rem;
    min-height: 2.4rem;
    padding: .45rem .85rem;
    border: 1px solid var(--ui-border, var(--bs-border-color));
    border-radius: .5rem;
    background: var(--ui-control, var(--bs-body-bg));
    color: var(--ui-text, var(--bs-body-color));
    font-size: .9rem;
}

.range-btn:hover,
.range-btn:focus-visible,
.range-btn[aria-expanded="true"] {
    border-color: var(--acc-blue);
}

.range-tag {
    padding: .1rem .5rem;
    border-radius: 999px;
    background: color-mix(in srgb, var(--acc-blue) 12%, transparent);
    color: var(--acc-blue);
    font-size: .7rem;
    font-weight: 600;
}

.range-caret {
    color: var(--ui-muted, var(--bs-secondary-color));
    font-size: .7rem;
}

.range-pop {
    position: absolute;
    top: calc(100% + .5rem);
    left: 0;
    z-index: 1040;
    display: flex;
    width: min(92vw, 36rem);
    overflow: hidden;
    border: 1px solid var(--ui-border, var(--bs-border-color));
    border-radius: var(--card-radius);
    background: var(--ui-surface, var(--bs-body-bg));
    color: var(--ui-text, var(--bs-body-color));
    box-shadow: var(--ui-shadow-lg, 0 20px 45px rgb(16 24 40 / .18));
}

.presets {
    display: flex;
    flex: 0 0 9.5rem;
    flex-direction: column;
    gap: .15rem;
    margin: 0;
    padding: .5rem;
    border-right: 1px solid var(--ui-border, var(--bs-border-color));
    background: var(--ui-surface-muted, var(--bs-tertiary-bg));
    list-style: none;
}

.presets button {
    width: 100%;
    padding: .45rem .65rem;
    border: 0;
    border-radius: .4rem;
    background: transparent;
    color: inherit;
    font-size: .8125rem;
    text-align: left;
}

.presets button:hover {
    background: color-mix(in srgb, var(--acc-blue) 10%, transparent);
}

.presets button.active {
    background: var(--acc-blue);
    color: #fff;
}

.cal {
    min-width: 0;
    flex: 1;
    padding: .85rem;
}

.cal-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: .5rem;
    font-size: .9rem;
    font-weight: 600;
}

.cal-nav {
    width: 2rem;
    height: 2rem;
    border: 0;
    border-radius: 50%;
    background: transparent;
    color: inherit;
}

.cal-nav:hover:not(:disabled) {
    background: color-mix(in srgb, var(--acc-blue) 10%, transparent);
}

.cal-nav:disabled {
    opacity: .35;
}

.cal-week,
.cal-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
}

.cal-week span {
    padding: .25rem 0;
    color: var(--ui-muted, var(--bs-secondary-color));
    font-size: .6875rem;
    text-align: center;
}

.cal-day {
    height: 2.1rem;
    border: 0;
    border-radius: 0;
    background: transparent;
    color: inherit;
    font-size: .8125rem;
}

.cal-day:hover:not(:disabled) {
    background: color-mix(in srgb, var(--acc-blue) 22%, transparent);
}

.cal-day.in-range {
    background: color-mix(in srgb, var(--acc-blue) 14%, transparent);
}

.cal-day.is-start,
.cal-day.is-end {
    background: var(--acc-blue);
    color: #fff;
    font-weight: 600;
}

.cal-day.is-start { border-radius: 999px 0 0 999px; }
.cal-day.is-end { border-radius: 0 999px 999px 0; }
.cal-day.is-start.is-end { border-radius: 999px; }

.cal-day.is-today:not(.is-start):not(.is-end) {
    color: var(--acc-blue);
    font-weight: 700;
}

.cal-day:disabled {
    opacity: .3;
    cursor: not-allowed;
}

.cal-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    margin-top: .75rem;
    padding-top: .75rem;
    border-top: 1px solid var(--ui-border, var(--bs-border-color));
    font-size: .8125rem;
}

.cal-summary {
    color: var(--ui-muted, var(--bs-secondary-color));
}

.panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    padding: 1rem 1.25rem .5rem;
}

.panel-head h2,
.performance h2 {
    margin: 0;
    font-size: .95rem;
    font-weight: 600;
}

.panel-meta {
    color: var(--ui-muted, var(--bs-secondary-color));
    font-size: .75rem;
}

.report-empty,
.report-loading {
    padding: 2.5rem 1rem;
    color: var(--ui-muted, var(--bs-secondary-color));
    text-align: center;
}

.report-empty.small {
    padding: .75rem 0;
    text-align: left;
}

.report-footnote {
    padding: .25rem 0 1rem;
}

/* KPI cards */
.kpi {
    height: 100%;
    padding: 1.1rem 1.25rem 1rem;
    border-top: 3px solid var(--accent);
}

.accent-blue { --accent: var(--acc-blue); }
.accent-orange { --accent: var(--acc-orange); }
.accent-red { --accent: var(--acc-red); }
.accent-purple { --accent: var(--acc-purple); }

.kpi-label {
    margin: 0 0 .35rem;
    color: var(--ui-muted, var(--bs-secondary-color));
    font-size: .8125rem;
}

.kpi-main {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
}

.kpi-value {
    font-size: 1.6rem;
    font-weight: 700;
    line-height: 1.2;
    overflow-wrap: anywhere;
}

.kpi-icon {
    display: inline-flex;
    width: 2.25rem;
    height: 2.25rem;
    flex: 0 0 auto;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: color-mix(in srgb, var(--accent) 14%, transparent);
    color: var(--accent);
}

.kpi-note {
    margin: .4rem 0 0;
    color: var(--ui-muted, var(--bs-secondary-color));
    font-size: .75rem;
}

.kpi-spark {
    display: block;
    width: 100%;
    height: 2.4rem;
    margin-top: .6rem;
}

.spark-line {
    fill: none;
    stroke: var(--accent);
    stroke-width: 1.75;
    stroke-linecap: round;
}

.spark-area {
    fill: color-mix(in srgb, var(--accent) 12%, transparent);
}

/* Daily sales chart */
.chart {
    display: grid;
    grid-template-columns: 2.75rem minmax(0, 1fr);
    grid-template-rows: 14rem auto;
    column-gap: .6rem;
    padding: 1rem 1.25rem 1rem .75rem;
}

.chart-y {
    position: relative;
}

.chart-y span {
    position: absolute;
    right: 0;
    transform: translateY(50%);
    color: var(--ui-muted, var(--bs-secondary-color));
    font-size: .6875rem;
    line-height: 1;
}

.chart-plot {
    position: relative;
}

.chart-line {
    position: absolute;
    right: 0;
    left: 0;
    border-top: 1px dashed var(--ui-border, var(--bs-border-color));
}

.chart-line:first-of-type {
    border-top-style: solid;
}

.chart-bars {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: stretch;
    gap: 3px;
}

.chart-bar {
    position: relative;
    display: flex;
    flex: 1 1 0;
    min-width: 0;
    align-items: flex-end;
    outline: none;
}

.chart-fill {
    position: relative;
    display: block;
    width: 100%;
    border-radius: 3px 3px 0 0;
    background: color-mix(in srgb, var(--acc-blue) 55%, transparent);
    transition: background-color .12s;
}

.chart-bar.is-best .chart-fill,
.chart-bar:hover .chart-fill,
.chart-bar:focus-visible .chart-fill {
    background: var(--acc-blue);
}

.chart-bar:focus-visible .chart-fill {
    outline: 2px solid var(--acc-blue);
    outline-offset: 2px;
}

.chart-tip {
    position: absolute;
    bottom: calc(100% + .35rem);
    z-index: 2;
    padding: .25rem .5rem;
    border-radius: .35rem;
    background: #1f2937;
    color: #fff;
    font-size: .6875rem;
    white-space: nowrap;
    pointer-events: none;
    opacity: 0;
    transition: opacity .1s;
}

.tip-center { left: 50%; transform: translateX(-50%); }
.tip-left { left: 0; }
.tip-right { right: 0; }

.chart-bar:hover .chart-tip,
.chart-bar:focus-visible .chart-tip {
    opacity: 1;
}

.chart-x {
    grid-column: 2;
    display: flex;
    gap: 3px;
    padding-top: .5rem;
}

.chart-x span {
    flex: 1 1 0;
    min-width: 0;
    overflow: visible;
    color: var(--ui-muted, var(--bs-secondary-color));
    font-size: .6875rem;
    text-align: center;
    white-space: nowrap;
}

/* Clean table */
.clean-table {
    --bs-table-bg: transparent;
    --bs-table-color: var(--ui-text, var(--bs-body-color));
    --bs-table-border-color: var(--ui-border, var(--bs-border-color));
    --bs-table-hover-bg: var(--ui-surface-muted, var(--bs-tertiary-bg));
    font-size: .875rem;
}

.clean-table thead th {
    padding: .65rem 1.25rem;
    border-bottom: 1px solid var(--ui-border, var(--bs-border-color));
    background: var(--ui-surface-muted, var(--bs-tertiary-bg));
    color: var(--ui-muted, var(--bs-secondary-color));
    font-size: .75rem;
    font-weight: 500;
}

.clean-table tbody td {
    padding: .7rem 1.25rem;
}

.num-col {
    width: 3.5rem;
    color: var(--ui-muted, var(--bs-secondary-color));
}

/* Performance card */
.performance {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem;
    border-radius: var(--card-radius);
    background: var(--acc-blue);
    color: #fff;
    text-align: center;
}

.performance h2 {
    align-self: flex-start;
    color: #fff;
}

.donut {
    --share: 0%;
    display: grid;
    width: 8rem;
    height: 8rem;
    place-items: center;
    border-radius: 50%;
    background: conic-gradient(var(--acc-orange) var(--share), rgb(255 255 255 / 25%) 0);
}

.donut span {
    display: grid;
    width: 5.6rem;
    height: 5.6rem;
    place-items: center;
    border-radius: 50%;
    background: var(--acc-blue);
    font-size: 1.5rem;
    font-weight: 700;
}

.performance-text {
    margin: 0;
    font-size: .8125rem;
    line-height: 1.5;
}

.performance-btn {
    width: 100%;
    margin-top: auto;
    border: 1px solid rgb(255 255 255 / 70%);
    color: #fff;
    font-size: .8125rem;
}

.performance-btn:hover,
.performance-btn:focus-visible {
    background: rgb(255 255 255 / 15%);
    color: #fff;
}

/* Key figures */
.figure-list {
    margin: 0;
    padding: .25rem 1.25rem 1rem;
}

.figure-list > div {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: .7rem 0;
    border-bottom: 1px solid var(--ui-border, var(--bs-border-color));
    font-size: .875rem;
}

.figure-list > div:last-child {
    border-bottom: 0;
}

.figure-list dt {
    color: var(--ui-muted, var(--bs-secondary-color));
    font-weight: 400;
}

.figure-list dd {
    margin: 0;
    font-weight: 600;
    text-align: right;
}

/* Breakdown lists */
.breakdown {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(13rem, 1fr));
    gap: 1.25rem;
    padding: .25rem 1.25rem 1.25rem;
}

.breakdown h3 {
    margin: 0 0 .5rem;
    color: var(--ui-muted, var(--bs-secondary-color));
    font-size: .75rem;
    font-weight: 500;
}

.line-list {
    margin: 0;
    padding: 0;
    list-style: none;
}

.line-list li {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    padding: .55rem 0;
    border-bottom: 1px solid var(--ui-border, var(--bs-border-color));
    font-size: .8125rem;
}

.line-list li:last-child {
    border-bottom: 0;
}

.line-main {
    display: flex;
    min-width: 0;
    flex-direction: column;
}

.line-main small {
    color: var(--ui-muted, var(--bs-secondary-color));
    font-size: .6875rem;
}

.status.is-good { color: var(--good); }
.status.is-bad { color: var(--bad); }

/* Modals */
.modal-backdrop-custom {
    position: fixed;
    inset: 0;
    z-index: 1050;
    display: grid;
    place-items: center;
    overflow-y: auto;
    padding: 1rem;
    background: rgb(15 23 42 / 65%);
}

.modal-card {
    width: min(100%, 36rem);
    overflow: hidden;
    border: 1px solid var(--ui-border, var(--bs-border-color));
    border-radius: var(--ui-modal-radius, .875rem);
    background: var(--ui-surface, var(--bs-body-bg));
    color: var(--ui-text, var(--bs-body-color));
    box-shadow: var(--ui-shadow-lg, 0 20px 45px rgb(16 24 40 / .2));
}

.modal-head,
.modal-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.25rem;
}

.modal-head {
    border-bottom: 1px solid var(--ui-border, var(--bs-border-color));
    background: var(--ui-surface-muted, var(--bs-tertiary-bg));
}

.modal-foot {
    justify-content: flex-end;
    border-top: 1px solid var(--ui-border, var(--bs-border-color));
}

.modal-body-custom {
    padding: 1.25rem;
}

.analysis-metrics {
    display: grid;
    gap: .75rem;
    margin: 0;
}

.analysis-metrics > div {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding-bottom: .65rem;
    border-bottom: 1px solid var(--ui-border, var(--bs-border-color));
}

.analysis-metrics dt {
    color: var(--ui-muted, var(--bs-secondary-color));
    font-weight: 400;
}

.analysis-metrics dd {
    margin: 0;
    font-weight: 700;
}

.analysis-metrics > .analysis-result {
    margin-top: .15rem;
    padding: .8rem;
    border: 0;
    border-radius: var(--ui-radius-md, .5rem);
    background: var(--ui-success-soft, var(--bs-success-bg-subtle));
}

.analysis-metrics > .analysis-result.is-negative {
    background: var(--ui-danger-soft, var(--bs-danger-bg-subtle));
}

.analysis-metrics > .analysis-result dt {
    color: var(--ui-text, var(--bs-body-color));
}

.share-track {
    height: .7rem;
    overflow: hidden;
    border-radius: 999px;
    background: var(--ui-surface-muted, var(--bs-tertiary-bg));
}

.share-fill {
    height: 100%;
    border-radius: inherit;
    background: var(--acc-blue);
}

.note {
    padding: .75rem;
    border-radius: var(--ui-radius-md, .5rem);
    background: var(--ui-surface-muted, var(--bs-tertiary-bg));
}

/* Summary card */
.summary-line {
    margin: 0;
    padding: 0 1.25rem .75rem;
    color: var(--good);
    font-size: 1.05rem;
    font-weight: 600;
}

.summary-line.is-negative {
    color: var(--bad);
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr));
    gap: 0;
    margin: 0;
    padding: 0 1.25rem 1rem;
}

.summary-grid > div {
    padding: .65rem 0;
    border-top: 1px solid var(--ui-border, var(--bs-border-color));
}

.summary-grid dt {
    color: var(--ui-muted, var(--bs-secondary-color));
    font-size: .75rem;
    font-weight: 400;
}

.summary-grid dd {
    margin: .1rem 0 0;
    font-size: 1rem;
    font-weight: 600;
}

/* Mobile */
@media (max-width: 575.98px) {
    .range,
    .store-select,
    .export-btn {
        width: 100%;
        flex: 1 1 100%;
        margin-left: 0 !important;
    }

    .range-btn {
        width: 100%;
    }

    .range-pop {
        flex-direction: column;
        width: 100%;
    }

    .presets {
        flex: 0 0 auto;
        flex-flow: row wrap;
        border-right: 0;
        border-bottom: 1px solid var(--ui-border, var(--bs-border-color));
    }

    .presets button {
        width: auto;
    }

    .cal-foot {
        flex-wrap: wrap;
    }

    .chart {
        grid-template-columns: 2.4rem minmax(0, 1fr);
        grid-template-rows: 12rem auto;
        padding-inline: .5rem .75rem;
    }

    .chart-bars,
    .chart-x {
        gap: 2px;
    }

    .panel-head,
    .summary-line,
    .summary-grid,
    .figure-list,
    .breakdown {
        padding-inline: 1rem;
    }

    .clean-table thead th,
    .clean-table tbody td {
        padding-inline: .75rem;
    }

    .modal-head,
    .modal-body-custom,
    .modal-foot {
        padding: 1rem;
    }
}
</style>