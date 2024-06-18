<template>
    <BaseModal @closed="closeModal" v-if="true" modal-image="/Svgs/Overlays/illu_money_source_create.svg">
            <div class="mx-4">
                <!--   Heading   -->
                <div class="my-1">
                    <div class="flex-grow headline1 mb-6">
                        {{ moneySource.is_group ? $t('Funding source group') : $t('Source of funding') }}
                    </div>
                    <div class="flex items-center w-full mt-4">
                        <div class="mt-2 xsDark text-xs flex items-center"
                             v-if="moneySource.users">
                            <div class="flex items-center mb-4">
                                <div class="mr-2">
                                    {{ $t('responsible')}}:
                                </div>
                                <div v-for="(user,index) in moneySource.users">
                                    <NewUserToolTip :height="7" :width="7" v-if="user"
                                                    :user="user" :id="index + 'moneySourceUser' + user.id"/>
                                </div>
                            </div>
                        </div>
                        <div class="flex mt-2 mb-4 ml-12 xsDark items-center">
                            {{ $t('Created by')}}
                            <div class="ml-1">
                            <NewUserToolTip :height="7" :width="7" v-if="moneySource.creator"
                                            :user="moneySource.creator" :id="moneySource.creator.id + 'creator'"/>
                            </div>
                        </div>
                    </div>
                    <!-- Form when Single Source -->
                    <div v-if="!moneySource.is_group">
                        <div class=" pb-2">
                            <div class="mb-2">
                                <input type="text"
                                       v-model="this.editSingleSourceForm.name"
                                       id="sourceName"
                                       :placeholder="$t('Title*')"
                                       class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                            </div>
                            <div class="flex mb-2 space-x-2">
                                <div class="w-1/2">
                                    <input type="number"
                                           v-model="this.editSingleSourceForm.amount"
                                           id="sourceAmount"
                                           :placeholder="$t('Sum*')"
                                           class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                </div>
                                <div class="w-1/2">
                                    <input type="text"
                                           v-model="this.editSingleSourceForm.source_name"
                                           id="nameOfSource"
                                           :placeholder="$t('Source')"
                                           class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                </div>
                            </div>
                            <div class="flex mb-2 space-x-2">
                                <div class="w-1/2">
                                    <input type="date"
                                           v-model="this.editSingleSourceForm.start_date"
                                           id="sourceStartDate"
                                           :placeholder="$t('Runtime Start')"
                                           class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                </div>
                                <div class="w-1/2">
                                    <input type="date"
                                           v-model="this.editSingleSourceForm.end_date"
                                           id="sourceEndDate"
                                           :placeholder="$t('Runtime End')"
                                           class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                </div>
                            </div>
                            <div class="flex mb-2 space-x-2">
                                <div class="w-1/2">
                                    <input type="text" onfocus="(this.type='date')"
                                           v-model="this.editSingleSourceForm.funding_start_date"
                                           id="sourceStartDate"
                                           :placeholder="$t('Funding period Start')"
                                           class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                </div>
                                <div class="w-1/2">
                                    <input type="text" onfocus="(this.type='date')"
                                           v-model="this.editSingleSourceForm.funding_end_date"
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
                                <div v-if="usersToAdd !== null" class="mt-2 mb-4 flex items-center">
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
                                    <ListboxOptions class="w-5/6 bg-primary max-h-32 overflow-y-auto text-sm absolute">
                                        <ListboxOption v-for="moneySourceGroup in this.moneySourceGroups"
                                                       class="hover:bg-indigo-800 text-secondary cursor-pointer p-2 flex justify-between "
                                                       :key="moneySourceGroup.id"
                                                       :value="moneySourceGroup"
                                                       v-slot="{ active, selected }">
                                            <div :class="[selected ? 'xsWhiteBold' : 'xsLight', 'flex']">
                                                {{ moneySourceGroup.name }}
                                            </div>
                                            <IconCheck stroke-width="1.5" v-if="selected" class="h-5 w-5 text-success" aria-hidden="true"/>
                                        </ListboxOption>
                                    </ListboxOptions>
                                </Listbox>
                            </div>
                            <div class="flex">
                                    <textarea :placeholder="$t('Comment / Note')"
                                              id="description"
                                              v-model="this.editSingleSourceForm.description"
                                              rows="4"
                                              class="border-2 placeholder-xsLight focus:xsDark resize-none w-full text-sm focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300"/>
                            </div>
                            <div class="flex justify-center mt-2">
                                <FormButton
                                           @click="editSingleSource()" :text="$t('Save')"/>
                            </div>
                        </div>
                    </div>
                    <!-- Form when Source Group -->
                    <div v-else>
                        <div class="mb-2">
                            <input type="text"
                                   v-model="this.editSourceGroupForm.name"
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
                            <div v-if="usersToAdd !== null" class="mt-2 mb-4 flex items-center">
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
                        <div class="mb-2">
                            <div class="relative w-full">
                                <div class="w-full">
                                    <input id="userSearch" v-model="moneySource_query" type="text" autocomplete="off"
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
                            <div v-if="subMoneySources" class="mt-2 mb-4 flex items-center">
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
                                              v-model="this.editSourceGroupForm.description"
                                              rows="4"
                                              class="border-2 placeholder-xsLight focus:xsDark resize-none w-full text-sm focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300"/>
                        </div>
                        <div class="flex justify-center mt-2">
                            <FormButton
                                       @click="editGroupSource()" :text="$t('Save')"/>
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
import NewUserToolTip from "@/Layouts/Components/NewUserToolTip.vue";
import Permissions from "@/Mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/Mixins/IconLib.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";

export default {
    name: 'EventComponent',
    mixins: [Permissions, IconLib],
    components: {
        BaseModal,
        FormButton,
        NewUserToolTip,
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
        },
        subMoneySources() {
            let subMoneySourceArray = [];
            if (this.moneySource.subMoneySources) {
                this.moneySource.subMoneySources.forEach((subMoneySource) => {
                    subMoneySourceArray.push(subMoneySource);
                })
                return subMoneySourceArray;
            }else{
                return [];
            }

        }
    },
    data() {
        return {
            isSingleSourceTab: true,
            isGroupTab: false,
            user_search_results: [],
            user_query: '',
            usersToAdd: this.moneySource.users,
            hasGroup: !!this.moneySource.group_id,
            moneySource_query: '',
            moneySource_search_results: [],
            selectedMoneySourceGroup: this.moneySource.group_id ? this.moneySource.moneySourceGroup : null,
            searchType: 'single',
            editSingleSourceForm: useForm({
                name: this.moneySource.name,
                amount: this.moneySource.amount,
                start_date: this.moneySource.start_date,
                end_date: this.moneySource.end_date,
                source_name: this.moneySource.source_name,
                description: this.moneySource.description,
                is_group: false,
                group_id: this.moneySource.group_id,
                funding_start_date: this.moneySource.funding_start_date,
                funding_end_date: this.moneySource.funding_end_date,
                users: []
            }),
            editSourceGroupForm: useForm({
                name: this.moneySource.name,
                amount: this.moneySource.amount,
                start_date: this.moneySource.start_date,
                end_date: this.moneySource.end_date,
                source_name: this.moneySource.source_name,
                description: this.moneySource.description,
                is_group: true,
                users: [],
                sub_money_source_ids: []
            }),
        }
    },

    props: ['moneySource', 'moneySourceGroups', 'moneySources'],

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
            if(!this.usersToAdd.find(userToAdd => userToAdd.id === user.id)){
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
            this.moneySource.subMoneySources.splice(index,1);
        },
        editSingleSource() {
            this.editSingleSourceForm.users = {};
            this.usersToAdd.forEach((userToAdd) => {
                this.editSingleSourceForm.users[userToAdd.id] = {
                    user_id: userToAdd.id,
                    competent: true,
                    write_access: true
                };
            })
            if (this.selectedMoneySourceGroup && this.hasGroup) {
                this.editSingleSourceForm.group_id = this.selectedMoneySourceGroup.id;
            } else {
                this.editSingleSourceForm.group_id = null;
            }
            this.editSingleSourceForm.is_group = false;

            this.editSingleSourceForm.patch(route('money_sources.update', {moneySource: this.moneySource.id}));
            this.closeModal(true);
        },
        editGroupSource(){
            this.editSourceGroupForm.users = {};
            this.usersToAdd.forEach((userToAdd) => {
                this.editSourceGroupForm.users[userToAdd.id] = {
                    user_id: userToAdd.id,
                    competent: true,
                    write_access: true
                };
            })
            this.subMoneySources.forEach((subMoneySource) => {
                this.editSourceGroupForm.sub_money_source_ids.push(subMoneySource.id);
            })
            this.editSourceGroupForm.patch(route('money_sources.update', {moneySource: this.moneySource.id}));
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
        openModal() {

        },
        closeModal(bool) {
            this.usersToAdd = [];
            this.$emit('closed', bool);
        },
    },
}
</script>

<style scoped></style>
