<template>
    <div>
        <h3 class="text-[11px] font-semibold uppercase tracking-wide text-secondary mb-2">{{ $t('Documents') }}</h3>
        <div v-if="project.project_files_all?.length > 0" class="space-y-1">
            <div v-for="file in project.project_files_all" :key="file.id" class="flex items-center gap-2 text-sm text-text-muted border-b border-border-subtle pb-1">
                <component :is="IconFile" class="h-4 w-4 text-text-subtle shrink-0" />
                <span>{{ file.name || file.basename || '-' }}</span>
            </div>
        </div>
        <div v-else class="text-sm text-secondary">
            {{ $t('No entries') }}
        </div>
    </div>
</template>
<script setup>
import { IconFile } from "@tabler/icons-vue";
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
