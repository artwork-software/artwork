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
    'w-11',
    'shrink-0',
    'rounded-full',
    props.modelValue ? 'bg-blue-600' : 'bg-gray-200',
    'p-0.5',
    'inset-ring',
    'inset-ring-gray-900/5',
    'outline-offset-2',
    'outline-blue-600',
    'transition-colors',
    'duration-200',
    'ease-in-out',
    'has-checked:bg-blue-600',
    'has-focus-visible:outline-2',
    props.disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer',
].join(' '))
</script>

<template>
    <div class="flex items-start gap-4">
        <div :class="wrapperClasses">
            <!-- Thumb -->
            <span
                class="size-5 rounded-full bg-white shadow-xs ring-1 ring-gray-900/5 transition-transform duration-200 ease-in-out group-has-checked:translate-x-5"
                aria-hidden="true"
            />
            <!-- Native Checkbox (steuert die has-checked Variants) -->
            <input
                :id="baseId"
                type="checkbox"
                class="absolute inset-0 appearance-none focus:outline-hidden"
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
            <label :id="labelId" class="text-sm/6 font-medium text-gray-900" :for="baseId">
                {{ useTranslation ? $t(label) : label }}
            </label>
            <span v-if="description" :id="descId" class="text-sm text-gray-500">
                {{ useTranslation ? $t(description) : description }}
            </span>
        </span>
    </div>
</template>
