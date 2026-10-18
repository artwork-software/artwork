<template>
    <!-- The series question as a plain card, so it can sit inside the form it refers to. -->
    <div class="flex flex-col gap-3">
        <div class="flex items-start gap-3">
            <span class="flex size-8 shrink-0 items-center justify-center rounded-md bg-accent-100 text-accent-700">
                <IconRepeat class="size-4" stroke-width="1.75" aria-hidden="true" />
            </span>
            <div class="min-w-0 flex-1">
                <p class="font-lexend text-[13px] font-semibold leading-8 text-text">{{ $t('This date is part of a series') }}</p>
                <p class="-mt-1 text-[13px] leading-5 text-text-muted">
                    {{ count ? $t('Apply the change to all {count} dates of the series, or only to this one? A date changed on its own leaves the series.', { count }) : $t('Apply the change to all dates of the series, or only to this one? A date changed on its own leaves the series.') }}
                </p>
            </div>
        </div>
        <div class="flex flex-wrap items-center justify-end gap-2">
            <button type="button" class="ui-button" @click="$emit('cancel')">{{ $t('Cancel') }}</button>
            <button type="button" class="ui-button" @click="$emit('single')">{{ $t('Only this date') }}</button>
            <button type="button" class="ui-button-add" @click="$emit('all')">{{ count ? $t('All {count} dates', { count }) : $t('All dates of the series') }}</button>
        </div>
    </div>
</template>

<script setup>
import { IconRepeat } from '@tabler/icons-vue'

defineProps({
    /** How many dates the series has; the button names the number when known. */
    count: { type: Number, default: 0 },
})

defineEmits(['cancel', 'single', 'all'])
</script>
