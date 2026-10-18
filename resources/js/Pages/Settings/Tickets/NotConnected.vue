<template>
    <div class="rounded-lg border border-border-subtle/70 bg-surface shadow-raised px-5 py-8 text-center">
        <IconPlugConnectedX class="mx-auto size-8 text-text-subtle" stroke-width="1.5" />
        <h2 class="font-lexend mt-3 text-base font-semibold text-text">{{ $t('Not connected yet') }}</h2>
        <p class="mx-auto mt-1 max-w-[480px] text-sm leading-[22px] text-text-muted">{{ message }}</p>
        <Link :href="route('settings.tickets')" class="ui-button-add mt-5 inline-flex">
            <IconPlugConnected class="size-4" />{{ $t('Connect Artwork-Tickets') }}
        </Link>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { IconPlugConnected, IconPlugConnectedX } from '@tabler/icons-vue'

defineProps({
    message: { type: String, required: true },
})
</script>
