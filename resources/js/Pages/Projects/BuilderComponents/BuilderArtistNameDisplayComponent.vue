<template>
    <div class="space-y-1">
        <div class="text-[11px] font-semibold uppercase tracking-wide text-secondary">
            {{ $t('Artists') }}
        </div>
        <div class="font-lexend font-black tracking-wide text-sm text-primary">
            <span v-if="artistText">{{ artistText }}</span>
            <span v-else class="text-secondary text-xs font-normal">{{ $t('No entries') }}</span>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    project: {
        type: Object,
        required: true
    },
    component: {
        type: Object,
        required: false,
    },
})

const artistText = computed(() => props.project?.artists || '')
</script>

<style scoped>
</style>
