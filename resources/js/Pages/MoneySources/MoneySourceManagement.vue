<template>
    <app-layout>
        <div class="">
            <div class="max-w-screen-xl mb-40 flex flex-row ml-14 mr-40">
                <div class="flex flex-1 flex-wrap">
                    <div>
                        <p class="items-center flex mr-2 headline1 mb-11">Finanzierungsquellen</p>
                    </div>
                    <div class="w-full flex my-auto">
                        <div class="flex justify-between w-full">
                            <div class="flex">
                            <Listbox as="div" class="flex" v-model="moneySourceFilter">
                                <ListboxButton
                                    class="bg-white w-full relative py-2 cursor-pointer focus:outline-none">
                                    <div class="flex items-center my-auto">
                                        <p class="items-center flex mr-2 xsDark">
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
                                        class="absolute w-80 z-10 mt-12 bg-primary shadow-lg max-h-64 p-3 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none">
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
                            <div class="flex items-center ml-8">
                                <div v-if="!showSearchbar" @click="this.showSearchbar = !this.showSearchbar"
                                     class="cursor-pointer inset-y-0 mr-3">
                                    <SearchIcon class="h-5 w-5" aria-hidden="true"/>
                                </div>
                                <div v-else class="flex items-center w-full mr-2">
                                    <inputComponent v-model="this.moneySource_query"
                                                    placeholder="Suche nach Quellen/Gruppen"/>
                                    <XIcon class="ml-2 cursor-pointer h-5 w-5" @click="closeSearchbar()"/>
                                </div>
                            </div>
                            </div>
                            <div class="flex"
                                 v-if="$can('view edit add money_sources') || $can('can edit and delete money sources') || $role('artwork admin')">
                                <div v-if="$page.props.can.show_hints" class="flex mt-1">
                                    <span
                                        class="hind ml-1 my-auto">Lege neue Finanzierungsquellen oder -gruppen an</span>
                                    <img src="/Svgs/IconSvgs/icon_grey_arrow_right.svg"
                                         class="h-4 w-4 ml-1"
                                         aria-hidden="true"/>
                                </div>
                                <AddButton @click="openAddMoneySourceModal" text="Neu" mode="page"/>

                            </div>

                        </div>

                    </div>
                    <ul role="list" class="mt-5 w-full">
                        <li v-if="moneySource_query.length < 1" v-for="(moneySource,index) in sourcesToShow"
                            :key="moneySource.id"
                        >
                            <div class="py-5 flex justify-between border-b-2 border-gray-200 my-2"
                                 v-if="($page.props.myMoneySources.some(source => source.money_source_id === moneySource.id) || $page.props.is_money_source_admin || $canAny('view edit add money_sources','can edit and delete money sources'))">
                                <div class="flex w-full">
                                    <div class="flex">
                                        <img v-if="moneySource.is_group" src="/Svgs/IconSvgs/icon_group_red.svg"
                                             class=" h-6 w-6 ml-5" alt="groupIcon"/>
                                        <Link :href="getEditHref(moneySource)"
                                              class="sDark ml-5 my-auto w-full justify-start mr-6">
                                            {{ moneySource.name }}
                                        </Link>
                                    </div>
                                    <div v-if="moneySource.group_id !== null"
                                         class="w-full flex items-center xxsLight subpixel-antialiased ml-14 mt-1">
                                        Gehört zu:
                                        {{
                                            this.moneySourceGroups.find(sourceGroup => sourceGroup.id === moneySource.group_id).name
                                        }}
                                    </div>
                                </div>

                                <div class="flex">
                                    <Menu as="div" class="my-auto relative">
                                        <div class="flex">
                                            <MenuButton
                                                class="flex bg-tagBg p-0.5 rounded-full">
                                                <DotsVerticalIcon
                                                    class=" flex-shrink-0 h-6 w-6 text-menuButtonBlue my-auto"
                                                    aria-hidden="true"/>
                                            </MenuButton>
                                        </div>
                                        <transition enter-active-class="transition ease-out duration-100"
                                                    enter-from-class="transform opacity-0 scale-95"
                                                    enter-to-class="transform opacity-100 scale-100"
                                                    leave-active-class="transition ease-in duration-75"
                                                    leave-from-class="transform opacity-100 scale-100"
                                                    leave-to-class="transform opacity-0 scale-95">
                                            <MenuItems
                                                class="origin-top-right z-10 absolute right-0 mr-4 mt-2 w-72 shadow-lg bg-primary ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                                <div class="py-1">
                                                    <MenuItem v-slot="{ active }">
                                                        <a :href="getEditHref(moneySource)"
                                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                            <PencilAltIcon
                                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                aria-hidden="true"/>
                                                            Bearbeiten
                                                        </a>
                                                    </MenuItem>
                                                    <MenuItem v-slot="{ active }" v-if="getMemberInMoneySource(moneySource).write_access.includes($page.props.user.id) || getMemberInMoneySource(moneySource).competent.includes($page.props.user.id) || $can('view edit add money_sources') || $can('can edit and delete money sources') || $role('artwork admin')">
                                                        <a @click="duplicateMoneySource(moneySource)"
                                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                            <DuplicateIcon
                                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                aria-hidden="true"/>
                                                            Duplizieren
                                                        </a>
                                                    </MenuItem>
                                                    <MenuItem v-slot="{ active }" v-if="getMemberInMoneySource(moneySource).write_access.includes($page.props.user.id) || getMemberInMoneySource(moneySource).competent.includes($page.props.user.id) || $can('view edit add money_sources') || $can('can edit and delete money sources') || $role('artwork admin')">
                                                        <a @click="openDeleteSourceModal(moneySource)"
                                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                            <TrashIcon
                                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                aria-hidden="true"/>
                                                            Löschen
                                                        </a>
                                                    </MenuItem>
                                                </div>
                                            </MenuItems>
                                        </transition>
                                    </Menu>

                                </div>
                            </div>
                        </li>
                        <li v-else v-for="(moneySource,index) in moneySource_search_results" :key="moneySource.id"
                            class="py-5 flex justify-between">
                            <div class="flex">
                                <Link :href="getEditHref(moneySource)" class="ml-5 my-auto w-full justify-start mr-6">
                                    <div class="flex my-auto">
                                        <p class="sDark">{{ moneySource.name }}</p>
                                    </div>
                                </Link>
                            </div>
                            <div class="flex">
                                <Menu as="div" class="my-auto relative">
                                    <div class="flex">
                                        <MenuButton
                                            class="flex bg-tagBg p-0.5 rounded-full">
                                            <DotsVerticalIcon
                                                class=" flex-shrink-0 h-6 w-6 text-menuButtonBlue my-auto"
                                                aria-hidden="true"/>
                                        </MenuButton>
                                    </div>
                                    <transition enter-active-class="transition ease-out duration-100"
                                                enter-from-class="transform opacity-0 scale-95"
                                                enter-to-class="transform opacity-100 scale-100"
                                                leave-active-class="transition ease-in duration-75"
                                                leave-from-class="transform opacity-100 scale-100"
                                                leave-to-class="transform opacity-0 scale-95">
                                        <MenuItems
                                            class="origin-top-right z-10 absolute right-0 mr-4 mt-2 w-72 shadow-lg bg-primary ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                            <div class="py-1">
                                                <MenuItem v-slot="{ active }">
                                                    <a :href="getEditHref(moneySource)"
                                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                        <PencilAltIcon
                                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                            aria-hidden="true"/>
                                                        Bearbeiten
                                                    </a>
                                                </MenuItem>
                                                <MenuItem v-slot="{ active }" v-if="getMemberInMoneySource(moneySource).write_access.includes($page.props.user.id) || getMemberInMoneySource(moneySource).competent.includes($page.props.user.id) || $can('view edit add money_sources') || $can('can edit and delete money sources') || $role('artwork admin')">
                                                    <a @click="duplicateMoneySource(moneySource)"
                                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                        <DuplicateIcon
                                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                            aria-hidden="true"/>
                                                        Duplizieren
                                                    </a>
                                                </MenuItem>
                                                <MenuItem v-slot="{ active }" v-if="getMemberInMoneySource(moneySource).write_access.includes($page.props.user.id) || getMemberInMoneySource(moneySource).competent.includes($page.props.user.id) || $can('view edit add money_sources') || $can('can edit and delete money sources') || $role('artwork admin')">
                                                    <a @click="openDeleteSourceModal(moneySource)"
                                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                        <TrashIcon
                                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                            aria-hidden="true"/>
                                                        Löschen
                                                    </a>
                                                </MenuItem>
                                            </div>
                                        </MenuItems>
                                    </transition>
                                </Menu>

                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </app-layout>
    <create-money-source-component
        v-if="showMoneySourceModal"
        @closed="onCreateMoneySourceModalClose()"
        :moneySourceGroups="this.moneySourceGroups"
    />
    <confirm-delete-modal
        v-if="showDeleteSourceModal"
        title="Finanzierungsquelle/gruppe löschen"
        :description="'Bist du sicher, dass du die Finanzierungsquelle/Gruppe ' + this.sourceToDelete.name + ' löschen möchtest?'"
        @closed="afterConfirm"
        @delete="afterConfirm(true)"/>
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
    MenuItem,
    MenuItems
} from "@headlessui/vue";
import {ChevronDownIcon, DotsVerticalIcon, SearchIcon, TrashIcon, XIcon} from "@heroicons/vue/solid";
import AddButton from "@/Layouts/Components/AddButton";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import InputComponent from "@/Layouts/Components/InputComponent";
import CreateMoneySourceComponent from "@/Layouts/Components/CreateMoneySourceComponent";
import {DuplicateIcon, PencilAltIcon} from "@heroicons/vue/outline";
import {Link} from "@inertiajs/inertia-vue3";
import Permissions from "@/mixins/Permissions.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";


export default defineComponent({
    mixins: [Permissions],
    components: {
        ConfirmDeleteModal,
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
        CreateMoneySourceComponent,
        DotsVerticalIcon,
        PencilAltIcon,
        Link,
        DuplicateIcon,
        TrashIcon,
    },
    props: ['moneySources', 'moneySourceGroups'],
    created() {

    },
    computed: {
        sourcesToShow: function () {
            if (this.moneySourceFilter.type === 'all') {
                return this.moneySources
            } else if (this.moneySourceFilter.type === 'single') {
                return this.moneySources.filter(moneySource => moneySource.is_group === 0);
            } else if (this.moneySourceFilter.type === 'group') {
                return this.moneySourceGroups;
            }
        },
    },
    methods: {
        getMemberInMoneySource(moneySource){
            const returnArray = {
                competent: [],
                write_access: []
            }
            moneySource.users.forEach((user) => {
                if(user.pivot.competent){
                    returnArray.competent.push(user.id);
                }
                if(user.pivot.write_access){
                    returnArray.write_access.push(user.id);
                }
            })
            return returnArray;
        },
        closeSearchbar() {
            this.showSearchbar = !this.showSearchbar;
            this.moneySource_query = ''
        },
        openAddMoneySourceModal() {
            this.showMoneySourceModal = true;
        },
        onCreateMoneySourceModalClose() {
            this.showMoneySourceModal = false;
        },
        getEditHref(moneySource) {
            return route('money_sources.show', {moneySource: moneySource.id});
        },
        duplicateMoneySource(moneySource) {
            this.$inertia.post(`/money_sources/${moneySource.id}/duplicate`);
        },
        deleteMoneySource(moneySource) {
            this.$inertia.delete(`/money_sources/${moneySource.id}`);
            this.showDeleteSourceModal = false;
        },
        openDeleteSourceModal(moneySourceToDelete) {
            this.sourceToDelete = moneySourceToDelete;
            this.showDeleteSourceModal = true;
        },
        async afterConfirm(bool) {
            if (!bool) return this.showDeleteSourceModal = false;

            this.deleteMoneySource(this.sourceToDelete)
        },
    },
    watch: {
        moneySource_query: {
            handler() {
                if (this.moneySource_query.length > 0) {
                    axios.get('/money_sources/search', {
                        params: {query: this.moneySource_query, type: this.moneySourceFilter.type}
                    }).then(response => {
                        this.moneySource_search_results = response.data
                    })
                }
            },
            deep: true
        },
    },
    data() {
        return {
            moneySourceFilters: [{
                'name': 'Alle Finanzierungsquellen & -gruppen',
                'type': 'all'
            }, {'name': 'Alle Finanzierungsquellen', 'type': 'single'}, {
                'name': 'Alle Finanzierungsgruppen',
                'type': 'group'
            }],
            moneySourceFilter: {'name': 'Alle Finanzierungsquellen & -gruppen', 'type': 'all'},
            showSearchbar: false,
            moneySource_query: '',
            showMoneySourceModal: false,
            moneySource_search_results: [],
            showDeleteSourceModal: false,
            sourceToDelete: null,
        }
    },
    setup() {
        return {}
    }
})
</script>
