<template>
    <div class="grid gap-2" :style="gridStyle">
        <div
            v-for="day in days"
            :key="day.date"
            class="rounded-xl border p-2 text-center"
            :class="day.is_rejected ? 'border-red-500 bg-red-50/30' : 'border-gray-200 bg-white'"
        >
            <div class="text-xs font-medium text-gray-900">{{ day.label }}</div>

            <div v-if="day.rejection_reason" class="mt-1 text-[10px] font-semibold text-red-600 leading-tight">
                {{ day.rejection_reason }}
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    days: { type: Array, required: true },
    gridStyle: { type: Object, required: true }
});
</script>
