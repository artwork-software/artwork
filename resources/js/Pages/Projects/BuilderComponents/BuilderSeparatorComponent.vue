<template>
    <div :style="{ 'paddingTop' : calculateHeight + 'px',  'paddingBottom' : calculateHeight + 'px' }" class="w-full flex items-center justify-center ">
        <div class="w-full flex items-center justify-center h-0.5 bg-gray-300" v-if="component.data?.showLine"></div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
    component: {
        type: Object,
        required: true,
    },
})

const calculateHeight = computed(() => parseInt(props.component.data?.height || 0) / 2)
</script>

<style scoped>
</style>
