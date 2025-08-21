<template>
    <!-- headlessui Listbox; expose "open" für Chevron-Rotation -->
    <Listbox v-model="internalValue" :by="by" :disabled="disabled" as="div" v-slot="{ open }">
        <ListboxLabel v-if="label" :class="labelClass">
            <slot name="label">{{ label }}</slot>
        </ListboxLabel>

        <div class="relative mt-2">
            <ListboxButton :class="buttonClass">
                <slot name="button" :selected="internalValue" :placeholder="placeholder">
                    <div class="col-start-1 row-start-1 truncate pr-6">
                        {{ displayText }}
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
                    <ListboxOptions
                        v-if="!loading"
                        :class="optionsClass"
                    >
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
                                        <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">
                                          {{ getLabel(item) }}
                                        </span>

                                        <span
                                            v-if="selected"
                                            :class="[active ? 'text-white' : 'text-indigo-600', 'absolute inset-y-0 right-0 flex items-center pr-4']"
                                        >
                      <IconCheck class="size-5" aria-hidden="true"/>
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
import {computed} from 'vue'
import {
    Listbox,
    ListboxButton,
    ListboxLabel,
    ListboxOption,
    ListboxOptions,
} from '@headlessui/vue'
import {IconChevronUp, IconCheck} from '@tabler/icons-vue'
import type {PropType} from 'vue'

type Option = Record<string, any>

const props = defineProps({
    /** v-model */
    modelValue: {type: Object as PropType<Option | null>, default: null},
    /** Auswahlliste */
    items: {type: Array as PropType<Option[]>, default: () => []},

    /** Property, das headlessui zum Identifizieren/Vergleichen nutzt (z.B. "id") */
    by: {type: String as PropType<string>, default: 'id'},

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

    /** Texte */
    label: {type: String, default: ''},
    placeholder: {type: String, default: 'Please select'},
    emptyText: {type: String, default: 'No options available'},

    /** Zustände */
    disabled: {type: Boolean, default: false},
    loading: {type: Boolean, default: false},

    /** Klassen – Standard an dein Design angelehnt, aber überschreibbar */
    labelClass: {type: String, default: 'xsDark'},
    buttonClass: {type: String, default: 'menu-button bg-white focus:outline-hidden focus:ring-0 focus:border-0 w-full text-left rounded-md border border-gray-200 shadow-sm px-3 py-2 text-sm font-normal text-gray-900 focus:ring-1 focus:ring-blue-600 sm:text-sm'},
    optionsClass: {
        type: String,
        default:
            'absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base ring-1 shadow-lg ring-black/5 focus:outline-hidden sm:text-sm',
    },
    optionBaseClass: {
        type: String,
        default: 'relative cursor-default py-2 pr-9 pl-3 select-none',
    },
    activeClass: {type: String, default: 'bg-indigo-600 text-white outline-hidden'},
    inactiveClass: {type: String, default: 'text-gray-900'},
})

const emit = defineEmits<{
    (e: 'update:modelValue', value: Option | null): void
    (e: 'change', value: Option | null): void
}>()

const internalValue = computed({
    get: () => props.modelValue,
    set: (val: Option | null) => {
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

const displayText = computed(() =>
    internalValue.value ? getLabel(internalValue.value) : props.placeholder
)
</script>
