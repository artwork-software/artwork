<template>
    <div role="note" class="flex items-start gap-2.5 rounded-lg border border-warning-border bg-warning-surface px-3.5 py-2.5 text-[13px] leading-5 text-warning">
        <IconFlask class="mt-0.5 size-4 shrink-0" />
        <p>
            <strong class="font-semibold">{{ $t('Test mode.') }}</strong>
            {{ $t('Payments run with test data, the shop opens only with its test login, and everything sold here is deleted when Artwork-Tickets goes live.') }}
        </p>
    </div>
</template>

<script setup>
import { IconFlask } from '@tabler/icons-vue'

/* Wherever ticketing shows sales while the house is still in test mode, so nobody takes them for real. */
</script>
