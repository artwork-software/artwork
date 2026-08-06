<template>
    <div>
        <h3 class="text-[11px] font-semibold uppercase tracking-wide text-text-subtle mb-2">{{ $t('General shift information') }}</h3>
        <div class="text-sm" v-if="project.shift_description">
            {{ project.shift_description }}
        </div>
        <div v-else class="text-sm text-text-subtle">
            {{ $t('No shift information has been entered yet') }}
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
        required: false,
    },
})
</script>
<style scoped>
</style>
