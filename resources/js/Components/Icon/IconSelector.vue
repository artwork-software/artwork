<template>
    <Popover class="relative">
        <!-- Trigger -->
        <PopoverButton
            id="iconSelectorButton"
            class="size-10 inline-flex items-center justify-center rounded-full ring-1 ring-gray-200 bg-white hover:ring-indigo-300 hover:shadow-sm transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500"
        >
            <ToolTipComponent
                :icon="selectedIconComp"
                icon-size="size-7"
                :tooltip-text="$t('Select an icon')"
                direction="bottom"
                @click="openPanel"
            />
        </PopoverButton>

        <!-- Panel -->
        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="translate-y-1 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-1 opacity-0"
        >
            <PopoverPanel
                ref="panelRef"
                class="absolute z-10 mt-3 w-screen max-w-sm transform px-4 sm:px-0 lg:max-w-4xl"
            >
                <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-xl">
                    <!-- Header (sticky) -->
                    <div class="sticky top-0 z-10 border-b border-gray-100 bg-white/80 backdrop-blur supports-[backdrop-filter]:bg-white/60">
                        <div class="flex items-center justify-between gap-4 px-5 py-3">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">{{ $t('Icons') }}</h3>
                                <p class="mt-0.5 text-xs text-gray-500" v-if="!loading">
                                    {{ $t('Select an icon from {0} different icons.', [filteredNames.length]) }}
                                </p>
                            </div>

                            <!-- Suche -->
                            <div class="relative w-60 sm:w-72">
                                <input
                                    v-model="searchInput"
                                    type="text"
                                    :placeholder="$t('Search')"
                                    class="w-full rounded-lg bg-gray-50 pl-10 pr-8 py-2 text-sm text-gray-900 ring-1 ring-gray-200 outline-none focus:bg-white focus:ring-2 focus:ring-indigo-500 transition"
                                    @keydown.enter.prevent="selectFirstIfAny"
                                />
                                <component :is="SearchIconComp" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 size-4 text-gray-400" />
                                <button
                                    v-if="searchInput.length > 0"
                                    type="button"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 grid place-items-center rounded p-1 hover:bg-gray-100"
                                    @click="searchInput = ''"
                                >
                                    <component :is="XIconComp" class="size-4 text-gray-500" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Body -->
                    <div ref="scrollRef" class="max-h-96 overflow-auto p-5">
                        <!-- Skeleton Grid -->
                        <div v-if="loading" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                            <div v-for="n in 12" :key="n" class="rounded-xl border border-gray-100 p-4">
                                <div class="h-8 w-8 rounded bg-gray-200/80 animate-pulse mx-auto"></div>
                                <div class="mt-3 h-3 w-20 rounded bg-gray-200/80 animate-pulse mx-auto"></div>
                            </div>
                        </div>

                        <!-- Kein Treffer -->
                        <div v-else-if="filteredNames.length === 0" class="py-14 text-center">
                            <div class="mx-auto mb-3 flex size-10 items-center justify-center rounded-full bg-gray-50 text-gray-400">
                                <component :is="SearchIconComp" class="size-5" />
                            </div>
                            <p class="text-sm font-medium text-gray-800">{{ $t('No results') }}</p>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ $t('Try a different keyword.') }}
                            </p>
                        </div>

                        <!-- Grid -->
                        <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                            <div v-for="name in visibleNames" :key="name">
                                <button
                                    type="button"
                                    @click="selectIcon(name)"
                                    class="group relative w-full rounded-xl border border-gray-200 bg-white p-4 transition hover:border-gray-300 hover:shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500"
                                    :class="selectedIconName === name ? 'ring-2 ring-indigo-500/70 border-indigo-200' : ''"
                                >
                                    <!-- Auswahl-Badge -->
                                    <span
                                        v-if="selectedIconName === name"
                                        class="absolute right-2 top-2 inline-flex items-center justify-center rounded-full bg-indigo-600 text-white"
                                    >
                    <component :is="CheckIconComp" class="size-4" />
                  </span>

                                    <div class="grid place-items-center">
                                        <component :is="iconComp(name)" class="h-8 w-8 text-gray-900 transition group-hover:scale-105" stroke-width="1.5" />
                                        <p class="mt-2 text-center text-xs text-gray-700">
                                            {{ toDisplayName(name) }}
                                        </p>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <!-- Sentinel für Infinite Scroll -->
                        <div ref="sentinelRef" class="h-6"></div>
                    </div>
                </div>
            </PopoverPanel>
        </transition>
    </Popover>
</template>

<script setup>
import { ref, computed, onMounted, watch, defineAsyncComponent, unref } from 'vue'
import { Popover, PopoverButton, PopoverPanel } from '@headlessui/vue'
import ToolTipComponent from '@/Components/ToolTips/ToolTipComponent.vue'

/* Props */
const props = defineProps({
    currentIcon: {
        type: [String, Object, Function],
        required: false,
    },
})

/* Lazy Tabler Loader */
let tablerMod = null
let loadPromise = null
const ensureTabler = async () => {
    if (tablerMod) return tablerMod
    if (!loadPromise) loadPromise = import('@tabler/icons-vue')
    tablerMod = await loadPromise
    return tablerMod
}

/* State */
const loading = ref(false)
const allNames = ref([])           // nur Export-Namen ("IconHome", ...)
const searchInput = ref('')
const debouncedQuery = ref('')
const selectedIconName = ref(null) // string-basierte Auswahl

const panelRef = ref(null)
const scrollRef = ref(null)
const sentinelRef = ref(null)

/* Öffnen => erst dann Icons einlesen */
async function openPanel () {
    if (allNames.value.length) return
    loading.value = true
    const mod = await ensureTabler()
    allNames.value = Object.keys(mod).filter(k => /^Icon[A-Z0-9]/.test(k))
    loading.value = false
    resetPagination()
}

/* Suche (debounced) */
let debTimer = null
watch(searchInput, (v) => {
    clearTimeout(debTimer)
    debTimer = setTimeout(() => {
        debouncedQuery.value = v.toLowerCase().trim()
        resetPagination()
    }, 200)
})
const filteredNames = computed(() => {
    if (!debouncedQuery.value) return allNames.value
    return allNames.value.filter(n => toDisplayName(n).toLowerCase().includes(debouncedQuery.value))
})

/* Progressive Rendering */
const PAGE_SIZE = 120
const page = ref(0)
const visibleNames = ref([])
function resetPagination () {
    page.value = 0
    visibleNames.value = filteredNames.value.slice(0, PAGE_SIZE)
}
function loadMore () {
    const next = (page.value + 1) * PAGE_SIZE
    if (visibleNames.value.length >= filteredNames.value.length) return
    visibleNames.value = filteredNames.value.slice(0, next)
    page.value++
}
let observer = null
onMounted(() => {
    observer = new IntersectionObserver((entries) => {
        entries.forEach((e) => e.isIntersecting && loadMore())
    }, { root: scrollRef.value, rootMargin: '0px', threshold: 0.1 })

    const stop = watch(() => !!sentinelRef.value && !!scrollRef.value, (ok) => {
        if (ok) {
            observer.observe(sentinelRef.value)
            stop()
        }
    })

    // Initiale Vorauswahl (nur für visuelles Highlight)
    const initial = toExportName(props.currentIcon)
    selectedIconName.value = initial
})

/* Async Komponente pro Icon (mit Cache) */
const compCache = new Map()
function iconComp (name) {
    if (compCache.has(name)) return compCache.get(name)
    const Comp = defineAsyncComponent({
        loader: async () => {
            const mod = await ensureTabler()
            return mod?.[name] ?? mod?.IconPhotoCircle
        },
        delay: 0,
        timeout: 10000,
        onError (err, _retry, fail, attempts) {
            if (attempts > 1) fail(err)
        }
    })
    compCache.set(name, Comp)
    return Comp
}

/* Kleine Hilfs-Icons */
const SearchIconComp = iconComp('IconSearch')
const XIconComp = iconComp('IconX')
const CheckIconComp = iconComp('IconCheck')

/* Button-Icon (aktuelle Auswahl) */
function isComponentLike (v) {
    const t = typeof v
    return v && (t === 'function' || t === 'object')
}
function toExportName (input) {
    if (!input) return 'IconPhotoCircle'
    const raw = unref(input)
    if (isComponentLike(raw)) return 'IconPhotoCircle' // Komponente wird direkt unten genutzt
    const s = String(raw).trim()
    if (/^Icon[A-Z0-9]/.test(s)) return s
    const core = s.replace(/^icon[-_]*/i, '')
        .toLowerCase()
        .replace(/(^\w|[-_]\w)/g, m => m.replace(/[-_]/, '').toUpperCase())
    return `Icon${core}`
}
const selectedIconComp = computed(() => {
    if (isComponentLike(props.currentIcon)) return props.currentIcon
    const name = selectedIconName.value || toExportName(props.currentIcon)
    return iconComp(name)
})

/* Auswahl & Aktionen */
const emit = defineEmits(['update:modelValue'])
function selectIcon (name) {
    selectedIconName.value = name
    emit('update:modelValue', name)
    document.getElementById('iconSelectorButton')?.click()
}
function selectFirstIfAny () {
    if (filteredNames.value.length > 0) selectIcon(filteredNames.value[0])
}

/* Utils */
function toDisplayName (iconName) {
    return iconName
        .replace(/^Icon/, '')
        .replace(/([a-z])([A-Z])/g, '$1 $2')
        .replace(/\b(\d+)\b/g, '$1D')
        .trim()
}
</script>
