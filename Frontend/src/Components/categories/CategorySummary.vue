<script setup>
defineProps({
    summary: {
        type: Object,
        required: true
    }
})

const cards = [{
        key: 'total',
        label: 'Total Products',
        icon: 'bi-grid-3x3-gap',
        theme: 'total-card'
    },
    {
        key: 'active',
        label: 'Active Categories',
        icon: 'bi-check-circle',
        theme: 'active-card'
    },
    {
        key: 'inactive',
        label: 'Inactive Categories',
        icon: 'bi-x-circle',
        theme: 'inactive-card'
    }
]
</script>

<template>
<div class="row g-3 mb-4">

    <div v-for="card in cards" :key="card.key" class="col-12 col-sm-6 col-md-4">
        <div class="card summary-card border-0 shadow-sm h-100" :class="card.theme">
            <div class="card-body">

                <div class="summary-content">

                    <div class="summary-info">
                        <div class="summary-label">
                            {{ card.label }}
                        </div>

                        <div class="summary-value">
                            {{ summary[card.key] ?? 0 }}
                        </div>

                    </div>

                    <div class="icon-box">

                        <i class="bi" :class="card.icon"></i>

                    </div>

                </div>

            </div>
        </div>
    </div>

</div>
</template>

<style scoped>
 /* ========================================================= BASE CARD ========================================================= */
 .summary-card {
     position: relative;
     overflow: hidden;
     min-height: 108px;
     border-radius: 12px;
     border: 1px solid #e5e7eb !important;
     border-left: 4px solid transparent !important;
     background: transparent !important;
     transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease, border-color 0.2s ease;
 }

 .summary-card:hover {
     transform: translateY(-2px);
     box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08) !important;
 }

 /* ========================================================= CARD BODY ========================================================= */
 .summary-card .card-body {
     padding: 18px 20px;
     background: transparent !important;
 }

 /* ========================================================= CONTENT ========================================================= */
 .summary-content {
     height: 100%;
     display: flex;
     align-items: center;
     justify-content: space-between;
     gap: 16px;
 }

 .summary-info {
     min-width: 0;
 }

 /* ========================================================= LABEL ========================================================= */
 .summary-label {
     font-size: 12px;
     font-weight: 600;
     color: #64748b;
     line-height: 1.4;
 }

 /* ========================================================= VALUE ========================================================= */
 .summary-value {
     margin-top: 6px;
     font-size: 24px;
     line-height: 1;
     font-weight: 700;
     letter-spacing: -0.3px;
 }

 /* ========================================================= ICON ========================================================= */
 .icon-box {
     flex: 0 0 44px;
     width: 44px;
     height: 44px;
     display: flex;
     align-items: center;
     justify-content: center;
     border-radius: 10px;
     font-size: 19px;
 }

 /* ========================================================= TOTAL ========================================================= */
 .total-card {
     background: linear-gradient(135deg, #eff6ff 0%, #f8fbff 100%) !important;
     border-left-color: #2563eb !important;
 }

 .total-card .card-body {
     background: transparent !important;
 }

 .total-card .summary-value {
     color: #1e40af;
 }

 .total-card .icon-box {
     background: #dbeafe;
     color: #2563eb;
 }

 /* ========================================================= ACTIVE ========================================================= */
 .active-card {
     background: linear-gradient(135deg, #f0fdf4 0%, #f8fff9 100%) !important;
     border-left-color: #16a34a !important;
 }

 .active-card .card-body {
     background: transparent !important;
 }

 .active-card .summary-value {
     color: #166534;
 }

 .active-card .icon-box {
     background: #dcfce7;
     color: #16a34a;
 }

 /* ========================================================= INACTIVE ========================================================= */
 .inactive-card {
     background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%) !important;
     border-left-color: #64748b !important;
 }

 .inactive-card .card-body {
     background: transparent !important;
 }

 .inactive-card .summary-value {
     color: #334155;
 }

 .inactive-card .icon-box {
     background: #e2e8f0;
     color: #64748b;
 }

 /* ========================================================= DARK MODE — CARD ========================================================= */
 :global(.dark-theme) .summary-card {
     border-color: #334155 !important;
     box-shadow: 0 4px 12px rgba(0, 0, 0, 0.20) !important;
 }

 :global(.dark-theme) .summary-card .card-body {
     background: transparent !important;
 }

 :global(.dark-theme) .summary-card:hover {
     box-shadow: 0 8px 20px rgba(0, 0, 0, 0.30) !important;
 }

 /* ========================================================= DARK TOTAL ========================================================= */
 :global(.dark-theme) .total-card {
     background: linear-gradient(135deg, #172554 0%, #1e293b 100%) !important;
     border-left-color: #3b82f6 !important;
 }

 :global(.dark-theme) .total-card .card-body {
     background: transparent !important;
 }

 :global(.dark-theme) .total-card .summary-label {
     color: #93c5fd;
 }

 :global(.dark-theme) .total-card .summary-value {
     color: #60a5fa;
 }

 :global(.dark-theme) .total-card .icon-box {
     background: #1e3a8a;
     color: #60a5fa;
 }

 /* ========================================================= DARK ACTIVE ========================================================= */
 :global(.dark-theme) .active-card {
     background: linear-gradient(135deg, #052e16 0%, #1e293b 100%) !important;
     border-left-color: #22c55e !important;
 }

 :global(.dark-theme) .active-card .card-body {
     background: transparent !important;
 }

 :global(.dark-theme) .active-card .summary-label {
     color: #86efac;
 }

 :global(.dark-theme) .active-card .summary-value {
     color: #4ade80;
 }

 :global(.dark-theme) .active-card .icon-box {
     background: #14532d;
     color: #4ade80;
 }

 /* ========================================================= DARK INACTIVE ========================================================= */
 :global(.dark-theme) .inactive-card {
     background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%) !important;
     border-left-color: #64748b !important;
 }

 :global(.dark-theme) .inactive-card .card-body {
     background-color: transparent!important;
 }

 :global(.dark-theme) .inactive-card .summary-label {
     color: #94a3b8;
 }

 :global(.dark-theme) .inactive-card .summary-value {
     color: #cbd5e1;
 }

 :global(.dark-theme) .inactive-card .icon-box {
     background: #334155;
     color: #94a3b8;
 }

 /* ========================================================= TABLET ========================================================= */
 @media (max-width: 768px) {
     .summary-card {
         min-height: 100px;
     }

     .summary-card .card-body {
         padding: 16px;
     }

     .summary-value {
         font-size: 22px;
     }

     .icon-box {
         width: 40px;
         height: 40px;
         flex-basis: 40px;
         font-size: 17px;
     }
 }

 /* ========================================================= SMALL MOBILE ========================================================= */
 @media (max-width: 576px) {
     .summary-card {
         min-height: 96px;
     }

     .summary-label {
         font-size: 11px;
     }

     .summary-value {
         font-size: 21px;
     }
 }
</style>
