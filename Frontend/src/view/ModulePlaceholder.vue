<script setup>
defineProps({
    title: { type: String, required: true },
    icon: { type: String, default: 'bi-grid' }
})
</script>

<template>
    <div class="module-page">
        <div class="module-heading">
            <div class="module-icon"><i :class="`bi ${icon}`"></i></div>
            <div>
                <p>Northstar workspace</p>
                <h1>{{ title }}</h1>
                <span>This workspace is ready for your {{ title.toLowerCase() }} workflow.</span>
            </div>
        </div>
        <section class="module-panel">
            <div class="module-panel-icon"><i class="bi bi-layout-text-window-reverse"></i></div>
            <div>
                <h2>{{ title }} workspace</h2>
                <p>This page is connected to the navigation and ready for its data table and actions.</p>
            </div>
        </section>
    </div>
</template>

<style scoped>
.module-page {
    max-width: 1100px;
    margin: 0 auto;
}

.module-heading {
    display: flex;
    align-items: center;
    gap: 16px;
    margin: 20px 0 26px;
}

.module-icon {
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    border-radius: 12px;
    background: #edf3ff;
    color: #1f5eff;
    font-size: 21px;
}

.module-heading p {
    margin: 0 0 5px;
    color: #667085;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
}

.module-heading h1 {
    margin: 0;
    color: #101828;
    font-size: 27px;
    letter-spacing: -.03em;
}

.module-heading span {
    display: block;
    margin-top: 5px;
    color: #667085;
    font-size: 12px;
}

.module-panel {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 24px;
    border: 1px solid #e4e7ec;
    border-radius: 12px;
    background: #ffffff;
    box-shadow: 0 2px 8px rgba(16, 24, 40, .04);
}

.module-panel-icon {
    width: 38px;
    height: 38px;
    display: grid;
    place-items: center;
    border-radius: 9px;
    background: #f2f4f7;
    color: #667085;
}

.module-panel h2 {
    margin: 0;
    color: #344054;
    font-size: 14px;
}

.module-panel p {
    margin: 5px 0 0;
    color: #667085;
    font-size: 12px;
}
</style>
