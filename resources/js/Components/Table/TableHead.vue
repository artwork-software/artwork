<script>
export default {
    name: "TableHead",
}
</script>

<template>
    <thead class="sticky top-0 z-10">
        <tr class="flex w-full bg-surface-sunken border-b border-border font-lexend font-semibold text-[11px] uppercase tracking-[0.08em] text-text-muted">
            <slot />
        </tr>
    </thead>
</template>

<style scoped>

</style>
