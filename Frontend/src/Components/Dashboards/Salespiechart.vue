<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Pie } from 'vue-chartjs'
import { Chart as ChartJS, ArcElement, Tooltip } from 'chart.js'

ChartJS.register(ArcElement, Tooltip)

const props = defineProps({
    // Real data: dashboardStore.topProducts
    products: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    maxSlices: { type: Number, default: 5 },
    title: { type: String, default: 'Product sales share' },
    subtitle: { type: String, default: 'Best sellers by units sold' },
    // Set true to switch animation off for users with "reduce motion" enabled
    respectReducedMotion: { type: Boolean, default: false },
})

/* ---------- Data mapping (same field fallbacks as the dashboard) ---------- */
const PALETTE = ['#E06666', '#FAE59A', '#B5D6A7', '#D5A6BD', '#F8CFCB', '#A9C4EB', '#F9CB9C']

const productName = p => p.name ?? p.product_name ?? p.product?.name ?? 'Unnamed product'
const soldQty = p => Number(p.total_sold ?? p.quantity_sold ?? p.total_quantity ?? 0) || 0

const slices = computed(() => {
    const rows = (props.products ?? [])
        .map(p => ({ name: productName(p), value: soldQty(p) }))
        .filter(r => r.value > 0)
        .sort((a, b) => b.value - a.value)

    const top = rows.slice(0, props.maxSlices)
    const rest = rows.slice(props.maxSlices)
    if (rest.length) {
        top.push({ name: 'Others', value: rest.reduce((s, r) => s + r.value, 0) })
    }
    return top.map((r, i) => ({ ...r, color: PALETTE[i % PALETTE.length] }))
})

const total = computed(() => slices.value.reduce((s, r) => s + r.value, 0))
const hasData = computed(() => total.value > 0)

const percent = value => (total.value ? (value / total.value) * 100 : 0)
const formatNumber = v => new Intl.NumberFormat('en-US').format(Number(v) || 0)

/* ---------- Animate when the card scrolls into view ---------- */
const root = ref(null)
const visible = ref(false)
let observer = null

onMounted(() => {
    if (!('IntersectionObserver' in window)) {
        visible.value = true
        return
    }
    observer = new IntersectionObserver(
        ([entry]) => {
            if (entry.isIntersecting) {
                visible.value = true
                observer?.disconnect()
            }
        },
        { threshold: 0.25 }
    )
    observer.observe(root.value)
})

onBeforeUnmount(() => {
    observer?.disconnect()
    cancelAnimationFrame(rafId)
})

/* ---------- Count-up for total ---------- */
const displayTotal = ref(0)
let rafId = 0

const countUp = target => {
    cancelAnimationFrame(rafId)
    if (reduceMotion) {
        displayTotal.value = target
        return
    }
    const start = performance.now()
    const duration = 1400
    const from = 0
    const tick = now => {
        const t = Math.min((now - start) / duration, 1)
        const eased = 1 - Math.pow(1 - t, 3)
        displayTotal.value = Math.round(from + (target - from) * eased)
        if (t < 1) rafId = requestAnimationFrame(tick)
    }
    rafId = requestAnimationFrame(tick)
}

const reduceMotion =
    props.respectReducedMotion &&
    typeof window !== 'undefined' &&
    window.matchMedia?.('(prefers-reduced-motion: reduce)').matches

watch(
    [visible, total],
    async ([isVisible, value]) => {
        if (!isVisible) return
        await nextTick()
        countUp(value)
    },
    { immediate: true }
)

/* ---------- Chart.js config ---------- */
const isLight = hex => {
    const n = parseInt(hex.slice(1), 16)
    const r = (n >> 16) & 255, g = (n >> 8) & 255, b = n & 255
    return (0.299 * r + 0.587 * g + 0.114 * b) / 255 > 0.62
}

// Draws "28.0%" inside every slice, fading in as the slices finish growing
const percentLabels = {
    id: 'percentLabels',
    afterDatasetsDraw(chart, args) {
        const { ctx } = chart
        const meta = chart.getDatasetMeta(0)
        const data = chart.data.datasets[0].data
        const colors = chart.data.datasets[0].backgroundColor
        const sum = data.reduce((s, v) => s + v, 0)
        if (!sum) return

        const progress = Math.max(0, (args.easingValue - 0.55) / 0.45)
        ctx.save()
        ctx.globalAlpha = progress
        ctx.font = '600 12px Inter, system-ui, sans-serif'
        ctx.textAlign = 'center'
        ctx.textBaseline = 'middle'

        meta.data.forEach((arc, i) => {
            const share = (data[i] / sum) * 100
            if (share < 4) return // too small to fit a label
            const { x, y } = arc.getCenterPoint()
            ctx.fillStyle = isLight(colors[i]) ? '#1d2939' : '#ffffff'
            ctx.fillText(`${share.toFixed(1)}%`, x, y)
        })
        ctx.restore()
    },
}

const chartData = computed(() => ({
    labels: slices.value.map(s => s.name),
    datasets: [
        {
            data: slices.value.map(s => s.value),
            backgroundColor: slices.value.map(s => s.color),
            borderColor: '#ffffff',
            borderWidth: 3,
            hoverOffset: 10,
            hoverBorderColor: '#ffffff',
        },
    ],
}))

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    layout: { padding: 12 },
    animation: reduceMotion
        ? false
        : {
              animateRotate: true,
              animateScale: true,
              duration: 1300,
              easing: 'easeOutQuart',
              // each slice starts a little after the previous one
              delay: ctx =>
                  ctx.type === 'data' && ctx.mode === 'default' ? ctx.dataIndex * 140 : 0,
          },
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: '#101828',
            padding: 12,
            displayColors: true,
            boxPadding: 4,
            callbacks: {
                label: c =>
                    ` ${formatNumber(c.parsed)} units · ${percent(c.parsed).toFixed(1)}%`,
            },
        },
    },
}))

// New data = new key = chart replays its opening animation
const replayCount = ref(0)
const replay = () => {
    replayCount.value++
    countUp(total.value)
}
const chartKey = computed(
    () => replayCount.value + '|' + slices.value.map(s => `${s.name}:${s.value}`).join('|')
)
</script>

<template>
    <article ref="root" class="panel pie-card">
        <div class="pie-heading">
            <div class="pie-title">
                <span class="pie-title-icon"><i class="bi bi-pie-chart"></i></span>
                <div>
                    <h3>{{ title }}</h3>
                    <p>{{ subtitle }}</p>
                </div>
            </div>
            <div class="pie-side">
                <button v-if="hasData" type="button" class="pie-replay" @click="replay">
                    <i class="bi bi-arrow-repeat"></i>
                    Replay
                </button>
                <div class="pie-total" v-if="hasData">
                    <span>Total units sold</span>
                    <strong>{{ formatNumber(displayTotal) }}</strong>
                </div>
            </div>
        </div>

        <div v-if="loading" class="pie-state">
            <span class="spinner-border spinner-border-sm"></span>
            Loading chart...
        </div>

        <div v-else-if="!hasData" class="pie-state">
            <i class="bi bi-pie-chart"></i>
            <strong>No sales data yet</strong>
            <p>The chart appears once products have sold.</p>
        </div>

        <div v-else class="pie-body">
            <div class="pie-canvas">
                <Pie
                    v-if="visible"
                    :key="chartKey"
                    :data="chartData"
                    :options="chartOptions"
                    :plugins="[percentLabels]"
                />
            </div>

            <ul :key="chartKey" class="pie-legend" :class="{ show: visible, still: reduceMotion }">
                <li
                    v-for="(slice, index) in slices"
                    :key="slice.name"
                    :style="{ '--i': index }"
                >
                    <span class="dot" :style="{ background: slice.color }"></span>
                    <span class="name">{{ slice.name }}</span>
                    <span class="value">{{ formatNumber(slice.value) }}</span>
                    <span class="share">{{ percent(slice.value).toFixed(1) }}%</span>
                </li>
            </ul>
        </div>
    </article>
</template>

<style scoped>
.pie-card {
    grid-column: 1 / -1; /* full row inside .quick-view-grid */
    padding: 22px;
}

.pie-heading {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
}

.pie-title {
    display: flex;
    align-items: center;
    gap: 10px;
}

.pie-title-icon {
    display: grid;
    place-items: center;
    width: 38px;
    height: 38px;
    border-radius: 11px;
    background: #f4f0ff;
    color: #6941c6;
    font-size: 16px;
}

.pie-title h3 {
    margin: 0;
    color: #101828;
    font-size: 14px;
    font-weight: 750;
}

.pie-title p {
    margin: 4px 0 0;
    color: #667085;
    font-size: 11px;
}

.pie-side {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 10px;
}

.pie-replay {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 11px;
    border: 1px solid #d0d5dd;
    border-radius: 8px;
    background: #fff;
    color: #344054;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
}

.pie-replay:hover {
    border-color: #b7c8ef;
    background: #f8faff;
}

.pie-total {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4px;
}

.pie-total span {
    color: #667085;
    font-size: 11px;
}

.pie-total strong {
    color: #101828;
    font-size: 24px;
    font-weight: 800;
    letter-spacing: -0.6px;
    font-variant-numeric: tabular-nums;
}

.pie-body {
    display: grid;
    grid-template-columns: minmax(240px, 380px) minmax(0, 1fr);
    align-items: center;
    gap: 36px;
    margin-top: 12px;
}

.pie-canvas {
    position: relative;
    height: 320px;
}

.pie-legend {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 6px 28px;
    margin: 0;
    padding: 0;
    list-style: none;
}

.pie-legend li {
    display: grid;
    grid-template-columns: 22px minmax(0, 1fr) auto auto;
    align-items: center;
    gap: 10px;
    padding: 12px 0;
    border-bottom: 1px solid #f2f4f7;
    opacity: 0;
    transform: translateX(-8px);
}

.pie-legend.show li {
    animation: legend-in 0.5s cubic-bezier(0.22, 1, 0.36, 1) forwards;
    animation-delay: calc(0.5s + var(--i) * 0.12s);
}

.dot {
    width: 18px;
    height: 18px;
    border-radius: 50%;
}

.name {
    overflow: hidden;
    color: #344054;
    font-size: 12px;
    font-weight: 650;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.value {
    color: #101828;
    font-size: 12px;
    font-weight: 750;
    font-variant-numeric: tabular-nums;
}

.share {
    min-width: 46px;
    color: #667085;
    font-size: 11px;
    text-align: right;
    font-variant-numeric: tabular-nums;
}

.pie-state {
    display: flex;
    min-height: 240px;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 9px;
    color: #667085;
    font-size: 12px;
    text-align: center;
}

.pie-state > i {
    color: #98a2b3;
    font-size: 25px;
}

.pie-state p {
    margin: 0;
    color: #98a2b3;
    font-size: 10px;
}

@keyframes legend-in {
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@media (max-width: 850px) {
    .pie-body {
        grid-template-columns: minmax(0, 1fr);
        gap: 12px;
    }

    .pie-canvas {
        height: 280px;
    }
}

@media (max-width: 575px) {
    .pie-legend {
        grid-template-columns: minmax(0, 1fr);
    }

    .pie-heading {
        flex-direction: column;
    }

    .pie-total {
        align-items: flex-start;
    }
}

.pie-legend.still li {
    opacity: 1;
    transform: none;
    animation: none !important;
}
</style>