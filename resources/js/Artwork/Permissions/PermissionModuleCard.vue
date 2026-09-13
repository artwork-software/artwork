<template>
    <section
        :id="`permission-module-${module.key}`"
        class="rounded-3xl border bg-white shadow-sm transition"
        :class="summary.enabled ? 'border-border-subtle' : 'border-dashed border-border opacity-80'"
    >
        <header class="flex items-center justify-between gap-3 px-5 py-3.5 sm:px-6">
            <button type="button" class="flex min-w-0 items-center gap-3 text-left" @click="open = !open">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-accent-50 text-accent-700">
                    <PropertyIcon :name="module.icon" class="size-5" />
                </span>
                <span class="min-w-0">
                    <span class="flex items-center gap-2">
                        <span class="truncate text-sm font-semibold text-text">{{ $t(module.title) }}</span>
                        <span class="rounded-full bg-surface-sunken px-2 py-0.5 text-[11px] font-medium tabular-nums text-text-muted">
                            {{ summary.granted }} / {{ summary.total }}
                        </span>
                    </span>
                    <span v-if="!summary.enabled" class="mt-0.5 block text-[11px] font-medium text-warning">
                        {{ $t('Module disabled in the system settings – permissions are stored but have no effect') }}
                        <a
                            v-if="moduleSettingsUrl"
                            :href="moduleSettingsUrl"
                            class="ml-1 underline underline-offset-2"
                            @click.stop
                        >{{ $t('Open module settings') }}</a>
                    </span>
                </span>
            </button>
            <div class="flex shrink-0 items-center gap-3">
                <button
                    v-if="!readonly"
                    type="button"
                    class="text-xs font-medium text-accent-700 underline underline-offset-2 hover:text-accent-800"
                    @click="$emit(summary.granted > 0 ? 'clear-all' : 'grant-all')"
                >
                    {{ summary.granted > 0 ? $t('Deselect all') : $t('Select all') }}
                </button>
                <button type="button" class="text-text-subtle" :aria-label="open ? $t('Collapse') : $t('Expand')" @click="open = !open">
                    <PropertyIcon name="IconChevronDown" class="size-4 transition-transform" :class="open ? 'rotate-180' : ''" />
                </button>
            </div>
        </header>

        <div v-if="open" class="border-t border-border-subtle px-4 pb-4 pt-3 sm:px-5">
            <p v-if="module.hint" class="mb-3 rounded-xl bg-accent-50/60 px-3 py-2 text-xs text-text-muted">
                {{ $t(module.hint) }}
            </p>

            <template v-if="tiers.length">
                <h4 class="mb-1 px-2.5 text-[11px] font-semibold uppercase tracking-wide text-text-subtle">{{ $t('Levels') }}</h4>
                <div class="space-y-0.5">
                    <PermissionRow
                        v-for="def in tiers"
                        :key="def.name"
                        v-bind="rowProps(def)"
                        :tier-index="tierIndexOf(def)"
                        :show-connector="hasVisiblePreviousTier(def)"
                        @toggle="$emit('toggle', def.name)"
                        @open="$emit('open', def.name)"
                        @jump="$emit('jump', $event)"
                    />
                </div>
            </template>

            <template v-if="extras.length">
                <h4 class="mb-1 mt-3 px-2.5 text-[11px] font-semibold uppercase tracking-wide text-text-subtle">
                    {{ tiers.length ? $t('Additional permissions') : $t('Permissions') }}
                </h4>
                <div class="space-y-0.5">
                    <PermissionRow
                        v-for="def in extras"
                        :key="def.name"
                        v-bind="rowProps(def)"
                        @toggle="$emit('toggle', def.name)"
                        @open="$emit('open', def.name)"
                        @jump="$emit('jump', $event)"
                    />
                </div>
            </template>

            <template v-if="advanced.length">
                <button
                    type="button"
                    class="mt-3 flex w-full items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-left hover:bg-surface-sunken"
                    @click="advancedOpen = !advancedOpen"
                >
                    <PropertyIcon name="IconChevronRight" class="size-3.5 shrink-0 text-text-subtle transition-transform" :class="advancedOpen ? 'rotate-90' : ''" />
                    <span class="text-[11px] font-semibold uppercase tracking-wide text-text-subtle">
                        {{ $t(module.advanced_title ?? 'Advanced permissions') }}
                        <span class="font-normal normal-case tracking-normal">({{ advancedGranted }} / {{ advanced.length }})</span>
                    </span>
                    <span v-if="module.advanced_hint" class="ml-2 text-[11px] text-text-subtle">{{ $t(module.advanced_hint) }}</span>
                </button>
                <div v-if="advancedOpen" class="mt-1 space-y-0.5">
                    <PermissionRow
                        v-for="def in advanced"
                        :key="def.name"
                        v-bind="rowProps(def)"
                        @toggle="$emit('toggle', def.name)"
                        @open="$emit('open', def.name)"
                        @jump="$emit('jump', $event)"
                    />
                </div>
            </template>

            <p v-if="module.admin_only?.length" class="mt-3 px-2.5 text-[11px] text-text-subtle">
                {{ $t('Only for artwork admins') }}: {{ module.admin_only.map((a) => $t(a)).join(' · ') }}
            </p>

            <p v-if="!tiers.length && !extras.length && !advanced.length" class="px-2.5 py-2 text-xs text-text-subtle">
                {{ $t('No permissions match the current filter.') }}
            </p>
        </div>
    </section>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import PermissionRow from '@/Artwork/Permissions/PermissionRow.vue'

const props = defineProps({
    module: { type: Object, required: true },
    /** gefilterte Definitionen dieses Moduls */
    tiers: { type: Array, default: () => [] },
    extras: { type: Array, default: () => [] },
    advanced: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({ total: 0, granted: 0, enabled: true }) },
    /** name => Zeilen-Props (checked, implied, supersets, status, missing) */
    rowState: { type: Object, default: () => ({}) },
    titles: { type: Object, default: () => ({}) },
    readonly: { type: Boolean, default: false },
    highlightedName: { type: String, default: null },
    initiallyOpen: { type: Boolean, default: true },
    /** Feinrechte ausgeklappt zeigen (z. B. bei Suche) */
    forceAdvancedOpen: { type: Boolean, default: false },
    /** Link zu den Modul-Einstellungen (nur bei deaktiviertem Modul angezeigt) */
    moduleSettingsUrl: { type: String, default: null },
})
defineEmits(['toggle', 'open', 'jump', 'grant-all', 'clear-all'])

const open = ref(props.initiallyOpen)
watch(() => props.initiallyOpen, (value) => { open.value = value })
const advancedOpen = ref(props.forceAdvancedOpen)
watch(() => props.forceAdvancedOpen, (value) => { if (value) advancedOpen.value = true })
watch(() => props.highlightedName, (name) => {
    if (name && props.advanced.some((d) => d.name === name)) advancedOpen.value = true
    if (name && [...props.tiers, ...props.extras, ...props.advanced].some((d) => d.name === name)) open.value = true
})

/** Stufennummer aus der vollständigen Leiter des Moduls, damit sie beim Filtern erhalten bleibt */
const tierIndexOf = (def) => Math.max(0, (props.module.tiers ?? []).findIndex((t) => t.name === def.name))

/** Verbindungslinie nur zeichnen, wenn die vorherige Stufe im (gefilterten) Ergebnis steht */
const hasVisiblePreviousTier = (def) => {
    const index = tierIndexOf(def)
    if (index === 0) return false
    const previous = props.module.tiers?.[index - 1]
    return !!previous && props.tiers.some((t) => t.name === previous.name)
}

const advancedGranted = computed(() => props.advanced.filter((d) => props.rowState[d.name]?.checked || props.rowState[d.name]?.implied).length)

const rowProps = (def) => ({
    definition: def,
    titles: props.titles,
    readonly: props.readonly,
    highlighted: props.highlightedName === def.name,
    ...(props.rowState[def.name] ?? {}),
})
</script>
