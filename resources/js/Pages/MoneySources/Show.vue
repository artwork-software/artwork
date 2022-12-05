<template>
    <app-layout>
        <div class="py-4">

            <div class="max-w-screen-lg mb-40 my-12 flex flex-row ml-14 mr-40">
                <div class="flex flex-1 flex-wrap">
                    <div class="w-full flex my-auto justify-between">
                        <div class="flex">
                            <!--
                            <Listbox as="div" class="flex" v-model="moneySourceFilter">
                                <ListboxButton
                                    class="bg-white w-full relative py-2 cursor-pointer focus:outline-none sm:text-sm">
                                    <div class="flex items-center my-auto">
                                        <p class="items-center flex mr-2 headline1">
                                            {{ moneySourceFilter.name }}</p>
                                        <span
                                            class="inset-y-0 flex items-center pr-2 pointer-events-none">
                                            <ChevronDownIcon class="h-5 w-5" aria-hidden="true"/>
                                         </span>
                                    </div>
                                </ListboxButton>
                                <transition leave-active-class="transition ease-in duration-100"
                                            leave-from-class="opacity-100" leave-to-class="opacity-0">
                                    <ListboxOptions
                                        class="absolute w-56 z-10 mt-12 bg-primary shadow-lg max-h-64 p-3 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm">
                                        <ListboxOption as="template" class="max-h-8"
                                                       v-for="filter in moneySourceFilters"
                                                       :key="filter.name"
                                                       :value="filter"
                                                       v-slot="{ active, selected }">
                                            <li :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group cursor-pointer flex items-center justify-between py-2 px-3 text-sm subpixel-antialiased']">
                                                    <span
                                                        :class="[selected ? 'xsWhiteBold' : 'xsLight', 'block truncate']">
                                                        {{ filter.name }}
                                                    </span>
                                            </li>
                                        </ListboxOption>
                                    </ListboxOptions>
                                </transition>
                            </Listbox>
                            -->
                            <div class="flex"
                                 v-if="this.$page.props.is_admin">
                                <AddButton @click="openAddMoneySourceModal" text="Neu" mode="page"/>
                                <div v-if="$page.props.can.show_hints" class="flex mt-1">
                                    <span
                                        class="hind ml-1 my-auto">Lege neue Finanzierungsquellen oder -gruppen an</span>
                                    <SvgCollection svgName="arrowRight" class="mt-1 ml-2"/>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <div v-if="!showSearchbar" @click="this.showSearchbar = !this.showSearchbar"
                                 class="cursor-pointer inset-y-0 mr-3">
                                <SearchIcon class="h-5 w-5" aria-hidden="true"/>
                            </div>
                            <div v-else class="flex items-center w-full w-64 mr-2">
                                <inputComponent v-model="this.moneySource_query" placeholder="Suche nach Quellen oder Gruppen"/>
                                <XIcon class="ml-2 cursor-pointer h-5 w-5" @click="closeSearchbar()"/>
                            </div>
                        </div>
                    </div>
                </div>


            </div>

            {{moneySources}}
        </div>
    </app-layout>
    <create-money-source-component
        v-if="showMoneySourceModal"
        @closed="onCreateMoneySourceModalClose()"
        :moneySourceGroups="this.moneySourceGroups"
    />
</template>

<script>

import {defineComponent} from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    Listbox,
    ListboxButton,
    ListboxLabel,
    ListboxOption,
    ListboxOptions,
    Menu,
    MenuButton,
    MenuItem, MenuItems
} from "@headlessui/vue";
import {ChevronDownIcon, SearchIcon, XIcon} from "@heroicons/vue/solid";
import AddButton from "@/Layouts/Components/AddButton";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import InputComponent from "@/Layouts/Components/InputComponent";
import CreateMoneySourceComponent from "@/Layouts/Components/CreateMoneySourceComponent";



export default defineComponent({
    components: {
        AppLayout,
        Listbox,
        ListboxButton,
        ListboxLabel,
        ListboxOption,
        ListboxOptions,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        ChevronDownIcon,
        AddButton,
        SvgCollection,
        SearchIcon,
        InputComponent,
        XIcon,
        CreateMoneySourceComponent



    },
    props: ['moneySources','moneySourceGroups'],
    created() {

    },
    methods: {
        closeSearchbar() {
            this.showSearchbar = !this.showSearchbar;
            this.moneySource_query = ''
        },
        openAddMoneySourceModal(){
            this.showMoneySourceModal = true;
        },
        onCreateMoneySourceModalClose(){
            this.showMoneySourceModal = false;
        },
    },
    watch: {
        moneySource_query: {
            handler() {
                if (this.moneySource_query.length > 0) {
                    axios.get('/money_sources/search', {
                        params: {query: this.moneySource_query, type: this.searchType}
                    }).then(response => {
                        console.log(response);
                    })
                }
            },
            deep: true
        }
    },
    data() {
        return {
            showSearchbar: false,
            moneySource_query: '',
            showMoneySourceModal: false,
            searchType: 'single'
        }
    },
    setup() {
        return {}
    }
})
</script>
