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
        <ListboxLabel v-if="label" :class="labelClass">
            <slot name="label">{{ $t(label) }}</slot>
        </ListboxLabel>

        <div class="relative mt-2">
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
            >
                <div v-if="!disabled" class="relative">
                    <ListboxOptions v-if="!loading" :class="optionsClass">
                        <template v-if="items.length">
                            <ListboxOption
                                v-for="item in items"
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
        </div>
    </Listbox>
</template>

<script setup lang="ts">
import { computed, getCurrentInstance } from 'vue'
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
            'absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base ring-1 shadow-lg ring-black/5 focus:outline-hidden sm:text-sm',
    },
    optionBaseClass: {
        type: String,
        default: 'relative cursor-default py-2 pr-9 pl-3 select-none',
    },
    activeClass: { type: String, default: 'bg-indigo-600 text-white outline-hidden' },
    inactiveClass: { type: String, default: 'text-gray-900' },
})

const emit = defineEmits<{
    (e: 'update:modelValue', value: ModelValue): void
    (e: 'change', value: ModelValue): void
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
    // bevorzugt Composition API, fallback auf globale $t
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
</script>
