<script setup>
import {Listbox, ListboxButton, ListboxOption, ListboxOptions} from "@headlessui/vue";

import {ChevronDownIcon} from "@heroicons/vue/outline";
import Button from "@/Jetstream/Button.vue";
import {CheckIcon} from "@heroicons/vue/solid";
import {ref} from "vue";

const props = defineProps({
    projectStates: {
        type: Object,
        required: true
    },
    selectedProjectState: {
        type: Number,
        required: false
    }
})

const backgroundColorWithOpacity = (color, percent = 15) => {
    if (!color) return `rgb(255, 255, 255, ${percent}%)`;
    return `rgb(${parseInt(color.slice(-6, -4), 16)}, ${parseInt(color.slice(-4, -2), 16)}, ${parseInt(color.slice(-2), 16)}, ${percent}%)`;
}
const textColorWithDarken = (color, percent = 75) => {
    if (!color) return 'rgb(180, 180, 180)';
    return `rgb(${parseInt(color.slice(-6, -4), 16) - percent}, ${parseInt(color.slice(-4, -2), 16) - percent}, ${parseInt(color.slice(-2), 16) - percent})`;
}

const currentState = ref(props.selectedProjectState ? props.selectedProjectState : null)

const emit = defineEmits(['update:selectedProjectState'])


</script>

<template>
    <Listbox as="div" class="w-full relative" v-model="currentState" :on-update:model-value="$emit('update:selectedProjectState', currentState)">
        <ListboxButton class="w-full text-left">
            <button class="menu-button">
                <span v-if="!selectedProjectState">
                    {{ $t('Select project status') }}
                </span>
                <span v-else class="items-center inline-flex border px-3 py-0.5 rounded-full"
                      :style="{backgroundColor: backgroundColorWithOpacity(projectStates?.find(state => state.id === currentState)?.color),
                               color: textColorWithDarken(projectStates?.find(state => state.id === currentState)?.color),
                               borderColor: textColorWithDarken(projectStates?.find(state => state.id === currentState)?.color)
                              }">
                      {{ projectStates?.find(state => state.id === currentState)?.name}}
                </span>
                <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
            </button>
        </ListboxButton>
        <transition leave-active-class="transition ease-in duration-100"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0">
            <ListboxOptions
                class="absolute z-10 w-full bg-white rounded-lg shadow-lg max-h-40 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                <ListboxOption as="template" class=""
                               v-for="state in projectStates"
                               :key="state.id"
                               :value="state.id" v-slot="{ active, selected }">
                    <li :class="[active ? ' text-secondary' : 'text-secondary', 'group hover:border-l-4 hover:border-l-success cursor-pointer flex justify-between items-center py-1 pl-3 text-sm subpixel-antialiased']">
                        <div class="flex">
                            <span class=" items-center font-medium px-2 py-1.5 inline-flex border rounded-full" :style="{backgroundColor: backgroundColorWithOpacity(state.color), color: textColorWithDarken(state.color), borderColor: textColorWithDarken(state.color)}">
                                {{ state.name }}
                            </span>
                        </div>
                        <span :class="[active ? ' text-white' : 'text-secondary', ' group flex justify-end items-center text-sm subpixel-antialiased']">
                            <CheckIcon v-if="selected" class="h-5 w-5 flex text-success" aria-hidden="true"/>
                        </span>
                    </li>
                </ListboxOption>
            </ListboxOptions>
        </transition>
    </Listbox>
</template>

<style scoped>

</style>
