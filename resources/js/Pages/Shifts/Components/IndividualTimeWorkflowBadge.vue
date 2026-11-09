<template>
    <span
        v-if="badge"
        class="inline-flex w-fit shrink-0 items-center rounded-full px-1.5 py-0.5 text-[9px] font-semibold ring-1 ring-inset"
        :class="badge.classes"
    >
        {{ $t(badge.label) }}
    </span>
</template>

<script setup>
import { computed } from 'vue';
import { individualTimeWorkflowBadge } from '@/Helper/kwWorkflowStatus.js';

/** Freigabe-Status einer individuellen Zeit: „In Freigabe-Anfrage“ bzw. „Freigegeben“, sonst nichts. */
const props = defineProps({
    status: { type: String, default: null },
});

const badge = computed(() => individualTimeWorkflowBadge(props.status));
</script>
