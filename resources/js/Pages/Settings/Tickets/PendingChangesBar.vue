<template>
    <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="translate-y-3 opacity-0" enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition duration-150 ease-in" leave-from-class="translate-y-0 opacity-100" leave-to-class="translate-y-3 opacity-0">
        <div v-if="visible" role="status" class="fixed inset-x-0 bottom-5 z-30 flex justify-center px-4 pointer-events-none">
            <div class="pointer-events-auto flex items-center gap-4 rounded-full border border-border-subtle bg-surface pl-4 pr-1.5 py-1.5 shadow-[0_8px_30px_rgba(28,31,36,.14)]">
                <span class="flex items-center gap-2 text-[13px] text-text">
                    <span class="size-2 rounded-full bg-warning animate-pulse"></span>
                    {{ message }}
                </span>
                <div class="flex items-center gap-1.5">
                    <button type="button" class="rounded-full px-3 h-8 text-[13px] font-medium text-text-subtle hover:text-text hover:bg-surface-sunken" :disabled="processing" @click="$emit('discard')">
                        {{ $t('Discard') }}
                    </button>
                    <button type="button" class="ui-button-add rounded-full h-8 px-3.5" :disabled="!canSubmit" @click="$emit('submit')">
                        <IconRefresh class="size-4" />{{ processing ? $t('Syncing…') : submitLabel }}
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup>
import { IconRefresh } from '@tabler/icons-vue'

/* Floats in as soon as the drafts differ from what tickets holds — the sync button in the
   toolbar stays, but nobody has to know it is there. */
defineProps({
    visible: { type: Boolean, required: true },
    message: { type: String, required: true },
    submitLabel: { type: String, required: true },
    canSubmit: { type: Boolean, required: true },
    processing: { type: Boolean, default: false },
})

defineEmits(['discard', 'submit'])
</script>
