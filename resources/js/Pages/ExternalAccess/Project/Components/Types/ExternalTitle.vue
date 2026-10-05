<template>
    <div>
        <div
            v-if="barColor"
            class="rounded-lg px-4 py-2.5"
            :style="{ backgroundColor: barColor, color: barTextColor }"
        >
            <h2 class="font-semibold" :style="{ fontSize: titleSize + 'px' }">{{ title }}</h2>
        </div>
        <h2 v-else class="font-bold text-text" :style="{ fontSize: titleSize + 'px' }">
            {{ title }}
        </h2>
        <p v-if="schema.subtitle" class="mt-2 whitespace-pre-line text-sm text-text-muted">{{ schema.subtitle }}</p>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { useColorHelper } from '@/Composeables/UseColorHelper.js'

const props = defineProps({
    component: { type: Object, required: true },
    projectId: { type: Number, required: true },
    tabId: { type: Number, required: true },
    scope: { type: Object, required: true },
})

const { getTextColorBasedOnBackground } = useColorHelper()

const schema = computed(() => ({ title: '', title_size: 18, subtitle: '', bar_color: '', ...(props.component.data_schema || {}), ...(props.component.value || {}) }))
const title = computed(() => schema.value.title || props.component.name)
const titleSize = computed(() => Number(schema.value.title_size) || 18)
const barColor = computed(() => schema.value.bar_color || '')
const barTextColor = computed(() => getTextColorBasedOnBackground(barColor.value))
</script>
