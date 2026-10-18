<template>
    <div class="flex flex-wrap gap-2" role="group" :aria-label="label">
        <button v-for="reduction in reductions" :key="reduction.id" type="button" role="checkbox" :aria-checked="modelValue.includes(reduction.id)"
                class="inline-flex h-8 items-center gap-1.5 rounded-full border px-3 text-[13px] transition-colors"
                :class="modelValue.includes(reduction.id) ? 'border-accent-600 bg-accent-50 text-accent-700' : 'border-border bg-surface text-text-muted hover:bg-surface-sunken hover:text-text'"
                @click="toggle(reduction.id)">
            <IconCheck v-if="modelValue.includes(reduction.id)" class="size-3.5" stroke-width="2.5" />
            <IconPlus v-else class="size-3.5 opacity-60" stroke-width="2" />
            {{ reduction.name }}<span class="tabular-nums" :class="modelValue.includes(reduction.id) ? 'text-accent-700/70' : 'text-text-subtle'">{{ reductionValue(reduction) }}</span>
        </button>
    </div>
</template>

<script setup>
import { IconCheck, IconPlus } from '@tabler/icons-vue'
import { reductionValue } from '@/Pages/Projects/Tab/Components/Ticketing/ticketing.js'

/* The house reductions as toggles; v-model is the list of granted ids. */
const props = defineProps({
    reductions: { type: Array, required: true },
    modelValue: { type: Array, required: true },
    label: { type: String, required: true },
})

const emit = defineEmits(['update:modelValue'])

function toggle(id) {
    emit('update:modelValue', props.modelValue.includes(id) ? props.modelValue.filter((granted) => granted !== id) : [...props.modelValue, id])
}
</script>
