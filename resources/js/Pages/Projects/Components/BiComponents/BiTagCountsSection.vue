<template>
    <div class="mb-8 border-b border-dashed border-gray-400 pb-6" v-if="tagCounts.length > 0">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">{{ $t('Tag-based counts') }}</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div
                v-for="tag in tagCounts"
                :key="tag.tag_id"
                class="rounded-lg border border-gray-200 p-3"
            >
                <div class="flex items-center gap-2 mb-1">
                    <span
                        v-if="tag.color"
                        class="w-3 h-3 rounded-full inline-block"
                        :style="{ backgroundColor: tag.color }"
                    ></span>
                    <span class="text-xs font-medium text-gray-500">{{ tag.tag_name_de }}</span>
                </div>
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
