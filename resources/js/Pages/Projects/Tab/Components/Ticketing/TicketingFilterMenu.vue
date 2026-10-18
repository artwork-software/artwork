<template>
    <Popover class="relative">
        <PopoverButton class="inline-flex h-7 items-center gap-1 rounded-md border pl-2.5 pr-2 text-xs font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-accent-600"
                       :class="active ? 'border-accent-200 bg-accent-50 text-accent-700' : 'border-border bg-surface text-text hover:bg-surface-sunken'">
            {{ label }}<IconChevronDown class="size-3.5" stroke-width="2" />
        </PopoverButton>
        <transition enter-active-class="transition duration-100 ease-out" enter-from-class="-translate-y-1 opacity-0" enter-to-class="translate-y-0 opacity-100"
                    leave-active-class="transition duration-75 ease-in" leave-from-class="translate-y-0 opacity-100" leave-to-class="-translate-y-1 opacity-0">
            <PopoverPanel v-slot="{ close }" class="absolute left-0 top-full z-20 mt-1.5 w-60 rounded-lg border border-border-subtle bg-surface py-1.5 shadow-overlay">
                <p class="px-3 pb-1.5 pt-1 font-lexend text-[11px] font-semibold uppercase tracking-[.02em] text-text-subtle">{{ label }}</p>
                <slot />
                <div v-if="active" class="mt-1.5 border-t border-border-hairline px-3 pt-2">
                    <button type="button" class="text-xs text-text-subtle hover:text-text" @click="$emit('reset'); close()">{{ $t('Reset') }}</button>
                </div>
            </PopoverPanel>
        </transition>
    </Popover>
</template>

<script setup>
import { Popover, PopoverButton, PopoverPanel } from '@headlessui/vue'
import { IconChevronDown } from '@tabler/icons-vue'

defineProps({
    label: { type: String, required: true },
    /** Whether a value is set: tints the button and offers a reset. */
    active: { type: Boolean, default: false },
})

defineEmits(['reset'])
</script>
