<template>
    <div class="rounded-xl p-4 mb-5 border cursor-default" :class="bgClass">
        <div class="flex gap-x-3 items-center">
            <div class="shrink-0">
                <component :is="iconClass" class="size-5" :class="iconTextClass" aria-hidden="true" />
            </div>
            <div class="">
                <p class="text-xs" :class="textClass">
                    <span v-if="useTranslation">
                        {{ $t(message) }}
                    </span>
                    <span v-else>
                        {{ message }}
                    </span>
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>

import {computed} from "vue";
import {
    IconAlertSquareFilled,
    IconCircleCheckFilled,
    IconExclamationCircleFilled,
    IconInfoSquareRoundedFilled, IconMoodSadFilled
} from "@tabler/icons-vue";

const props = defineProps({
    type: {
        type: String,
        default: 'info',
        required: false,
        validator: (value) => {
            return ['success', 'error', 'warning', 'info'].includes(value)
        }
    },
    message: {
        type: String,
        required: true
    },
    useTranslation: {
        type: Boolean,
        default: false,
        required: false
    },
})

class types {
    static success = 'success'
    static error = 'error'
    static warning = 'warning'
    static info = 'info'
}

const bgClass = computed(() => {
    switch (props.type) {
        case types.success:
            return 'bg-green-50 border-green-200'
        case types.error:
            return 'bg-red-50 border-red-200'
        case types.warning:
            return 'bg-yellow-50 border-yellow-200'
        case types.info:
            return 'bg-blue-50 border-blue-200'
        default:
            return 'bg-gray-50 border-gray-200'
    }
})

const iconClass = computed(() => {
    switch (props.type) {
        case types.success:
            return IconCircleCheckFilled
        case types.error:
            return IconExclamationCircleFilled
        case types.warning:
            return IconAlertSquareFilled
        case types.info:
            return IconInfoSquareRoundedFilled
        default:
            return IconMoodSadFilled
    }
})

const iconTextClass = computed(() => {
    switch (props.type) {
        case types.success:
            return 'text-green-500'
        case types.error:
            return 'text-red-500'
        case types.warning:
            return 'text-yellow-500'
        case types.info:
            return 'text-blue-500'
        default:
            return 'text-gray-500'
    }
})

const textClass = computed(() => {
    switch (props.type) {
        case types.success:
            return 'text-green-500'
        case types.error:
            return 'text-red-500'
        case types.warning:
            return 'text-yellow-500'
        case types.info:
            return 'text-blue-500'
        default:
            return 'text-gray-500'
    }
})

</script>

<style scoped>

</style>
