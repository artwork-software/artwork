<template>
    <ArtworkBaseModal
        title="Select Material Set"
        :description="$t('Select a material set to issue.')"
        @close="$emit('close')"
        modal-size="max-w-5xl"
    >
        <!-- Kopfbereich: Suche + Filter + Sortierung -->
        <div class="space-y-3">
            <p class="text-sm text-gray-600">
                {{ $t('Please select a material set to issue.') }}
            </p>

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <!-- Suche -->
                <div class="relative w-full sm:max-w-md">
                    <input
                        v-model="query"
                        :placeholder="$t('Search by name, description or item')"
                        class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm outline-none ring-0 focus:border-blue-500"
                        type="text"
                        @keydown.down.prevent="moveActive(1)"
                        @keydown.up.prevent="moveActive(-1)"
                        @keydown.enter.prevent="selectActive()"
                    />
                    <button
                        v-if="query"
                        class="absolute inset-y-0 right-0 mr-2 rounded p-1 text-gray-400 hover:text-gray-600"
                        @click="query = ''"
                        :aria-label="$t('Clear search')"
                    >
                        ×
                    </button>
                </div>

                <!-- Filter + Sortierung -->
                <div class="flex items-center gap-2">
                    <label class="flex items-center gap-2 text-xs text-gray-600">
                        <input
                            type="checkbox"
                            v-model="onlyWithItems"
                            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        />
                        {{ $t('Only sets with items') }}
                    </label>

                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-600">{{ $t('Sort by') }}:</span>
                        <select
                            v-model="sortBy"
                            class="rounded-md border border-gray-300 bg-white px-2 py-1 text-xs outline-none focus:border-blue-500"
                        >
                            <option value="relevance">{{ $t('Relevance') }}</option>
                            <option value="name">{{ $t('Name (A–Z)') }}</option>
                            <option value="items">{{ $t('Items (desc)') }}</option>
                            <option value="newest">{{ $t('Newest') }}</option>
                            <option value="updated">{{ $t('Last updated') }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ergebnisliste -->
        <div
            class="mt-3 rounded-md border border-gray-200"
            role="listbox"
            :aria-activedescendant="activeId"
        >
            <div class="sticky top-0 z-[1] flex items-center justify-between border-b bg-white px-3 py-2 text-xs text-gray-500">
                <span>{{ $t('Results') }}: {{ filteredSets.length }}</span>
                <span v-if="query" class="truncate">{{ $t('Search') }}: “{{ query }}”</span>
            </div>

            <div class="max-h-96 overflow-y-auto" ref="scrollEl">
                <ul class="divide-y divide-gray-100">
                    <li
                        v-for="(set, idx) in pagedSets"
                        :key="set.id"
                        :id="`material-set-${set.id}`"
                        class="group flex cursor-pointer items-start justify-between gap-x-4 px-3 py-2 hover:bg-gray-50"
                        role="option"
                        :aria-selected="activeIndex === idx"
                        :class="activeIndex === idx ? 'bg-gray-50' : ''"
                        @mousemove="activeIndex = idx"
                        @click="select(set)"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="truncate text-sm font-semibold text-gray-900">
                                    {{ set.name }}
                                </p>
                                <span
                                    class="shrink-0 rounded-full border border-gray-200 px-2 py-0.5 text-[10px] text-gray-600"
                                    :title="$t('Items in this set.')"
                                >
                  {{ (set.items?.length ?? 0) }} {{ $t('Items') }}
                </span>
                            </div>
                            <p class="mt-0.5 line-clamp-2 text-xs text-gray-500">
                                {{ set.description || '—' }}
                            </p>

                            <!-- Mini-Preview der Items -->
                            <div v-if="(set.items?.length ?? 0) > 0" class="mt-1 flex flex-wrap gap-1">
                <span
                    v-for="(it, i) in (set.items?.slice(0, 4) ?? [])"
                    :key="`${set.id}-${i}`"
                    class="rounded bg-gray-100 px-1.5 py-0.5 text-[10px] text-gray-600"
                >
                  {{ (it.quantity ?? 1) }}× {{ it.name || it.article?.name || $t('Item') }}
                </span>
                                <span
                                    v-if="(set.items?.length ?? 0) > 4"
                                    class="rounded bg-gray-100 px-1.5 py-0.5 text-[10px] text-gray-600"
                                >
                  +{{ (set.items?.length ?? 0) - 4 }}
                </span>
                            </div>
                        </div>

                        <!-- Tooltip rechts -->
                        <div class="hidden shrink-0 sm:flex sm:flex-col sm:items-end">
                            <ToolTipWithTextComponent
                                :text="(set.items?.length ?? 0) + ' ' + $t('Items in this set.')"
                                classes="text-[11px] whitespace-nowrap text-gray-600"
                                tooltip-width="whitespace-nowrap"
                                direction="left"
                                :tooltip-text="createToolTipTextByItems(set)"
                            />
                            <button
                                class="mt-2 rounded-md border border-gray-300 px-2 py-1 text-xs text-gray-700 hover:bg-gray-50"
                                @click.stop="select(set)"
                            >
                                {{ $t('Select') }}
                            </button>
                        </div>
                    </li>

                    <!-- Keine Ergebnisse -->
                    <li v-if="pagedSets.length === 0" class="px-3 py-6 text-center text-sm text-gray-500">
                        {{ $t('No material sets found for your filters.') }}
                    </li>

                    <!-- Mehr laden -->
                    <li v-if="canLoadMore && pagedSets.length > 0" class="px-3 py-3 text-center">
                        <button
                            class="rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50"
                            @click="loadMore"
                        >
                            {{ $t('Load more') }}
                        </button>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-4 flex items-center justify-end">
            <BaseUIButton is-cancel-button :label="$t('Close')" @click="$emit('close')"/>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { ref, computed, inject, onMounted, watch } from 'vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import ArtworkBaseModalButton from '@/Artwork/Buttons/ArtworkBaseModalButton.vue'
import ToolTipWithTextComponent from '@/Components/ToolTips/ToolTipWithTextComponent.vue'
import {useTranslation} from "@/Composeables/Translation.js";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

const emit = defineEmits(['close', 'add-material-set'])

// Datenquelle wie gehabt via provide/inject
const materialSets = inject('materialSets', ref([])) // Fallback: ref([])

// UI-State
const query = ref('')
const sortBy = ref('relevance') // relevance | name | items | newest | updated
const onlyWithItems = ref(false)

// Tastatur-Navigation
const activeIndex = ref(-1)
const activeId = computed(() => {
    const set = pagedSets.value[activeIndex.value]
    return set ? `material-set-${set.id}` : undefined
})
const scrollEl = ref(null)
const $t = useTranslation();
// Pagination (clientseitig)
const pageSize = 50
const page = ref(1)
const resetPage = () => (page.value = 1)
const canLoadMore = computed(() => filteredSets.value.length > page.value * pageSize)
const pagedSets = computed(() => filteredSets.value.slice(0, page.value * pageSize))
const loadMore = () => {
    if (canLoadMore.value) page.value += 1
}

// Normalisierung
const norm = (v) => (v || '').toString().toLowerCase().normalize('NFKD')

// Relevanz-Score für einfache Fuzzy-Suche (ohne externe Lib)
function relevanceScore(set, q) {
    if (!q) return 0
    const name = norm(set.name)
    const desc = norm(set.description)
    const items = (set.items ?? []).map(i => norm(i.name || i.article?.name)).join(' ')
    let score = 0
    if (name.includes(q)) score += 3
    if (desc.includes(q)) score += 1
    if (items.includes(q)) score += 2
    // leichte Bevorzugung kürzerer Namen bei Treffern
    if (name.includes(q)) score += Math.max(0, 2 - Math.floor(name.length / 20))
    return score
}

// Gefilterte & sortierte Liste
const filteredSets = computed(() => {
    const q = norm(query.value).trim()
    const base = Array.isArray(materialSets.value) ? materialSets.value : []

    let out = base.filter((s) => {
        if (onlyWithItems.value && (s.items?.length ?? 0) === 0) return false
        if (!q) return true
        const hay =
            norm(s.name) +
            ' ' +
            norm(s.description) +
            ' ' +
            (s.items ?? [])
                .map((i) => norm(i.name || i.article?.name))
                .join(' ')
        return hay.includes(q)
    })

    switch (sortBy.value) {
        case 'name':
            out = out.slice().sort((a, b) => norm(a.name).localeCompare(norm(b.name)))
            break
        case 'items':
            out = out.slice().sort((a, b) => (b.items?.length ?? 0) - (a.items?.length ?? 0))
            break
        case 'newest':
            out = out
                .slice()
                .sort(
                    (a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
                )
            break
        case 'updated':
            out = out
                .slice()
                .sort(
                    (a, b) => new Date(b.updated_at).getTime() - new Date(a.updated_at).getTime()
                )
            break
        default:
            // relevance
            out = out
                .map((s) => ({ s, score: relevanceScore(s, q) }))
                .sort((A, B) => B.score - A.score || norm(A.s.name).localeCompare(norm(B.s.name)))
                .map((x) => x.s)
    }

    return out
})

// Auswahl
function select(set) {
    emit('add-material-set', set)
}
function moveActive(delta) {
    if (pagedSets.value.length === 0) return
    const next = Math.max(0, Math.min(pagedSets.value.length - 1, activeIndex.value + delta))
    activeIndex.value = next
    // Element sichtbar halten
    const el = scrollEl.value?.querySelector(`#material-set-${pagedSets.value[next].id}`)
    if (el && scrollEl.value) {
        const box = el.getBoundingClientRect()
        const parent = scrollEl.value.getBoundingClientRect()
        if (box.top < parent.top) {
            el.scrollIntoView({ block: 'nearest' })
        } else if (box.bottom > parent.bottom) {
            el.scrollIntoView({ block: 'nearest' })
        }
    }
}
function selectActive() {
    const set = pagedSets.value[activeIndex.value]
    if (set) select(set)
}

// Reset Paging/Index bei Filteränderungen
watch([query, sortBy, onlyWithItems], () => {
    resetPage()
    activeIndex.value = -1
})

// Tooltip-Text robust erzeugen
function createToolTipTextByItems(set) {
    const items = set?.items ?? []
    if (items.length === 0) return `${0} ${$t('items')}`
    const list = items
        .map((it) => `${it.quantity ?? 1}x ${it.name || it.article?.name || $t('Item')}`)
        .join(', ')
    return `${list} (${items.length} ${$t('items')})`
}

onMounted(() => {
    // initial aktives Element setzen
    if (pagedSets.value.length > 0) activeIndex.value = 0
})
</script>
