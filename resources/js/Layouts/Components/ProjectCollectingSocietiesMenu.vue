<template>
    <Listbox as="div" v-model="selectedCollectingSociety" id="collecting_society">
        <ListboxButton
            class="border-2 border-gray-300 w-full cursor-pointer truncate flex p-4">
            <div v-if="selectedCollectingSociety" class="flex-grow text-left">
                {{selectedCollectingSociety}}
            </div>
            <div v-else class="flex-grow xsLight text-left subpixel-antialiased">
                {{$t('Choose a collecting society')}}*
            </div>
            <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
        </ListboxButton>
        <ListboxOptions class="w-[85%] bg-primary overflow-y-auto text-sm absolute">
            <ListboxOption v-for="society in collectingSocieties"
                           class="hover:bg-indigo-800 text-secondary cursor-pointer p-3 flex justify-between "
                           :key="society.name"
                           :value="society.name"
                           v-slot="{ active, selected }">
                <div :class="[selected ? 'text-white' : '']">
                    {{ society.name }}
                </div>
                <CheckIcon v-if="selected" class="h-5 w-5 text-success" aria-hidden="true"/>
            </ListboxOption>
        </ListboxOptions>
    </Listbox>
</template>

<script>
import {ChevronDownIcon, DocumentTextIcon, CheckIcon} from "@heroicons/vue/outline";
import {
    Listbox,
    ListboxOption,
    ListboxOptions,
    ListboxButton
} from "@headlessui/vue";
import BaseFilterDisclosure from "@/Layouts/Components/BaseFilterDisclosure";
import Permissions from "@/mixins/Permissions.vue";

export default {
    name: "ProjectCollectingSocietiesMenu",
    mixins: [Permissions],
    components: {
        BaseFilterDisclosure,
        ChevronDownIcon,
        Listbox,
        ListboxOption,
        ListboxOptions,
        ListboxButton,
        DocumentTextIcon,
        CheckIcon
    },
    props: {
        copyright: Object
    },
    data() {
        return {
            selectedCollectingSociety: this.copyright?.collecting_society,
            societiesOpened: false,
            collectingSocieties: []
        }
    },
    mounted() {
        axios.get(route('collecting_societies.index')).then(res => {
            this.collectingSocieties = res.data
        })
    }
}
</script>

<style scoped>

</style>
