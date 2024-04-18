<script>

export default {
    name: "DropComponentsToolTip",
    data() {
        return {
            show: false
        }
    },
    props: {
        tooltipText: String,
        top: Boolean
    },
}
</script>

<template>
    <div class="relative">
        <div v-if="show && top" class="absolute z-10 -top-3 w-64 p-2 text-sm leading-tight text-center text-white bg-black rounded-md shadow-lg transform -translate-x-1/2 -translate-y-full left-1/2">
            {{ tooltipText }}
            <!-- Tooltip Pfeil unten -->
            <div class="absolute bg-black h-3 w-3 transform rotate-45 left-1/2 -translate-x-1/2 -bottom-1.5"></div>
        </div>
        <div @mouseover="show = true" @mouseleave="show = false" class="focus:outline-none">
            <slot />
        </div>
    </div>

</template>

<style scoped>

</style>
