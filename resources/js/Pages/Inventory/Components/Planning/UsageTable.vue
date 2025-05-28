<template>
    <div v-if="issues && issues.length" class="space-y-4">
        <div
            v-for="issue in issues"
            :key="issue.id"
            class="rounded-lg border p-4 shadow-sm bg-white"
        >
            <div class="font-medium text-gray-800">{{ issue.name }}</div>
            <div class="text-xs text-gray-500">{{ issue.start_date_time }} – {{ issue.end_date_time }}</div>

            <ul class="mt-2 divide-y text-sm text-gray-700">
                <li
                    v-for="article in issue.articles"
                    :key="article.id"
                    class="flex justify-between py-1"
                >
                    <span>{{ article.name }}</span>
                    <span>{{ article.pivot.quantity }} Stk</span>
                </li>
            </ul>
        </div>
    </div>
    <div v-else class="text-sm text-gray-400 italic">
        {{ $t('No data available') }}
    </div>
</template>

<script setup lang="ts">

const props = defineProps({
    issues: {
        type: Array,
        default: () => []
    }
});

</script>

<style scoped>

</style>