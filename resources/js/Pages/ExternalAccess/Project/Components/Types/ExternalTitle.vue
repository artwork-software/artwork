<template>
    <h2 class="font-bold text-text" :style="{ fontSize: titleSize + 'px' }">
        {{ title }}
    </h2>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    component: { type: Object, required: true },
    projectId: { type: Number, required: true },
    tabId: { type: Number, required: true },
    scope: { type: Object, required: true },
})

const schema = computed(() => ({ title: '', title_size: 18, ...(props.component.data_schema || {}), ...(props.component.value || {}) }))
const title = computed(() => schema.value.title || props.component.name)
const titleSize = computed(() => Number(schema.value.title_size) || 18)
</script>
