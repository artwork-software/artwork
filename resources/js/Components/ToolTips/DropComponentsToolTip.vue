<template>
    <div class="relative">
        <!-- Tooltip -->
        <transition
            enter-active-class="transition duration-150 ease-out motion-reduce:transition-none"
            enter-from-class="opacity-0 translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-100 ease-in motion-reduce:transition-none"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-1"
        >
            <div
                v-if="show && top"
                class="pointer-events-none absolute left-1/2 z-20 -top-2 w-64 -translate-x-1/2 -translate-y-full rounded-md bg-surface-inverse px-2 py-1 text-center text-xs font-medium text-text-inverse shadow-overlay"
            >
                {{ tooltipText }}
                <div class="absolute -bottom-1 left-1/2 h-2 w-2 -translate-x-1/2 rotate-45 bg-surface-inverse"></div>
            </div>
        </transition>

        <!-- Trigger -->
        <div
            @mouseover="show = true"
            @mouseleave="show = false"
            @focusin="show = true"
            @focusout="show = false"
        >
            <slot />
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

defineOptions({ name: 'DropComponentsToolTip' })

const props = defineProps<{
    tooltipText?: string
    top?: boolean
}>()

const show = ref(false)
</script>
