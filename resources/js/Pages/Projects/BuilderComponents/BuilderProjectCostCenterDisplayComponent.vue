<template>
    <div class="space-y-2">
        <div class="font-lexend font-black tracking-wide text-primaryText text-md">
            {{ $t('cost unit') }}
        </div>
        <div class="text-sm text-primaryText">
            {{ project.cost_center?.name || '-' }}
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
    component: {
        type: Object,
        required: true,
    },
})
</script>

<style scoped>
</style>
