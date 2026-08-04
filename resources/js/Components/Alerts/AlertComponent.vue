<script setup>
import {computed} from 'vue';
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

const props = defineProps({
    showIcon: {
        type: Boolean,
        required: false,
        default: false
    },
    type: {
        type: String,
        required: false,
        default: ''
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
    },
    classes: {
        type: String,
        required: false,
        default: ''
    }
});

const icon = computed(() => {
    switch (props.type) {
        case 'info':
            return 'IconInfoCircle';
        case 'warning':
            return 'IconInfoCircle';
        case 'error':
            return 'IconInfoCircle';
        case 'success':
            return 'IconInfoCircle';
        case 'plus':
            return 'IconCirclePlus';
        default:
            return 'IconInfoCircle';
    }
});

const textColor = computed(() => {
    switch (props.type) {
        case 'info':
            return 'text-info';
        case 'warning':
            return 'text-warning';
        case 'error':
            return 'text-danger';
        case 'success':
            return 'text-success';
        case 'dashboard':
            return 'text-text-muted';
        case 'plus':
            return 'text-info cursor-pointer !items-center hover:text-accent-700 duration-150 ease-in-out';
        default:
            return 'text-accent-600';
    }
});
</script>

<template>
    <div class="flex items-start gap-x-1" :class="[textColor, textSize, classes]">
        <PropertyIcon :name="icon" :class="iconSize" :stroke-width="1.5" v-if="showIcon" />
        <span v-if="text">
            {{ text }}
        </span>
    </div>
</template>

<style scoped>
</style>
