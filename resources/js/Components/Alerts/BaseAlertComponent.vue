<template>
    <div class="rounded-[8px] py-2.5 px-3 mb-5 border cursor-default" :class="bgClass">
        <div class="flex gap-x-3" :class="title ? 'items-start' : 'items-center'">
            <div class="shrink-0">
                <PropertyIcon :name="iconClass" class="size-5" :class="iconTextClass" aria-hidden="true" />
            </div>
            <div class="flex-1 min-w-0">
                <p v-if="title" class="text-[13px] font-semibold" :class="textClass">
                    <span v-if="useTranslation">{{ $t(title) }}</span>
                    <span v-else>{{ title }}</span>
                </p>
                <p class="text-[13px]" :class="textClass">
                    <span v-if="useTranslation">
                        {{ $t(message) }}
                    </span>
                    <span v-else>
                        {{ message }}
                    </span>
                </p>
            </div>
            <div v-if="actionLabel" class="shrink-0 self-center">
                <BaseUIButton
                    variant="secondary"
                    size="sm"
                    hide-icon
                    :label="actionLabel"
                    :use-translation="useTranslation"
                    @click="emit('action')"
                />
            </div>
        </div>
    </div>
</template>

<script setup>

import {computed} from "vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";


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
    /** Optionale Titelzeile über der Message */
    title: {
        type: String,
        required: false,
        default: ''
    },
    /** Optionaler Action-Button rechts; Klick emittiert 'action' */
    actionLabel: {
        type: String,
        required: false,
        default: ''
    },
})

const emit = defineEmits(['action'])

class types {
    static success = 'success'
    static error = 'error'
    static warning = 'warning'
    static info = 'info'
}

const bgClass = computed(() => {
    switch (props.type) {
        case types.success:
            return 'bg-success-surface border-success-border'
        case types.error:
            return 'bg-danger-surface border-danger-border'
        case types.warning:
            return 'bg-warning-surface border-warning-border'
        case types.info:
            return 'bg-info-surface border-info-border'
        default:
            return 'bg-surface-sunken border-border'
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
            return 'text-success'
        case types.error:
            return 'text-danger'
        case types.warning:
            return 'text-warning'
        case types.info:
            return 'text-info'
        default:
            return 'text-text-muted'
    }
})

const textClass = computed(() => {
    switch (props.type) {
        case types.success:
            return 'text-success'
        case types.error:
            return 'text-danger'
        case types.warning:
            return 'text-warning'
        case types.info:
            return 'text-info'
        default:
            return 'text-text-muted'
    }
})

</script>

<style scoped>

</style>
