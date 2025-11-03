<script lang="ts" setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AddSingleShiftPresetModal from './Components/AddSingleShiftPresetModal.vue'
import { useI18n } from 'vue-i18n'
import ShiftSettingsHeader from "@/Pages/Settings/Components/ShiftSettingsHeader.vue"

// Tabler Icons
import {
    IconPlus,
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
function deletePreset(id: number) {
    if (confirm($t('Preset wirklich löschen?'))) {
        router.delete(route('single-shift-presets.destroy', { id }), {
            onSuccess: () => router.reload({ only: ['presets'] })
        })
    }
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
    { bar: 'bg-blue-500', chip: 'bg-blue-50 text-blue-700 border-blue-200' },
    { bar: 'bg-emerald-500', chip: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
    { bar: 'bg-violet-500', chip: 'bg-violet-50 text-violet-700 border-violet-200' },
    { bar: 'bg-amber-500', chip: 'bg-amber-50 text-amber-800 border-amber-200' },
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
                <IconPlus class="size-4" />
                <span>{{ $t('Create Preset') }}</span>
            </button>
        </template>

        <div class="space-y-6">
            <!-- Toolbar -->
            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    <!-- Suche -->
                    <div class="relative">
                        <IconSearch class="pointer-events-none absolute left-3 top-2.5 size-4 text-gray-400" />
                        <input
                            v-model="search"
                            type="text"
                            :placeholder="$t('Search by name or craft...')"
                            class="w-full rounded-xl border border-gray-200 px-9 py-2 text-sm outline-none placeholder:text-gray-400 focus:border-blue-500"
                        />
                    </div>

                    <!-- Craft-Filter -->
                    <div class="grid grid-cols-[1fr_auto] gap-2">
                        <div class="relative">
                            <IconChevronDown class="pointer-events-none absolute right-3 top-2.5 size-4 text-gray-400" />
                            <select
                                v-model="selectedCraftId"
                                class="w-full appearance-none rounded-xl border border-gray-200 px-3 py-2 text-sm outline-none focus:border-blue-500"
                            >
                                <option :value="'all'">{{ $t('All Crafts') }}</option>
                                <option v-for="c in props.crafts" :key="c.id" :value="c.id">
                                    {{ c.name }} {{ c.abbreviation ? `(${c.abbreviation})` : '' }}
                                </option>
                            </select>
                        </div>
                        <!-- Aktiver Zustand sichtbar (ui-button + Extras) -->
                        <button
                            type="button"
                            class="ui-button"
                            :class="selectedCraftId !== 'all' ? 'ring-1 ring-blue-500 bg-blue-50 text-blue-700 font-semibold' : 'ring-0'"
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
                ? 'ring-1 ring-blue-500 bg-blue-50 text-blue-700 font-semibold'
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
                ? 'ring-1 ring-blue-500 bg-blue-50 text-blue-700 font-semibold'
                : 'ring-0'"
                        >
                            <IconArrowsSort class="size-4" />
                            <span>{{ $t('Sort by: Name') }} {{ sortBy==='name' ? (sortDir==='asc' ? '↑' : '↓') : '' }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Liste -->
            <div v-if="filteredAndSorted.length" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <!-- Desktop Header -->
                <div class="hidden md:grid grid-cols-[8px_22%_12%_12%_18%_auto_160px] items-center bg-gray-50 text-xs font-medium uppercase text-gray-500">
                    <div></div>
                    <div class="px-6 py-3">{{ $t('Name') }}</div>
                    <div class="px-6 py-3">{{ $t('Start') }}</div>
                    <div class="px-6 py-3">{{ $t('End') }}</div>
                    <div class="px-6 py-3">{{ $t('Craft') }}</div>
                    <div class="px-6 py-3">{{ $t('Qualifications') }}</div>
                    <div class="px-6 py-3 text-right"></div>
                </div>

                <!-- Desktop Rows -->
                <div class="hidden md:block divide-y divide-gray-100">
                    <div
                        v-for="(preset, i) in filteredAndSorted"
                        :key="preset.id"
                        class="grid grid-cols-[8px_22%_12%_12%_18%_auto_160px] items-center hover:bg-gray-50/60"
                    >
                        <!-- Akzentleiste -->
                        <div :class="accentByIndex(i).bar"></div>

                        <!-- Name + Desc -->
                        <div class="px-6 py-4">
                            <div class="font-medium text-gray-900">{{ preset.name }}</div>
                            <div v-if="preset.description" class="truncate text-xs text-gray-500">
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
                <span class="rounded-lg border border-gray-200 px-2 py-1 text-xs font-semibold text-gray-700">
                  {{ craftAbbrById(preset.craft_id) || '—' }}
                </span>
                                <span class="text-sm text-gray-900">{{ craftNameById(preset.craft_id) }}</span>
                            </div>
                        </div>

                        <!-- NEU: Eigene Spalte für Qualifikationen -->
                        <div class="px-6 py-4">
                            <div class="flex flex-wrap gap-1.5">
                                <template v-if="normalizeQualifications(preset).length">
                  <span
                      v-for="q in normalizeQualifications(preset)"
                      :key="q.id"
                      class="inline-flex items-center gap-1 rounded-full border border-gray-200 bg-white px-2 py-0.5 text-[11px] text-gray-700"
                  >
                    <span class="font-medium">{{ qualNameById(q.id) }}</span>
                    <span class="rounded bg-gray-100 px-1 font-semibold">{{ qualQuantity(q) }}</span>
                  </span>
                                </template>
                                <span v-else class="text-xs text-gray-400">—</span>
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
                <div class="md:hidden divide-y divide-gray-100">
                    <div
                        v-for="(preset, i) in filteredAndSorted"
                        :key="preset.id"
                        class="relative p-4"
                    >
                        <div class="absolute inset-y-0 left-0 w-1 rounded-r" :class="accentByIndex(i).bar"></div>

                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-base font-semibold text-gray-900">{{ preset.name }}</div>
                                <div v-if="preset.description" class="mt-0.5 text-xs text-gray-500">
                                    {{ preset.description }}
                                </div>
                            </div>
                            <div class="shrink-0 text-right">
                                <div class="text-xs text-gray-500">{{ $t('Time') }}</div>
                                <div class="mt-1 flex items-center gap-1">
                  <span class="rounded-full border px-2 py-0.5 text-xs" :class="accentByIndex(i).chip">
                    {{ toHHMM(preset.start_time) }}
                  </span>
                                    <span class="text-gray-400">→</span>
                                    <span class="rounded-full border px-2 py-0.5 text-xs" :class="accentByIndex(i).chip">
                    {{ toHHMM(preset.end_time) }}
                  </span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 flex items-center gap-2">
              <span class="rounded-lg border border-gray-200 px-2 py-1 text-xs font-semibold text-gray-700">
                {{ craftAbbrById(preset.craft_id) || '—' }}
              </span>
                            <span class="text-sm text-gray-900">{{ craftNameById(preset.craft_id) }}</span>
                        </div>

                        <!-- Qualifikationen VOR Buttons -->
                        <div class="mt-3 flex flex-wrap gap-1.5">
                            <template v-if="normalizeQualifications(preset).length">
                <span
                    v-for="q in normalizeQualifications(preset)"
                    :key="q.id"
                    class="inline-flex items-center gap-1 rounded-full border border-gray-200 bg-white px-2 py-0.5 text-[11px] text-gray-700"
                >
                  <span class="font-medium">{{ qualNameById(q.id) }}</span>
                  <span class="rounded bg-gray-100 px-1 font-semibold">{{ qualQuantity(q) }}</span>
                </span>
                            </template>
                            <span v-else class="text-xs text-gray-400">—</span>
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
            <div v-else class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center">
                <div class="mx-auto max-w-md space-y-3">
                    <h3 class="text-lg font-semibold text-gray-900">{{ $t('No presets available yet') }}</h3>
                </div>
                <div class="mt-4 flex justify-center">
                    <button class="ui-button" type="button" @click="openAddModal">
                        <IconPlus class="size-4" />
                        <span>{{ $t('Create Preset') }}</span>
                    </button>
                </div>
            </div>

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
