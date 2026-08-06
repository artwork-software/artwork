<script setup>
import { computed } from 'vue'

/**
 * Props
 */
const props = defineProps({
    modelValue: { type: Boolean, default: false }, // für v-model
    id: { type: String, default: '' },             // optional, sonst auto
    name: { type: String, default: '' },
    label: { type: String, required: true },
    description: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
    useTranslation: { type: Boolean, default: false },
    /** Kompakte Variante: kleinerer Schalter + kleinere Beschriftung */
    isSmall: { type: Boolean, default: false },
})

/**
 * Emits
 */
const emit = defineEmits(['update:modelValue', 'change'])

/**
 * Abgeleitete IDs für A11y
 */
const baseId = computed(() => props.id || `toggle-${Math.random().toString(36).slice(2, 9)}`)
const labelId = computed(() => `${baseId.value}-label`)
const descId  = computed(() => `${baseId.value}-desc`)

/**
 * Event-Handler
 */
function onInputChange(e) {
    const checked = e.target.checked
    emit('update:modelValue', checked)
    emit('change', checked)
}

const wrapperClasses = computed(() => [
    'group',
    'relative',
    'inline-flex',
    props.isSmall ? 'h-[18px] w-[30px]' : 'h-5 w-[34px]',
    'shrink-0',
    'rounded-full',
    props.disabled
        ? (props.modelValue ? 'bg-accent-200' : 'bg-border-subtle')
        : (props.modelValue ? 'bg-accent-600' : 'bg-border'),
    'p-0.5',
    'transition-colors',
    'duration-200',
    'ease-in-out',
    props.disabled ? 'cursor-not-allowed' : 'cursor-pointer',
].join(' '))
</script>

<template>
    <div class="flex items-start" :class="isSmall ? 'gap-2.5' : 'gap-4'">
        <div :class="wrapperClasses">
            <!-- Thumb -->
            <span
                class="rounded-full bg-white shadow-raised transition-transform duration-200 ease-in-out"
                :class="isSmall ? 'size-3.5 group-has-checked:translate-x-3' : 'size-4 group-has-checked:translate-x-[14px]'"
                aria-hidden="true"
            />
            <!-- Native Checkbox (steuert die has-checked Variants) -->
            <input
                :id="baseId"
                type="checkbox"
                class="absolute inset-0 appearance-none rounded-full"
                :name="name || baseId"
                :checked="modelValue"
                :disabled="disabled"
                role="switch"
                :aria-checked="String(modelValue)"
                :aria-labelledby="labelId"
                :aria-describedby="description ? descId : undefined"
                @change="onInputChange"
            />
        </div>
        <span class="flex grow flex-col min-w-0">
            <label :id="labelId" class="font-medium" :class="[isSmall ? 'text-xs/5' : 'text-sm/6', disabled ? 'text-text-subtle' : 'text-text']" :for="baseId">
                {{ useTranslation ? $t(label) : label }}
            </label>
            <span v-if="description" :id="descId" class="text-text-muted" :class="isSmall ? 'text-xs' : 'text-sm'">
                {{ useTranslation ? $t(description) : description }}
            </span>
        </span>
    </div>
</template>
