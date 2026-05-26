<template>
    <div class="w-full">
        <Chart :type="type" :data="data" :options="mergedOptions" class="w-full" :style="{ height: height }" />
    </div>
</template>

<script setup>
import { computed } from 'vue';
import Chart from 'primevue/chart';

const props = defineProps({
    type: { type: String, default: 'bar' },
    data: { type: Object, required: true },
    options: { type: Object, default: () => ({}) },
    height: { type: String, default: '280px' },
});

const baseOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            labels: { font: { family: 'inherit' } },
        },
    },
};

const mergedOptions = computed(() => ({ ...baseOptions, ...props.options }));
</script>
