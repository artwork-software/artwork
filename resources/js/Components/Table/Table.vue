<script>
export default {
    name: "Table"
}
</script>

<template>
    <table class="relative w-full bg-surface">
        <slot name="head"></slot>
        <slot name="body"></slot>
    </table>
</template>

<style scoped>

</style>
