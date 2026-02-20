<template>
    <div>
        <h3 class="text-[11px] font-semibold uppercase tracking-wide text-secondary mb-2">{{ $t('Budget deadline') }}</h3>
        <div class="flex items-center gap-x-1 text-sm" v-if="project.budget_deadline">
            <component :is="IconClock" class="h-4 w-4" stroke-width="2"/>
            <span class="font-bold">{{ project.budget_deadline }}</span>
        </div>
        <div v-else class="text-sm text-secondary">
            {{ $t('No entries') }}
        </div>
    </div>
</template>
<script setup>
import {IconClock} from "@tabler/icons-vue";
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
