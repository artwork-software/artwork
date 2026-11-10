<template>
    <div class="inline-flex h-8 p-0.5 rounded-md bg-surface-sunken border border-border-subtle" role="radiogroup" :aria-label="label">
        <button v-for="option in options" :key="option.value" type="button" role="radio" :aria-checked="modelValue === option.value"
                :disabled="disabled || option.disabled"
                class="flex items-center px-2.5 rounded text-xs font-medium transition-colors disabled:cursor-not-allowed"
                :class="modelValue === option.value ? 'bg-surface text-text shadow-[0_1px_2px_rgba(28,31,36,.08)]' : 'text-text-subtle hover:text-text disabled:hover:text-text-subtle'"
                @click="$emit('update:modelValue', option.value)">
            {{ option.label }}
        </button>
    </div>
</template>

<script setup>
/* A small either-or switch; options are [{ value, label, disabled? }], labels already translated. */
defineProps({
    modelValue: { type: String, required: true },
    options: { type: Array, required: true },
    label: { type: String, default: undefined },
    disabled: { type: Boolean, default: false },
})

defineEmits(['update:modelValue'])
</script>
