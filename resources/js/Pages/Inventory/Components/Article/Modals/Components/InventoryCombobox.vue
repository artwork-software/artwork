<template>
    <Combobox
        v-model="internalValue"
        :by="returnObject ? by : undefined"
        :disabled="disabled"
        as="div"
        class="w-full"
    >
        <ComboboxLabel v-if="label" :class="labelClass">
            <slot name="label">{{ label }}</slot>
        </ComboboxLabel>

        <div :class="wrapperClass">
            <ComboboxInput
                :class="inputClass"
                :placeholder="placeholder"
                :display-value="() => selectedText"
                @change="onChange"
                @blur="onBlur"
            />

            <ComboboxButton :class="buttonClass">
                <slot name="button-icon">
                    <IconSelector class="size-5 text-gray-400" aria-hidden="true" />
                </slot>
            </ComboboxButton>

            <ComboboxOptions
                v-if="!loading && filteredItems.length > 0"
                :class="optionsClass"
            >
                <ComboboxOption
                    v-for="item in filteredItems"
                    :key="getKey(item)"
                    :value="returnObject ? item : getKey(item)"
                    as="template"
                    v-slot="{ active, selected }"
                >
                    <li :class="[optionBaseClass, active ? activeClass : inactiveClass]">
                        <slot name="option" :item="item" :active="active" :selected="selected">
              <span :class="['block truncate', selected && 'font-semibold']">
                {{ getLabel(item) }}
              </span>
                            <span
                                v-if="selected"
                                :class="['absolute inset-y-0 right-0 flex items-center pr-4', active ? 'text-white' : 'text-indigo-600']"
                            >
                <IconCheck class="size-5" aria-hidden="true" />
              </span>
                        </slot>
                    </li>
                </ComboboxOption>
            </ComboboxOptions>

            <!-- Loading / Empty -->
            <div
                v-else-if="loading"
                class="absolute z-10 mt-1 w-full rounded-md bg-white py-2 text-xs ring-1 shadow-lg ring-black/5 text-gray-500"
            >
                <slot name="loading">Loading…</slot>
            </div>
            <div
                v-else
                class="absolute z-10 mt-1 w-full rounded-md bg-white py-2 text-xs ring-1 shadow-lg ring-black/5 text-gray-500"
            >
                <slot name="empty">{{ emptyText }}</slot>
            </div>
        </div>
    </Combobox>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import {
    Combobox,
    ComboboxButton,
    ComboboxInput,
    ComboboxLabel,
    ComboboxOption,
    ComboboxOptions,
} from '@headlessui/vue'
import { IconSelector, IconCheck } from '@tabler/icons-vue'
import type { PropType } from 'vue'

type Option = Record<string, any>

const props = defineProps({
    /** v-model: id | object | null */
    modelValue: { type: [String, Number, Object, null] as PropType<any>, default: null },
    items: { type: Array as PropType<Option[]>, default: () => [] },

    /** Gibt die Komponente ein ganzes Objekt zurück (true) oder nur den Key (false)? */
    returnObject: { type: Boolean, default: false },
    by: { type: String, default: 'id' },

    /** Anzeige/Keys */
    optionLabel: {
        type: [String, Function] as PropType<string | ((item: Option) => string)>,
        default: 'name',
    },
    optionKey: {
        type: [String, Function] as PropType<string | ((item: Option) => string | number)>,
        default: 'id',
    },

    /** Suche */
    caseSensitive: { type: Boolean, default: false },
    searchFields: { type: Array as PropType<string[]>, default: () => [] },
    customFilter: {
        type: Function as PropType<(query: string, items: Option[]) => Option[]>,
        default: null,
    },

    /** UX / Texte */
    label: { type: String, default: '' },
    placeholder: { type: String, default: 'Please select' },
    emptyText: { type: String, default: 'No results' },
    clearQueryOnSelect: { type: Boolean, default: true },
    clearQueryOnBlur: { type: Boolean, default: true },

    /** Typkonvertierung bei ID-Modus */
    coerce: { type: String as PropType<'number' | 'string' | 'none'>, default: 'none' },

    /** States */
    disabled: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },

    /** Klassen – auf deine Styles abgestimmt, überschreibbar */
    labelClass: { type: String, default: 'xsDark' },
    wrapperClass: {
        type: String,
        default:
            'relative px-3 py-3 text-sm block w-full font-lexend bg-white shadow-sm border border-gray-200 rounded-md focus:outline-none focus:ring-1 focus:ring-artwork-buttons-create focus:border-artwork-buttons-create',
    },
    inputClass: {
        type: String,
        default:
            'w-full pl-1 !ring-0 focus:outline-hidden rounded-md bg-white text-xs text-gray-900 placeholder:text-gray-400 border-none focus-visible:ring-0',
    },
    buttonClass: {
        type: String,
        default:
            'absolute inset-y-0 right-0 flex items-center rounded-r-md px-2 focus:outline-hidden',
    },
    optionsClass: {
        type: String,
        default:
            'absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-xs ring-1 shadow-lg ring-black/5 focus:outline-hidden sm:text-sm',
    },
    optionBaseClass: {
        type: String,
        default: 'relative cursor-default py-2 pr-9 pl-3 select-none',
    },
    activeClass: { type: String, default: 'bg-indigo-600 text-white outline-hidden' },
    inactiveClass: { type: String, default: 'text-gray-900' },
})

const emit = defineEmits<{
    (e: 'update:modelValue', value: any): void
    (e: 'change', value: any): void
}>()

const query = ref('')

const internalValue = computed({
    get: () => props.modelValue,
    set: (val: any) => {
        const out =
            !props.returnObject && props.coerce !== 'none'
                ? coerceValue(val, props.coerce)
                : val
        emit('update:modelValue', out)
        emit('change', out)
        if (props.clearQueryOnSelect) query.value = ''
    },
})

function coerceValue(v: any, mode: 'number' | 'string' | 'none') {
    if (v == null || mode === 'none') return v
    return mode === 'number' ? Number(v) : String(v)
}

const labelField = computed(() =>
    typeof props.optionLabel === 'string' ? props.optionLabel : null
)

const getLabel = (item: Option) => {
    if (!item) return ''
    return typeof props.optionLabel === 'function'
        ? props.optionLabel(item)
        : (item?.[props.optionLabel as string] ?? String(item))
}

const getKey = (item: Option) => {
    if (!item) return ''
    return typeof props.optionKey === 'function'
        ? props.optionKey(item)
        : (item?.[props.optionKey as string] ?? getLabel(item))
}

const selectedItem = computed<Option | null>(() => {
    if (props.returnObject) return internalValue.value ?? null
    return props.items.find((it) => String(getKey(it)) === String(internalValue.value)) ?? null
})

const selectedText = computed(() =>
    selectedItem.value ? getLabel(selectedItem.value) : ''
)

function normalize(str: string) {
    return props.caseSensitive
        ? str
        : str.normalize('NFD').replace(/\p{Diacritic}/gu, '').toLowerCase()
}

const searchKeys = computed<string[]>(() => {
    if (props.searchFields.length > 0) return props.searchFields
    return labelField.value ? [labelField.value] : []
})

const filteredItems = computed<Option[]>(() => {
    if (!query.value) return props.items
    if (props.customFilter) return props.customFilter(query.value, props.items)

    const q = normalize(query.value)
    return props.items.filter((item) => {
        if (searchKeys.value.length === 0) {
            return normalize(getLabel(item)).includes(q)
        }
        return searchKeys.value.some((k) =>
            normalize(String(item?.[k] ?? '')).includes(q)
        )
    })
})

function onChange(e: Event) {
    const target = e.target as HTMLInputElement
    query.value = target.value ?? ''
}
function onBlur() {
    if (props.clearQueryOnBlur) query.value = ''
}
</script>
