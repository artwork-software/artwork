<template>
    <div>
        <label v-if="property.type !== 'checkbox'" :for="inputId" class="block text-sm font-medium text-text-muted">
            {{ property.name }}<span v-if="property.is_required" class="ml-0.5 text-danger">*</span>
        </label>
        <p v-if="property.tooltip_text && property.type !== 'checkbox'" class="text-xs text-text-subtle">{{ property.tooltip_text }}</p>

        <textarea
            v-if="property.type === 'textarea'"
            :id="inputId"
            :value="modelValue ?? ''"
            rows="3"
            class="mt-1 block w-full rounded-lg border border-border px-3 py-2 text-sm focus:border-accent-600 focus:outline-none focus:ring-1 focus:ring-accent-600"
            @input="$emit('update:modelValue', $event.target.value)"
        />
        <label v-else-if="property.type === 'checkbox'" class="mt-1 inline-flex items-center gap-2 text-sm text-text-muted">
            <input
                :id="inputId"
                type="checkbox"
                :checked="modelValue === '1'"
                class="rounded border-border"
                @change="$emit('update:modelValue', $event.target.checked ? '1' : '0')"
            />
            {{ property.name }}<span v-if="property.is_required" class="text-danger">*</span>
        </label>
        <select
            v-else-if="property.type === 'select'"
            :id="inputId"
            :value="modelValue ?? ''"
            class="mt-1 block w-full rounded-lg border border-border px-3 py-2 text-sm focus:border-accent-600 focus:outline-none focus:ring-1 focus:ring-accent-600"
            @change="$emit('update:modelValue', $event.target.value)"
        >
            <option value="">{{ $t('Please select') }}</option>
            <option v-for="option in selectOptions" :key="option" :value="option">{{ option }}</option>
        </select>
        <input
            v-else
            :id="inputId"
            :type="inputType"
            :value="modelValue ?? ''"
            class="mt-1 block w-full rounded-lg border border-border px-3 py-2 text-sm focus:border-accent-600 focus:outline-none focus:ring-1 focus:ring-accent-600"
            @input="$emit('update:modelValue', $event.target.value)"
        />

        <p v-if="error" class="mt-1 text-xs text-danger">{{ error }}</p>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    property: { type: Object, required: true },
    modelValue: { type: [String, Number, null], default: '' },
    error: { type: String, default: '' },
})
defineEmits(['update:modelValue'])

const inputId = computed(() => `crm-prop-${props.property.id}`)
const inputType = computed(() => ({ number: 'number', date: 'date', link: 'url' })[props.property.type] ?? 'text')
const selectOptions = computed(() => (props.property.select_values ?? []).filter((value) => value !== '' && value != null))
</script>
