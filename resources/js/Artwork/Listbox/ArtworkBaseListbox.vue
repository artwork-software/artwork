<template>
    <!-- headlessui Listbox; expose "open" für Chevron-Rotation -->
    <Listbox
        v-model="internalValue"
        :by="by"
        :disabled="disabled"
        :multiple="multiple"
        as="div"
        v-slot="{ open }"
    >
        <OpenSync :open="open" @change="handleOpenChange" />
        <ListboxLabel v-if="label" :class="labelClass">
            <slot name="label">{{ $t(label) }}</slot>
        </ListboxLabel>

        <div class="relative">
            <!-- NEU -->
            <Float
                portal
                strategy="fixed"
                auto-placement
                :offset="6"
                placement="bottom-start"
                floating-as="div"
                class="relative w-fit"
            >
            <ListboxButton :class="buttonClass">
                <slot name="button" :selected="internalValue" :placeholder="placeholder">
                    <div class="col-start-1 row-start-1 truncate pr-6 flex items-center gap-2">
                        <span
                            v-if="showColorIndicator && getColor(internalValue as Option)"
                            class="inline-block size-3 rounded-full flex-shrink-0"
                            :style="{ backgroundColor: getColor(internalValue as Option) }"
                        ></span>
                        <span class="truncate">{{ displayText }}</span>
                    </div>
                    <IconChevronUp
                        class="col-start-1 row-start-1 size-5 self-center justify-self-end text-gray-500 sm:size-4 transition-transform"
                        :class="open ? 'rotate-180' : ''"
                        aria-hidden="true"
                    />
                </slot>
            </ListboxButton>

            <transition
                enter-active-class="transition ease-out duration-100"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
                @after-leave="onDropdownClose"
            >
                <div v-if="!disabled" class="relative">
                    <ListboxOptions v-if="!loading" :class="finalOptionsClass">
                        <!-- Sticky Controls (Suchfeld + Sortierung) -->
                        <div
                            v-if="shouldShowControls"
                            class="sticky top-0 z-10 bg-white/95 backdrop-blur supports-backdrop-blur:bg-white border-b border-gray-100"
                        >
                            <slot
                                name="controls"
                                :query="localSearchQuery"
                                :setQuery="setQuery"
                                :sortBy="localSortBy"
                                :setSortBy="setSortBy"
                                :sortDirection="localSortDirection"
                                :toggleDirection="toggleDirection"
                            >
                                <div class="p-2 flex items-center gap-2">
                                    <!-- Sort-Feld-Auswahl (nur wenn sortBy als Array von Keys übergeben wurde) -->
                                    <div v-if="sortFieldChoices.length" class="min-w-0">
                                        <label class="sr-only">Sort by</label>
                                        <select
                                            class="block w-40 rounded-md border border-gray-200 bg-white px-2 py-1.5 text-sm text-gray-900 shadow-sm focus:outline-hidden focus:ring-1 focus:ring-blue-600"
                                            :value="localSortBy"
                                            @change="e => setSortBy((e.target as HTMLSelectElement).value)"
                                        >
                                            <option v-for="key in sortFieldChoices" :key="key" :value="key">
                                                {{ prettyKey(key) }}
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Sort-Richtung -->
                                    <button v-if="sortFieldChoices.length"
                                        type="button"
                                        class="rounded-md border border-gray-200 px-2.5 py-1.5 text-sm shadow-sm hover:bg-gray-50 focus:outline-hidden focus:ring-1 focus:ring-blue-600"
                                        @click="toggleDirection"
                                    >
                                        {{ localSortDirection === 'asc' ? $t('Ascending') : $t('Descending') }}
                                    </button>

                                    <!-- Suche -->
                                    <div class="flex-1 min-w-0">
                                        <label class="sr-only">{{ tFn(searchPlaceholder) }}</label>
                                        <input
                                            type="text"
                                            :placeholder="$t(searchPlaceholder)"
                                            class="w-full rounded-md border border-gray-200 bg-white px-2.5 py-1.5 text-sm text-gray-900 shadow-sm focus:outline-hidden focus:ring-1 focus:ring-blue-600"
                                            :value="localSearchQuery"
                                            @input="e => setQuery((e.target as HTMLInputElement).value)"
                                            @keydown.stop
                                        />
                                    </div>
                                </div>
                            </slot>
                        </div>

                        <template v-if="processedItems.length">
                            <ListboxOption
                                v-for="item in processedItems"
                                :key="getKey(item)"
                                :value="item"
                                as="template"
                                v-slot="{ active, selected }"
                            >
                                <li :class="[active ? activeClass : inactiveClass, optionBaseClass]">
                                    <slot name="option" :item="item" :active="active" :selected="selected">
                                        <div class="flex items-center gap-2">
                                            <span
                                                v-if="showColorIndicator && getColor(item)"
                                                class="inline-block size-3 rounded-full flex-shrink-0"
                                                :style="{ backgroundColor: getColor(item) }"
                                            ></span>
                                            <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">
                                              {{ useTranslations ? $t(getLabel(item)) : getLabel(item) }}
                                            </span>
                                        </div>

                                        <span
                                            v-if="selected"
                                            :class="[active ? 'text-white' : 'text-indigo-600', 'absolute inset-y-0 right-0 flex items-center pr-4']"
                                        >
                                          <IconCheck class="size-5" aria-hidden="true" />
                                        </span>
                                    </slot>
                                </li>
                            </ListboxOption>
                        </template>

                        <li v-else class="relative cursor-default select-none py-2 pl-3 pr-9 text-gray-500">
                            <slot name="empty">
                                {{ emptyText }}
                            </slot>
                        </li>
                    </ListboxOptions>

                    <div
                        v-else
                        class="absolute z-10 mt-1 w-full rounded-md bg-white py-2 text-sm ring-1 ring-black/5 shadow-lg text-gray-500"
                    >
                        <slot name="loading">Loading…</slot>
                    </div>
                </div>
            </transition>
            </Float>
        </div>
    </Listbox>
</template>

<script setup lang="ts">
import { computed, getCurrentInstance, ref, watch, defineComponent } from 'vue'
import {
    Listbox,
    ListboxButton,
    ListboxLabel,
    ListboxOption,
    ListboxOptions,
} from '@headlessui/vue'
import { IconChevronUp, IconCheck } from '@tabler/icons-vue'
import type { PropType } from 'vue'
import { useI18n } from 'vue-i18n'
import {Float} from "@headlessui-float/vue";
type Option = Record<string, any>
type ModelValue = Option | Option[] | null

const props = defineProps({
    /** v-model: single = Option|null, multiple = Option[] */
    modelValue: { type: [Object, Array, null] as PropType<ModelValue>, default: null },

    /** Auswahlliste */
    items: { type: Array as PropType<Option[]>, default: () => [] },

    /** Single- oder Multi-Select */
    multiple: { type: Boolean, default: false },

    /** Property, das headlessui zum Identifizieren/Vergleichen nutzt (z.B. "id") */
    by: { type: String as PropType<string>, default: 'id' },

    useTranslations: { type: Boolean, default: false },
    /** Welches Feld soll angezeigt werden? (string-Key oder Funktion) */
    optionLabel: {
        type: [String, Function] as PropType<string | ((item: Option) => string)>,
        default: 'name',
    },

    /** Key für v-for :key (string-Key oder Funktion) */
    optionKey: {
        type: [String, Function] as PropType<string | ((item: Option) => string | number)>,
        default: 'id',
    },

    /** Optional: eigenes Anzeige-Formatting bei Multi-Select */
    selectedFormatter: {
        type: Function as PropType<(items: Option[]) => string>,
        default: null,
    },

    /** Texte */
    label: { type: String, default: '' },
    placeholder: { type: String, default: 'Please select' },
    emptyText: { type: String, default: 'No options available' },

    /** Zustände */
    disabled: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },

    /** Color indicator options */
    showColorIndicator: { type: Boolean, default: false },
    colorProperty: { type: String, default: 'color' },

    /** Klassen – Standard an dein Design angelehnt, aber überschreibbar */
    labelClass: { type: String, default: 'xsDark' },
    buttonClass: {
        type: String,
        default:
            'menu-button bg-white focus:outline-hidden focus:ring-0 focus:border-0 w-full text-left rounded-md border border-gray-200 shadow-sm px-3 py-2 text-sm font-normal text-gray-900 focus:ring-1 focus:ring-blue-600 sm:text-sm',
    },
    optionsClass: {
        type: String,
        default:
            'mt-1 max-h-60 overflow-auto rounded-md bg-white py-1 text-base ring-1 shadow-lg ring-black/5 focus:outline-hidden sm:text-sm',
    },
    optionBaseClass: {
        type: String,
        default: 'relative cursor-default py-2 pr-9 pl-3 select-none',
    },
    activeClass: { type: String, default: 'bg-indigo-600 text-white outline-hidden' },
    inactiveClass: { type: String, default: 'text-gray-900' },

    /** --- NEU: Suche / Filter / Sortierung --- */
    enableSearch: { type: Boolean, default: true },
    searchThreshold: { type: Number, default: 5 },
    /** Felder, über die gesucht wird (Standard: optionLabel) – z.B. ['name','id'] */
    searchKeys: { type: Array as PropType<Array<string | ((i: Option) => string)>>, default: () => [] },
    /** Initialer Query; v-model via update:searchQuery */
    searchQuery: { type: String, default: '' },
    searchPlaceholder: { type: String, default: 'Search…' },

    /** Optionaler Filter & Sortierer aus Parent */
    filterFn: { type: Function as PropType<(item: Option) => boolean>, default: null },
    sortFn: { type: Function as PropType<(a: Option, b: Option) => number>, default: null },

    /**
     * sortBy:
     *  - String: key (z.B. 'name')
     *  - Array<string>: Auswahl an Keys -> UI zeigt Select
     *  - Function: (item) => comparable
     */
    sortBy: { type: [String, Array, Function] as PropType<string | string[] | ((i: Option) => any)>, default: null },
    sortDirection: { type: String as PropType<'asc' | 'desc'>, default: 'asc' },

    resetOnClose: { type: Boolean, default: true },
})

const emit = defineEmits<{
    (e: 'update:modelValue', value: ModelValue): void
    (e: 'change', value: ModelValue): void
    (e: 'update:searchQuery', value: string): void
    (e: 'update:sortBy', value: string | ((i: Option) => any)): void
    (e: 'update:sortDirection', value: 'asc' | 'desc'): void
}>()

/** v-model Wrapper: liefert bei multiple immer ein Array, sonst Option|null */
const internalValue = computed<ModelValue>({
    get: () => {
        if (props.multiple) {
            return Array.isArray(props.modelValue) ? props.modelValue : []
        }
        return (props.modelValue as Option | null) ?? null
    },
    set: (val) => {
        emit('update:modelValue', val)
        emit('change', val)
    },
})

const getLabel = (item: Option) => {
    if (!item) return ''
    return typeof props.optionLabel === 'function'
        ? props.optionLabel(item)
        : (item?.[props.optionLabel] ?? String(item))
}

const getKey = (item: Option) => {
    if (!item) return Math.random().toString(36)
    return typeof props.optionKey === 'function'
        ? props.optionKey(item)
        : (item?.[props.optionKey] ?? getLabel(item))
}

const getColor = (item: Option) => {
    if (!item || !props.showColorIndicator) return null
    return item?.[props.colorProperty] ?? null
}

const i18n = getCurrentInstance()
const capi = (() => { try { return useI18n?.() } catch { return null } })()
const tFn = (key: string) => {
    if (!props.useTranslations) return key
    if (capi?.t) return capi.t(key)
    // @ts-ignore - globaler $t Fallback
    return i18n?.proxy?.$t ? i18n.proxy.$t(key) : key
}

const displayText = computed(() => {
    if (!props.multiple) {
        const val = internalValue.value as Option | null
        const label = val ? getLabel(val) : props.placeholder
        return tFn(String(label))
    }

    const arr = Array.isArray(internalValue.value) ? internalValue.value : []
    if (arr.length === 0) return tFn(String(props.placeholder))

    if (props.selectedFormatter) return props.selectedFormatter(arr)

    const labels = arr.map(getLabel).filter(Boolean).map(l => tFn(String(l)))
    if (labels.length <= 2) return labels.join(', ')
    return `${labels[0]}, ${labels[1]} +${labels.length - 2}`
})

/** --------- Suche / Filter / Sortierung (intern steuerbar + v-modelfähig) --------- */
const localSearchQuery = ref(props.searchQuery)
watch(() => props.searchQuery, q => { if (q !== localSearchQuery.value) localSearchQuery.value = q })
const setQuery = (q: string) => {
    localSearchQuery.value = q
    emit('update:searchQuery', q)
}

const sortFieldChoices = computed<string[]>(() => Array.isArray(props.sortBy) ? props.sortBy : [])
const defaultSortKey = computed<string>(() => {
    if (typeof props.sortBy === 'string') return props.sortBy
    if (Array.isArray(props.sortBy) && props.sortBy.length) return props.sortBy[0]
    // Fallback auf optionLabel, wenn string
    return typeof props.optionLabel === 'string' ? props.optionLabel : 'id'
})

const localSortBy = ref<string | ((i: Option) => any)>(
    typeof props.sortBy === 'function' ? props.sortBy :
        typeof props.sortBy === 'string' ? props.sortBy :
            defaultSortKey.value
)
watch(() => props.sortBy, v => {
    if (typeof v === 'function') localSortBy.value = v
    else if (typeof v === 'string') localSortBy.value = v
    else if (Array.isArray(v) && v.length) localSortBy.value = v[0]
})
const setSortBy = (v: string | ((i: Option) => any)) => {
    localSortBy.value = v
    emit('update:sortBy', v)
}

const localSortDirection = ref<'asc'|'desc'>(props.sortDirection)
watch(() => props.sortDirection, d => { localSortDirection.value = d })
const toggleDirection = () => {
    localSortDirection.value = (localSortDirection.value === 'asc' ? 'desc' : 'asc')
    emit('update:sortDirection', localSortDirection.value)
}

const shouldShowControls = computed(() =>
    props.enableSearch && (props.items?.length ?? 0) > props.searchThreshold || !!localSearchQuery.value
)

/** Normalisierung für Textsuche */
const norm = (v: unknown) =>
    String(v ?? '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/\p{Diacritic}/gu, '')

/** Welche Keys werden durchsucht? */
const effectiveSearchKeys = computed<(string | ((i: Option) => string))[]>(() => {
    if (props.searchKeys.length) return props.searchKeys
    // Fallback: optionLabel (falls string) sonst 'name'/'id'
    if (typeof props.optionLabel === 'string') return [props.optionLabel]
    return ['name', 'id']
})

/** Suche matcht, wenn irgendein Feld matcht */
const matchesSearch = (item: Option, query: string) => {
    const q = norm(query)
    if (!q) return true
    for (const key of effectiveSearchKeys.value) {
        const value = typeof key === 'function' ? key(item) : item?.[key]
        if (norm(value).includes(q)) return true
    }
    return false
}

/** Sortieren: sortFn > sortBy(function/key) > optionLabel */
const compareItems = (a: Option, b: Option) => {
    if (props.sortFn) return props.sortFn(a, b)

    let av: any
    let bv: any

    if (typeof localSortBy.value === 'function') {
        av = localSortBy.value(a)
        bv = localSortBy.value(b)
    } else if (typeof localSortBy.value === 'string') {
        av = a?.[localSortBy.value]
        bv = b?.[localSortBy.value]
    } else if (typeof props.optionLabel === 'string') {
        av = a?.[props.optionLabel]
        bv = b?.[props.optionLabel]
    } else {
        av = getLabel(a)
        bv = getLabel(b)
    }

    // String- und Zahl-Vergleich robust
    const an = norm(av)
    const bn = norm(bv)
    if (an < bn) return -1
    if (an > bn) return 1
    return 0
}

const processedItems = computed<Option[]>(() => {
    let res = Array.isArray(props.items) ? props.items.slice() : []

    // 1) Filter
    if (props.filterFn) res = res.filter(props.filterFn)

    // 2) Suche
    if (props.enableSearch && localSearchQuery.value) {
        res = res.filter((it) => matchesSearch(it, localSearchQuery.value))
    }

    // 3) Sortierung
    res.sort(compareItems)
    if (localSortDirection.value === 'desc') res.reverse()

    return res
})

/** Kleine Helfer für UI */
const prettyKey = (key: string) => {
    if (!key) return ''
    // "snake_case"/"camelCase" -> "Snake case"/"Camel case"
    const spaced = key.replace(/([a-z])([A-Z])/g, '$1 $2').replace(/[_\-]+/g, ' ')
    return spaced.charAt(0).toUpperCase() + spaced.slice(1)
}
const finalOptionsClass = computed(() => `${props.optionsClass} z-[99999]`);

const onDropdownClose = () => {
    if (!props.resetOnClose) return

    // Suche zurücksetzen (und Parent via v-model syncen)
    if (localSearchQuery.value !== '') {
        setQuery('')
    }

    // Sortierfeld zurücksetzen:
    // - wenn sortBy Function: auf die Function zurück
    // - sonst auf defaultSortKey (z.B. erstes Feld aus Array oder optionLabel)
    const targetSortBy = typeof props.sortBy === 'function'
        ? props.sortBy
        : defaultSortKey.value

    if (localSortBy.value !== targetSortBy) {
        setSortBy(targetSortBy)
    }

    // Sortierrichtung auf die in den Props angegebene Ausgangsrichtung (Standard: 'asc')
    if (localSortDirection.value !== props.sortDirection) {
        localSortDirection.value = props.sortDirection
        emit('update:sortDirection', props.sortDirection)
    }
}

// Welches Feld ist der "Startzustand" für Sortierung?
const initialSortBy = computed<string | ((i: Option) => any)>(() =>
    typeof props.sortBy === 'function'
        ? props.sortBy
        : defaultSortKey.value
)

// Reset-Funktion (Suche + Sortierung)
const resetSearchAndSort = () => {
    if (!props.resetOnClose) return

    // Suche leeren (inkl. v-model sync nach außen)
    if (localSearchQuery.value !== '') {
        setQuery('')
    }

    // Sortierfeld zurücksetzen
    if (localSortBy.value !== initialSortBy.value) {
        setSortBy(initialSortBy.value)
    }

    // Sortierrichtung zurücksetzen
    if (localSortDirection.value !== props.sortDirection) {
        localSortDirection.value = props.sortDirection
        emit('update:sortDirection', props.sortDirection)
    }
}

// Open-Änderungen überwachen (true -> false = Close)
const isOpen = ref(false)
const handleOpenChange = (next: boolean) => {
    if (isOpen.value && !next) {
        // gerade geschlossen -> reset
        resetSearchAndSort()
    }
    isOpen.value = next
}

// Kleinst-Komponente, die den slot-Prop `open` reaktiv macht
const OpenSync = defineComponent({
    name: 'OpenSync',
    props: { open: { type: Boolean, required: true } },
    emits: ['change'],
    setup(props, { emit }) {
        watch(() => props.open, v => emit('change', v), { immediate: true })
        return () => null
    },
})
</script>
