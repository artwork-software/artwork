<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/40 p-4 sm:p-8" @mousedown.self="$emit('close')">
            <div
                role="dialog"
                aria-modal="true"
                :aria-labelledby="titleId"
                class="w-full max-w-2xl rounded-2xl bg-white shadow-xl"
                @keydown.esc="$emit('close')"
            >
                <header class="flex items-start justify-between gap-4 border-b border-border-subtle px-6 py-4">
                    <div>
                        <h2 :id="titleId" class="text-lg font-semibold text-text">{{ title }}</h2>
                        <p v-if="description" class="mt-1 text-xs text-text-subtle">{{ description }}</p>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg p-1 text-text-subtle hover:bg-surface-sunken hover:text-text"
                        :aria-label="$t('Close')"
                        @click="$emit('close')"
                    >
                        <IconX class="size-5" />
                    </button>
                </header>
                <div class="px-6 py-5">
                    <slot />
                </div>
                <footer v-if="$slots.footer" class="flex justify-end gap-2 border-t border-border-subtle px-6 py-4">
                    <slot name="footer" />
                </footer>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { IconX } from '@tabler/icons-vue'

defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
})
defineEmits(['close'])

const titleId = `external-modal-${Math.random().toString(36).slice(2)}`
</script>
