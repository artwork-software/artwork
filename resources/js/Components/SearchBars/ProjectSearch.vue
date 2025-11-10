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
                class="absolute z-50 mt-1 w-full max-h-72 overflow-auto rounded-lg border border-zinc-200 bg-white/95 shadow-lg ring-1 ring-black/5 backdrop-blur supports-[backdrop-filter]:bg-white"
            >
                <!-- Loading -->
                <div v-if="loading" class="px-3 py-2 text-[13px] text-zinc-500">
                    {{ $t ? $t('Searching…') : 'Searching…' }}
                </div>

                <!-- Error -->
                <div v-else-if="error" class="px-3 py-2 text-[13px] text-rose-600">
                    {{ error }}
                </div>

                <!-- Empty -->
                <div v-else-if="filtered.length === 0" class="px-3 py-2 text-[13px] text-zinc-500">
                    <BaseAlertComponent
                        v-if="query.trim() !== ''"
                        :message="$t ? $t('No Projects or Groups found') : 'No Projects or Groups found'"
                        type="info"
                        use-translation
                        class="!mb-0"
                    />
                </div>

                <!-- Results -->
                <ul v-else class="py-1">
                    <li
                        v-for="(proj, idx) in filtered"
                        :key="proj.id ?? idx"
                        :id="`project-option-${idx}`"
                        role="option"
                        :aria-selected="idx === activeIndex"
                        @mouseenter="activeIndex = idx"
                        @mousedown.prevent="selectProject(proj)"
                    class="cursor-pointer select-none px-3 py-2 text-[13px] text-zinc-800 flex items-center gap-2
                    hover:bg-indigo-50/60 data-[active=true]:bg-indigo-50/60"
                    :data-active="idx === activeIndex"
                    >
                    <span class="inline-block size-1.5 rounded-full" :class="proj.is_group ? 'bg-emerald-400' : 'bg-indigo-400'"></span>
                    <span class="truncate font-medium">{{ proj.name }}</span>

                    <!-- Mini-Badges rechts -->
                    <div class="ml-auto flex items-center gap-1.5 shrink-0">
              <span
                  v-if="proj.is_group"
                  class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700 ring-1 ring-emerald-200"
              >
                {{ $t ? $t('Group') : 'Group' }}
              </span>
                        <span
                            v-if="proj.marked_as_done"
                            class="inline-flex items-center rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-medium text-zinc-600 ring-1 ring-zinc-200"
                        >
                {{ $t ? $t('Done') : 'Done' }}
              </span>
                    </div>
                    </li>
                </ul>
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
}

function onKeydown(e: KeyboardEvent) {
    if (!open.value && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) {
        open.value = true
    }

    if (e.key === 'Escape') {
        open.value = false
        return
    }

    if (!filtered.value.length) return

    if (e.key === 'ArrowDown') {
        e.preventDefault()
        activeIndex.value = (activeIndex.value + 1) % filtered.value.length
        scrollActiveIntoView()
    } else if (e.key === 'ArrowUp') {
        e.preventDefault()
        activeIndex.value =
            activeIndex.value <= 0 ? filtered.value.length - 1 : activeIndex.value - 1
        scrollActiveIntoView()
    } else if (e.key === 'Enter') {
        if (activeIndex.value >= 0 && activeIndex.value < filtered.value.length) {
            e.preventDefault()
            selectProject(filtered.value[activeIndex.value])
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
function selectProject(project: Project) {
    emit('project-selected', project)
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
