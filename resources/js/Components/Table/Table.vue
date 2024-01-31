<script>
export default {
    name: "Table"
}
</script>

<template>
    <table class="w-full bg-white max-h-[var(--dynamic-height)] overflow-y-auto">
        <slot name="head"></slot>
        <slot name="body"></slot>
    </table>
</template>

<style scoped>

</style>
