<script setup>
import { IconInfoCircle } from '@tabler/icons-vue';
import {computed} from 'vue';

const props = defineProps({
    showIcon: {
        type: Boolean,
        required: false,
        default: false
    },
    type: {
        type: String,
        required: false,
        default: 'info'
    },
    iconSize: {
        type: String,
        required: false,
        default: 'w-6 h-6'
    },
    text: {
        type: String,
        required: false,
        default: ''
    },
    textSize: {
        type: String,
        required: false,
        default: 'text-xs'
    }
});

const icon = computed(() => {
    switch (props.type) {
        case 'info':
            return IconInfoCircle;
        case 'warning':
            return IconInfoCircle;
        case 'error':
            return IconInfoCircle;
        case 'success':
            return IconInfoCircle;
        default:
            return IconInfoCircle;
    }
});

const textColor = computed(() => {
    switch (props.type) {
        case 'info':
            return 'text-artwork-messages-info';
        case 'warning':
            return 'text-artwork-messages-warning';
        case 'error':
            return 'text-artwork-messages-error';
        case 'success':
            return 'text-artwork-messages-success';
        default:
            return 'text-artwork-buttons-create';
    }
});
</script>

<template>
    <div class="flex items-center gap-x-1" :class="[textColor, textSize]">
        <component :is="icon" :class="iconSize" v-if="showIcon" />
        <span v-if="text">
            {{ text }}
        </span>
    </div>
</template>

<style scoped>
</style>
