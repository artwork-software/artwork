<script>
export default {
    name: "Table"
}
</script>

<template>
    <table class="w-full bg-white">
        <div>
            <slot name="head"></slot>
            <slot name="body"></slot>
        </div>
    </table>
</template>

<style scoped>

</style>
