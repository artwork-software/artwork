<template>
    <div>
        <div class="grid grid-cols-[minmax(0,1fr)_150px_110px_130px_150px_28px] gap-2.5 mb-1.5 font-lexend text-xs font-medium text-[#3F424A]">
            <span>{{ $t('Name') }}</span><span>{{ $t('Kind') }}</span><span>{{ $t('Value') }}</span><span>{{ $t('Proof required') }}</span><span>{{ $t('On by default') }}</span><span></span>
        </div>
        <div class="flex flex-col gap-2">
            <div v-for="(reduction, ri) in reductions" :key="ri" class="grid grid-cols-[minmax(0,1fr)_150px_110px_130px_150px_28px] items-center gap-2.5">
                <BaseInput :id="`tickets-reduction-${ri}-name`" v-model="reduction.name" :label="$t('Name')" :show-label="false" required is-small />
                <div class="inline-flex h-8 p-0.5 rounded-md bg-surface-sunken border border-border-subtle" role="radiogroup">
                    <button v-for="kind in ['percent', 'fixed']" :key="kind" type="button" role="radio" :aria-checked="reduction.kind === kind"
                            class="flex items-center px-2.5 rounded text-xs font-medium transition-colors"
                            :class="reduction.kind === kind ? 'bg-surface text-text shadow-[0_1px_2px_rgba(28,31,36,.08)]' : 'text-text-subtle hover:text-text'"
                            @click="reduction.kind = kind">
                        {{ kind === 'percent' ? $t('Percent') : $t('Amount') }}
                    </button>
                </div>
                <div class="flex h-8 overflow-hidden rounded-md border border-border bg-surface">
                    <input v-model="reduction.value" type="number" :min="0" step="0.01" required :aria-label="$t('Value')"
                           class="flex-1 min-w-0 border-0 px-2.5 text-[13px] tabular-nums focus:ring-0" />
                    <span class="flex items-center px-2 bg-surface-sunken border-l border-border-subtle text-xs text-text-subtle">{{ reduction.kind === 'percent' ? '%' : '€' }}</span>
                </div>
                <ArtworkBaseToggle v-model="reduction.requires_proof" :id="`tickets-reduction-${ri}-proof`" :label="$t('Proof')" is-small />
                <ArtworkBaseToggle v-model="reduction.default_enabled" :id="`tickets-reduction-${ri}-default`" :label="$t('Default on')" is-small />
                <button type="button" class="flex size-7 items-center justify-center rounded-md text-text-subtle hover:text-danger hover:bg-danger-surface" :aria-label="$t('Remove')" @click="reductions.splice(ri, 1)">
                    <IconTrash class="size-[15px]" />
                </button>
            </div>
        </div>
        <button type="button" class="mt-2.5 inline-flex items-center gap-1.5 text-[13px] font-medium text-accent-600 hover:underline" @click="reductions.push(emptyReduction())">
            <IconPlus class="size-3.5" stroke-width="2.5" />{{ $t('Add reduction') }}
        </button>
        <p class="mt-3.5 text-xs leading-[18px] text-text-subtle"><slot name="hint"></slot></p>
    </div>
</template>

<script setup>
import { IconPlus, IconTrash } from '@tabler/icons-vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import ArtworkBaseToggle from '@/Artwork/Toggles/ArtworkBaseToggle.vue'
import { emptyReduction } from '@/Pages/Settings/Tickets/drafts.js'

/* Edits the reduction drafts in place (see drafts.js); the parent owns the array. */
defineProps({
    reductions: { type: Array, required: true },
})
</script>
