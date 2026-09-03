<template>
    <div class="space-y-4">
        <!-- Kopfzeile: Zusammenfassung, Rollenbilder, Suche, Filter -->
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <p class="text-xs text-text-muted">
                {{ $t('{granted} of {total} permissions granted', { granted: totals.granted, total: totals.total }) }}
                <template v-if="totals.disabledModules"> · {{ $t('{count} modules disabled', { count: totals.disabledModules }) }}</template>
            </p>
            <div class="relative z-[60] flex flex-wrap items-center gap-2">
                <div v-if="!readonly && presets.length" class="relative">
                    <BaseUIButton variant="secondary" :icon="IconUserCog" label="Apply role preset" @click="presetOpen = !presetOpen; peopleOpen = false" />
                    <div
                        v-if="presetOpen"
                        class="absolute right-0 z-[60] mt-1 w-72 rounded-xl border border-border-subtle bg-white p-1 shadow-lg"
                    >
                        <button
                            v-for="preset in presets"
                            :key="preset.id ?? preset.name"
                            type="button"
                            class="flex w-full items-start justify-between gap-2 rounded-lg px-3 py-2 text-left text-sm hover:bg-surface-sunken"
                            @click="previewSelection(preset.name, preset.permissions, 'preset')"
                        >
                            <span class="text-text">{{ preset.name }}</span>
                            <span class="shrink-0 text-[11px] tabular-nums text-text-subtle">+{{ deltaFor(preset.permissions).length }}</span>
                        </button>
                    </div>
                </div>
                <div v-if="!readonly && people.length" class="relative">
                    <BaseUIButton variant="secondary" :icon="IconUsers" label="Same rights as another person" @click="peopleOpen = !peopleOpen; presetOpen = false" />
                    <div
                        v-if="peopleOpen"
                        class="absolute right-0 z-[60] mt-1 w-80 rounded-xl border border-border-subtle bg-white p-2 shadow-lg"
                    >
                        <input
                            v-model="peopleQuery"
                            type="search"
                            :placeholder="$t('Search person…')"
                            class="mb-1 h-8 w-full rounded-lg border border-border bg-white px-2 text-sm text-text placeholder:text-text-subtle"
                        />
                        <div class="max-h-64 overflow-y-auto">
                            <button
                                v-for="person in filteredPeople"
                                :key="person.id"
                                type="button"
                                class="flex w-full items-start justify-between gap-2 rounded-lg px-3 py-1.5 text-left text-sm hover:bg-surface-sunken"
                                @click="previewSelection(person.name, person.permissions, 'person')"
                            >
                                <span class="text-text">{{ person.name }}</span>
                                <span class="shrink-0 text-[11px] tabular-nums" :title="$t('Plus: this person has more · minus: would be removed with 1:1')">
                                    <span class="text-success">+{{ deltaFor(person.permissions).length }}</span>
                                    <span class="ml-1.5 text-danger">−{{ surplusFor(person.permissions).length }}</span>
                                </span>
                            </button>
                            <p v-if="!filteredPeople.length" class="px-3 py-2 text-xs text-text-subtle">{{ $t('No person found.') }}</p>
                        </div>
                    </div>
                </div>
                <div class="relative">
                    <input
                        v-model="query"
                        type="search"
                        :placeholder="$t('Search permissions or functions…')"
                        class="h-9 w-56 rounded-lg border border-border bg-white px-8 text-sm text-text placeholder:text-text-subtle focus:border-border-strong"
                    />
                    <PropertyIcon name="IconSearch" class="pointer-events-none absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-text-subtle" />
                </div>
                <label v-if="!readonly" class="inline-flex items-center gap-2 text-xs text-text-muted">
                    <input v-model="onlyGranted" type="checkbox" class="h-4 w-4 rounded border-border text-accent-600 focus:ring-accent-600" />
                    {{ $t('Only granted') }}
                </label>
            </div>
        </div>

        <!-- Erklärbaustein -->
        <SettingsGuideBanner
            v-if="showExplainer"
            variant="banner"
            storage-key="settings-guide.permissions.editor"
            icon="IconShieldLock"
            title="How permissions work together"
            :paragraphs="[
                'artwork admins can do everything. For everyone else, a permission only takes effect when its module is enabled in the system settings.',
                'Permissions are grouped by module in the order of the main menu. Levels build on each other: a higher level always includes the lower ones.',
                'Some permissions need another permission, a setting or an interface to take effect – the page shows this next to the permission.',
                'Access to a single project is granted in the project team; component settings can only restrict that further, never extend it.'
            ]"
        />

        <!-- Modul-Karten -->
        <div class="grid grid-cols-1 gap-4" :class="compact ? '' : 'xl:grid-cols-2'">
            <PermissionModuleCard
                v-for="entry in visibleModules"
                :key="entry.module.key"
                :module="entry.module"
                :tiers="entry.tiers"
                :extras="entry.extras"
                :advanced="entry.advanced"
                :summary="entry.summary"
                :row-state="rowState"
                :titles="titles"
                :readonly="readonly"
                :highlighted-name="highlightedName"
                :initially-open="initiallyOpen(entry)"
                :force-advanced-open="query.length > 0 || onlyGranted"
                :module-settings-url="moduleSettingsUrl"
                @toggle="toggle"
                @open="openName = $event"
                @jump="jumpTo"
                @grant-all="grantModule(entry.module)"
                @clear-all="clearModule(entry.module)"
            />
        </div>
        <p v-if="!visibleModules.length" class="rounded-2xl border border-dashed border-border px-4 py-6 text-center text-sm text-text-subtle">
            {{ $t('No permissions match the current filter.') }}
        </p>

        <PermissionEffectCard
            v-if="openDefinition"
            :definition="openDefinition"
            :requirements="requirementsFor(openDefinition)"
            :supersets="activeSupersetsOf(openDefinition.name)"
            :titles="titles"
            :granted="effectiveSet.has(openDefinition.name)"
            :editable="!readonly && !rowState[openDefinition.name]?.implied"
            :usage-count="usageOf(openDefinition.name)"
            @close="openName = null"
            @toggle="toggle(openDefinition.name); openName = null"
            @jump="openName = null; jumpTo($event)"
        />

        <!-- Vorschau/Rückfrage für Mehrfach-Änderungen -->
        <PermissionSelectionPreview
            v-if="preview"
            :title="preview.title"
            :description="preview.description"
            :added="preview.added"
            :removed="preview.removed"
            :titles="titles"
            :modules-by-name="modulesByName"
            :confirm-label="preview.confirmLabel"
            :exact-label="preview.exactLabel ?? null"
            :exact-description="preview.exactDescription ?? null"
            :exact="previewExact"
            @update:exact="previewExact = $event"
            @close="preview = null"
            @confirm="confirmPreview"
        />
    </div>
</template>

<script setup>
/**
 * Gemeinsamer Rechte-Editor (Nutzerrechte-Seite, Presets-Modal, Einladungsmodal, Referenzseite).
 * v-model = Liste gesetzter Rechtenamen. Implikationen (Stufenleiter) werden beim Setzen/Entfernen
 * automatisch nachgezogen; das Backend ergänzt sie beim Speichern erneut.
 * Mehrfach-Änderungen (Rollenbild, "wie Person", Stufe mit abhängigen Rechten entfernen) zeigen vorher eine Vorschau.
 */
import { computed, ref, watch, nextTick, getCurrentInstance, onMounted, onBeforeUnmount } from 'vue'
import { IconUserCog, IconUsers } from '@tabler/icons-vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import SettingsGuideBanner from '@/Artwork/Guide/SettingsGuideBanner.vue'
import PermissionModuleCard from '@/Artwork/Permissions/PermissionModuleCard.vue'
import PermissionEffectCard from '@/Artwork/Permissions/PermissionEffectCard.vue'
import PermissionSelectionPreview from '@/Artwork/Permissions/PermissionSelectionPreview.vue'
import { usePermissionCatalog } from '@/Composeables/usePermissionCatalog.js'

const props = defineProps({
    /** Payload von PermissionCatalogPresenter::present() */
    catalog: { type: Object, required: true },
    modelValue: { type: Array, default: () => [] },
    /** Rechte-Presets { id, name, permissions: string[] } */
    presets: { type: Array, default: () => [] },
    /** Andere Personen { id, name, permissions: string[] } für "Rechte wie Person …" */
    people: { type: Array, default: () => [] },
    readonly: { type: Boolean, default: false },
    /** kompakt: einspaltig, Karten eingeklappt (Einladung) */
    compact: { type: Boolean, default: false },
    showExplainer: { type: Boolean, default: true },
    /** Link zu den Modul-Einstellungen (für deaktivierte Module) */
    moduleSettingsUrl: { type: String, default: null },
})
const emit = defineEmits(['update:modelValue', 'change'])

const selected = computed(() => props.modelValue)
const {
    modules, definitions, effectiveSet, requirementsFor, hardMissing, statusOf,
    activeSupersetsOf, withGranted, withRevoked, dependentsOf, visibleDefinitions, moduleSummary,
} = usePermissionCatalog(computed(() => props.catalog), selected)

const query = ref('')
const onlyGranted = ref(false)
const presetOpen = ref(false)
const peopleOpen = ref(false)
const peopleQuery = ref('')
const openName = ref(null)
const highlightedName = ref(null)
const previewRaw = ref(null)
const previewExact = ref(false)

const titles = computed(() => Object.fromEntries(Object.values(definitions.value).map((d) => [d.name, d.title])))
const modulesByName = computed(() => {
    const map = {}
    for (const module of modules.value) {
        for (const list of ['tiers', 'extras', 'advanced']) {
            for (const def of module[list] ?? []) map[def.name] = module.title
        }
    }
    return map
})
const openDefinition = computed(() => (openName.value ? definitions.value[openName.value] : null))
const usageOf = (name) => {
    const usage = props.catalog?.usage
    if (!usage) return null
    return usage[name] ?? 0
}

const rowState = computed(() => {
    const state = {}
    for (const def of Object.values(definitions.value)) {
        const status = statusOf(def.name)
        state[def.name] = {
            checked: selected.value.includes(def.name),
            implied: status === 'implied' || (!selected.value.includes(def.name) && effectiveSet.value.has(def.name)),
            supersets: activeSupersetsOf(def.name),
            status,
            missing: effectiveSet.value.has(def.name) ? hardMissing(def).filter((r) => r.type !== 'module') : [],
        }
    }
    return state
})

/* Suche: Titel, Wirkung, "Schaltet frei", "Erlaubt" – in beiden Sprachen. Bewusst ohne Hinweise (note). */
const normalizedQuery = computed(() => query.value.trim().toLowerCase())
const { proxy } = getCurrentInstance()
const t = (key) => {
    try {
        return proxy?.$t ? proxy.$t(key) : key
    } catch {
        return key
    }
}

function matchesQuery(def) {
    if (!normalizedQuery.value) return true
    const haystack = [def.title, def.effect, ...(def.unlocks ?? []), ...(def.allows ?? []), def.name]
        .flatMap((s) => [s, t(s)])
        .join(' ')
        .toLowerCase()
    return haystack.includes(normalizedQuery.value)
}

function passesFilters(def) {
    if (def.hidden) return false
    if (onlyGranted.value && !effectiveSet.value.has(def.name)) return false
    return matchesQuery(def)
}

const visibleModules = computed(() =>
    modules.value
        .map((module) => {
            // Feinrechte immer mitliefern; die Karte zeigt sie eingeklappt und öffnet sie bei Suche/Filter
            const tiers = (module.tiers ?? []).filter(passesFilters)
            const extras = (module.extras ?? []).filter(passesFilters)
            const advanced = (module.advanced ?? []).filter(passesFilters)
            return { module, tiers, extras, advanced, summary: moduleSummary(module) }
        })
        .filter((entry) => {
            if (normalizedQuery.value || onlyGranted.value) {
                return entry.tiers.length || entry.extras.length || entry.advanced.length
            }
            return true
        })
)

const totals = computed(() => {
    let total = 0
    let granted = 0
    let disabledModules = 0
    for (const module of modules.value) {
        const summary = moduleSummary(module)
        total += summary.total
        granted += summary.granted
        if (!summary.enabled) disabledModules++
    }
    return { total, granted, disabledModules }
})

/* Große Karten auf schmalen Bildschirmen eingeklappt starten; bei Suche/Filter immer offen */
const isNarrow = ref(false)
const mediaQuery = typeof window !== 'undefined' && window.matchMedia ? window.matchMedia('(max-width: 1279px)') : null
const updateNarrow = () => { isNarrow.value = !!mediaQuery?.matches }
onMounted(() => { updateNarrow(); mediaQuery?.addEventListener?.('change', updateNarrow) })
onBeforeUnmount(() => mediaQuery?.removeEventListener?.('change', updateNarrow))

function initiallyOpen(entry) {
    if (normalizedQuery.value || onlyGranted.value) return true
    if (props.compact) return false
    return !(isNarrow.value && entry.summary.total > 12)
}

/* Personen-Auswahl */
const filteredPeople = computed(() => {
    const q = peopleQuery.value.trim().toLowerCase()
    const list = q ? props.people.filter((p) => p.name.toLowerCase().includes(q)) : props.people
    return list.slice(0, 50)
})

/* Änderungen */
function commit(next) {
    emit('update:modelValue', next)
    emit('change', next)
}

function toggle(name) {
    if (props.readonly) return
    if (rowState.value[name]?.implied) return
    if (selected.value.includes(name)) {
        const dependents = dependentsOf(name)
        if (dependents.length) {
            preview.value = {
                title: t(titles.value[name] ?? name),
                description: 'Removing this level also removes the higher levels that include it.',
                added: [],
                removed: [name, ...dependents],
                confirmLabel: 'Remove',
                next: withRevoked(name),
            }
            return
        }
        commit(withRevoked(name))
        return
    }
    commit(withGranted(name))
}

function grantModule(module) {
    let next = [...selected.value]
    for (const def of visibleDefinitions(module, true)) {
        if (!next.includes(def.name)) next = [...new Set([...next, def.name])]
    }
    commit(next)
}

function clearModule(module) {
    const names = new Set(visibleDefinitions(module, true).map((d) => d.name))
    commit(selected.value.filter((n) => !names.has(n)))
}

function closureOf(names) {
    const result = new Set()
    const queue = [...names]
    while (queue.length) {
        const n = queue.shift()
        if (result.has(n) || !definitions.value[n]) continue
        result.add(n)
        for (const implied of definitions.value[n]?.implies ?? []) queue.push(implied)
    }
    return [...result]
}

/** Rechte, die die aktuelle Person mehr hat als die Liste (würden bei 1:1-Übernahme entfallen) */
function surplusFor(names) {
    const target = new Set(closureOf(names ?? []))
    return [...effectiveSet.value].filter((n) => !target.has(n) && definitions.value[n] && !definitions.value[n].hidden)
}

/** Rechte, die durch eine Liste neu dazukämen (inkl. Implikationen) */
function deltaFor(names) {
    return closureOf(names ?? []).filter((n) => !effectiveSet.value.has(n) && !definitions.value[n]?.hidden)
}

/**
 * Vorschau-Objekt für das Modal. Bei "Gleiche Rechte wie andere Person" entscheidet previewExact:
 * additiv (nur, was die Person mehr hat) oder 1:1 (auch überzählige Rechte entfernen).
 */
const preview = computed({
    get() {
        const raw = previewRaw.value
        if (!raw) return null
        if (raw.kind !== 'person') return raw
        const target = closureOf(raw.names ?? [])
        const targetSet = new Set(target)
        const exact = previewExact.value
        const removed = exact
            ? [...effectiveSet.value].filter((n) => !targetSet.has(n) && definitions.value[n] && !definitions.value[n].hidden)
            : []
        return {
            ...raw,
            description: exact
                ? 'The permissions are set exactly like this person\'s: missing ones are added, surplus ones are removed.'
                : 'The permissions of this person are added; existing permissions stay.',
            added: deltaFor(raw.names),
            removed,
            confirmLabel: removed.length ? 'Apply and remove' : 'Apply',
            next: exact ? target : [...new Set([...selected.value, ...target])],
        }
    },
    set(value) {
        previewRaw.value = value
    },
})

function previewSelection(label, names, kind) {
    presetOpen.value = false
    peopleOpen.value = false
    previewExact.value = false
    if (kind === 'person') {
        previewRaw.value = {
            kind,
            names,
            title: t('Same rights as another person') + ': ' + label,
            exactLabel: 'Also remove permissions this person does not have',
            exactDescription: 'Off: only permissions this person has in addition are granted. On: the permissions are set 1:1.',
        }
        return
    }
    previewRaw.value = {
        kind,
        title: t('Role preset') + ': ' + label,
        description: 'The permissions of this role preset are added; existing permissions stay.',
        added: deltaFor(names),
        removed: [],
        confirmLabel: 'Apply',
        next: [...new Set([...selected.value, ...closureOf(names ?? [])])],
    }
}

function confirmPreview() {
    if (!preview.value) return
    commit(preview.value.next)
    previewRaw.value = null
}

async function jumpTo(name) {
    highlightedName.value = name
    await nextTick()
    document.getElementById(`permission-row-${name}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' })
    window.setTimeout(() => { if (highlightedName.value === name) highlightedName.value = null }, 2500)
}

watch(() => props.catalog, () => { openName.value = null })

defineExpose({ jumpTo })
</script>
