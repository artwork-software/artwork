<template>
    <div v-if="tagCounts.length > 0">
        <h4 class="text-xs font-medium text-gray-500 mb-2">{{ $t('Tag-based counts') }}</h4>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div
                v-for="tag in tagCounts"
                :key="tag.tag_id"
                class="rounded-lg border border-gray-200 bg-white p-3 border-l-4"
                :style="tag.color ? { borderLeftColor: tag.color } : {}"
            >
                <span class="text-xs font-medium text-gray-500 block truncate">{{ tag.tag_name_de }}</span>
                <span class="text-lg font-bold text-gray-900">{{ tag.count }}</span>
                <span class="text-xs text-gray-400 ml-1">{{ $t('days') }}</span>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    tagCounts: { type: Array, default: () => [] },
});
</script>
