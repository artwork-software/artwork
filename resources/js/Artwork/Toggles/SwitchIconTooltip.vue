<template>
    <div v-if="!roomMode" class="inline-flex items-center">
        <Switch
            v-model="model"
            :disabled="disabled"
            :class="[
        model ? 'bg-blue-600 hover:bg-blue-600/95' : 'bg-gray-200',
        sizeClasses.track,
        'relative inline-flex cursor-pointer rounded-full transition-colors duration-300 ease-out shadow-inner',
        'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500',
        disabled ? 'opacity-60 cursor-not-allowed' : ''
      ]"
        >
            <span class="sr-only">Toggle</span>

            <!-- Knopf -->
            <span
                aria-hidden="true"
                :class="[
          model ? sizeClasses.knobTranslateOn : sizeClasses.knobTranslateOff,
          sizeClasses.knob,
          'inline-flex transform items-center justify-center rounded-full bg-white ring-1 ring-black/5 shadow transition duration-300 ease-out'
        ]"
            >
        <!-- Tooltip bleibt erhalten -->
        <ToolTipComponent
            :icon="icon"
            :tooltip-text="tooltipText"
            :icon-size="sizeClasses.icon"
            direction="bottom"
            :stroke="1.5"
        />
      </span>
        </Switch>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { Switch } from '@headlessui/vue'
import { IconList } from '@tabler/icons-vue'
import ToolTipComponent from '@/Components/ToolTips/ToolTipComponent.vue'

defineOptions({ name: 'SwitchIconTooltip' })

/**
 * Props
 */
const props = defineProps({
    modelValue: { type: Boolean, required: true }, // v-model
    roomMode: { type: Boolean, default: false },
    tooltipText: { type: String, default: 'At a glance' },
    icon: { type: [Object, Function], default: () => IconList },
    size: { type: String, default: 'sm' }, // 'sm' | 'md'
    disabled: { type: Boolean, default: false }
})

const emit = defineEmits(['update:modelValue', 'change'])

/**
 * v-model Proxy
 */
const model = computed({
    get: () => props.modelValue,
    set: (val) => {
        emit('update:modelValue', val)
        emit('change', val)
    }
})

/**
 * Größen-Klassen (kompakt = sm, optional md)
 */
const sizeClasses = computed(() => {
    if (props.size === 'md') {
        return {
            track: 'h-7 w-14 p-0.5',
            knob: 'h-6 w-6',
            knobTranslateOn: 'translate-x-7',
            knobTranslateOff: 'translate-x-0',
            icon: 'h-4 w-4'
        }
    }
    // default: sm (kompakt)
    return {
        track: 'h-5 w-10 p-0.5',
        knob: 'h-4 w-4',
        knobTranslateOn: 'translate-x-5',
        knobTranslateOff: 'translate-x-0',
        icon: 'h-4 w-4'
    }
})
</script>
