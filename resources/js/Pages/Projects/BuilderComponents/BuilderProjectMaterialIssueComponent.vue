<template>
    <div class="space-y-2">
        <div class="font-lexend font-black tracking-wide text-primaryText text-md">
            {{ $t('Material Issue') }}
        </div>
        <div v-if="issues.length > 0" class="space-y-1.5">
            <div v-for="issue in issues" :key="issue.id" class="text-sm text-primaryText border-b border-gray-100 pb-1">
                {{ issue.name || issue.title || '-' }}
            </div>
        </div>
        <div v-else class="text-sm text-secondary">
            {{ $t('No entries') }}
        </div>
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

const issues = computed(() => props.project.material_issues?.internal || [])
</script>

<style scoped>
</style>
