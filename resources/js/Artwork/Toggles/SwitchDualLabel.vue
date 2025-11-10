<template>
    <div class="inline-flex items-center gap-3 select-none">
        <!-- Left Label -->
        <span
            class="model-title cursor-pointer"
            :class="model ? 'text-gray-300' : ''"
            role="button"
            tabindex="0"
            @click="set(false)"
            @keydown.enter.prevent="set(false)"
            @keydown.space.prevent="set(false)"
        >
      {{ leftLabel }}
    </span>

        <!-- Toggle -->
        <Switch
            v-model="model"
            :disabled="disabled"
            :class="[
        model ? 'bg-blue-600 hover:bg-blue-600/95' : 'bg-gray-200',
        sizeClasses.track,
        'relative inline-flex cursor-pointer rounded-full transition-colors duration-300 ease-out shadow-inner',
        'focus-visible:outline focus-visible:outline-offset-2 focus-visible:outline-blue-500',
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
        <ToolTipComponent
            :icon="icon"
            :tooltip-text="tooltipText"
            :icon-size="sizeClasses.icon"
            direction="bottom"
            :stroke="1.5"
            classes-button=""
            :no-tooltip="!tooltipText"
        />
      </span>
        </Switch>

        <!-- Right Label -->
        <span
            class="model-title cursor-pointer"
            :class="model ? '' : 'text-gray-300'"
            role="button"
            tabindex="0"
            @click="set(true)"
            @keydown.enter.prevent="set(true)"
            @keydown.space.prevent="set(true)"
        >
      {{ rightLabel }}
    </span>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { Switch } from '@headlessui/vue'
import { IconList } from '@tabler/icons-vue'
import ToolTipComponent from '@/Components/ToolTips/ToolTipComponent.vue'

defineOptions({ name: 'SwitchDualLabel' })

const props = defineProps({
    /**
     * Wenn true => rechte Beschriftung aktiv (z. B. "Project group").
     * Wenn false => linke Beschriftung aktiv (z. B. "Project").
     */
    modelValue: { type: Boolean, required: true },
    leftLabel: { type: String, default: 'Project' },
    rightLabel: { type: String, default: 'Project group' },
    tooltipText: { type: String, default: '' },
    icon: { type: [Object, Function, String], default: () => IconList },
    size: { type: String, default: 'sm' }, // 'sm' | 'md'
    disabled: { type: Boolean, default: false }
})

const emit = defineEmits(['update:modelValue', 'change'])

const model = computed({
    get: () => props.modelValue,
    set: (val) => {
        emit('update:modelValue', val)
        emit('change', val)
    }
})

function set(val) {
    if (!props.disabled) {
        model.value = val
    }
}

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
        track: 'h-5 w-12 p-0.5',
        knob: 'h-4 w-4',
        knobTranslateOn: 'translate-x-7',
        knobTranslateOff: 'translate-x-0',
        icon: 'h-4 w-4'
    }
})
</script>
