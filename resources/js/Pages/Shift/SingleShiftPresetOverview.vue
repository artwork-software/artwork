<script lang="ts" setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AddSingleShiftPresetModal from './Components/AddSingleShiftPresetModal.vue'
import { useI18n } from 'vue-i18n'
import ShiftSettingsHeader from "@/Pages/Settings/Components/ShiftSettingsHeader.vue"
import SearchableSelect from "@/Artwork/Listbox/SearchableSelect.vue"
import SettingsGuideBanner from "@/Artwork/Guide/SettingsGuideBanner.vue"
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue"

// Tabler Icons
import {
    IconCirclePlus,
    IconPencil,
    IconTrash,
    IconSearch,
    IconArrowsSort,
    IconChevronDown
} from '@tabler/icons-vue'

const { t: $t } = useI18n()

const props = defineProps<{
    presets: Array<any>
    crafts: Array<any>
    shiftQualifications: Array<any>
}>()

const showModal = ref(false)
const editPreset = ref<any | null>(null)

// Toolbar-States
const search = ref('')
const selectedCraftId = ref<number | 'all'>('all')
const sortBy = ref<'name' | 'start_time' | 'end_time'>('start_time')
const sortDir = ref<'asc' | 'desc'>('asc')

function openAddModal() { editPreset.value = null; showModal.value = true }
function openEditModal(p) { editPreset.value = p; showModal.value = true }
function handleSaved() { showModal.value = false; router.reload({ only: ['presets'] }) }
function handleClosed() { showModal.value = false }
const presetIdToDelete = ref<number | null>(null)
function deletePreset(id: number) {
    presetIdToDelete.value = id
}
function closeDeleteModal() {
    presetIdToDelete.value = null
}
function confirmDeletePreset() {
    if (presetIdToDelete.value === null) return
    router.delete(route('single-shift-presets.destroy', { id: presetIdToDelete.value }), {
        onSuccess: () => router.reload({ only: ['presets'] }),
        onFinish: () => closeDeleteModal()
    })
}

// Helper
function craftNameById(id?: number) {
    return props.crafts.find(c => c.id === id)?.name ?? '—'
}
function craftAbbrById(id?: number) {
    return props.crafts.find(c => c.id === id)?.abbreviation ?? ''
}
function normalizeQualifications(preset: any) {
    const list = preset?.shifts_qualifications ?? preset?.shift_qualifications ?? []
    return Array.isArray(list) ? list : []
}
function qualNameById(id: number) {
    return props.shiftQualifications.find(sq => sq.id === id)?.name ?? `#${id}`
}
function qualQuantity(q: any) {
    const pv = q?.pivot?.quantity
    if (typeof pv === 'number') return pv
    if (typeof q?.quantity === 'number') return q.quantity
    return 1
}

// Normalize any time-like value to 'HH:MM' (trims seconds, handles ISO strings)
function toHHMM(val: any): string | null {
    if (val === null || typeof val === 'undefined') return null
    let s = String(val).trim()
    if (!s) return null
    const tIndex = s.indexOf('T')
    if (tIndex !== -1) s = s.slice(tIndex + 1)
    const m = s.match(/(\d{1,2}):(\d{2})/)
    if (m) {
        const hh = m[1].padStart(2, '0')
        const mm = m[2]
        return `${hh}:${mm}`
    }
    return null
}

// Farbakzent zyklisch
const accentSet = [
    { bar: 'bg-accent-600', chip: 'bg-accent-50 text-accent-700 border-accent-200' },
    { bar: 'bg-success', chip: 'bg-success-surface text-success border-success-border' },
    { bar: 'bg-special-violet', chip: 'bg-special-violet-surface text-special-violet border-special-violet-border' },
    { bar: 'bg-warning', chip: 'bg-warning-surface text-warning border-warning-border' },
]
function accentByIndex(i: number) { return accentSet[i % accentSet.length] }

const filteredAndSorted = computed(() => {
    let rows = [...(props.presets ?? [])]

    if (search.value.trim()) {
        const s = search.value.toLowerCase()
        rows = rows.filter(p => {
            const name = (p?.name ?? '').toLowerCase()
            const craft = craftNameById(p?.craft_id).toLowerCase()
            return name.includes(s) || craft.includes(s)
        })
    }
    if (selectedCraftId.value !== 'all') {
        rows = rows.filter(p => p?.craft_id === selectedCraftId.value)
    }
    rows.sort((a, b) => {
        const dir = sortDir.value === 'asc' ? 1 : -1
        const key = sortBy.value
        const va = (a?.[key] ?? '').toString().toLowerCase()
        const vb = (b?.[key] ?? '').toString().toLowerCase()
        if (va < vb) return -1 * dir
        if (va > vb) return  1 * dir
        return 0
    })
    return rows
})

const totalCount = computed(() => props.presets?.length ?? 0)
const shownCount = computed(() => filteredAndSorted.value.length)

function toggleSort(key: 'name' | 'start_time' | 'end_time') {
    if (sortBy.value === key) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortBy.value = key
        sortDir.value = 'asc'
    }
}
</script>

<template>
    <ShiftSettingsHeader
        :title="$t('Shift Templates')"
        :description="$t('Templates with times, trades, and qualifications — quick to plan and reusable.')"
    >
        <template #actions>
            <button class="ui-button-add" type="button" @click="openAddModal">
                <IconCirclePlus class="size-4" />
                <span>{{ $t('Create Preset') }}</span>
            </button>
        </template>

        <div class="space-y-6">
            <SettingsGuideBanner
                storage-key="settings-guide.shift.shift-templates"
                title="How shift templates work"
                :paragraphs="[
                    'A template combines times, craft and qualification demand. Applying it in the shift plan creates a fully configured shift in a single step.',
                    'Not to be confused: time presets (main tab) only store times as a quick selection, and shift preset groups (own tab) bundle several templates so they can be applied together.'
                ]"
            />

            <!-- Toolbar -->
            <div class="rounded-2xl border border-border-subtle bg-white p-4 shadow-sm">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    <!-- Suche -->
                    <div class="relative">
                        <IconSearch class="pointer-events-none absolute left-3 top-2.5 size-4 text-text-subtle" />
                        <input
                            v-model="search"
                            type="text"
                            :placeholder="$t('Search by name or craft...')"
                            class="w-full rounded-xl border border-border-subtle px-9 py-2 text-sm outline-none placeholder:text-text-subtle focus:border-accent-600"
                        />
                    </div>

                    <!-- Craft-Filter -->
                    <div class="grid grid-cols-[1fr_auto] gap-2">
                        <div>
                            <SearchableSelect
                                v-model="selectedCraftId"
                                :options="props.crafts"
                                value-key="id"
                                :label-key="c => c.abbreviation ? `${c.name} (${c.abbreviation})` : c.name"
                                :empty-option="{ label: 'All Crafts', value: 'all' }"
                                :placeholder="$t('All Crafts')"
                            />
                        </div>
                        <!-- Aktiver Zustand sichtbar (ui-button + Extras) -->
                        <button
                            type="button"
                            class="ui-button"
                            :class="selectedCraftId !== 'all' ? 'ring-1 ring-accent-600 bg-accent-50 text-accent-700 font-semibold' : 'ring-0'"
                            @click="selectedCraftId = 'all'"
                        >
                            {{ $t('Reset') }}
                        </button>
                    </div>

                    <!-- Sortierung (ui-button + aktive Hervorhebung) -->
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            class="ui-button"
                            type="button"
                            @click="toggleSort('start_time')"
                            :class="sortBy==='start_time'
                ? 'ring-1 ring-accent-600 bg-accent-50 text-accent-700 font-semibold'
                : 'ring-0'"
                        >
                            <IconArrowsSort class="size-4" />
                            <span>{{ $t('Sort: Start') }} {{ sortBy==='start_time' ? (sortDir==='asc' ? '↑' : '↓') : '' }}</span>
                        </button>
                        <button
                            class="ui-button"
                            type="button"
                            @click="toggleSort('name')"
                            :class="sortBy==='name'
                ? 'ring-1 ring-accent-600 bg-accent-50 text-accent-700 font-semibold'
                : 'ring-0'"
                        >
                            <IconArrowsSort class="size-4" />
                            <span>{{ $t('Sort by: Name') }} {{ sortBy==='name' ? (sortDir==='asc' ? '↑' : '↓') : '' }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Liste -->
            <div v-if="filteredAndSorted.length" class="overflow-hidden rounded-2xl border border-border-subtle bg-white shadow-sm">
                <!-- Desktop Header -->
                <div class="hidden md:grid grid-cols-[8px_22%_12%_12%_18%_auto_160px] items-center bg-surface-sunken text-xs font-medium uppercase text-text-subtle">
                    <div></div>
                    <div class="px-6 py-3">{{ $t('Name') }}</div>
                    <div class="px-6 py-3">{{ $t('Start') }}</div>
                    <div class="px-6 py-3">{{ $t('End') }}</div>
                    <div class="px-6 py-3">{{ $t('Craft') }}</div>
                    <div class="px-6 py-3">{{ $t('Qualifications') }}</div>
                    <div class="px-6 py-3 text-right"></div>
                </div>

                <!-- Desktop Rows -->
                <div class="hidden md:block divide-y divide-border-subtle">
                    <div
                        v-for="(preset, i) in filteredAndSorted"
                        :key="preset.id"
                        class="grid grid-cols-[8px_22%_12%_12%_18%_auto_160px] items-center hover:bg-surface-sunken"
                    >
                        <!-- Akzentleiste -->
                        <div :class="accentByIndex(i).bar"></div>

                        <!-- Name + Desc -->
                        <div class="px-6 py-4">
                            <div class="font-medium text-text">{{ preset.name }}</div>
                            <div v-if="preset.description" class="truncate text-xs text-text-subtle">
                                {{ preset.description }}
                            </div>
                        </div>

                        <!-- Start -->
                        <div class="px-6 py-4">
              <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs"
                    :class="accentByIndex(i).chip">
                {{ toHHMM(preset.start_time) }}
              </span>
                        </div>

                        <!-- Ende -->
                        <div class="px-6 py-4">
              <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs"
                    :class="accentByIndex(i).chip">
                {{ toHHMM(preset.end_time) }}
              </span>
                        </div>

                        <!-- Gewerk -->
                        <div class="px-6 py-4">
                            <div class="flex items-center gap-2">
                <span class="rounded-lg border border-border-subtle px-2 py-1 text-xs font-semibold text-text-muted">
                  {{ craftAbbrById(preset.craft_id) || '—' }}
                </span>
                                <span class="text-sm text-text">{{ craftNameById(preset.craft_id) }}</span>
                            </div>
                        </div>

                        <!-- NEU: Eigene Spalte für Qualifikationen -->
                        <div class="px-6 py-4">
                            <div class="flex flex-wrap gap-1.5">
                                <template v-if="normalizeQualifications(preset).length">
                  <span
                      v-for="q in normalizeQualifications(preset)"
                      :key="q.id"
                      class="inline-flex items-center gap-1 rounded-full border border-border-subtle bg-white px-2 py-0.5 text-[11px] text-text-muted"
                  >
                    <span class="font-medium">{{ qualNameById(q.id) }}</span>
                    <span class="rounded bg-surface-sunken px-1 font-semibold">{{ qualQuantity(q) }}</span>
                  </span>
                                </template>
                                <span v-else class="text-xs text-text-subtle">—</span>
                            </div>
                        </div>

                        <!-- Aktionen: Buttons allein -->
                        <div class="px-6 py-4">
                            <div class="flex justify-end gap-2">
                                <button class="ui-button" type="button" @click="openEditModal(preset)">
                                    <IconPencil class="size-4" />
                                    <span>{{ $t('Edit') }}</span>
                                </button>
                                <button class="ui-button" type="button" @click="deletePreset(preset.id)">
                                    <IconTrash class="size-4" />
                                    <span>{{ $t('Delete') }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mobile Cards -->
                <div class="md:hidden divide-y divide-border-subtle">
                    <div
                        v-for="(preset, i) in filteredAndSorted"
                        :key="preset.id"
                        class="relative p-4"
                    >
                        <div class="absolute inset-y-0 left-0 w-1 rounded-r" :class="accentByIndex(i).bar"></div>

                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-base font-semibold text-text">{{ preset.name }}</div>
                                <div v-if="preset.description" class="mt-0.5 text-xs text-text-subtle">
                                    {{ preset.description }}
                                </div>
                            </div>
                            <div class="shrink-0 text-right">
                                <div class="text-xs text-text-subtle">{{ $t('Time') }}</div>
                                <div class="mt-1 flex items-center gap-1">
                  <span class="rounded-full border px-2 py-0.5 text-xs" :class="accentByIndex(i).chip">
                    {{ toHHMM(preset.start_time) }}
                  </span>
                                    <span class="text-text-subtle">→</span>
                                    <span class="rounded-full border px-2 py-0.5 text-xs" :class="accentByIndex(i).chip">
                    {{ toHHMM(preset.end_time) }}
                  </span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 flex items-center gap-2">
              <span class="rounded-lg border border-border-subtle px-2 py-1 text-xs font-semibold text-text-muted">
                {{ craftAbbrById(preset.craft_id) || '—' }}
              </span>
                            <span class="text-sm text-text">{{ craftNameById(preset.craft_id) }}</span>
                        </div>

                        <!-- Qualifikationen VOR Buttons -->
                        <div class="mt-3 flex flex-wrap gap-1.5">
                            <template v-if="normalizeQualifications(preset).length">
                <span
                    v-for="q in normalizeQualifications(preset)"
                    :key="q.id"
                    class="inline-flex items-center gap-1 rounded-full border border-border-subtle bg-white px-2 py-0.5 text-[11px] text-text-muted"
                >
                  <span class="font-medium">{{ qualNameById(q.id) }}</span>
                  <span class="rounded bg-surface-sunken px-1 font-semibold">{{ qualQuantity(q) }}</span>
                </span>
                            </template>
                            <span v-else class="text-xs text-text-subtle">—</span>
                        </div>

                        <div class="mt-4 flex justify-end gap-2">
                            <button class="ui-button" type="button" @click="openEditModal(preset)">
                                <IconPencil class="size-4" />
                                <span>{{ $t('Edit') }}</span>
                            </button>
                            <button class="ui-button" type="button" @click="deletePreset(preset.id)">
                                <IconTrash class="size-4" />
                                <span>{{ $t('Delete') }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="rounded-2xl border border-dashed border-border bg-white p-10 text-center">
                <div class="mx-auto max-w-md space-y-3">
                    <h3 class="text-lg font-semibold text-text">{{ $t('No presets available yet') }}</h3>
                </div>
                <div class="mt-4 flex justify-center">
                    <button class="ui-button" type="button" @click="openAddModal">
                        <IconCirclePlus class="size-4" />
                        <span>{{ $t('Create Preset') }}</span>
                    </button>
                </div>
            </div>

            <!-- Delete Modal -->
            <ConfirmDeleteModal
                v-if="presetIdToDelete !== null"
                :title="$t('Delete shift template')"
                :description="$t('Would you like to delete the shift template?')"
                @closed="closeDeleteModal"
                @delete="confirmDeletePreset"
            />

            <!-- Modal -->
            <AddSingleShiftPresetModal
                v-if="showModal"
                :edit="!!editPreset"
                :preset="editPreset"
                :crafts="props.crafts"
                :shift-qualifications="props.shiftQualifications"
                @saved="handleSaved"
                @closed="handleClosed"
            />
        </div>
    </ShiftSettingsHeader>
</template>

<style scoped>
/* Buttons bleiben primär über .ui-button gestylt.
   Für Sort/Filter wird aktiv zusätzlich mit Tailwind-Utilities hervorgehoben. */
</style>
