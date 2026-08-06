<template>
    <div :style="{ height: height + 'px' }" class="flex items-center">
        <hr v-if="showLine" class="w-full border-border-subtle" />
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    component: { type: Object, required: true },
    projectId: { type: Number, required: true },
    tabId: { type: Number, required: true },
    scope: { type: Object, required: true },
})

const schema = computed(() => ({ height: 16, showLine: false, ...(props.component.data_schema || {}), ...(props.component.value || {}) }))
const height = computed(() => Number(schema.value.height) || 16)
const showLine = computed(() => Boolean(schema.value.showLine))
</script>
