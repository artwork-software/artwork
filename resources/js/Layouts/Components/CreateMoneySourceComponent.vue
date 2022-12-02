<template>
    <jet-dialog-modal :show="true" @close="closeModal()">
        <template #content>
            <img alt="Finanzierungsquelle erstellen" src="/Svgs/Overlays/illu_money_source_create.svg"
                 class="-ml-6 -mt-8 mb-4"/>
            <XIcon @click="closeModal()" class="text-secondary h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                   aria-hidden="true"/>
            <div class="mx-4">
                <!--   Heading   -->
                <div class="my-1">
                    <div class="flex-grow headline1 mb-6">
                        Neue Finanzierungsquelle
                    </div>
                    <div class="xsLight">
                        Lege eine Finanzierungsquelle an und verknüpfe Projekte und Posten um einen Überblick über
                        dein Budget zu erhalten.
                    </div>
                    <div class="mb-4">
                        <div class="hidden sm:block">
                            <div class="border-gray-200">
                                <nav class="-mb-px uppercase text-xs tracking-wide pt-4 flex space-x-8"
                                     aria-label="Tabs">
                                    <a @click="changeTab(tab)" v-for="tab in tabs" href="#" :key="tab.name"
                                       :class="[tab.current ? 'border-buttonBlue text-buttonBlue' : 'border-transparent text-secondary hover:text-gray-600 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium font-semibold']"
                                       :aria-current="tab.current ? 'page' : undefined">
                                        {{ tab.name }}
                                    </a>
                                </nav>
                            </div>
                        </div>
                    </div>
                    <!-- Form when Single Source -->
                    <div v-if="isSingleSourceTab">
                        <div class=" pb-2">
                            <div class="mb-2">
                                <input type="text"
                                       v-model="this.createSingleSourceForm.name"
                                       id="sourceName"
                                       placeholder="Titel*"
                                       class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                            </div>
                            <div class="flex mb-2 space-x-2">
                                <div class="w-1/2">
                                    <input type="number"
                                           v-model="this.createSingleSourceForm.amount"
                                           id="sourceAmount"
                                           placeholder="Summe*"
                                           class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                </div>
                                <div class="w-1/2">
                                    <input type="text"
                                           v-model="this.createSingleSourceForm.source_name"
                                           id="nameOfSource"
                                           placeholder="Quelle"
                                           class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                </div>
                            </div>
                            <div class="flex mb-2 space-x-2">
                                <div class="w-1/2">
                                    <input type="text" onfocus="(this.type='date')"
                                           v-model="this.createSingleSourceForm.start_date"
                                           id="sourceStartDate"
                                           placeholder="Laufzeit Start"
                                           class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                </div>
                                <div class="w-1/2">
                                    <input type="text" onfocus="(this.type='date')"
                                           v-model="this.createSingleSourceForm.end_date"
                                           id="sourceEndDate"
                                           placeholder="Laufzeit Ende"
                                           class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                </div>
                            </div>
                            <div class="mb-2">
                                <div class="relative w-full">
                                    <div class="w-full">
                                        <input id="userSearch" v-model="user_query" type="text" autocomplete="off"
                                               placeholder="Wer ist zuständig?"
                                               class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                    </div>
                                    <transition leave-active-class="transition ease-in duration-100"
                                                leave-from-class="opacity-100"
                                                leave-to-class="opacity-0">
                                        <div v-if="user_search_results.length > 0 && user_query.length > 0"
                                             class="absolute z-10 mt-1 w-full max-h-60 bg-primary shadow-lg
                                                        text-base ring-1 ring-black ring-opacity-5
                                                        overflow-auto focus:outline-none sm:text-sm">
                                            <div class="border-gray-200">
                                                <div v-for="(user, index) in user_search_results" :key="index"
                                                     class="flex items-center cursor-pointer">
                                                    <div class="flex-1 text-sm py-4">
                                                        <p @click="addUserToMoneySourceUserArray(user)"
                                                           class="font-bold px-4 text-white hover:border-l-4 hover:border-l-success">
                                                            {{ user.first_name }} {{ user.last_name }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </transition>
                                </div>
                                <div v-if="usersToAdd.length > 0" class="mt-2 mb-4 flex items-center">
                                        <span v-for="(user,index) in usersToAdd"
                                              class="flex mr-5 rounded-full items-center font-bold text-primary">
                                        <div class="flex items-center">
                                            <img class="flex h-11 w-11 rounded-full object-cover"
                                                 :src="user.profile_photo_url"
                                                 alt=""/>
                                            <span class="flex ml-4 sDark">
                                            {{ user.first_name }} {{ user.last_name }}
                                            </span>
                                            <button type="button" @click="deleteUserFromMoneySourceUserArray(index)">
                                                <span class="sr-only">User aus Finanzierungsquelle entfernen</span>
                                                <XIcon
                                                    class="ml-2 h-4 w-4 p-0.5 hover:text-error rounded-full bg-buttonBlue text-white border-0 "/>
                                            </button>
                                        </div>

                                        </span>
                                </div>
                            </div>
                            <div class="flex items-center mb-2">
                                <input id="hasGroup" type="checkbox" v-model="this.hasGroup"
                                       class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                                <label for="hasGroup" :class="this.hasGroup ? 'xsDark' : 'xsLight subpixel-antialiased'"
                                       class="ml-2">
                                    Gehört zu Finanzierungsquellen-Gruppe
                                </label>
                            </div>
                            <div v-if="this.hasGroup" class="mb-2">
                                <Listbox as="div" v-model="this.selectedMoneySourceGroup" id="room">
                                    <ListboxButton class="inputMain w-full h-10 cursor-pointer truncate flex p-2">
                                        <div class="flex-grow flex text-left xsDark">
                                            {{
                                                this.selectedMoneySourceGroup ? this.selectedMoneySourceGroup.name : 'Finanzierungsgruppe suchen'
                                            }}
                                        </div>
                                        <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                                    </ListboxButton>
                                    <ListboxOptions class="w-5/6 bg-primary max-h-32 overflow-y-auto text-sm absolute">
                                        <ListboxOption v-for="moneySourceGroup in this.moneySourceGroups"
                                                       class="hover:bg-indigo-800 text-secondary cursor-pointer p-2 flex justify-between "
                                                       :key="moneySourceGroup.id"
                                                       :value="moneySourceGroup"
                                                       v-slot="{ active, selected }">
                                            <div :class="[selected ? 'xsWhiteBold' : 'xsLight', 'flex']">
                                                {{ moneySourceGroup.name }}
                                            </div>
                                            <CheckIcon v-if="selected" class="h-5 w-5 text-success" aria-hidden="true"/>
                                        </ListboxOption>
                                    </ListboxOptions>
                                </Listbox>
                            </div>
                            <div class="flex">
                                    <textarea placeholder="Kommentar/Notiz"
                                              id="description"
                                              v-model="this.createSingleSourceForm.description"
                                              rows="4"
                                              class="border-2 placeholder-xsLight focus:xsDark resize-none w-full text-sm focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                            </div>
                            <div class="flex justify-center mt-2">
                                <AddButton mode="modal" class="bg-primary text-white resize-none"
                                           @click="createSingleSource()" text="Finanzierungsquelle anlegen"/>
                            </div>
                        </div>
                    </div>
                    <!-- Form when Source Group -->
                    <div v-else>
                        <div class="mb-2">
                            <input type="text"
                                   v-model="this.createSourceGroupForm.name"
                                   id="sourceName"
                                   placeholder="Titel*"
                                   class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                        </div>
                        <div class="mb-2">
                            <div class="relative w-full">
                                <div class="w-full">
                                    <input id="userSearch" v-model="user_query" type="text" autocomplete="off"
                                           placeholder="Wer ist zuständig?"
                                           class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                </div>
                                <transition leave-active-class="transition ease-in duration-100"
                                            leave-from-class="opacity-100"
                                            leave-to-class="opacity-0">
                                    <div v-if="user_search_results.length > 0 && user_query.length > 0"
                                         class="absolute z-10 mt-1 w-full max-h-60 bg-primary shadow-lg
                                                        text-base ring-1 ring-black ring-opacity-5
                                                        overflow-auto focus:outline-none sm:text-sm">
                                        <div class="border-gray-200">
                                            <div v-for="(user, index) in user_search_results" :key="index"
                                                 class="flex items-center cursor-pointer">
                                                <div class="flex-1 text-sm py-4">
                                                    <p @click="addUserToMoneySourceUserArray(user)"
                                                       class="font-bold px-4 text-white hover:border-l-4 hover:border-l-success">
                                                        {{ user.first_name }} {{ user.last_name }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </transition>
                            </div>
                            <div v-if="usersToAdd.length > 0" class="mt-2 mb-4 flex items-center">
                                        <span v-for="(user,index) in usersToAdd"
                                              class="flex mr-5 rounded-full items-center font-bold text-primary">
                                        <div class="flex items-center">
                                            <img class="flex h-11 w-11 rounded-full object-cover"
                                                 :src="user.profile_photo_url"
                                                 alt=""/>
                                            <span class="flex ml-4 sDark">
                                            {{ user.first_name }} {{ user.last_name }}
                                            </span>
                                            <button type="button" @click="deleteUserFromMoneySourceUserArray(index)">
                                                <span class="sr-only">User aus Finanzierungsquelle entfernen</span>
                                                <XIcon
                                                    class="ml-2 h-4 w-4 p-0.5 hover:text-error rounded-full bg-buttonBlue text-white border-0 "/>
                                            </button>
                                        </div>

                                        </span>
                            </div>
                        </div>
                        <div class="mb-2">
                            <div class="relative w-full">
                                <div class="w-full">
                                    <input id="userSearch" v-model="moneySource_query" type="text" autocomplete="off"
                                           placeholder="Welche Finanzierungsquellen gehören zu dieser Gruppe?"
                                           class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                </div>
                                <transition leave-active-class="transition ease-in duration-100"
                                            leave-from-class="opacity-100"
                                            leave-to-class="opacity-0">
                                    <div v-if="moneySource_search_results.length > 0 && moneySource_query.length > 0"
                                         class="absolute z-10 mt-1 w-full max-h-60 bg-primary shadow-lg
                                                        text-base ring-1 ring-black ring-opacity-5
                                                        overflow-auto focus:outline-none sm:text-sm">
                                        <div class="border-gray-200">
                                            <div v-for="(moneySource, index) in moneySource_search_results" :key="index"
                                                 class="flex items-center cursor-pointer">
                                                <div class="flex-1 text-sm py-4">
                                                    <p @click="addMoneySourceToGroup(moneySource)"
                                                       class="font-bold px-4 text-white hover:border-l-4 hover:border-l-success">
                                                        {{ moneySource.name }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </transition>
                            </div>
                            <div v-if="subMoneySources.length > 0" class="mt-2 mb-4 flex items-center">
                                        <span v-for="(subMoneySource,index) in subMoneySources"
                                              class="flex mr-5 rounded-full items-center font-bold text-primary">
                                            <span
                                                class="rounded-full items-center font-medium text-tagText border bg-tagBg border-tag px-3 text-sm mr-1 mb-1 h-8 inline-flex">
                                                {{ subMoneySource.name }}
                                                <button type="button"
                                                        @click="this.deleteSubMoneySourceFromGroup(index)">
                                                    <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
                                                </button>
                                            </span>
                                        </span>
                            </div>
                        </div>
                        <div class="flex">
                                    <textarea placeholder="Kommentar/Notiz"
                                              id="description"
                                              v-model="this.createSourceGroupForm.description"
                                              rows="4"
                                              class="border-2 placeholder-xsLight focus:xsDark resize-none w-full text-sm focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                        </div>
                        <div class="flex justify-center mt-2">
                            <AddButton mode="modal" class="bg-primary text-white resize-none"
                                       @click="createMoneySourceGroup()" text="Finanzierungsquellengruppe anlegen"/>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </jet-dialog-modal>

</template>

<script>

import JetDialogModal from "@/Jetstream/DialogModal";
import {ChevronDownIcon, DotsVerticalIcon, PencilAltIcon, XCircleIcon, XIcon} from '@heroicons/vue/outline';
import EventTypeIconCollection from "@/Layouts/Components/EventTypeIconCollection";
import {
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions,
    Menu,
    MenuButton,
    MenuItem,
    MenuItems
} from "@headlessui/vue";
import {CheckIcon, ChevronUpIcon, TrashIcon} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import Input from "@/Jetstream/Input";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent";
import TagComponent from "@/Layouts/Components/TagComponent";
import InputComponent from "@/Layouts/Components/InputComponent";
import {useForm} from "@inertiajs/inertia-vue3";
import AddButton from "@/Layouts/Components/AddButton";

export default {
    name: 'EventComponent',

    components: {
        Input,
        JetDialogModal,
        XIcon,
        XCircleIcon,
        EventTypeIconCollection,
        Listbox,
        ListboxButton,
        ListboxOption,
        ListboxOptions,
        ChevronDownIcon,
        ChevronUpIcon,
        SvgCollection,
        CheckIcon,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        PencilAltIcon,
        TrashIcon,
        DotsVerticalIcon,
        ConfirmationComponent,
        TagComponent,
        InputComponent,
        AddButton
    },
    computed: {
        tabs() {
            return [
                {name: 'Einzelquelle', href: '#', current: this.isSingleSourceTab},
                {name: 'Gruppe', href: '#', current: this.isGroupTab},
            ]
        },

    },
    data() {
        return {
            isSingleSourceTab: true,
            isGroupTab: false,
            user_search_results: [],
            user_query: '',
            usersToAdd: [],
            hasGroup: false,
            subMoneySources: [],
            moneySource_query: '',
            moneySource_search_results: [],
            selectedMoneySourceGroup: null,
            createSingleSourceForm: useForm({
                name: '',
                amount: null,
                start_date: null,
                end_date: null,
                source_name: null,
                description: null,
                is_group: false,
                group_id: null,
                users: [],
            }),
            createSourceGroupForm: useForm({
                name: '',
                amount: null,
                start_date: null,
                end_date: null,
                source_name: null,
                description: null,
                is_group: true,
                users: [],
                sub_money_source_ids: []
            }),
        }
    },

    props: ['moneySourceGroups'],

    emits: ['closed'],

    watch: {
        user_query: {
            handler() {
                if (this.user_query.length > 0) {
                    axios.get('/users/search', {
                        params: {query: this.user_query}
                    }).then(response => {
                        this.user_search_results = response.data
                    })
                }
            },
            deep: true
        },
        moneySource_query: {
            handler() {
                if (this.moneySource_query.length > 0) {
                    axios.get('/money_sources/search', {
                        params: {query: this.moneySource_query}
                    }).then(response => {
                        this.moneySource_search_results = response.data
                    })
                }
            },
            deep: true
        },
    },

    methods: {
        addUserToMoneySourceUserArray(user) {
            this.usersToAdd.push(user);
            this.user_query = '';
        },
        addMoneySourceToGroup(moneySource){
            this.subMoneySources.push(moneySource);
            this.moneySource_query = '';
        },
        deleteUserFromMoneySourceUserArray(index) {
            this.usersToAdd.splice(index, 1);
        },
        deleteSubMoneySourceFromGroup(index) {
            this.subMoneySources.splice(index,1);
        },
        createSingleSource() {
            this.usersToAdd.forEach((userToAdd) => {
                this.createSingleSourceForm.users.push(userToAdd.id);
            })
            if (this.selectedMoneySourceGroup) {
                this.createSingleSourceForm.group_id = this.selectedMoneySourceGroup.id
            }
            this.createSingleSourceForm.post(route('money_sources.store'));
            this.closeModal(true);
        },
        createMoneySourceGroup(){
            this.usersToAdd.forEach((userToAdd) => {
                this.createSourceGroupForm.users.push(userToAdd.id);
            })
            this.subMoneySources.forEach((subMoneySource) => {
                this.createSourceGroupForm.sub_money_source_ids.push(subMoneySource.id);
            })
            this.createSourceGroupForm.post(route('money_sources.store'));
            this.closeModal(true);
        },
        changeTab(selectedTab) {
            this.usersToAdd = [];
            this.isSingleSourceTab = false;
            this.isGroupTab = false;
            if (selectedTab.name === 'Einzelquelle') {
                this.isSingleSourceTab = true;
            } else {
                this.isGroupTab = true;
            }
        },
        openModal() {

        },
        closeModal(bool) {
            this.$emit('closed', bool);
        },
    },
}
</script>

<style scoped></style>
