<template>
    <div class="relative" ref="rootEl">
        <!-- Input -->
        <BaseInput
            id="project_search"
            v-model="query"
            :label="label"
            :placeholder="placeholder"
            class="w-full"
            autocomplete="off"
            role="combobox"
            aria-expanded="open"
            aria-controls="project-search-listbox"
            aria-autocomplete="list"
            @focus="onFocus"
            @keydown="onKeydown"
        />

        <!-- Dropdown -->
        <transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="opacity-0 translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-1"
        >
            <div
                v-if="open"
                id="project-search-listbox"
                role="listbox"
                class="absolute z-50 mt-1 w-full max-h-72 overflow-auto rounded-lg border border-border-subtle bg-white/95 shadow-lg ring-1 ring-black/5 backdrop-blur supports-[backdrop-filter]:bg-white"
            >
                <!-- Loading -->
                <div v-if="loading" class="px-3 py-2 text-[13px] text-text-subtle">
                    {{ $t ? $t('Searching…') : 'Searching…' }}
                </div>

                <!-- Error -->
                <div v-else-if="error" class="px-3 py-2 text-[13px] text-danger">
                    {{ error }}
                </div>

                <!-- Empty -->
                <div v-else-if="displayList.length === 0" class="px-3 py-2 text-[13px] text-text-subtle">
                    <BaseAlertComponent
                        v-if="query.trim() !== ''"
                        :message="$t ? $t('No Projects or Groups found') : 'No Projects or Groups found'"
                        type="info"
                        use-translation
                        class="!mb-0"
                    />
                </div>

                <!-- Results -->
                <div v-else>
                <div v-if="isShowingRecent" class="px-3 pt-2 pb-1 text-[11px] font-semibold uppercase tracking-wide text-text-subtle">
                    {{ $t ? $t('Recently used projects') : 'Recently used projects' }}
                </div>
                <ul class="py-1">
                    <li
                        v-for="(proj, idx) in displayList"
                        :key="proj.id ?? idx"
                        :id="`project-option-${idx}`"
                        role="option"
                        :aria-selected="idx === activeIndex"
                        @mouseenter="activeIndex = idx"
                        @mousedown.prevent="selectProject(proj)"
                    class="cursor-pointer select-none px-3 py-2 text-[13px] text-text flex items-center gap-2 hover:bg-accent-50/60 data-[active=true]:bg-accent-50/60"
                    :data-active="idx === activeIndex"
                    >
                    <span class="inline-block size-1.5 rounded-full" :class="proj.is_group ? 'bg-success' : 'bg-accent-500'"></span>
                    <span class="truncate font-medium">{{ proj.name }}</span>

                    <!-- Mini-Badges rechts -->
                    <div class="ml-auto flex items-center gap-1.5 shrink-0">
              <span
                  v-if="proj.is_group"
                  class="inline-flex items-center rounded-full bg-success-surface px-2 py-0.5 text-[11px] font-medium text-success ring-1 ring-success-border"
              >
                {{ $t ? $t('Group') : 'Group' }}
              </span>
                        <span
                            v-if="proj.marked_as_done"
                            class="inline-flex items-center rounded-full bg-surface-sunken px-2 py-0.5 text-[11px] font-medium text-text-muted ring-1 ring-border-subtle"
                        >
                {{ $t ? $t('Done') : 'Done' }}
              </span>
                    </div>
                    </li>
                </ul>
                </div>
            </div>
        </transition>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import axios from 'axios'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseAlertComponent from '@/Components/Alerts/BaseAlertComponent.vue'

type Project = {
    id?: number | string
    name: string
    is_group?: boolean
    marked_as_done?: boolean
    // optional: first_event / last_event falls getFirstLastEvent genutzt wird
}

const props = defineProps<{
    label?: string
    placeholder?: string
    noProjectGroups?: boolean
    onlyProjectGroups?: boolean
    getFirstLastEvent?: boolean
    /** ab wie vielen Zeichen gesucht wird */
    minChars?: number
    /** debounce in ms */
    delay?: number
    /** zeigt bei leerem Suchfeld die zuletzt geöffneten Projekte (localStorage) als Schnellauswahl */
    showRecentProjects?: boolean
}>()

const emit = defineEmits<{
    (e: 'project-selected', project: Project): void
}>()

// Defaults
const label = props.label ?? 'Search for projects'
const placeholder = props.placeholder ?? ''
const minChars = props.minChars ?? 2
const delay = props.delay ?? 220

// State
const query = ref<string>('')
const projects = ref<Project[]>([])
const open = ref(false)
const loading = ref(false)
const error = ref<string | null>(null)
const activeIndex = ref<number>(-1)
const rootEl = ref<HTMLElement | null>(null)

// Cancel + Debounce
let controller: AbortController | null = null
let debounceTimer: number | null = null

// Filterlogik
function mustList(p: Project): boolean {
    if (p.marked_as_done) return false
    if (props.noProjectGroups) return !p.is_group
    if (props.onlyProjectGroups) return !!p.is_group
    return true
}
const filtered = computed(() => projects.value.filter(mustList))

// Zuletzt geöffnete Projekte (gleiche Quelle wie LastedProjects.vue: localStorage "lastedProjects")
const RECENT_STORAGE_KEY = 'lastedProjects'
const RECENT_LIMIT = 5
const recentProjects = ref<Project[]>([])

const isShowingRecent = computed(
    () => !!props.showRecentProjects && query.value.trim().length < minChars && recentProjects.value.length > 0
)

const displayList = computed<Project[]>(() =>
    query.value.trim().length >= minChars ? filtered.value : (isShowingRecent.value ? recentProjects.value : [])
)

async function loadRecentProjects() {
    if (!props.showRecentProjects) return
    let items: Project[] = []
    try {
        const raw = localStorage.getItem(RECENT_STORAGE_KEY)
        const parsed = raw ? JSON.parse(raw) : []
        items = (Array.isArray(parsed) ? parsed : []).filter(mustList)
    } catch {
        items = []
    }
    recentProjects.value = items.slice(0, RECENT_LIMIT)

    // Inzwischen gelöschte Projekte entfernen (Liste liegt rein clientseitig im localStorage)
    const ids = recentProjects.value
        .map((p) => Number(p.id))
        .filter((id) => Number.isInteger(id) && id > 0)
    if (ids.length === 0) return
    try {
        const { data } = await axios.post(route('project.filterExistingIds'), { ids })
        const existing = new Set((Array.isArray(data) ? data : []).map((id: any) => Number(id)))
        recentProjects.value = recentProjects.value.filter((p) => existing.has(Number(p.id)))
    } catch {
        /* best effort — bei Fehler ungefiltert anzeigen */
    }
}

// API
async function fetchProjects(q: string) {
    if (controller) controller.abort()
    controller = new AbortController()
    loading.value = true
    error.value = null
    try {
        const { data } = await axios.post(
            route('project.scoutSearch'),
            {
                project_search: q,
                get_first_last_event: !!props.getFirstLastEvent,
                wantsJson: true,
            },
            { signal: controller.signal }
        )
        projects.value = Array.isArray(data) ? data : []
    } catch (e: any) {
        if (e?.name === 'CanceledError' || e?.name === 'AbortError') return
        error.value = e?.response?.data?.message ?? 'Error while searching.'
        projects.value = []
    } finally {
        loading.value = false
    }
}

// Watch query (debounced)
watch(query, (q) => {
    if (!open.value) open.value = true
    activeIndex.value = -1

    if (!q || q.trim().length < minChars) {
        projects.value = []
        loading.value = false
        error.value = null
        if (controller) controller.abort()
        return
    }

    if (debounceTimer) window.clearTimeout(debounceTimer)
    debounceTimer = window.setTimeout(() => {
        fetchProjects(q.trim())
    }, delay) as any
})

// Focus / Keyboard
function onFocus() {
    open.value = true
    if (props.showRecentProjects && query.value.trim().length < minChars) {
        loadRecentProjects()
    }
}

function onKeydown(e: KeyboardEvent) {
    if (!open.value && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) {
        open.value = true
    }

    if (e.key === 'Escape') {
        open.value = false
        return
    }

    if (!displayList.value.length) return

    if (e.key === 'ArrowDown') {
        e.preventDefault()
        activeIndex.value = (activeIndex.value + 1) % displayList.value.length
        scrollActiveIntoView()
    } else if (e.key === 'ArrowUp') {
        e.preventDefault()
        activeIndex.value =
            activeIndex.value <= 0 ? displayList.value.length - 1 : activeIndex.value - 1
        scrollActiveIntoView()
    } else if (e.key === 'Enter') {
        if (activeIndex.value >= 0 && activeIndex.value < displayList.value.length) {
            e.preventDefault()
            selectProject(displayList.value[activeIndex.value])
        }
    }
}

function scrollActiveIntoView() {
    nextTick(() => {
        const list = rootEl.value?.querySelector('#project-search-listbox')
        const opt = list?.querySelector<HTMLElement>(`#project-option-${activeIndex.value}`)
        opt?.scrollIntoView({ block: 'nearest' })
    })
}

// Select
async function selectProject(project: Project) {
    let toEmit = project
    // Einträge aus der Schnellauswahl haben keine first/last-event-Daten aus der Suche —
    // bei Bedarf über die reguläre Projektsuche nachladen, damit z. B. die
    // Zeitraum-Vorbelegung identisch zur normalen Auswahl funktioniert.
    const cameFromRecent = isShowingRecent.value
    if (cameFromRecent && props.getFirstLastEvent && project.name) {
        try {
            const { data } = await axios.post(route('project.scoutSearch'), {
                project_search: project.name,
                get_first_last_event: true,
                wantsJson: true,
            })
            const match = Array.isArray(data)
                ? data.find((p: any) => Number(p.id) === Number(project.id))
                : null
            if (match) toEmit = match
        } catch {
            /* Fallback: Rohdaten aus der Schnellauswahl emittieren */
        }
    }
    emit('project-selected', toEmit)
    // UX: Input resetten, Dropdown schließen
    query.value = ''
    projects.value = []
    open.value = false
    activeIndex.value = -1
}

// Click outside
function onDocClick(ev: MouseEvent) {
    const el = rootEl.value
    if (!el) return
    if (el.contains(ev.target as Node)) return
    open.value = false
}

onMounted(() => {
    document.addEventListener('mousedown', onDocClick)
})

onBeforeUnmount(() => {
    document.removeEventListener('mousedown', onDocClick)
    if (controller) controller.abort()
    if (debounceTimer) window.clearTimeout(debounceTimer)
})
</script>

<style scoped>
/* aktives Item zusätzlich hervorheben */
[data-active="true"] { background-color: rgb(238 242 255 / 0.6); } /* indigo-50/60 */
</style>
