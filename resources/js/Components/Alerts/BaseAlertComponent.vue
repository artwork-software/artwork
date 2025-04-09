<template>
    <div class="rounded-md p-4 mb-5 border cursor-default" :class="bgClass">
        <div class="flex items-center">
            <div class="shrink-0">
                <component :is="iconClass" class="size-6" :class="iconTextClass" aria-hidden="true" />
            </div>
            <div class="ml-3">
                <p class="text-xs font-lexend font-bold" :class="textClass">
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
            return 'bg-gradient-to-br from-green-50 to-green-100 border-green-200'
        case types.error:
            return 'bg-gradient-to-br from-red-50 to-red-100 border-red-200'
        case types.warning:
            return 'bg-gradient-to-br from-yellow-50 to-yellow-100 border-yellow-200'
        case types.info:
            return 'bg-gradient-to-br from-blue-50 to-blue-100 border-blue-200'
        default:
            return 'bg-gradient-to-br from-gray-50 to-gray-100 border-gray-200'
    }
})

const iconClass = computed(() => {
    switch (props.type) {
        case types.success:
            return 'IconCircleCheckFilled'
        case types.error:
            return 'IconExclamationCircleFilled'
        case types.warning:
            return 'IconAlertSquareFilled'
        case types.info:
            return 'IconInfoSquareRoundedFilled'
        default:
            return 'IconMoodSadFilled'
    }
})

const iconTextClass = computed(() => {
    switch (props.type) {
        case types.success:
            return 'text-green-400'
        case types.error:
            return 'text-red-400'
        case types.warning:
            return 'text-yellow-400'
        case types.info:
            return 'text-blue-400'
        default:
            return 'text-gray-400'
    }
})

const textClass = computed(() => {
    switch (props.type) {
        case types.success:
            return 'text-green-800'
        case types.error:
            return 'text-red-800'
        case types.warning:
            return 'text-yellow-800'
        case types.info:
            return 'text-blue-800'
        default:
            return 'text-gray-800'
    }
})

</script>

<style scoped>

</style>