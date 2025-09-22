<template>
    <div class="relative" ref="rootEl">
        <!-- Input -->
        <BaseInput
            id="room_search_input"
            v-model="query"
            :label="label"
            :placeholder="placeholder"
            class="w-full"
            autocomplete="off"
            role="combobox"
            aria-expanded="open"
            aria-controls="room-search-listbox"
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
                id="room-search-listbox"
                role="listbox"
                class="absolute z-50 mt-1 w-full max-h-64 overflow-auto rounded-lg border border-zinc-200 bg-white/95 shadow-lg ring-1 ring-black/5 backdrop-blur supports-[backdrop-filter]:bg-white"
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
                <div
                    v-else-if="results.length === 0"
                    class="px-3 py-2 text-[13px] text-zinc-500"
                >
                    {{ $t ? $t('No rooms found') : 'No rooms found' }}
                </div>

                <!-- Results -->
                <ul v-else class="py-1">
                    <li
                        v-for="(room, idx) in results"
                        :key="room.id ?? idx"
                        :id="`room-option-${idx}`"
                        role="option"
                        :aria-selected="idx === activeIndex"
                        @mouseenter="activeIndex = idx"
                        @mousedown.prevent="selectRoom(room)"
                    class="cursor-pointer select-none px-3 py-2 text-[13px] text-zinc-800 flex items-center gap-2
                    hover:bg-indigo-50/60 data-[active=true]:bg-indigo-50/60"
                    :data-active="idx === activeIndex"
                    >
                    <span class="inline-block size-1.5 rounded-full bg-indigo-400"></span>
                    <span class="truncate font-medium">{{ room.name }}</span>
                    </li>
                </ul>
            </div>
        </transition>
    </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import axios from 'axios'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'

type Room = { id?: number|string; name: string }

// Props
const props = defineProps<{
    label?: string
    placeholder?: string
    /** ab wie vielen Zeichen gesucht wird */
    minChars?: number
    /** debounce in ms */
    delay?: number
}>()

const emit = defineEmits<{
    (e: 'room-selected', room: Room): void
}>()

// Defaults
const label = props.label ?? 'Search for Rooms'
const placeholder = props.placeholder ?? ''
const minChars = props.minChars ?? 2
const delay = props.delay ?? 220

// State
const query = ref<string>('')
const results = ref<Room[]>([])
const open = ref(false)
const loading = ref(false)
const error = ref<string | null>(null)
const activeIndex = ref<number>(-1)
const rootEl = ref<HTMLElement | null>(null)

// Cancel laufender Request
let controller: AbortController | null = null
let debounceTimer: number | null = null

// Search
async function fetchRooms(q: string) {
    if (controller) controller.abort()
    controller = new AbortController()
    loading.value = true
    error.value = null
    try {
        const { data } = await axios.post(route('room.search'), {
            search: q,
            wantsJson: true,
        }, { signal: controller.signal })
        results.value = Array.isArray(data) ? data : []
    } catch (e: any) {
        if (e?.name === 'CanceledError' || e?.name === 'AbortError') return
        error.value = e?.response?.data?.message ?? 'Error while searching.'
        results.value = []
    } finally {
        loading.value = false
    }
}

// Debounced watcher
watch(query, (q) => {
    if (!open.value) open.value = true
    activeIndex.value = -1

    // Mindestlänge
    if (!q || q.trim().length < minChars) {
        results.value = []
        loading.value = false
        error.value = null
        if (controller) controller.abort()
        return
    }

    // debounce
    if (debounceTimer) window.clearTimeout(debounceTimer)
    debounceTimer = window.setTimeout(() => {
        fetchRooms(q.trim())
    }, delay) as any
})

// Focus handling
function onFocus() {
    // Öffnen, aber erst suchen wenn minChars erfüllt
    open.value = true
}

// Keyboard navigation
function onKeydown(e: KeyboardEvent) {
    if (!open.value && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) {
        open.value = true
    }

    if (e.key === 'Escape') {
        open.value = false
        return
    }

    if (!results.value.length) return

    if (e.key === 'ArrowDown') {
        e.preventDefault()
        activeIndex.value = (activeIndex.value + 1) % results.value.length
        scrollActiveIntoView()
    } else if (e.key === 'ArrowUp') {
        e.preventDefault()
        activeIndex.value =
            activeIndex.value <= 0 ? results.value.length - 1 : activeIndex.value - 1
        scrollActiveIntoView()
    } else if (e.key === 'Enter') {
        if (activeIndex.value >= 0 && activeIndex.value < results.value.length) {
            e.preventDefault()
            selectRoom(results.value[activeIndex.value])
        }
    }
}

function scrollActiveIntoView() {
    nextTick(() => {
        const list = rootEl.value?.querySelector('#room-search-listbox')
        const opt = list?.querySelector<HTMLElement>(`#room-option-${activeIndex.value}`)
        opt?.scrollIntoView({ block: 'nearest' })
    })
}

// Select
function selectRoom(room: Room) {
    emit('room-selected', room)
    // UX: Input wieder leeren (wie vorher), Dropdown schließen
    query.value = ''
    results.value = []
    open.value = false
    activeIndex.value = -1
}

// Click outside → schließen
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
/* aktives Listenelement – zusätzlich zur hover-Farbe */
[data-active="true"] { background-color: rgb(238 242 255 / 0.6); } /* indigo-50/60 */
</style>
