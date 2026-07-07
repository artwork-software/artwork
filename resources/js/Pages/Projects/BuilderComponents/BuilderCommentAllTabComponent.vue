<template>
    <div>
        <h3 class="text-[11px] font-semibold uppercase tracking-wide text-secondary mb-2">{{ $t('Comments') }}</h3>
        <div v-if="project.comments_all?.length > 0" class="space-y-3">
            <div v-for="comment in project.comments_all" :key="comment.id" class="border-b border-gray-100 pb-2">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs font-medium text-gray-900">{{ comment.user?.full_name || comment.user?.name || '-' }}</span>
                    <span class="text-xs text-gray-400">{{ formatDate(comment.created_at) }}</span>
                </div>
                <div class="text-sm text-gray-700">{{ comment.text }}</div>
            </div>
        </div>
        <div v-else class="text-sm text-secondary">
            {{ $t('No entries') }}
        </div>
    </div>
</template>
<script setup>
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

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}
</script>
<style scoped>
</style>
