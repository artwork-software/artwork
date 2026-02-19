<template>
    <div class="space-y-1">
        <div v-if="label" class="text-sm font-bold text-gray-900">{{ label }}</div>
        <div v-if="links.length > 0" class="space-y-0.5">
            <div v-for="(link, index) in links" :key="index">
                <a
                    v-if="link.url"
                    :href="link.url"
                    target="_blank"
                    class="text-sm text-blue-600 hover:underline"
                >
                    {{ link.label || link.url }}
                </a>
            </div>
        </div>
        <div v-else class="text-sm text-secondary">-</div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

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

const componentData = computed(() => props.project['LinkList']?.[props.component.id]?.data || {})
const label = computed(() => componentData.value?.label || '')
const links = computed(() => componentData.value?.links || [])
</script>

<style scoped>
</style>
