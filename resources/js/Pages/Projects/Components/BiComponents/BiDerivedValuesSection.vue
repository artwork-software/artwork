<template>
    <div class="mb-8 border-b border-dashed border-gray-400 pb-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">{{ $t('Derived values') }}</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="rounded-lg border border-gray-200 p-3" v-for="item in items" :key="item.key">
                <span class="text-xs font-medium text-gray-500 block mb-1">{{ $t(item.label) }}</span>
                <span class="text-lg font-bold text-gray-900">{{ derivedValues[item.key] ?? 0 }}</span>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    derivedValues: { type: Object, default: () => ({}) },
});

const items = [
    { key: 'contract_count', label: 'Contracts' },
    { key: 'event_count', label: 'Events' },
    { key: 'task_total', label: 'Tasks total' },
    { key: 'task_open', label: 'Tasks open' },
    { key: 'task_done', label: 'Tasks done' },
    { key: 'document_count', label: 'Documents' },
    { key: 'department_count', label: 'Departments involved' },
    { key: 'user_count', label: 'People involved' },
];
</script>
