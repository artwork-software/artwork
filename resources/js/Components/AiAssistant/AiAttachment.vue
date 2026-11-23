<!--
    Download-Karte fuer ein `attachment` aus artwork-ai (core-ki-bar-attachment): PDF, Excel oder CSV
    (core-ai-export-formats), Icon nach MIME-Typ.
    Die URL ist relativ und laeuft ueber die Session — kein Token, kein Umweg ueber artwork-ai.
-->
<template>
    <a
        :href="url"
        :download="filename"
        class="flex items-center gap-3 rounded-xl border border-border-subtle bg-surface p-3 text-sm text-text hover:bg-surface-sunken"
        data-ai-attachment
    >
        <PropertyIcon :name="attachmentIcon(mime)" class="size-6 shrink-0 text-accent-600" :stroke-width="1.5" aria-hidden="true" />
        <span class="min-w-0 flex-1">
            <span class="block truncate font-semibold">{{ title }}</span>
            <span class="block truncate text-xs text-text-subtle">{{ filename }}</span>
        </span>
        <span class="shrink-0 text-xs font-semibold text-accent-600 underline">{{ $t('Download') }}</span>
    </a>
</template>

<script setup>
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import { attachmentIcon } from './aiAttachment.js'

defineProps({
    url: { type: String, required: true },
    filename: { type: String, required: true },
    title: { type: String, required: true },
    mime: { type: String, default: 'application/pdf' },
})
</script>
