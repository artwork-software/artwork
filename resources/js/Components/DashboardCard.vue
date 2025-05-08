<template>
    <div class="flex flex-col w-full bg-white shadow-[0_3px_10px_rgb(0,0,0,0.2)] border border-gray-50 rounded-lg" :class="hasPadding ? 'p-4' : ''">
        <slot />
    </div>
</template>

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
