<template>
    <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_360px] items-start">
        <div>
            <BaseChip variant="neutral" class="mb-3.5">
                <span class="size-1.5 rounded-full bg-border-strong"></span>
                {{ $t('Not connected') }}
            </BaseChip>
            <h2 class="font-lexend text-xl font-semibold text-text mb-2">{{ $t('Sell tickets for your dates') }}</h2>
            <p class="text-sm leading-[22px] text-text-muted max-w-[560px] mb-5">
                {{ $t('artwork tickets is the ticket shop of the suite. Connecting creates a ticket house for this installation and syncs rooms and reductions. Afterwards you release dates for sale straight from artwork.') }}
            </p>
            <div class="flex items-center gap-3">
                <button type="button" class="ui-button-add min-h-9 px-4" @click="$emit('start')">
                    <IconPlugConnected class="size-4" />
                    {{ $t('Connect artwork tickets') }}
                </button>
                <span class="text-xs text-text-subtle">{{ $t('About 5 minutes · nothing is created without confirmation') }}</span>
            </div>
            <p class="mt-5 flex items-center gap-2 text-[13px] text-text-muted">
                <IconUser class="size-4 text-text-subtle shrink-0" />
                <span><strong class="font-medium text-text">{{ userEmail }}</strong> {{ $t('becomes the owner of the house and signs in with an e-mail code afterwards.') }}</span>
            </p>
        </div>
        <div class="rounded-lg bg-surface-sunken border border-border-subtle px-5 py-[18px]">
            <span class="font-lexend block text-xs font-semibold uppercase tracking-[.02em] text-text-subtle mb-3">{{ $t('How it goes') }}</span>
            <ol class="flex flex-col">
                <li v-for="(step, index) in steps" :key="step.title"
                    class="grid grid-cols-[26px_minmax(0,1fr)] gap-3" :class="index < steps.length - 1 ? 'pb-3.5' : ''">
                    <span class="font-lexend flex size-[26px] items-center justify-center rounded-full bg-surface border border-border text-xs font-semibold text-accent-600">{{ index + 1 }}</span>
                    <span>
                        <strong class="font-lexend block text-[13px] font-medium text-text">{{ step.title }}</strong>
                        <span class="text-xs leading-[18px] text-text-subtle">{{ step.text }}</span>
                    </span>
                </li>
            </ol>
        </div>
    </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { IconPlugConnected, IconUser } from '@tabler/icons-vue'
import BaseChip from '@/Artwork/Chips/BaseChip.vue'

defineProps({
    userEmail: { type: String, required: true },
})

defineEmits(['start'])

const { t } = useI18n()

const steps = [
    { title: t('House'), text: t('Name and address in the ticket shop') },
    { title: t('Details'), text: t('Legal details and bank account — can be added later') },
    { title: t('Rooms'), text: t('Which rooms sell, with address, price classes and default prices') },
    { title: t('Reductions'), text: t('House-wide reductions, e.g. 50 % with proof') },
    { title: t('Review & connect'), text: t('Everything at a glance, then one click') },
]
</script>
