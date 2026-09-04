<template>
    <!-- Legende der Zell-Marker im Schichtplan (Info-Popover an der Funktionsleiste) -->
    <Popover class="relative">
        <PopoverButton class="ui-button" :title="$t('Legend')">
            <IconInfoCircle class="h-5 w-5" stroke-width="1.5" />
        </PopoverButton>
        <transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <PopoverPanel class="absolute right-0 z-50 mt-2 w-72 origin-top-right rounded-xl border border-border-subtle bg-surface p-3 shadow-xl ring-1 ring-black/5 text-xs">
                <p class="mb-2 font-semibold text-text">{{ $t('Legend') }}</p>
                <ul class="space-y-2">
                    <li class="flex items-start gap-2">
                        <span class="mt-0.5 inline-flex h-4 items-center gap-0.5 shrink-0">
                            <svg class="h-3.5 w-3.5 text-warning" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-[9px] font-semibold text-warning">2</span>
                        </span>
                        <span class="text-text">
                            <span class="font-medium">{{ $t('Open rule violation') }}</span>
                            <span class="block text-text-subtle">{{ $t('Warning triangle in the colour of the rule; the number shows how many violations are open on this day.') }}</span>
                        </span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="mt-0.5 inline-flex h-4 items-center gap-0.5 rounded-full bg-surface-sunken text-text-subtle px-1 shrink-0">
                            <svg class="h-2.5 w-2.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-[9px] font-semibold">1</span>
                        </span>
                        <span class="text-text">
                            <span class="font-medium">{{ $t('Processed rule violation') }}</span>
                            <span class="block text-text-subtle">{{ $t('Substitute days off have been booked; ignored violations are not shown in the cell.') }}</span>
                        </span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="mt-0.5 inline-flex h-4 items-center shrink-0">
                            <svg class="h-3.5 w-3.5 text-danger" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        <span class="text-text">
                            <span class="font-medium">{{ $t('Assigned but not available') }}</span>
                            <span class="block text-text-subtle">{{ $t('Red for committed shifts, otherwise orange.') }}</span>
                        </span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="mt-0.5 inline-flex h-4 items-center rounded-full bg-warning-surface text-warning border border-warning-border px-1 shrink-0">
                            <PropertyIcon name="IconCalendarStar" class="h-3 w-3" />
                        </span>
                        <span class="text-text">
                            <span class="font-medium">{{ $t('Special Day') }}</span>
                            <span class="block text-text-subtle">{{ $t('Holiday marked as special day: without work the daily target is reduced (if the contract has the special day rule active).') }}</span>
                        </span>
                    </li>
                </ul>
                <p class="mt-3 text-[11px] text-text-subtle">{{ $t('Click a marker to open the day and process the violations.') }}</p>
            </PopoverPanel>
        </transition>
    </Popover>
</template>

<script setup>
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import { Popover, PopoverButton, PopoverPanel } from '@headlessui/vue';
import { IconInfoCircle } from '@tabler/icons-vue';
</script>
