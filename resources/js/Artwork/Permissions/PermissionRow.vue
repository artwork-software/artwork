<template>
    <div
        :id="`permission-row-${definition.name}`"
        class="relative"
        :style="tierIndex ? { marginLeft: `${tierIndex * 1.75}rem` } : undefined"
    >
        <!-- Stufen-Verbindung: die vorherige Stufe ist in dieser enthalten -->
        <span
            v-if="tierIndex && showConnector"
            aria-hidden="true"
            class="pointer-events-none absolute -left-4 -top-3 h-[calc(50%+0.75rem)] w-4 rounded-bl-lg border-b-2 border-l-2"
            :class="checked || implied ? 'border-accent-300' : 'border-border-subtle'"
        ></span>
        <div
            class="group grid items-start gap-x-3 rounded-xl px-2.5 py-2 transition"
            :class="[
                readonly
                    ? (tierIndex !== null ? 'grid-cols-[22px_1fr]' : 'grid-cols-[1fr]')
                    : (tierIndex !== null ? 'grid-cols-[18px_22px_1fr]' : 'grid-cols-[18px_1fr]'),
                highlighted ? 'ring-2 ring-accent-300 bg-accent-50/60' : 'hover:bg-surface-sunken',
            ]"
        >
            <div v-if="!readonly" class="pt-[3px]">
                <input
                    type="checkbox"
                    class="h-4 w-4 rounded border-border text-accent-600 focus:ring-accent-600 disabled:opacity-60"
                    :checked="checked || implied"
                    :disabled="readonly || implied"
                    :aria-label="$t(definition.title)"
                    @change="$emit('toggle')"
                />
            </div>
            <span
                v-if="tierIndex !== null"
                class="mt-0.5 inline-flex size-5 items-center justify-center rounded-full text-[11px] font-semibold"
                :class="checked || implied ? 'bg-accent-600 text-white' : 'bg-surface-sunken text-text-muted ring-1 ring-border-subtle'"
            >{{ tierIndex + 1 }}</span>
            <div class="min-w-0">
                <div class="flex items-center gap-1.5">
                    <button
                        type="button"
                        class="text-left text-sm font-medium underline-offset-2 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-300 rounded"
                        :class="checked || implied ? 'text-text' : 'text-text-muted'"
                        @click="$emit('open')"
                    >
                        {{ $t(definition.title) }}
                    </button>
                    <!-- Hinweis (Nebenwirkung, Abgrenzung): wie früher als Info-Icon mit Tooltip -->
                    <ToolTipDefault v-if="definition.note" top :tooltip-text="$t(definition.note)" />
                </div>
                <p class="mt-0.5 text-xs text-text-subtle">
                    <template v-if="tierIndex">{{ $t('Includes level {level}', { level: tierIndex }) }} · </template>{{ $t(definition.effect) }}
                </p>
                <div v-if="implied && supersets.length" class="mt-1 inline-flex items-center gap-1.5 rounded-md bg-accent-50 px-2 py-0.5 text-[11px] font-medium text-accent-700">
                    <PropertyIcon name="IconArrowMerge" class="size-3.5" />
                    {{ $t('Included in {permission}', { permission: supersets.map((n) => $t(titles[n] ?? n)).join(', ') }) }}
                </div>
                <div v-else-if="status === 'blocked_by_module'" class="mt-1 inline-flex items-center gap-1.5 rounded-md bg-surface-sunken px-2 py-0.5 text-[11px] font-medium text-text-muted">
                    <PropertyIcon name="IconPower" class="size-3.5" />
                    {{ $t('Module disabled – the permission is stored but has no effect') }}
                </div>
                <div v-else-if="missing.length" class="mt-1 flex flex-wrap items-center gap-1.5 rounded-md bg-warning-surface px-2 py-0.5 text-[11px] font-medium text-warning">
                    <PropertyIcon name="IconAlertTriangle" class="size-3.5" />
                    <span>{{ $t('Takes effect only with') }}</span>
                    <button
                        v-for="req in missing"
                        :key="req.type + req.value"
                        type="button"
                        class="underline underline-offset-2"
                        :disabled="req.type !== 'permission'"
                        @click="req.type === 'permission' ? $emit('jump', req.value) : null"
                    >{{ req.type === 'permission' ? $t(titles[req.value] ?? req.value) : $t(req.label) }}</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import ToolTipDefault from '@/Components/ToolTips/ToolTipDefault.vue'

defineProps({
    definition: { type: Object, required: true },
    /** 0-basierter Index in der Stufenleiter, null für Zusatz-/Feinrechte */
    tierIndex: { type: Number, default: null },
    checked: { type: Boolean, default: false },
    implied: { type: Boolean, default: false },
    /** gesetzte Supersets, die dieses Recht enthalten */
    supersets: { type: Array, default: () => [] },
    status: { type: String, default: 'inactive' },
    /** fehlende harte Voraussetzungen (Requirement-Objekte mit status) */
    missing: { type: Array, default: () => [] },
    titles: { type: Object, default: () => ({}) },
    readonly: { type: Boolean, default: false },
    highlighted: { type: Boolean, default: false },
    /** Verbindungslinie zur vorherigen Stufe zeichnen */
    showConnector: { type: Boolean, default: true },
})
defineEmits(['toggle', 'open', 'jump'])
</script>
