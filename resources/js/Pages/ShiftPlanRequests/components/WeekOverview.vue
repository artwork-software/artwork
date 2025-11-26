<template>
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-3">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-medium text-gray-600">{{ $t('Week overview') }}</span>
        </div>
        <div class="grid text-xs font-medium text-gray-600" :style="gridStyle">
            <div v-for="day in days" :key="day.date" class="flex flex-col items-center gap-1 rounded-xl px-2 py-2" :class="day.is_today ? 'bg-indigo-50 text-indigo-700' : 'bg-gray-50 text-gray-600'">
                <span>{{ day.label }}</span>
                <span class="text-[11px] text-gray-400" v-if="day.is_today">{{ $t('Today') }}</span>
            </div>
        </div>
    </div>
</template>
<script setup>
const props = defineProps({ days: { type: Array, required: true }, gridStyle: { type: Object, required: true } });
</script>

