<template>
    <div>
        <!-- Ändern geht immer über die Listbox (Chip = Button); nur das
             Entfernen-X entfällt bei Pflichtfeld -->
        <Listbox as="div" class="w-full relative" :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)">
            <!-- Chip-Ansicht bei gewähltem Status: Klick öffnet die Auswahl -->
            <div v-if="modelValue" class="w-full">
                <div class="text-text-subtle text-xs mb-2">
                    {{ t('Project status') }}{{ required ? '*' : '' }}
                </div>
                <div class="inline-flex items-center gap-x-1">
                    <ListboxButton
                        class="inline-flex items-center gap-x-2 px-3 py-1.5 rounded-full border border-border bg-white cursor-pointer hover:border-text-subtle transition-colors duration-150"
                    >
                        <div class="block w-3 h-3 rounded-full" :style="{'backgroundColor' : modelValue.color }"/>
                        <span class="text-sm flex items-center gap-x-1">
                            {{ modelValue.name }}
                            <IconCalendarMonth v-if="modelValue.is_planning === true || modelValue.is_planning === 1" class="w-4 h-4" />
                        </span>
                        <IconChevronDown class="h-4 w-4 text-text-subtle" aria-hidden="true"/>
                    </ListboxButton>
                    <button v-if="!required" type="button" @click="emit('update:modelValue', null)" class="ml-1">
                        <IconX class="h-4 w-4 text-text-subtle hover:text-danger" />
                    </button>
                </div>
            </div>
            <!-- Platzhalter-Ansicht ohne Status -->
            <ListboxButton v-else class="menu-button-no-padding relative">
                <div class="truncate">
                    <div class="top-2 left-4 absolute text-text-subtle text-xs">
                        {{ t('Project status') }}{{ required ? '*' : '' }}
                    </div>
                    <div class="pt-6 pb-2 flex items-center gap-x-2">
                        <div class="truncate">
                            <span>
                                {{ t('Select project status') }}
                            </span>
                        </div>
                    </div>
                </div>
                <IconChevronDown class="h-5 w-5 text-text" aria-hidden="true"/>
            </ListboxButton>
            <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <ListboxOptions class="absolute w-full z-10 bg-surface-inverse shadow-lg max-h-40 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                    <ListboxOption as="template" class="max-h-8"
                        v-for="state in states"
                        :key="state.id"
                        :value="state"
                        v-slot="{ active, selected }">
                        <li :class="[active ? 'text-white' : 'text-text-subtle', 'group hover:border-l-4 hover:border-l-success cursor-pointer flex justify-between items-center py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                            <div class="flex items-center">
                                <div>
                                    <div class="block w-3 h-3 rounded-full"
                                        :style="{'backgroundColor' : state?.color }"/>
                                </div>
                                <span
                                    :class="[selected ? 'text-sm/5 font-bold text-white' : 'font-normal', 'ml-4 flex items-center gap-x-1 truncate']">
                                    {{ state.name }}
                                    <IconCalendarMonth v-if="state.is_planning === true || state.is_planning === 1" class="w-4 h-4" />
                                </span>
                            </div>
                            <span
                                :class="[active ? 'text-white' : 'text-text-subtle', 'group flex justify-end items-center text-sm subpixel-antialiased']">
                                <IconCheck v-if="selected"
                                    class="h-5 w-5 flex text-success"
                                    aria-hidden="true"/>
                            </span>
                        </li>
                    </ListboxOption>
                </ListboxOptions>
            </transition>
        </Listbox>
        <div v-if="error" class="text-danger text-xs mt-1">
            {{ t('Project status is a required field.') }}
        </div>
    </div>
</template>

<script setup>
import { Listbox, ListboxButton, ListboxOption, ListboxOptions } from '@headlessui/vue'
import { IconCalendarMonth, IconCheck, IconChevronDown, IconX } from '@tabler/icons-vue'
import { useTranslation } from '@/Composeables/Translation.js'

/**
 * Projektstatus-Auswahl des Anlage-/Basisdaten-Dialogs — für Projekte und Projektgruppen.
 */
defineProps({
    modelValue: { type: Object, default: null },
    states: { type: Array, default: () => [] },
    required: { type: Boolean, default: false },
    error: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])
const t = useTranslation()
</script>
