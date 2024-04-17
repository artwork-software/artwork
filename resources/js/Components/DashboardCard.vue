<script>
export default {
    name: "DashboardCard",
    props: {
        hasPadding: {
            type: Boolean,
            default: true
        }
    }
}
</script>

<template>
    <div class="flex flex-col w-full bg-white shadow-cardShadow rounded-lg" :class="hasPadding ? 'p-4' : ''">
        <slot />
    </div>
</template>

<style scoped>

</style>
