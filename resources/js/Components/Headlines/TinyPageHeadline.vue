<script setup>

const props = defineProps({
    title: {
        type: String,
        required: true
    },
    description: {
        type: String,
        required: true
    },
})
</script>

<template>
    <div>
        <h3 class="font-lexend font-semibold text-[clamp(18px,2.5vw,20px)]/[25px] text-text mb-2 print:font-lexend font-semibold text-[clamp(16px,2vw,18px)]/[21px] text-text">{{ title }}</h3>
        <p class="text-sm/5 font-bold text-text-subtle print:hidden">
            {{ description }}
        </p>
    </div>
</template>

<style scoped>

</style>
