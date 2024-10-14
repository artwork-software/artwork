<template>
    <div class="flex flex-row items-center p-2 bg-backgroundGray rounded-lg gap-x-1">
        <component :is="dayService.icon"
                   stroke-width="1.5"
                   class="w-5 h-5"
                   :style="'color: ' + dayService.hex_color"/>
        <span class="text-xs">{{ dayService.name }}</span>
    </div>
</template>

<script setup>
const props = defineProps({
    dayService: {
        type: Object,
        required: true
    }
});
</script>
