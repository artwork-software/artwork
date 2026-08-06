<template>
    <div class="mt-6 mb-8">
        <div class="flex items-center space-x-3">
            <template v-for="(step, index) in steps" :key="index">
                <div
                    v-if="index > 0"
                    class="flex-1 h-px"
                    :class="dividerClass(index)"
                ></div>
                <span
                    class="flex items-center justify-center size-8 rounded-full text-sm font-bold shrink-0"
                    :class="circleClass(index)"
                >
                    <component v-if="stateOf(index) === 'done'" :is="IconCheck" class="size-4" />
                    <template v-else>{{ index + 1 }}</template>
                </span>
                <span class="text-sm" :class="stateOf(index) === 'active' ? 'font-medium text-text' : 'text-text-subtle'">
                    {{ $t(step) }}
                </span>
            </template>
        </div>
    </div>
</template>

<script setup>
import { IconCheck } from '@tabler/icons-vue'

const props = defineProps({
    steps: { type: Array, required: true },
    currentStep: { type: Number, required: true },
})

const stateOf = (index) => {
    if (index + 1 < props.currentStep) return 'done'
    if (index + 1 === props.currentStep) return 'active'
    return 'upcoming'
}

const circleClass = (index) => {
    const state = stateOf(index)
    if (state === 'done') return 'bg-success text-white'
    if (state === 'active') return 'bg-accent-600 text-white'
    return 'bg-border-subtle text-text-subtle'
}

// Divider before step `index`: colored by how far the progress has come
const dividerClass = (index) => {
    if (index + 1 < props.currentStep) return 'bg-success'
    if (index + 1 === props.currentStep) return 'bg-accent-200'
    return 'bg-border'
}
</script>
