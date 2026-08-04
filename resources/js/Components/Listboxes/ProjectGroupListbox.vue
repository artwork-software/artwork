<script setup>
import {IconCheck, IconChevronDown} from "@tabler/icons-vue";
import {Listbox, ListboxButton, ListboxOption, ListboxOptions} from "@headlessui/vue";

import Button from "@/Jetstream/Button.vue";
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
        <ListboxButton class="menu-button">
            <div>
                <span v-if="!currentGroup">{{ $t('Search project group') }}</span>
                <div v-else>
                    {{ currentGroup.name }}
                </div>
            </div>
            <IconChevronDown class="h-5 w-5 text-text" aria-hidden="true"/>
        </ListboxButton>
        <ListboxOptions class="absolute w-[88%] z-10 bg-surface-inverse shadow-lg max-h-40 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll sm:text-sm">
            <ListboxOption v-if="projectGroups.length === 0"
                           class="w-full text-text-subtle cursor-pointer p-2 flex justify-between"
                           :value="null">
                {{ $t('No project group has been created yet') }}
            </ListboxOption>
            <ListboxOption v-for="projectGroup in projectGroups"
                           class="text-text-subtle cursor-pointer p-2 flex justify-between "
                           :key="projectGroup.id"
                           :value="projectGroup"
                           v-slot="{ active, selected }">
                    <div :class="[selected ? 'text-sm/5 font-bold text-white' : 'text-sm/5 font-bold text-text-subtle', 'flex']">
                        {{ projectGroup.name }}
                    </div>
                <IconCheck v-if="selected" class="h-5 w-5 text-success" aria-hidden="true"/>
            </ListboxOption>
        </ListboxOptions>
    </Listbox>
</template>

<style scoped>

</style>
