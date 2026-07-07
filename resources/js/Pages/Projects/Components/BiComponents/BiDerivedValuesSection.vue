<template>
    <div>
        <h4 class="text-xs font-medium text-gray-500 mb-2">{{ $t('Derived values') }}</h4>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="rounded-lg border border-gray-200 bg-white p-3" v-for="item in items" :key="item.key">
                <div class="flex items-center gap-1.5">
                    <component :is="item.icon" class="size-4 text-gray-400 shrink-0" />
                    <span class="text-xs font-medium text-gray-500 truncate">{{ $t(item.label) }}</span>
                </div>
                <span class="text-lg font-bold text-gray-900">{{ derivedValues[item.key] ?? 0 }}</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import {
    IconFileText,
    IconCalendarEvent,
    IconListCheck,
    IconProgress,
    IconChecks,
    IconFiles,
    IconBuildingCommunity,
    IconUsersGroup,
} from '@tabler/icons-vue';

defineProps({
    derivedValues: { type: Object, default: () => ({}) },
});

const items = [
    { key: 'contract_count', label: 'Contracts', icon: IconFileText },
    { key: 'event_count', label: 'Events', icon: IconCalendarEvent },
    { key: 'task_total', label: 'Tasks total', icon: IconListCheck },
    { key: 'task_open', label: 'Tasks open', icon: IconProgress },
    { key: 'task_done', label: 'Tasks done', icon: IconChecks },
    { key: 'document_count', label: 'Documents', icon: IconFiles },
    { key: 'department_count', label: 'Departments involved', icon: IconBuildingCommunity },
    { key: 'user_count', label: 'People involved', icon: IconUsersGroup },
];
</script>
