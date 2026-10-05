<template>
    <div>
        <div
            v-if="barColor"
            class="rounded px-3 py-1.5 text-sm/5 font-semibold print:[print-color-adjust:exact] print:[-webkit-print-color-adjust:exact]"
            :style="{ backgroundColor: barColor, color: barTextColor }"
        >
            <p class="whitespace-pre-line">{{ titleData?.title }}</p>
        </div>
        <div v-else class="text-xs/[18px] text-text-subtle print:text-sm/5 font-semibold text-text">
            <p class="line-clamp-3 print:line-clamp-none whitespace-pre-line">{{ titleData?.title }}</p>
        </div>
        <p v-if="titleData?.subtitle" class="mt-1 whitespace-pre-line text-xs text-text-muted">{{ titleData.subtitle }}</p>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { useColorHelper } from '@/Composeables/UseColorHelper.js'

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

const { getTextColorBasedOnBackground } = useColorHelper()

const titleData = computed(() => props.project['Title']?.[props.component.id])
const barColor = computed(() => titleData.value?.bar_color || '')
const barTextColor = computed(() => getTextColorBasedOnBackground(barColor.value))
</script>
