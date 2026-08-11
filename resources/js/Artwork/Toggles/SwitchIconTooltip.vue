<template>
    <div v-if="!roomMode" class="inline-flex items-center">
        <!-- v-tooltip (PrimeVue-Direktive) braucht ein echtes DOM-Element — auf der
             Headless-UI-Switch-Komponente wird die Direktive nicht angewendet
             ("Runtime directive used on component with non-element root node").
             Deshalb trägt dieser Wrapper-Span den Tooltip. -->
        <span class="inline-flex" v-tooltip.bottom="tooltipBinding">
        <Switch
            v-model="model"
            :disabled="disabled"
            :aria-label="tooltipText"
            :class="[
        disabled
            ? (model ? 'bg-accent-200' : (onBand ? 'bg-white/10' : 'bg-border-subtle'))
            : (model ? 'bg-accent-600 hover:bg-accent-700' : (onBand ? 'bg-white/20 hover:bg-white/30' : 'bg-border')),
        sizeClasses.track,
        'relative inline-flex cursor-pointer rounded-full transition-colors duration-300 ease-out shadow-inner',
        'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-600',
        disabled ? 'cursor-not-allowed' : ''
      ]"
        >
            <span class="sr-only">{{ tooltipText }}</span>

            <!-- Knopf -->
            <span
                aria-hidden="true"
                :class="[
          model ? sizeClasses.knobTranslateOn : sizeClasses.knobTranslateOff,
          sizeClasses.knob,
          'inline-flex transform items-center justify-center rounded-full bg-white shadow-raised transition duration-300 ease-out'
        ]"
            >
        <PropertyIcon
            :name="icon"
            :class="sizeClasses.icon"
            :stroke-width="1.5"
            aria-hidden="true"
        />
      </span>
        </Switch>
        </span>

        <!-- Right Label -->
        <span v-if="label"
            class="model-title cursor-pointer ml-3 text-sm "
            :class="model ? (onBand ? 'text-text-inverse' : 'text-text') : (onBand ? 'text-text-inverse-muted' : 'text-text-subtle')"
            role="button"
            tabindex="0"
            @click="set(true)"
            @keydown.enter.prevent="set(true)"
            @keydown.space.prevent="set(true)"
        >
        {{ label }}
    </span>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { Switch } from '@headlessui/vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import {IconList} from "@tabler/icons-vue";

defineOptions({ name: 'SwitchIconTooltip' })

/**
 * Props
 */
const props = defineProps({
    modelValue: { type: Boolean, required: true }, // v-model
    roomMode: { type: Boolean, default: false },
    tooltipText: { type: String, default: 'Activate' },
    icon: { type: [Object, Function, String], default: () => IconList },
    size: { type: String, default: 'sm' }, // 'sm' | 'md'
    disabled: { type: Boolean, default: false },
    label: { type: String, default: '' },
    /** Switch sitzt im dunklen Toolbar-Band (CI »Bühnenlicht«):
     *  Aus-Zustand des Tracks weiß-transluzent statt bg-border, damit auf Dunkel sichtbar.
     *  Aktiv-Zustand (accent) bleibt unverändert. Default false = bisherige Optik. */
    onBand: { type: Boolean, default: false }
})

const emit = defineEmits(['update:modelValue', 'change'])

const tooltipBinding = computed(() => ({
    value: props.tooltipText,
    appendTo: 'body',
    useTranslation: false,
    class: 'aw-tooltip'
}))

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

function set(val) {
    if (!props.disabled) {
        model.value = val
    }
}

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
