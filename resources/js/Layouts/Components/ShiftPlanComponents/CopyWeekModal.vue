<template>
    <ArtworkBaseModal
        title="Copy week"
        description="Creates the shifts of one calendar week again in one or more target weeks."
        modal-size="sm:max-w-2xl"
        is-in-shift-plan
        @close="$emit('closed', false)"
    >
        <!-- Ergebnis nach dem Kopieren -->
        <div v-if="result" class="space-y-4">
            <div class="rounded-md bg-success-surface ring-1 ring-success-border px-3 py-2 text-sm text-success" role="status">
                {{ result.summary }}
            </div>

            <ul class="space-y-2">
                <li
                    v-for="target in result.targets"
                    :key="'result-' + target.year + '-' + target.week"
                    class="rounded-lg border border-border-subtle bg-white/90 p-3"
                >
                    <div class="flex items-center justify-between gap-2">
                        <div class="text-sm font-semibold text-text">
                            {{ $t('CW {0}', [target.week]) }} {{ target.year }}
                            <span class="ml-1 text-xs font-normal text-text-subtle">{{ weekRangeLabel(target.year, target.week) }}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <BaseChip variant="success" :count="target.created">{{ $t('created') }}</BaseChip>
                            <BaseChip :variant="target.skipped > 0 ? 'warning' : 'neutral'" :count="target.skipped">{{ $t('skipped') }}</BaseChip>
                        </div>
                    </div>
                    <ul v-if="target.skipped_shifts?.length" class="mt-2 space-y-0.5 text-xs text-text-muted">
                        <li v-for="(skipped, index) in target.skipped_shifts" :key="'skipped-' + index">
                            {{ skipped.date }} · {{ skipped.room ?? '—' }} · {{ skipped.start }}–{{ skipped.end }}
                            <span v-if="skipped.craft">· {{ skipped.craft }}</span>
                            <span class="text-text-subtle">— {{ $t('target time already occupied') }}</span>
                        </li>
                    </ul>
                </li>
            </ul>

            <div class="flex items-center justify-end gap-2 pt-2">
                <BaseUIButton type="button" is-add-button hide-icon @click="closeAndReload">
                    {{ $t('Close and reload plan') }}
                </BaseUIButton>
            </div>
        </div>

        <!-- Formular -->
        <div v-else class="space-y-4">
            <div class="rounded-md bg-accent-50 px-3 py-2 text-xs text-accent-700">
                {{ $t('Room, times, break, craft, function places and note are copied. People are not copied. Occupied target times are skipped. The new shifts are not committed.') }}
            </div>

            <!-- Quellwoche -->
            <section class="rounded-lg border border-border-subtle bg-white/90 p-3">
                <header class="flex items-center gap-2 mb-2">
                    <span class="inline-block size-2.5 rounded-full bg-accent-600"></span>
                    <h3 class="text-[13.5px] font-semibold text-text">{{ $t('Source week') }}</h3>
                </header>

                <div class="flex flex-wrap items-end gap-2">
                    <ToolTipComponent
                        direction="top"
                        :tooltip-text="$t('Previous week')"
                        icon="IconChevronLeft"
                        icon-size="h-5 w-5"
                        classes-button="ui-button"
                        @click="shiftSourceWeek(-1)"
                    />
                    <div class="w-24">
                        <BaseInput
                            id="copy-week-source-week"
                            v-model="sourceWeekInput"
                            type="number"
                            :min="1"
                            :max="53"
                            is-small
                            :label="$t('Calendar week')"
                            @change="applySourceInput"
                        />
                    </div>
                    <div class="w-28">
                        <BaseInput
                            id="copy-week-source-year"
                            v-model="sourceYearInput"
                            type="number"
                            :min="2000"
                            :max="2100"
                            is-small
                            :label="$t('Year')"
                            @change="applySourceInput"
                        />
                    </div>
                    <ToolTipComponent
                        direction="top"
                        :tooltip-text="$t('Next week')"
                        icon="IconChevronRight"
                        icon-size="h-5 w-5"
                        classes-button="ui-button"
                        @click="shiftSourceWeek(1)"
                    />
                    <div class="text-xs text-text-muted pb-2">
                        {{ weekRangeLabel(sourceYear, sourceWeek) }}
                    </div>
                </div>

                <p class="mt-2 text-xs" :class="previewTone" aria-live="polite">
                    <template v-if="previewLoading">{{ $t('Counting shifts…') }}</template>
                    <template v-else-if="previewCount === null">{{ $t('Preview not available.') }}</template>
                    <template v-else>{{ $t('{0} shifts in CW {1}', [previewCount, sourceWeek]) }}</template>
                </p>
            </section>

            <!-- Zielwochen -->
            <section class="rounded-lg border border-border-subtle bg-white/90 p-3">
                <header class="flex items-center justify-between gap-2 mb-2">
                    <div class="flex items-center gap-2">
                        <span class="inline-block size-2.5 rounded-full bg-success"></span>
                        <h3 class="text-[13.5px] font-semibold text-text">{{ $t('Target weeks') }}</h3>
                        <BaseChip variant="accent" :count="selectedTargets.length">{{ $t('selected') }}</BaseChip>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button type="button" class="text-xs text-accent-600 hover:text-accent-700" @click="selectOnlyNext">
                            {{ $t('Next week only') }}
                        </button>
                        <span class="text-text-subtle text-xs">·</span>
                        <button type="button" class="text-xs text-accent-600 hover:text-accent-700" @click="addNextUnselected">
                            {{ $t('+1 week') }}
                        </button>
                        <span class="text-text-subtle text-xs">·</span>
                        <button type="button" class="text-xs text-accent-600 hover:text-accent-700" @click="selectAllTargets">
                            {{ $t('All 8') }}
                        </button>
                        <span class="text-text-subtle text-xs">·</span>
                        <button type="button" class="text-xs text-text-subtle hover:text-text" @click="selectedTargetKeys = []">
                            {{ $t('Clear') }}
                        </button>
                    </div>
                </header>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1.5">
                    <div
                        v-for="target in targetOptions"
                        :key="target.key"
                        class="rounded-md px-2 py-1.5 transition-colors"
                        :class="isTargetSelected(target.key) ? 'bg-accent-50' : 'hover:bg-surface-sunken'"
                    >
                        <BaseCheckbox
                            :id="'copy-week-target-' + target.key"
                            :model-value="isTargetSelected(target.key)"
                            :label="$t('CW {0}', [target.week]) + ' ' + target.year"
                            :description="target.rangeLabel"
                            @update:modelValue="toggleTarget(target.key, $event)"
                        />
                    </div>
                </div>
            </section>

            <!-- Gewerke -->
            <section class="rounded-lg border border-border-subtle bg-white/90 p-3">
                <header class="flex items-center justify-between gap-2 mb-2">
                    <div class="flex items-center gap-2">
                        <span class="inline-block size-2.5 rounded-full bg-warning"></span>
                        <h3 class="text-[13.5px] font-semibold text-text">{{ $t('Crafts') }}</h3>
                    </div>
                    <button
                        v-if="!allCraftsSelected"
                        type="button"
                        class="text-xs text-accent-600 hover:text-accent-700"
                        @click="selectedCrafts = [...craftItems]"
                    >
                        {{ $t('Select all') }}
                    </button>
                </header>
                <ArtworkBaseListbox
                    v-model="selectedCrafts"
                    :items="craftItems"
                    multiple
                    by="id"
                    option-label="name"
                    option-key="id"
                    :label="$t('Only copy shifts of these crafts')"
                    :use-translations="false"
                    :selected-formatter="craftSummary"
                    is-small
                />
                <p class="mt-1 text-xs text-text-subtle">
                    {{ allCraftsSelected ? $t('All crafts are copied.') : $t('Only shifts of the selected crafts are copied.') }}
                </p>
            </section>

            <div v-if="requestError" class="rounded-md bg-danger-surface ring-1 ring-danger-border px-3 py-2 text-xs text-danger" role="alert">
                {{ requestError }}
            </div>

            <div class="flex items-center justify-between gap-2 pt-1">
                <span v-if="submitDisabledReason" class="text-xs text-text-subtle">{{ submitDisabledReason }}</span>
                <span v-else></span>
                <div class="flex items-center gap-2">
                    <BaseUIButton type="button" hide-icon @click="$emit('closed', false)">
                        {{ $t('Cancel') }}
                    </BaseUIButton>
                    <BaseUIButton
                        type="button"
                        is-add-button
                        icon="IconCopy"
                        :disabled="!canSubmit || submitting"
                        :processing="submitting"
                        @click="submit"
                    >
                        {{ $t('Copy to {0} week(s)', [selectedTargets.length]) }}
                    </BaseUIButton>
                </div>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { computed, ref, watch, onMounted } from 'vue'
import axios from 'axios'
import { router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseCheckbox from '@/Artwork/Inputs/BaseCheckbox.vue'
import BaseChip from '@/Artwork/Chips/BaseChip.vue'
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue'
import ToolTipComponent from '@/Components/ToolTips/ToolTipComponent.vue'

const { t: $t } = useI18n()

const props = defineProps({
    /** Aktueller Plan-Zeitraum [start, end] als YYYY-MM-DD — Quell-KW wird daraus vorbelegt */
    dateValue: { type: Array, default: () => [] },
    /** Gewerke ({id, name, abbreviation}) — Standard: alle ausgewählt */
    crafts: { type: [Array, Object], default: () => [] },
})

const emit = defineEmits(['closed'])

// ---------- ISO-Kalenderwochen-Helfer (lokale Zeit, mittags gegen DST-Kanten) ----------
const DAY_MS = 86400000
const pad = (n) => String(n).padStart(2, '0')
const atNoon = (d) => new Date(d.getFullYear(), d.getMonth(), d.getDate(), 12)
const addDays = (d, n) => atNoon(new Date(d.getTime() + n * DAY_MS))
const fromIso = (s) => {
    const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(s ?? ''))
    return m ? atNoon(new Date(+m[1], +m[2] - 1, +m[3])) : atNoon(new Date())
}
const isoWeekInfo = (d) => {
    const t = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()))
    const day = t.getUTCDay() || 7
    t.setUTCDate(t.getUTCDate() + 4 - day)
    const yearStart = new Date(Date.UTC(t.getUTCFullYear(), 0, 1))
    return { week: Math.ceil(((t - yearStart) / DAY_MS + 1) / 7), year: t.getUTCFullYear() }
}
/** Montag der ISO-KW: 4. Januar liegt immer in KW 1 */
const mondayOfIsoWeek = (year, week) => {
    const jan4 = atNoon(new Date(year, 0, 4))
    const jan4Weekday = (jan4.getDay() + 6) % 7
    return addDays(jan4, -jan4Weekday + (week - 1) * 7)
}
const isoWeeksInYear = (year) => isoWeekInfo(atNoon(new Date(year, 11, 28))).week
const formatDisplay = (d) => `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()}`
const weekRangeLabel = (year, week) => {
    const monday = mondayOfIsoWeek(year, week)
    return `${formatDisplay(monday)} – ${formatDisplay(addDays(monday, 6))}`
}
const targetKey = (year, week) => `${year}-${week}`

// ---------- Quellwoche ----------
const initial = isoWeekInfo(fromIso(props.dateValue?.[0]))
const sourceWeek = ref(initial.week)
const sourceYear = ref(initial.year)
const sourceWeekInput = ref(String(initial.week))
const sourceYearInput = ref(String(initial.year))

function setSource(year, week) {
    const maxWeek = isoWeeksInYear(year)
    let normalizedYear = year
    let normalizedWeek = week
    if (normalizedWeek < 1) {
        normalizedYear -= 1
        normalizedWeek = isoWeeksInYear(normalizedYear)
    } else if (normalizedWeek > maxWeek) {
        normalizedYear += 1
        normalizedWeek = 1
    }
    sourceYear.value = normalizedYear
    sourceWeek.value = normalizedWeek
    sourceWeekInput.value = String(normalizedWeek)
    sourceYearInput.value = String(normalizedYear)
}
function shiftSourceWeek(delta) {
    setSource(sourceYear.value, sourceWeek.value + delta)
}
function applySourceInput() {
    const week = parseInt(sourceWeekInput.value, 10)
    const year = parseInt(sourceYearInput.value, 10)
    if (Number.isNaN(week) || Number.isNaN(year) || year < 2000 || year > 2100) {
        sourceWeekInput.value = String(sourceWeek.value)
        sourceYearInput.value = String(sourceYear.value)
        return
    }
    setSource(year, Math.min(Math.max(week, 1), isoWeeksInYear(year)))
}

// ---------- Zielwochen: die nächsten 8 KWs nach der Quelle ----------
const targetOptions = computed(() => {
    const sourceMonday = mondayOfIsoWeek(sourceYear.value, sourceWeek.value)
    return Array.from({ length: 8 }, (_, i) => {
        const monday = addDays(sourceMonday, (i + 1) * 7)
        const info = isoWeekInfo(monday)
        return {
            key: targetKey(info.year, info.week),
            week: info.week,
            year: info.year,
            rangeLabel: `${formatDisplay(monday)} – ${formatDisplay(addDays(monday, 6))}`,
        }
    })
})
const selectedTargetKeys = ref([])
const selectedTargets = computed(() =>
    targetOptions.value.filter((target) => selectedTargetKeys.value.includes(target.key))
)
const isTargetSelected = (key) => selectedTargetKeys.value.includes(key)
function toggleTarget(key, checked) {
    if (checked) {
        if (!selectedTargetKeys.value.includes(key)) selectedTargetKeys.value = [...selectedTargetKeys.value, key]
    } else {
        selectedTargetKeys.value = selectedTargetKeys.value.filter((k) => k !== key)
    }
}
function selectOnlyNext() {
    selectedTargetKeys.value = [targetOptions.value[0].key]
}
function addNextUnselected() {
    const next = targetOptions.value.find((target) => !selectedTargetKeys.value.includes(target.key))
    if (next) selectedTargetKeys.value = [...selectedTargetKeys.value, next.key]
}
function selectAllTargets() {
    selectedTargetKeys.value = targetOptions.value.map((target) => target.key)
}
// Quellwoche gewechselt → Zieloptionen sind neu; nur noch gültige Auswahl behalten
watch(targetOptions, (options) => {
    const valid = new Set(options.map((o) => o.key))
    selectedTargetKeys.value = selectedTargetKeys.value.filter((k) => valid.has(k))
})

// ---------- Gewerke ----------
const craftItems = computed(() => {
    const list = Array.isArray(props.crafts) ? props.crafts : Object.values(props.crafts || {})
    return list
        .filter((craft) => craft && craft.id != null)
        .map((craft) => ({ id: craft.id, name: craft.name ?? craft.abbreviation ?? String(craft.id) }))
})
const selectedCrafts = ref([...craftItems.value])
watch(craftItems, (items) => {
    // Nachgeladene Gewerke: Standard bleibt „alle", solange nichts abgewählt wurde
    if (allCraftsSelected.value || selectedCrafts.value.length === 0) selectedCrafts.value = [...items]
})
const allCraftsSelected = computed(() =>
    craftItems.value.length > 0 && selectedCrafts.value.length === craftItems.value.length
)
const craftSummary = (items) =>
    items.length === craftItems.value.length
        ? $t('All crafts')
        : items.map((item) => item.name).join(', ')
/** null = kein Filter (alle Gewerke) */
const craftIdsPayload = computed(() =>
    allCraftsSelected.value ? null : selectedCrafts.value.map((craft) => craft.id)
)

// ---------- Vorschau ----------
const previewCount = ref(null)
const previewLoading = ref(false)
let previewTimer = null
let previewRequestSeq = 0
async function loadPreview() {
    const seq = ++previewRequestSeq
    previewLoading.value = true
    try {
        const { data } = await axios.get(route('shifts.copy-week.preview'), {
            params: {
                source_week: sourceWeek.value,
                source_year: sourceYear.value,
                craft_ids: craftIdsPayload.value ?? undefined,
            },
        })
        if (seq === previewRequestSeq) previewCount.value = data?.count ?? null
    } catch {
        if (seq === previewRequestSeq) previewCount.value = null
    } finally {
        if (seq === previewRequestSeq) previewLoading.value = false
    }
}
function schedulePreview() {
    if (previewTimer) clearTimeout(previewTimer)
    previewTimer = setTimeout(loadPreview, 250)
}
watch([sourceWeek, sourceYear, craftIdsPayload], schedulePreview)
onMounted(loadPreview)
const previewTone = computed(() => {
    if (previewLoading.value || previewCount.value === null) return 'text-text-subtle'
    return previewCount.value > 0 ? 'text-text-muted' : 'text-warning'
})

// ---------- Absenden ----------
const requestError = ref('')
const submitting = ref(false)
const result = ref(null)

const canSubmit = computed(() =>
    selectedTargets.value.length > 0
    && selectedCrafts.value.length > 0
    && previewCount.value !== 0
)
const submitDisabledReason = computed(() => {
    if (selectedTargets.value.length === 0) return $t('Select at least one target week.')
    if (selectedCrafts.value.length === 0) return $t('Select at least one craft.')
    if (previewCount.value === 0) return $t('The source week contains no shifts.')
    return ''
})

function extractError(error) {
    const data = error?.response?.data
    if (data?.errors && typeof data.errors === 'object') {
        const first = Object.values(data.errors).flat().find(Boolean)
        if (first) return String(first)
    }
    return data?.message ?? $t('An error has occurred')
}

async function submit() {
    if (!canSubmit.value || submitting.value) return
    requestError.value = ''
    submitting.value = true
    try {
        const { data } = await axios.post(route('shifts.copy-week'), {
            source_week: sourceWeek.value,
            source_year: sourceYear.value,
            targets: selectedTargets.value.map((target) => ({ week: target.week, year: target.year })),
            craft_ids: craftIdsPayload.value ?? [],
        })
        result.value = data
    } catch (error) {
        requestError.value = extractError(error)
    } finally {
        submitting.value = false
    }
}

/** Plan neu laden: die Schichtdaten werden beim Mount des Plans per Axios geholt, daher voller Inertia-Besuch */
function closeAndReload() {
    emit('closed', true)
    router.visit(window.location.href, { preserveScroll: true, preserveState: false })
}
</script>
