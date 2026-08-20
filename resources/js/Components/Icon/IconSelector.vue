<template>
    <Popover class="relative">
        <!-- Trigger -->
        <PopoverButton
            id="iconSelectorButton"
            class="size-10 inline-flex items-center justify-center rounded-full ring-1 ring-border-subtle bg-white hover:ring-accent-200 hover:shadow-sm transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-600"
        >
            <ToolTipComponent
                :icon="selectedIcon"
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
                <div class="overflow-hidden rounded-2xl border border-border-subtle bg-white shadow-xl">
                    <!-- Header (sticky) -->
                    <div class="sticky top-0 z-10 border-b border-border-subtle bg-white/80 backdrop-blur supports-[backdrop-filter]:bg-white/60">
                        <div class="flex items-center justify-between gap-4 px-5 py-3">
                            <div>
                                <h3 class="text-sm font-semibold text-text">{{ $t('Icons') }}</h3>
                                <p class="mt-0.5 text-xs text-text-subtle" v-if="!loading">
                                    {{ $t('Select an icon from {0} different icons.', [filteredNames.length]) }}
                                </p>
                            </div>

                            <!-- Suche -->
                            <div class="relative w-60 sm:w-72">
                                <input
                                    v-model="searchInput"
                                    type="text"
                                    :placeholder="$t('Search')"
                                    class="w-full rounded-lg bg-surface-sunken pl-10 pr-8 py-2 text-sm text-text ring-1 ring-border-subtle outline-none focus:bg-white focus:ring-2 focus:ring-accent-600 transition"
                                    @keydown.enter.prevent="selectFirstIfAny"
                                />
                                <IconSearch class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 size-4 text-text-subtle" />
                                <button
                                    v-if="searchInput.length > 0"
                                    type="button"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 grid place-items-center rounded p-1 hover:bg-surface-sunken"
                                    @click="searchInput = ''"
                                >
                                    <IconX class="size-4 text-text-subtle" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Body -->
                    <div ref="scrollRef" class="max-h-96 overflow-auto p-5">
                        <!-- Skeleton Grid -->
                        <div v-if="loading" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                            <div v-for="n in 12" :key="n" class="rounded-xl border border-border-subtle p-4">
                                <div class="h-8 w-8 rounded bg-border-subtle/80 animate-pulse mx-auto"></div>
                                <div class="mt-3 h-3 w-20 rounded bg-border-subtle animate-pulse mx-auto"></div>
                            </div>
                        </div>

                        <!-- Kein Treffer -->
                        <div v-else-if="filteredNames.length === 0" class="py-14 text-center">
                            <div class="mx-auto mb-3 flex size-10 items-center justify-center rounded-full bg-surface-sunken text-text-subtle">
                                <IconSearch class="size-5" />
                            </div>
                            <p class="text-sm font-medium text-text">{{ $t('No results') }}</p>
                            <p class="mt-1 text-xs text-text-subtle">
                                {{ $t('Try a different keyword.') }}
                            </p>
                        </div>

                        <!-- Grid -->
                        <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                            <div v-for="name in visibleNames" :key="name">
                                <button
                                    type="button"
                                    @click="selectIcon(name)"
                                    class="group relative w-full rounded-xl border border-border-subtle bg-white p-4 transition hover:border-border hover:shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-600"
                                    :class="selectedIconSlug === name ? 'ring-2 ring-accent-600 border-accent-200' : ''"
                                >
                                    <!-- Auswahl-Badge -->
                                    <span
                                        v-if="selectedIconSlug === name"
                                        class="absolute right-2 top-2 inline-flex items-center justify-center rounded-full bg-accent-600 text-white"
                                    >
                    <IconCheck class="size-4" />
                  </span>

                                    <div class="grid place-items-center">
                                        <component :is="iconComp(name)" class="h-8 w-8 text-text transition group-hover:scale-105" stroke-width="1.5" />
                                        <p class="mt-2 text-center text-xs text-text-muted">
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
import { ref, computed, onMounted, watch, unref } from 'vue'
import { Popover, PopoverButton, PopoverPanel } from '@headlessui/vue'
import { IconCheck, IconSearch, IconX } from '@tabler/icons-vue'
import ToolTipComponent from '@/Components/ToolTips/ToolTipComponent.vue'
import { iconComponent, loadIconNames, toDisplayName, toExportName, toSlug } from '@/Composeables/useTablerIcon'

/* Props */
const props = defineProps({
    currentIcon: {
        type: [String, Object, Function],
        required: false,
    },
})

const FALLBACK = 'photo-circle'

/*
 * Intern arbeitet der Picker mit Slugs ("home-2"), weil die SVGs unter diesem Namen in
 * public/icons/tabler/ liegen. Nach aussen — Prop rein, Emit raus — bleibt es beim
 * Tabler-Export-Namen ("IconHome2"), denn genau der steht in der DB und in den
 * PHP-Seedern. So braucht es keine Datenmigration.
 */

/* State */
const loading = ref(false)
const allNames = ref([])           // Slugs
const searchInput = ref('')
const debouncedQuery = ref('')
const selectedIconSlug = ref(null)

const panelRef = ref(null)
const scrollRef = ref(null)
const sentinelRef = ref(null)

/* Öffnen => erst dann den Slug-Index laden */
async function openPanel () {
    if (allNames.value.length) return
    loading.value = true
    allNames.value = await loadIconNames()
    loading.value = false
    resetPagination()
}

/* Suche (debounced) */
let debTimer = null
watch(searchInput, (v) => {
    clearTimeout(debTimer)
    debTimer = setTimeout(() => {
        // Slugs sind bindestrich-getrennt, angezeigt werden aber Namen mit Leerzeichen
        // ("Arrow Left") — Leerzeichen im Suchbegriff müssen deshalb auf Bindestriche
        // normalisiert werden, sonst findet "arrow left" nichts.
        debouncedQuery.value = v.toLowerCase().trim().replace(/\s+/g, '-')
        resetPagination()
    }, 200)
})
const filteredNames = computed(() => {
    if (!debouncedQuery.value) return allNames.value
    return allNames.value.filter(n => n.includes(debouncedQuery.value))
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
    selectedIconSlug.value = currentSlug()
})

/* Prop-Änderungen synchronisieren (z.B. externes Entfernen des Icons) */
watch(() => props.currentIcon, () => {
    selectedIconSlug.value = currentSlug()
})

/* Icon-Komponente pro Slug (Composable cached selbst) */
function iconComp (slug) {
    return iconComponent(slug)
}

/* Button-Icon (aktuelle Auswahl) */
function isComponentLike (v) {
    const t = typeof v
    return v && (t === 'function' || t === 'object')
}
function currentSlug () {
    const raw = unref(props.currentIcon)
    if (!raw || isComponentLike(raw)) return null
    return toSlug(raw)
}
const selectedIcon = computed(() => {
    // Eine direkt durchgereichte Komponente rendert PropertyIcon unveraendert,
    // ansonsten reicht der Slug als String.
    if (isComponentLike(props.currentIcon)) return props.currentIcon
    return selectedIconSlug.value ?? FALLBACK
})

/* Auswahl & Aktionen */
const emit = defineEmits(['update:modelValue'])
function selectIcon (slug) {
    selectedIconSlug.value = slug
    emit('update:modelValue', toExportName(slug))
    document.getElementById('iconSelectorButton')?.click()
}
function selectFirstIfAny () {
    if (filteredNames.value.length > 0) selectIcon(filteredNames.value[0])
}
</script>
