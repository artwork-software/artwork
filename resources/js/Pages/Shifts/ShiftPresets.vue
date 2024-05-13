<template>
    <ShiftHeader>
        <div class="w-full flex my-auto items-center justify-between mb-3 mt-10">
            <div class="flex items-center justify-between">
                <Listbox as="div" class="flex w-72 ml-5" v-model="selectedFilter">
                    <ListboxButton
                        class="bg-white w-full relative py-2 cursor-pointer focus:outline-none">
                        <div class="flex items-center my-auto">
                            <h2 class="headline1">{{ selectedFilter.name }}</h2>
                            <span class="inset-y-0 flex items-center pr-2 pointer-events-none">
                            <ChevronDownIcon class="h-5 w-5" aria-hidden="true"/>
                        </span>
                        </div>
                    </ListboxButton>
                    <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                        <ListboxOptions class="absolute w-80 z-10 mt-12 bg-artwork-navigation-background shadow-lg max-h-64 p-3 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none">
                            <ListboxOption as="template" class="max-h-8" key="0" :value="{name: 'Alle Vorlagen', id: 0}" v-slot="{ active, selected }">
                                <li :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group cursor-pointer flex items-center justify-between py-2 px-3 text-sm subpixel-antialiased']">
                                <span :class="[selected ? 'xsWhiteBold' : 'xsLight', 'block truncate']">
                                    {{$t('All shift templates')}}
                                </span>
                                </li>
                            </ListboxOption>
                            <ListboxOption as="template" class="max-h-8" v-for="filter in event_types" :key="filter.name" :value="filter"  v-show="filter.id !== 1" v-slot="{ active, selected }">
                                <li :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group cursor-pointer flex items-center justify-between py-2 px-3 text-sm subpixel-antialiased']">
                                <span :class="[selected ? 'xsWhiteBold' : 'xsLight', 'block truncate']">
                                    {{ filter.name }}
                                </span>
                                </li>
                            </ListboxOption>
                        </ListboxOptions>
                    </transition>
                </Listbox>
                <button @click="showAddShiftPresetModal = true" type="button" class="rounded-full bg-artwork-buttons-create p-1 mr-1 text-white shadow-sm hover:bg-artwork-buttons-hover focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-artwork-buttons-hover">
                    <PlusIcon class="h-4 w-4" aria-hidden="true" />
                </button>
                <div v-if="this.$page.props.show_hints" class="flex mt-1">
                    <SvgCollection svgName="arrowLeft" class="mt-1 ml-2"/>
                    <span class="hind ml-1 my-auto">{{$t('Create a new shift template')}}</span>
                </div>
            </div>
            <div>
                <div v-if="!showSearchbar" @click="this.showSearchbar = !this.showSearchbar"
                     class="cursor-pointer inset-y-0 mr-3">
                    <SearchIcon class="h-5 w-5" aria-hidden="true"/>
                </div>
                <div v-else class="flex items-center w-full w-64 mr-2">
                    <input v-model="preset_search"
                           id="changeEndTime"
                           type="text"
                           required
                           :placeholder="$t('Name of the template*')"
                           class="border-gray-300 inputMain xsDark placeholder-secondary  disabled:border-none w-full h-12"/>

                    <XIcon class="ml-2 cursor-pointer h-5 w-5" @click="closeSearchbar"/>
                </div>
            </div>
        </div>
        <div class="w-full px-5 min-h-screen">
            <div v-if="filteredShiftPresets.length > 0">
                <div v-for="preset in filteredShiftPresets">
                    <SingleShiftPreset :preset="preset"
                                       :crafts="crafts"
                                       :event_types="event_types"
                                       :shift-qualifications="shiftQualifications"
                    />
                </div>
            </div>
            <div v-else>
                <div class="rounded-md bg-red-50 p-4 ml-5">
                    <div class="flex text-sm text-red-700">
                        {{$t('No shift templates available.')}}
                    </div>
                </div>
            </div>
        </div>
        <AddShiftPresetModal :event_types="event_types" v-if="showAddShiftPresetModal" @closed="showAddShiftPresetModal = false" />
    </ShiftHeader>
</template>
<script>
import {defineComponent} from 'vue'
import ShiftHeader from "@/Pages/Shifts/ShiftHeader.vue";
import SingleShiftPreset from "@/Pages/Shifts/Components/SingleShiftPreset.vue";
import {ChevronDownIcon, PlusIcon} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import {Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions} from "@headlessui/vue";
import AddShiftPresetModal from "@/Pages/Projects/Components/AddShiftPresetModal.vue";
import {SearchIcon, XIcon} from "@heroicons/vue/outline";
import InputComponent from "@/Layouts/Components/InputComponent.vue";
import Input from "@/Jetstream/Input.vue";

export default defineComponent({
    name: "ShiftPresets",
    components: {
        Input,
        InputComponent,
        XIcon,
        SearchIcon,
        AddShiftPresetModal,
        ChevronDownIcon,
        SvgCollection,
        PlusIcon,
        SingleShiftPreset,
        ShiftHeader,
        Listbox,
        ListboxButton,
        ListboxLabel,
        ListboxOption,
        ListboxOptions
    },
    props: [
        'shiftPresets',
        'crafts',
        'event_types',
        'shiftQualifications'
    ],
    data(){
        return {
            selectedFilter: {
                name: this.$t('All templates'),
                id: 0
            },
            showAddShiftPresetModal: false,
            showSearchbar: false,
            preset_search: ''
        }
    },
    computed: {
        filteredShiftPresets(){
            if (this.selectedFilter.id === 0) {
                if (this.preset_search.length > 0) {
                    return this.shiftPresets.filter(
                        preset => preset.name.toLowerCase().includes(this.preset_search.toLowerCase())
                    )
                } else {
                    return this.shiftPresets
                }
            } else {
                if (this.preset_search.length > 0) {
                    return this.shiftPresets.filter(
                        preset => preset.name.toLowerCase().includes(this.preset_search.toLowerCase()) &&
                            preset.event_type.id === this.selectedFilter.id
                    )
                } else {
                    return this.shiftPresets.filter(preset => preset.event_type.id === this.selectedFilter.id)
                }
            }
        }
    },
    methods: {
        closeSearchbar(){
            this.showSearchbar = false
            this.preset_search = ''
        }
    }
})
</script>
