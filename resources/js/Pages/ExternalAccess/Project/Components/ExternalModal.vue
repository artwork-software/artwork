<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-50 flex items-stretch justify-center overflow-y-auto bg-black/40 sm:items-start sm:p-8" @mousedown.self="$emit('close')">
            <div
                role="dialog"
                aria-modal="true"
                :aria-labelledby="titleId"
                class="flex min-h-full w-full flex-col bg-white shadow-xl sm:min-h-0 sm:max-w-2xl sm:rounded-2xl"
                @keydown.esc="$emit('close')"
            >
                <header class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-border-subtle bg-white px-4 py-4 sm:static sm:rounded-t-2xl sm:px-6">
                    <div>
                        <h2 :id="titleId" class="text-lg font-semibold text-text">{{ title }}</h2>
                        <p v-if="description" class="mt-1 text-xs text-text-subtle">{{ description }}</p>
                    </div>
                    <button
                        type="button"
                        class="-m-1 shrink-0 rounded-lg p-2 text-text-subtle hover:bg-surface-sunken hover:text-text sm:m-0 sm:p-1"
                        :aria-label="$t('Close')"
                        @click="$emit('close')"
                    >
                        <IconX class="size-5" />
                    </button>
                </header>
                <div class="flex-1 px-4 py-5 sm:px-6">
                    <slot />
                </div>
                <footer v-if="$slots.footer" class="sticky bottom-0 flex gap-2 border-t border-border-subtle bg-white px-4 py-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] *:flex-1 *:py-3 sm:static sm:justify-end sm:rounded-b-2xl sm:px-6 sm:py-4 sm:*:flex-none sm:*:py-2">
                    <slot name="footer" />
                </footer>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { onBeforeUnmount, onMounted } from 'vue'
import { IconX } from '@tabler/icons-vue'

defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
})
defineEmits(['close'])

const titleId = `external-modal-${Math.random().toString(36).slice(2)}`

// Seite dahinter soll nicht mitscrollen (v. a. im Vollbild auf dem Handy)
onMounted(() => { document.body.style.overflow = 'hidden' })
onBeforeUnmount(() => { document.body.style.overflow = '' })
</script>
