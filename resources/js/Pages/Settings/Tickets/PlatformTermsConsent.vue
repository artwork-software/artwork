<template>
    <label class="flex items-start gap-3 cursor-pointer">
        <input v-model="accepted" type="checkbox" class="aw-checklist-input mt-0.5 cursor-pointer" />
        <span class="text-[13px] leading-5 text-text">
            {{ $t('On behalf of the house I accept the terms of use and the data processing agreement of artwork tickets.') }}
            <span class="mt-1 flex flex-wrap gap-x-4 text-xs">
                <a :href="termsUrl" target="_blank" rel="noopener" class="inline-flex items-center gap-1 font-medium text-accent-600 hover:underline">{{ $t('Terms of use') }}<IconExternalLink class="size-3" /></a>
                <a :href="dpaUrl" target="_blank" rel="noopener" class="inline-flex items-center gap-1 font-medium text-accent-600 hover:underline">{{ $t('Data processing agreement') }}<IconExternalLink class="size-3" /></a>
            </span>
        </span>
    </label>
</template>

<script setup>
import { IconExternalLink } from '@tabler/icons-vue'

/* The box that signs the platform's papers for the house — in the wizard when connecting,
   and in the billing tab when tickets raises their version. */

const accepted = defineModel({ type: Boolean, default: false })

defineProps({
    termsUrl: { type: String, required: true },
    dpaUrl: { type: String, required: true },
})
</script>
