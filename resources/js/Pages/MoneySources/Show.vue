<template>
    <app-layout :title="moneySource.name">
        <div class="max-w-screen-2xl pl-14 pr-10 flex flex-row">
            <div class="flex w-8/12 flex-col">
                <div class="flex items-center justify-between">
                    <h2 class="flex font-black font-lexend text-primary tracking-wide text-3xl">
                        {{ moneySource.name }}</h2>
                    <BaseMenu class="ml-4" v-if="$role('artwork admin') || access_member.includes($page.props.user.id) || competent_member.includes($page.props.user.id) || $can('view edit add money_sources') || $can('can edit and delete money sources')">
                        <MenuItem
                            v-if="$role('artwork admin') || access_member.includes($page.props.user.id) || competent_member.includes($page.props.user.id) || $can('view edit add money_sources') || $can('can edit and delete money sources')"
                            v-slot="{ active }">
                            <a @click="openEditMoneySourceModal"
                               :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                <IconEdit stroke-width="1.5"
                                          class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                          aria-hidden="true"/>
                                {{$t('Edit basic data')}}
                            </a>
                        </MenuItem>
                        <MenuItem v-slot="{ active }">
                            <a @click="duplicateMoneySource(this.moneySource) || competent_member.includes($page.props.user.id) || $can('view edit add money_sources') || $can('can edit and delete money sources')"
                               :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                <IconCopy stroke-width="1.5"
                                          class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                          aria-hidden="true"/>
                                {{ $t('Duplicate')}}
                            </a>
                        </MenuItem>
                        <MenuItem
                            v-if="$role('artwork admin') || access_member.includes($page.props.user.id) || competent_member.includes($page.props.user.id) || $can('view edit add money_sources') || $can('can edit and delete money sources')"
                            v-slot="{ active }">
                            <a @click="openDeleteSourceModal(this.moneySource)"
                               :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                <IconTrash stroke-width="1.5"
                                           class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                           aria-hidden="true"/>
                                {{ $t('Delete')}}
                            </a>
                        </MenuItem>
                    </BaseMenu>
                </div>
                <div class="flex items-center w-full justify-between mt-4">
                    <div class="flex mt-2 xsDark items-center">
                        {{ $t('Created by')}}
                        <UserPopoverTooltip v-if="moneySource.creator" :user="moneySource.creator" :id="moneySource.creator?.id"
                                            :height="7" :width="7" class="ml-2"/>
                    </div>
                    <button class="ml-4 mt-3 subpixel-antialiased flex items-center linkText cursor-pointer text-artwork-buttons-create"
                            @click="openMoneySourceHistoryModal()">
                        <IconChevronRight stroke-width="1.5"
                            class="-mr-0.5 h-4 w-4  group-hover:text-white"
                            aria-hidden="true"/>
                        {{ $t('View history')}}
                    </button>
                </div>
                <div class="xsDark mt-4 flex items-center" v-if="moneySource.group_id">
                    <img src="/Svgs/IconSvgs/icon_group_red.svg"
                         class=" h-4 w-4" alt="groupIcon"/>
                    <div class="ml-2">
                        {{$t('Belongs to')}}
                    </div>
                    <Link v-if="moneySource.group_id" :href="getEditHref(moneySource.group_id)"
                          class="linkText ml-1 mt-0.5">
                        {{ moneySource.moneySourceGroup.name }}
                    </Link>
                </div>
                <div class="mt-3 xsDark" v-if="moneySource.start_date && moneySource.end_date">
                    {{$t('Runtime')}}: {{ formatDate(moneySource.start_date) }} - {{ formatDate(moneySource.end_date) }}
                </div>
                <div :class="[
                        moneySource.hasSentExpirationReminderNotification ?
                            'text-error' :
                            '',
                        'mt-3 xsDark'
                     ]" v-if="moneySource.funding_start_date && moneySource.funding_end_date">
                    {{$t('Funding period')}}: {{ formatDate(moneySource.funding_start_date) }} - {{ formatDate(moneySource.funding_end_date) }}
                </div>
                <div class="mt-2 xsDark" v-if="moneySource.source_name">
                    {{ $t('Source')}}: {{ moneySource.source_name }}
                </div>
                <div class="mr-14 my-3 subpixel-antialiased text-secondary">
                    {{ moneySource.description }}
                </div>
                <div class="mt-12 flex">
                    <div class="w-1/2 xsLight uppercase border-r-2">
                        {{ $t('Original volume')}}
                        <div class="bigNumber my-4">
                            {{ toCurrencyString(moneySource.amount) }}
                        </div>
                    </div>
                    <div class="w-1/2 xsLight uppercase ml-6">
                        {{$t('Still available')}}
                        <div :class="[
                                 moneySource.amount_available <= 0 || moneySource.hasSentThresholdReminderNotification ?
                                    'text-red-500' :
                                    '',
                                    'bigNumber my-4'
                             ]">
                            {{ toCurrencyString(moneySource.amount_available) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Positions Div with Bg-Color -->
        <div class="w-full h-full mb-48">

            <div class="max-w-screen-2xl bg-lightBackgroundGray">
                <div class="flex pt-12 justify-between items-center">
                    <div class="headline4  ml-14">
                        {{ $t('Linked positions')}}
                    </div>
                    <div>
                        <Listbox as="div" class="flex h-12 mr-4 w-64" v-model="wantedProject"
                                 id="wantedProject">
                            <ListboxButton
                                class="pl-3 h-12 inputMain w-full bg-white relative font-semibold py-2 text-left cursor-pointer focus:outline-none sm:text-sm">
                                <div class="flex items-center my-auto">
                                        <span class="truncate items-center ml-3 flex" v-if="wantedProject">
                                            <span class="truncate mr-6">{{ wantedProject?.name }}</span>
                                        </span>
                                    <span class="truncate items-center ml-3 flex" v-else>
                                            <span>{{$t('All projects')}}</span>
                                        </span>
                                    <span
                                        class="ml-2 right-0 absolute inset-y-0 flex items-center pr-2 pointer-events-none">
                                     <IconChevronDown  stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                                </span>
                                </div>
                            </ListboxButton>

                            <transition leave-active-class="transition ease-in duration-100"
                                        leave-from-class="opacity-100" leave-to-class="opacity-0">
                                <ListboxOptions
                                    class="absolute w-64 z-10 mt-12 bg-artwork-navigation-background shadow-lg max-h-48 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                                    <ListboxOption as="template" class="max-h-8"
                                                   :value="null"
                                                   v-slot="{ active, selected }">
                                        <li :class="[active ? ' text-white' : 'text-secondary', 'group hover:border-l-4 hover:border-l-success cursor-pointer flex justify-between items-center py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                            <div class="flex">
                                                    <span
                                                        :class="[selected ? 'xsWhiteBold' : 'font-normal', 'ml-4 block truncate']">
                                                        {{$t('All projects')}}
                                                    </span>
                                            </div>
                                            <span
                                                :class="[active ? ' text-white' : 'text-secondary', ' group flex justify-end items-center text-sm subpixel-antialiased']">
                                                      <IconCheck stroke-width="1.5" v-if="selected" class="h-5 w-5 flex text-success"
                                                                 aria-hidden="true"/>
                                                </span>
                                        </li>
                                    </ListboxOption>
                                    <ListboxOption as="template" class="max-h-8"
                                                   v-for="project in this.linkedProjects"
                                                   :key="project.id"
                                                   :value="project"
                                                   v-slot="{ active, selected }">
                                        <li :class="[active ? ' text-white' : 'text-secondary', 'group hover:border-l-4 hover:border-l-success cursor-pointer flex justify-between items-center py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                            <div class="flex">
                                                    <span
                                                        :class="[selected ? 'xsWhiteBold' : 'font-normal', 'ml-4 block truncate']">
                                                        {{ project.name }}
                                                    </span>
                                            </div>
                                            <span
                                                :class="[active ? ' text-white' : 'text-secondary', ' group flex justify-end items-center text-sm subpixel-antialiased']">
                                                      <IconCheck stroke-width="1.5" v-if="selected" class="h-5 w-5 flex text-success"
                                                                 aria-hidden="true"/>
                                                </span>
                                        </li>
                                    </ListboxOption>
                                </ListboxOptions>
                            </transition>
                        </Listbox>
                    </div>
                </div>
                <div class="w-full ml-14 py-6 pr-14">
                    <div>
                        <div class="flex border-b border-gray-300 pb-5 pt-5"
                             v-for="position in filteredPositions">
                            <div class="sum w-72 text-2xl" :class="position.type === 'COST' ? 'text-red-500' : ''">
                                <span v-if="position.type === 'EARNING'">+</span><span v-else>-</span>
                                {{ toCurrencyString(position.value) }}
                            </div>
                            <div class="project">
                                <div class="text-gray-400"><a
                                    :href="getProjectHref(position.project)"
                                    class="text-artwork-buttons-create ">{{ position.project.name }}</a> |<span
                                    class="ml-2 text-gray-400 text-sm">{{ position.created_at }}</span></div>
                                <div class="text-gray-400 text-sm mt-2 flex">{{ position.mainPositionName }} <div class="flex px-1" v-if="position.subPositionName?.length > 0">|</div>
                                    {{ position.subPositionName }}
                                </div>
                                <div class="text-gray-400 text-sm mt-2 flex">
                                    {{position.column_name}}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <edit-money-source-component
            v-if="showEditMoneySourceModal"
            @closed="onEditMoneySourceModalClose()"
            :moneySource="this.moneySource"
            :moneySources="this.moneySources"
            :moneySourceGroups="this.moneySourceGroups"
        />
        <BaseSidenav :show="show" @toggle="this.show =! this.show">
            <MoneySourceSidenav
                :users="moneySource.users"
                :tasks="moneySource.tasks"
                :money_source="moneySource"
                :money-source-files="moneySource.money_source_files"
                :linked-projects="linkedProjects"
                :competent="competent_member"
                :write-access="access_member"
                :money-source-categories="moneySourceCategories"
                :positionSumsPerProject="positionSumsPerProject"
                :first_project_budget_tab_id="this.first_project_budget_tab_id"
            ></MoneySourceSidenav>
        </BaseSidenav>
    </app-layout>
    <confirm-delete-modal
        v-if="showDeleteSourceModal"
        :title="$t('Delete funding source/group')"
        :description="$t('Are you sure you want to delete the funding source/group {0}?', [sourceToDelete.name])"
        @closed="afterConfirm(false)"
        @delete="afterConfirm(true)"/>

    <MoneySourceHistoryComponent
        @closed="closeMoneySourceHistoryModal"
        v-if="showMoneySourceHistory"
        :history="moneySource.history"
    ></MoneySourceHistoryComponent>
</template>

<script>


import AppLayout from '@/Layouts/AppLayout.vue';
import {
    Listbox, ListboxButton, ListboxOption, ListboxOptions,
    Menu,
    MenuButton,
    MenuItem,
    MenuItems,
} from "@headlessui/vue";
import {
    PencilAltIcon,
    TrashIcon,
    DuplicateIcon,
} from "@heroicons/vue/outline";
import {
    DotsVerticalIcon,
    ChevronRightIcon, ChevronDownIcon, CheckIcon
} from "@heroicons/vue/solid";
import UserTooltip from "@/Layouts/Components/UserTooltip.vue";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import {Link} from "@inertiajs/vue3";
import EditMoneySourceComponent from "@/Layouts/Components/EditMoneySourceComponent.vue";
import BaseSidenav from "@/Layouts/Components/BaseSidenav.vue";
import MoneySourceSidenav from "@/Layouts/Components/MoneySourceSidenav.vue";
import MoneySourceHistoryComponent from "@/Layouts/Components/MoneySourceHistoryComponent.vue";
import Permissions from "@/Mixins/Permissions.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import IconLib from "@/Mixins/IconLib.vue";
import CurrencyFloatToStringFormatter from "@/Mixins/CurrencyFloatToStringFormatter.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";

export default {
    mixins: [Permissions, IconLib, CurrencyFloatToStringFormatter],
    name: "MoneySourceShow",
    props: [
        'moneySource',
        'moneySourceGroups',
        'moneySources',
        'moneySourceCategories',
        'projects',
        'linkedProjects',
        'first_project_budget_tab_id'
    ],
    components: {
        BaseMenu,
        UserPopoverTooltip,
        ConfirmDeleteModal,
        MoneySourceSidenav,
        BaseSidenav,
        AppLayout,
        UserTooltip,
        ChevronRightIcon,
        Menu,
        MenuItem,
        MenuItems,
        MenuButton,
        DuplicateIcon,
        PencilAltIcon,
        TrashIcon,
        DotsVerticalIcon,
        SvgCollection,
        Link,
        EditMoneySourceComponent,
        Listbox,
        ListboxOption,
        ListboxOptions,
        ListboxButton,
        ChevronDownIcon,
        CheckIcon,
        MoneySourceHistoryComponent
    },
    computed: {
        filteredPositions() {
            if (this.wantedProject !== null) {
                if (this.moneySource.is_group) {
                    return this.moneySource.subMoneySourcePositions.filter(position => {
                        return position.project.id === this.wantedProject?.id;
                    })
                } else {
                    return this.moneySource.positions.filter(position => {
                        return position.project.id === this.wantedProject?.id;
                    })
                }
            } else {
                if (this.moneySource.is_group) {
                    return this.moneySource.subMoneySourcePositions;
                } else {
                    return this.moneySource.positions;
                }
            }

        },
        positionSumsPerProject() {
            const sumsByProject = {};

            this.filteredPositions.forEach(position => {
                const projectId = position.project?.id;
                const value = parseFloat(position.value) || 0;

                if (!sumsByProject[projectId]) {
                    sumsByProject[projectId] = 0;
                }

                sumsByProject[projectId] += value;
            });

            return sumsByProject;
        },
        access_member(){
            const accessUserIds = [];
            this.moneySource.users.forEach((user) => {
                if(user.pivot?.write_access){
                    accessUserIds.push(user.id);
                }

            });
            return accessUserIds;
        },
        competent_member(){
            const competentUserIds = [];
            this.moneySource.users.forEach((user) => {
                if(user.pivot?.competent){
                    competentUserIds.push(user.id);
                }

            });
            return competentUserIds;
        }

    },
    data() {
        return {
            showEditMoneySourceModal: false,
            showDeleteSourceModal: false,
            sourceToDelete: null,
            show: false,
            wantedProject: null,
            showMoneySourceHistory: false
        }
    },
    methods: {
        getProjectHref(project) {
            return route('projects.tab', {project: project.id, projectTab: this.first_project_budget_tab_id});
        },
        getEditHref(moneySourceId) {
            return route('money_sources.show', {moneySource: moneySourceId});
        },
        formatDate(isoDate) {
            return isoDate.substring(8, 10) + '.' + isoDate.substring(5, 7) + '.' + isoDate.substring(0, 4)
        },
        openEditMoneySourceModal() {
            this.showEditMoneySourceModal = true;
        },
        onEditMoneySourceModalClose() {
            this.showEditMoneySourceModal = false;
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
        closeMoneySourceHistoryModal(){
            this.showMoneySourceHistory = false;
        },
        openMoneySourceHistoryModal(){
            this.showMoneySourceHistory = true;
        }
    },
    setup() {
        return {}
    }
}

</script>

<style scoped>
</style>
