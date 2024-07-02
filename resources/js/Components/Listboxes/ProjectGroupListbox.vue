<script setup>
import {Listbox, ListboxButton, ListboxOption, ListboxOptions} from "@headlessui/vue";

import {ChevronDownIcon} from "@heroicons/vue/outline";
import Button from "@/Jetstream/Button.vue";
import {CheckIcon} from "@heroicons/vue/solid";
import {ref} from "vue";

const props = defineProps({
    projectGroups: {
        type: Object,
        required: true
    },
    selectedProjectGroup: {
        type: Object,
        required: false
    }
})

const currentGroup = ref(props.selectedProjectGroup ? props.selectedProjectGroup : null)

const emit = defineEmits(['update:selectedProjectGroup'])

</script>

<template>
    <Listbox as="div" v-model="currentGroup" :on-update:model-value="$emit('update:selectedProjectGroup', currentGroup)" id="room">
        <ListboxButton class="inputMain w-full h-10 cursor-pointer truncate flex p-2">
            <div class="flex-grow flex text-left xsDark">
                {{
                    currentGroup ? currentGroup.name : $t('Search project group')
                }}
            </div>
            <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
        </ListboxButton>
        <ListboxOptions class="absolute w-[88%] z-10 bg-artwork-navigation-background shadow-lg max-h-40 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
            <ListboxOption v-if="projectGroups.length === 0"
                           class="w-full text-secondary cursor-pointer p-2 flex justify-between"
                           :value="null">
                {{ $t('No project group has been created yet') }}
            </ListboxOption>
            <ListboxOption v-for="projectGroup in projectGroups"
                           class="text-secondary cursor-pointer p-2 flex justify-between "
                           :key="projectGroup.id"
                           :value="projectGroup"
                           v-slot="{ active, selected }">
                    <div :class="[selected ? 'xsWhiteBold' : 'xsLight', 'flex']">
                        {{ projectGroup.name }}
                    </div>
                <CheckIcon v-if="selected" class="h-5 w-5 text-success" aria-hidden="true"/>
            </ListboxOption>
        </ListboxOptions>
    </Listbox>
</template>

<style scoped>

</style>
