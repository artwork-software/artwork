<template>
    <BaseModal @closed="closeModal" v-if="true" modal-image="/Svgs/Overlays/illu_money_source_create.svg">
            <div class="mx-4">
                <!--   Heading   -->
                <div class="my-1">
                    <div class="flex-grow headline1 mb-6">
                        {{ $t('New source of funding')}}
                    </div>
                    <div class="xsLight">
                        {{ $t('Create a funding source and link projects and items to get an overview of your budget.')}}
                    </div>
                    <div class="mb-4">
                        <div class="hidden sm:block">
                            <div class="border-gray-200">
                                <nav class="-mb-px uppercase text-xs tracking-wide pt-4 flex space-x-8"
                                     aria-label="Tabs">
                                    <a @click="changeTab(tab)" v-for="tab in tabs" href="#" :key="tab.name"
                                       :class="[tab.current ? 'border-artwork-buttons-create text-artwork-buttons-create' : 'border-transparent text-secondary hover:text-gray-600 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-semibold']"
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
                                       :placeholder="$t('Title*')"
                                       class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                            </div>
                            <div class="flex mb-2 space-x-2">
                                <div class="w-1/2">
                                    <input type="number"
                                           v-model="this.createSingleSourceForm.amount"
                                           id="sourceAmount"
                                           :placeholder="$t('Sum*')"
                                           class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                </div>
                                <div class="w-1/2">
                                    <input type="text"
                                           v-model="this.createSingleSourceForm.source_name"
                                           id="nameOfSource"
                                           :placeholder="$t('Source')"
                                           class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                </div>
                            </div>
                            <div class="flex mb-2 space-x-2">
                                <div class="w-1/2">
                                    <input type="text" onfocus="(this.type='date')"
                                           v-model="this.createSingleSourceForm.start_date"
                                           id="sourceStartDate"
                                           :placeholder="$t('Runtime Start')"
                                           class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                </div>
                                <div class="w-1/2">
                                    <input type="text" onfocus="(this.type='date')"
                                           v-model="this.createSingleSourceForm.end_date"
                                           id="sourceEndDate"
                                           :placeholder="$t('Runtime End')"
                                           class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                </div>
                            </div>
                            <div class="flex mb-2 space-x-2">
                                <div class="w-1/2">
                                    <input type="text" onfocus="(this.type='date')"
                                           v-model="this.createSingleSourceForm.funding_start_date"
                                           id="sourceStartDate"
                                           :placeholder="$t('Funding period Start')"
                                           class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                </div>
                                <div class="w-1/2">
                                    <input type="text" onfocus="(this.type='date')"
                                           v-model="this.createSingleSourceForm.funding_end_date"
                                           id="sourceEndDate"
                                           :placeholder="$t('Funding period End')"
                                           class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                </div>
                            </div>
                            <div class="mb-2">
                                <div class="relative w-full">
                                    <div class="w-full">
                                        <input id="userSearch" v-model="user_query" type="text" autocomplete="off"
                                               :placeholder="$t('Who is responsible?')"
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
                                                <span class="sr-only">{{ $t('Remove user from funding source')}}</span>
                                                <IconX stroke-width="1.5"
                                                    class="ml-2 h-4 w-4 p-0.5 hover:text-error rounded-full bg-artwork-buttons-create text-white border-0 "/>
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
                                    {{ $t('Belongs to funding Sources Group')}}
                                </label>
                            </div>
                            <div v-if="this.hasGroup" class="mb-2">
                                <Listbox as="div" v-model="this.selectedMoneySourceGroup" id="room">
                                    <ListboxButton class="inputMain w-full h-10 cursor-pointer truncate flex p-2">
                                        <div class="flex-grow flex text-left xsDark">
                                            {{
                                                this.selectedMoneySourceGroup ? this.selectedMoneySourceGroup.name : $t('Search for a funding group')
                                            }}
                                        </div>
                                        <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                                    </ListboxButton>
                                    <ListboxOptions class="w-[88%] bg-primary max-h-32 overflow-y-auto text-sm absolute">
                                        <ListboxOption v-if="this.moneySourceGroups.length > 0" v-for="moneySourceGroup in this.moneySourceGroups"
                                                       class="hover:bg-indigo-800 text-secondary cursor-pointer p-2 flex justify-between "
                                                       :key="moneySourceGroup.id"
                                                       :value="moneySourceGroup"
                                                       v-slot="{ active, selected }">
                                            <div :class="[selected ? 'xsWhiteBold' : 'xsLight', 'flex']">
                                                {{ moneySourceGroup.name }}
                                            </div>
                                            <IconCheck stroke-width="1.5" v-if="selected" class="h-5 w-5 text-success" aria-hidden="true"/>
                                        </ListboxOption>
                                        <div v-else class="text-secondary py-2 ml-2">
                                            {{ $t('No funding source groups available')}}
                                        </div>
                                    </ListboxOptions>
                                </Listbox>
                            </div>
                            <div class="flex">
                                    <textarea :placeholder="$t('Comment / Note')"
                                              id="description"
                                              v-model="this.createSingleSourceForm.description"
                                              rows="4"
                                              class="border-2 placeholder-xsLight focus:xsDark resize-none w-full text-sm focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300"/>
                            </div>
                            <div class="flex flex-col mt-2">
                                <div class="flex flex-row items-center">
                                    <input id="remindOnExpiration"
                                           type="checkbox"
                                           v-model="remindOnExpiration"
                                           class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"
                                    />
                                    <label for="remindOnExpiration"
                                           :class="[
                                               this.remindOnExpiration ?
                                                    'xsDark' :
                                                    'xsLight',
                                               'ml-2 subpixel-antialiased'
                                           ]">
                                        {{ $t('Remind me when this source runs out')}}
                                    </label>
                                </div>
                                <div v-if="remindOnExpiration" class="flex flex-col columns-1 mt-2">
                                    <div v-for="(expirationReminder, index) in expirationReminders"
                                         class="flex flex-col mb-2">
                                        <div class="flex flex-row items-center">
                                            <input type="number"
                                                   :class="[
                                                       !this.isValidNumber(expirationReminder.days) ?
                                                            'border-error' :
                                                            '',
                                                       'w-24 inputMain mr-2'
                                                   ]"
                                                   min="1"
                                                   v-model="expirationReminder.days"
                                            />
                                            <span class="xsLight">
                                                {{ $t('Remind day/s before')}}
                                            </span>
                                            <IconTrash stroke-width="1.5" class="w-5 h-5 cursor-pointer xsLight ml-2 hover:text-error"
                                                       @click="removeExpirationReminder(index)"
                                            />
                                        </div>
                                        <span v-if="!this.isValidNumber(expirationReminder.days)"
                                           class="text-error text-xs subpixel-antialiased mt-2">
                                            {{ $t('If a reminder is to be created, enter the number of days or remove the reminder.')}}
                                        </span>
                                    </div>
                                    <div class="flex flex-row items-center w-fit" @click="addExpirationReminder()">
                                        <IconCirclePlus class="h-5 w-5 rounded-full bg-backgroundBlue mr-2 cursor-pointer"/>
                                        <span class="text-xs underline text-artwork-buttons-create cursor-pointer">
                                            {{ $t('Add another reminder')}}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col mt-2">
                                <div class="flex flex-row items-center">
                                    <input id="remindOnThreshold"
                                           type="checkbox"
                                           v-model="remindOnThreshold"
                                           class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"
                                    />
                                    <label for="remindOnThreshold"
                                           :class="[
                                               this.remindOnThreshold ?
                                                    'xsDark' :
                                                    'xsLight',
                                               'ml-2 subpixel-antialiased'
                                           ]">
                                        {{ $t('Remind me when only a certain percentage of the source still exists')}}
                                    </label>
                                </div>
                                <div v-if="remindOnThreshold" class="flex flex-col columns-1 mt-2">
                                    <div v-for="(thresholdReminder, index) in thresholdReminders"
                                         class="flex flex-col mb-2">
                                        <div class="flex flex-row items-center">
                                            <input type="number"
                                                   :class="[
                                                       !this.isValidNumber(thresholdReminder.threshold) ?
                                                            'border-error' :
                                                            '',
                                                       'w-24 inputMain mr-2'
                                                   ]"
                                                   min="1"
                                                   v-model="thresholdReminder.threshold"
                                            />
                                            <span class="xsLight">
                                                {{ $t('Percent triggers a countdown notification')}}
                                            </span>
                                            <IconTrash stroke-width="1.5" class="w-5 h-5 cursor-pointer xsLight ml-2 hover:text-error"
                                                       @click="removeThresholdReminder(index)"
                                            />
                                        </div>
                                        <span v-if="!this.isValidNumber(thresholdReminder.threshold)"
                                              class="text-error text-xs subpixel-antialiased mt-2">
                                            {{ $t('If a countdown is to be created, enter the percentage or remove the countdown.')}}
                                        </span>
                                    </div>
                                    <div class="flex flex-row items-center w-fit" @click="addThresholdReminder()">
                                        <IconCirclePlus stroke-width="1.5" class="h-5 w-5 rounded-full bg-backgroundBlue mr-2 cursor-pointer"/>
                                        <span class="text-xs underline text-artwork-buttons-create cursor-pointer">
                                               {{ $t('Add another reminder')}}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-center mt-2">
                                <FormButton
                                    :text="$t('Creating a source of funding')"
                                    :disabled="!isFormComplete()"
                                    @click="createSingleSource()"
                                />
                            </div>
                        </div>
                    </div>
                    <!-- Form when Source Group -->
                    <div v-else>
                        <div class="mb-2">
                            <input type="text"
                                   v-model="this.createSourceGroupForm.name"
                                   id="sourceName"
                                   :placeholder="$t('Title*')"
                                   class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                        </div>
                        <div class="mb-2">
                            <div class="relative w-full">
                                <div class="w-full">
                                    <input id="userSearch" v-model="user_query" type="text" autocomplete="off"
                                           :placeholder="$t('Who is responsible?')"
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
                                                <span class="sr-only">{{ $t('Remove user from money source')}}</span>
                                                <IconX stroke-width="1.5"
                                                    class="ml-2 h-4 w-4 p-0.5 hover:text-error rounded-full bg-artwork-buttons-create text-white border-0 "/>
                                            </button>
                                        </div>


                                        </span>

                            </div>
                        </div>
                        <div class="mb-2">
                            <div class="relative w-full">
                                <div class="w-full">
                                    <input id="moneySourceSearch" v-model="moneySource_query" type="text" autocomplete="off"
                                           :placeholder="$t('Which sources of funding belong to this group?')"
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
                                                    <IconX stroke-width="1.5" class="ml-1 h-4 w-4 hover:text-error "/>
                                                </button>
                                            </span>
                                        </span>
                            </div>
                        </div>
                        <div class="flex">
                                    <textarea :placeholder="$t('Comment / Note')"
                                              id="description"
                                              v-model="this.createSourceGroupForm.description"
                                              rows="4"
                                              class="border-2 placeholder-xsLight focus:xsDark resize-none w-full text-sm focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300"/>
                        </div>
                        <div class="flex justify-center mt-2">
                            <FormButton :disabled="!isGroupFormComplete()"
                                       @click="createMoneySourceGroup()" :text="$t('Create funding source group')"/>
                        </div>
                    </div>
                </div>
            </div>
    </BaseModal>

</template>

<script>

import JetDialogModal from "@/Jetstream/DialogModal.vue";
import {ChevronDownIcon, DotsVerticalIcon, PencilAltIcon, XCircleIcon, XIcon} from '@heroicons/vue/outline';
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
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import Input from "@/Jetstream/Input.vue";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import InputComponent from "@/Layouts/Components/InputComponent.vue";
import {useForm} from "@inertiajs/vue3";
import Permissions from "@/Mixins/Permissions.vue";
import {router} from "@inertiajs/vue3";
import BaseButton from "@/Layouts/Components/General/Buttons/BaseButton.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/Mixins/IconLib.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";

export default {
    name: 'EventComponent',
    mixins: [Permissions, IconLib],
    components: {
        BaseModal,
        FormButton,
        BaseButton,
        Input,
        JetDialogModal,
        XIcon,
        XCircleIcon,
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
    },
    computed: {
        tabs() {
            return [
                {name: this.$t('Single source'), href: '#', current: this.isSingleSourceTab},
                {name: this.$t('Group'), href: '#', current: this.isGroupTab},
            ]
        }
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
            searchType: 'single',
            createSingleSourceForm: useForm({
                name: '',
                amount: null,
                start_date: null,
                end_date: null,
                funding_start_date: null,
                funding_end_date: null,
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
            remindOnExpiration: false,
            expirationReminders: [
                {
                    days: 1
                }
            ],
            remindOnThreshold: false,
            thresholdReminders: [
                {
                    threshold: 1
                }
            ],
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
                        params: {query: this.moneySource_query, type: this.searchType}
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
            if (!this.usersToAdd.find(userToAdd => userToAdd.id === user.id)) {
                this.usersToAdd.push(user);
            }
            this.user_query = '';
        },
        addMoneySourceToGroup(moneySource) {
            this.subMoneySources.push(moneySource);
            this.moneySource_query = '';
        },
        deleteUserFromMoneySourceUserArray(index) {
            this.usersToAdd.splice(index, 1);
        },
        deleteSubMoneySourceFromGroup(index) {
            this.subMoneySources.splice(index, 1);
        },
        createSingleSource() {
            this.createSingleSourceForm.users = {}
            this.usersToAdd.forEach((userToAdd) => {
                this.createSingleSourceForm.users[userToAdd.id] = {
                    user_id: userToAdd.id,
                    competent: true,
                    write_access: true
                };
            })
            if (this.selectedMoneySourceGroup) {
                this.createSingleSourceForm.group_id = this.selectedMoneySourceGroup.id
            }

            this.createSingleSourceForm.post(
                route('money_sources.store'),
                {
                    onSuccess: (response) => {
                        if (
                            (this.remindOnExpiration && this.expirationReminders.length > 0) ||
                            (this.remindOnThreshold && this.thresholdReminders.length > 0)
                        ) {
                            router.post(
                                route(
                                    'money_source.reminder.store',
                                    {
                                        money_source: response.props.recentlyCreatedMoneySourceId
                                    }
                                ),
                                {
                                    expirationReminders: this.remindOnExpiration ? this.expirationReminders : [],
                                    thresholdReminders: this.remindOnThreshold ? this.thresholdReminders : []
                                }
                            );
                        }

                        this.closeModal(true);
                    }
                }
            );
        },
        createMoneySourceGroup() {
            this.createSourceGroupForm.users = {}
            this.usersToAdd.forEach((userToAdd) => {
                this.createSourceGroupForm.users[userToAdd.id] = {
                    user_id: userToAdd.id,
                    competent: true,
                    write_access: true
                };
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
            if (selectedTab.name === this.$t('Single source')) {
                this.isSingleSourceTab = true;
            } else {
                this.isGroupTab = true;
            }
        },
        closeModal(bool) {
            this.$emit('closed', bool);
        },
        isFormComplete() {
            const form = this.createSingleSourceForm;
            let hasInvalidExpirationReminders = false;
            let hasInvalidThresholdReminders = false;

            if (this.remindOnExpiration) {
                hasInvalidExpirationReminders = this.expirationReminders.some(
                    (expirationReminder) => !this.isValidNumber(expirationReminder.days)
                )
            }

            if (this.remindOnThreshold) {
                hasInvalidThresholdReminders = this.thresholdReminders.some(
                    (thresholdReminder) => !this.isValidNumber(thresholdReminder.threshold)
                )
            }

            return form.name && form.amount && !hasInvalidExpirationReminders && !hasInvalidThresholdReminders;
        },
        isGroupFormComplete() {
            const form = this.createSourceGroupForm;
            return form.name;
        },
        addExpirationReminder() {
            this.expirationReminders.push({days: 1});
        },
        removeExpirationReminder(index) {
            this.expirationReminders.splice(index, 1);
        },
        addThresholdReminder() {
            this.thresholdReminders.push({threshold: 1});
        },
        removeThresholdReminder(index) {
            this.thresholdReminders.splice(index, 1);
        },
        isValidNumber(number) {
            return number >= 1 && Number.isInteger(number);
        }
    },
}
</script>

<style scoped></style>
