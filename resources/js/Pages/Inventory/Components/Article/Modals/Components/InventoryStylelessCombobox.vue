<template>
    <!-- Wenn returnObject=true, übernimmt headlessui den Vergleich über :by -->
    <Combobox
        v-model="internalValue"
        :by="returnObject ? by : undefined"
        :disabled="disabled"
        as="div"
        v-slot="{ open }"
    >
        <!--<ComboboxLabel v-if="label" :class="labelClass">
            <slot name="label">{{ label }}</slot>
        </ComboboxLabel>-->

        <div class="relative">
            <div class="relative">
                <ComboboxInput
                    :class="inputClass"
                    :placeholder="placeholder"
                    :display-value="() => selectedText"
                    @change="onChange"
                    @blur="onBlur"
                />
                <ComboboxButton :class="buttonClass">
                    <slot name="button-icon" :open="open">
                        <IconSelector class="size-5 text-gray-400" aria-hidden="true" />
                    </slot>
                </ComboboxButton>
            </div>

            <transition
                enter-active-class="transition ease-out duration-100"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="!disabled" class="relative">
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

                    <!-- Loading / Empty States -->
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
            </transition>
        </div>
    </Combobox>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
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

    /** Ob die Komponente das ganze Objekt zurückgibt (true) oder den Key (false) */
    returnObject: { type: Boolean, default: false },
    /** Vergleichsfeld für headlessui bei Objekt-Mode */
    by: { type: String, default: 'id' },

    /** Felder/Funktion für Anzeige + Key */
    optionLabel: {
        type: [String, Function] as PropType<string | ((item: Option) => string)>,
        default: 'name',
    },
    optionKey: {
        type: [String, Function] as PropType<string | ((item: Option) => string | number)>,
        default: 'id',
    },

    /** Suche konfigurieren */
    caseSensitive: { type: Boolean, default: false },
    searchFields: {
        type: Array as PropType<string[]>,
        default: () => [], // wenn leer, wird optionLabel verwendet (falls string)
    },
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

    /** Typ-Koerzierung, wenn returnObject=false */
    coerce: { type: String as PropType<'number' | 'string' | 'none'>, default: 'none' },

    /** States */
    disabled: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },

    /** Klassen (Defaults an deinen Stil angelehnt) */
    labelClass: { type: String, default: 'xsDark' },
    inputClass: {
        type: String,
        default:
            'block w-full ring-0 border-none focus:ring-0 rounded-md bg-white py-1.5 pr-12 pl-3 text-xs text-gray-900 placeholder:text-gray-400',
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
    // id-Mode: finde Item anhand Key
    return props.items.find((it) => compareLoose(getKey(it), internalValue.value)) ?? null
})

const selectedText = computed(() =>
    selectedItem.value ? getLabel(selectedItem.value) : ''
)

function normalize(str: string) {
    return props.caseSensitive
        ? str
        : str.normalize('NFD').replace(/\p{Diacritic}/gu, '').toLowerCase()
}

function compareLoose(a: any, b: any) {
    // toleranter Vergleich für "5" vs 5
    return String(a) === String(b)
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

/* Falls sich Items ändern und der aktuell ausgewählte Wert nicht mehr existiert,
   kannst du hier optional zurücksetzen. (auskommentiert, falls nicht gewünscht)
watch(
  () => props.items,
  () => {
    if (!selectedItem.value) emit('update:modelValue', props.returnObject ? null : null)
  }
)
*/
</script>
