<template>
    <div
        data-testid="shift-plan-week-navigator"
        class="sticky top-0 z-30 rounded-2xl border border-border-subtle bg-white/95 p-3 shadow-sm backdrop-blur supports-[backdrop-filter]:bg-white/85"
    >
        <div class="mb-2 grid grid-cols-[1fr_auto_1fr] items-center gap-2">
            <Link
                v-if="navigation?.previous"
                data-testid="shift-plan-previous-request"
                :href="requestShowUrl(navigation.previous.id)"
                preserve-scroll
                class="inline-flex h-9 min-w-9 items-center justify-self-start gap-1.5 rounded-full border border-border-subtle bg-white px-2.5 text-xs font-medium text-text-muted shadow-xs transition hover:border-accent-200 hover:bg-accent-50 hover:text-accent-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-600 sm:px-3"
                :aria-label="$t('Previous request')"
                :title="$t('Previous request')"
                @before="$emit('navigate', navigation.previous.id)"
            >
                <IconChevronLeft class="h-4 w-4 shrink-0"/>
                <span class="hidden sm:inline">KW {{ navigation.previous.week_number }} / {{ navigation.previous.year }}</span>
            </Link>
            <button
                v-else
                type="button"
                disabled
                class="inline-flex h-9 min-w-9 cursor-not-allowed items-center justify-self-start gap-1.5 rounded-full border border-border-subtle bg-surface-sunken px-2.5 text-xs font-medium text-text-subtle sm:px-3"
                :aria-label="$t('Previous request')"
                :title="$t('Previous request')"
            >
                <IconChevronLeft class="h-4 w-4 shrink-0"/>
            </button>

            <div class="flex h-9 items-center gap-1.5 rounded-full bg-accent-50 px-3 text-xs font-semibold text-accent-700 ring-1 ring-inset ring-accent-200">
                <IconCalendarWeek class="h-4 w-4 shrink-0"/>
                <span class="whitespace-nowrap">KW {{ request.week_number }} / {{ request.year }}</span>
            </div>

            <Link
                v-if="navigation?.next"
                data-testid="shift-plan-next-request"
                :href="requestShowUrl(navigation.next.id)"
                preserve-scroll
                class="inline-flex h-9 min-w-9 items-center justify-self-end gap-1.5 rounded-full border border-border-subtle bg-white px-2.5 text-xs font-medium text-text-muted shadow-xs transition hover:border-accent-200 hover:bg-accent-50 hover:text-accent-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-600 sm:px-3"
                :aria-label="$t('Next request')"
                :title="$t('Next request')"
                @before="$emit('navigate', navigation.next.id)"
            >
                <span class="hidden sm:inline">KW {{ navigation.next.week_number }} / {{ navigation.next.year }}</span>
                <IconChevronRight class="h-4 w-4 shrink-0"/>
            </Link>
            <button
                v-else
                type="button"
                disabled
                class="inline-flex h-9 min-w-9 cursor-not-allowed items-center justify-self-end gap-1.5 rounded-full border border-border-subtle bg-surface-sunken px-2.5 text-xs font-medium text-text-subtle sm:px-3"
                :aria-label="$t('Next request')"
                :title="$t('Next request')"
            >
                <IconChevronRight class="h-4 w-4 shrink-0"/>
            </button>
        </div>

        <WeekOverview :days="days" :grid-style="gridStyle"/>
    </div>
</template>

<script setup>
import {Link} from '@inertiajs/vue3';
import {IconCalendarWeek, IconChevronLeft, IconChevronRight} from '@tabler/icons-vue';
import WeekOverview from './WeekOverview.vue';

defineProps({
    request: {type: Object, required: true},
    navigation: {type: Object, default: () => ({previous: null, next: null})},
    days: {type: Array, required: true},
    gridStyle: {type: Object, required: true},
    requestShowUrl: {type: Function, required: true},
});

defineEmits(['navigate']);
</script>
