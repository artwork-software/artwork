<template>
    <div class="w-full" v-bind="$attrs">
        <!-- Label über dem Feld -->
        <label v-if="hasLabel" :for="id" class="mb-1 block font-lexend text-xs font-medium text-[#3F424A]">
            <span class="block truncate">
                {{ $t(label) }}
                <span v-if="required" class="text-danger">*</span>
            </span>
        </label>

        <textarea
            :id="id"
            :value="modelValue"
            :placeholder="placeholder"
            @input="$emit('update:modelValue', $event.target.value)"
            @blur="($event) => emit('focusout', $event)"
            :disabled="disabled"
            :required="required"
            :rows="rows"
            :aria-invalid="String(Boolean(error))"
            :aria-required="String(required)"
            :aria-describedby="error ? errorId : undefined"
            class="block w-full rounded-md border px-3 py-2 text-sm resize-none placeholder:text-text-subtle focus:border-accent-600 transition-[border-color,background-color] duration-150 ease-in-out"
            :class="[
                error ? 'border-danger-border' : 'border-border',
                disabled ? 'bg-surface-sunken text-text-subtle border-border-subtle cursor-not-allowed' : bgColor
            ]"
        />

        <!-- Error -->
        <p v-if="error" :id="errorId" class="mt-1 text-[11.5px] text-danger">
            {{ error }}
        </p>
    </div>
</template>

<script setup>
import { computed } from 'vue'

defineOptions({
    inheritAttrs: false
})

const props = defineProps({
    modelValue: String,
    label: { type: String, default: '' },
    id: {
        type: String,
        required: true
    },
    placeholder: {
        type: String,
        default: ''
    },
    disabled: {
        type: Boolean,
        default: false
    },
    rows: {
        type: [Number, String],
        default: 4
    },
    required: {
        type: Boolean,
        default: false
    },
    bgColor: {
        type: String,
        default: 'bg-surface'
    },
    /** NEU: optionale Fehlermeldung (danger-Rahmen + Text unter dem Feld) */
    error: {
        type: String,
        default: ''
    }
})

const emit = defineEmits(['update:modelValue', 'focusout'])

const hasLabel = computed(() => typeof props.label === 'string' && props.label.trim().length > 0)

const errorId = computed(() => `${props.id}-error`)
</script>
